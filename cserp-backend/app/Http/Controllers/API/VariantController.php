<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Variant;
use App\Enums\VariantType;
use App\Enums\VariantStatus;
use App\Enums\MaterialStatus;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VariantController extends Controller
{
    // =========================================================================
    // INDEX
    // =========================================================================

    /**
     * Lista wszystkich grup i wariantów dla zamówienia (plaska lista).
     * Frontend sam buduje drzewo na podstawie parent_variant_id.
     *
     * GET /api/orders/{order}/variants
     */
    public function index(Order $order)
    {
        $variants = $order->variants()
            ->with(['parentVariant', 'childVariants'])
            ->orderBy('variant_number')
            ->get();

        return response()->json($variants);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /**
     * Szczegoly wariantu lub grupy.
     *
     * GET /api/variants/{variant}
     */
    public function show(Variant $variant)
    {
        $variant->load([
            'order.customer',
            'parentVariant',
            'childVariants',
            'productionOrder.services.assignedWorker',
            'productionOrder.services.workstation',
            'deliveries',
        ]);

        return response()->json($variant);
    }

    // =========================================================================
    // STORE — TWORZENIE GRUPY
    // =========================================================================

    /**
     * Utwórz nową GRUPĘ dla zamówienia.
     *
     * Grupy to kontenery — nie mają wycen, materiałów ani produkcji.
     * Backend automatycznie nadaje kolejną wolną literę (A, B, C...).
     * is_group=true, quantity=0.
     *
     * POST /api/orders/{order}/variants
     * Payload: { name, description? }
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Następna wolna litera (pomija zajęte przez grupy top-level)
        $existingLetters = $order->variants()
            ->whereNull('parent_variant_id')
            ->whereRaw('LENGTH(variant_number) = 1')
            ->pluck('variant_number')
            ->toArray();

        $letter = 'A';
        while (in_array($letter, $existingLetters)) {
            $letter = chr(ord($letter) + 1);
        }

        $group = $order->variants()->create([
            'is_group'        => true,
            'variant_number'  => $letter,
            'name'            => $validated['name'],
            'description'     => $validated['description'] ?? null,
            'quantity'        => 0,
            'type'            => VariantType::SERIAL, // Grupy maja domyslnie SERIAL — nie ma znaczenia
            'status'          => VariantStatus::DRAFT,
            'parent_variant_id' => null,
        ]);

        return response()->json([
            'message' => 'Grupa utworzona pomyslnie',
            'data'    => $group,
        ], 201);
    }

    // =========================================================================
    // STORE CHILD — TWORZENIE WARIANTU W GRUPIE
    // =========================================================================

    /**
     * Utwórz WARIANT jako dziecko grupy lub innego wariantu.
     *
     * Backend automatycznie nadaje numer wg reguly:
     *   A (bez cyfry)  → A1, A2, A3 (separator brak)
     *   A1 (z cyfra)   → A1_1, A1_2  (separator _)
     *
     * POST /api/orders/{order}/variants/{parent}/children
     * Payload: { name, quantity (>=1), type (SERIAL|PROTOTYPE), description? }
     */
    public function storeChild(Request $request, Order $order, Variant $parent)
    {
        // Waliduj ze parent nalezy do tego zamowienia
        if ($parent->order_id !== $order->id) {
            return response()->json([
                'message' => 'Rodzic nie nalezy do tego zamowienia.',
            ], 422);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'quantity'    => 'required|integer|min:1',
            'type'        => ['required', Rule::enum(VariantType::class)],
            'description' => 'nullable|string',
        ]);

        $variantNumber = $this->nextChildNumber($parent);

        $variant = $order->variants()->create([
            'is_group'         => false,
            'parent_variant_id' => $parent->id,
            'variant_number'   => $variantNumber,
            'name'             => $validated['name'],
            'quantity'         => $validated['quantity'],
            'type'             => $validated['type'],
            'description'      => $validated['description'] ?? null,
            'status'           => VariantStatus::DRAFT,
            'is_approved'      => false,
        ]);

        $variant->load('parentVariant');

        return response()->json([
            'message' => 'Wariant utworzony pomyslnie',
            'data'    => $variant,
        ], 201);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * Aktualizuj grupe lub wariant.
     *
     * Blokujemy konwersje miedzy typami (is_group nie moze sie zmienic).
     *
     * PUT /api/variants/{variant}
     */
    public function update(Request $request, Variant $variant)
    {
        if ($variant->isGroup()) {
            // Grupy: tylko nazwa i opis
            $validated = $request->validate([
                'name'        => 'sometimes|string|max:255',
                'description' => 'nullable|string',
            ]);
        } else {
            // Warianty: pelna edycja
            $validated = $request->validate([
                'name'            => 'sometimes|string|max:255',
                'quantity'        => 'sometimes|integer|min:1',
                'description'     => 'nullable|string',
                'type'            => ['sometimes', Rule::enum(VariantType::class)],
                'status'          => ['sometimes', Rule::enum(VariantStatus::class)],
                'is_approved'     => 'boolean',
                'feedback_notes'  => 'nullable|string',
                'tkw_z_wyceny'    => 'nullable|numeric|min:0',
                'tkw_rzeczywiste' => 'nullable|numeric|min:0',
            ]);
        }

        $variant->update($validated);

        return response()->json([
            'message' => 'Zapisano pomyslnie',
            'data'    => $variant->fresh(),
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /**
     * Usun wariant lub grupe.
     *
     * Dla GRUP z dziecmi wymagany parametr ?force=true.
     * Bez niego backend zwraca 422.
     * Z force=true kasuje grupe i cale drzewo potomkow rekurencyjnie.
     *
     * Dla WARIANTOW — blokuje usuniecie jesli sa w produkcji (nie DRAFT/CANCELLED).
     *
     * DELETE /api/variants/{variant}?force=true
     */
    public function destroy(Request $request, Variant $variant)
    {
        $force = $request->boolean('force', false);

        return DB::transaction(function () use ($variant, $force) {

            if ($variant->isGroup()) {
                // Laduj dzieci
                $variant->load('childVariants');
                $hasChildren = $variant->childVariants->isNotEmpty();

                if ($hasChildren && !$force) {
                    return response()->json([
                        'message' => 'Grupa ma warianty. Uzyj ?force=true aby usunac grupe razem z calym drzewem.',
                        'children_count' => $variant->childVariants->count(),
                    ], 422);
                }

                if ($hasChildren) {
                    // Usun rekurencyjnie — wszystkich potomkow
                    $this->deleteDescendants($variant);
                }

                $variant->delete();

                return response()->json([
                    'message' => 'Grupa i jej zawartosc zostaly usuniete.',
                ]);
            }

            // Wariant: blokuj usuniecie jesli jest w produkcji
            $blocked = [VariantStatus::PRODUCTION, VariantStatus::COMPLETED];
            if (in_array($variant->status, $blocked)) {
                return response()->json([
                    'message' => 'Nie mozna usunac wariantu w trakcie lub po produkcji.',
                ], 400);
            }

            $variant->delete();

            return response()->json([
                'message' => 'Wariant zostal usuniety.',
            ]);
        });
    }

    /**
     * Rekurencyjnie usun wszystkich potomkow danego wezla.
     */
    private function deleteDescendants(Variant $parent): void
    {
        $parent->load('childVariants.childVariants');

        foreach ($parent->childVariants as $child) {
            $this->deleteDescendants($child);
            $child->delete();
        }
    }

    // =========================================================================
    // UPDATE STATUS
    // =========================================================================

    /**
     * Zmien status wariantu (niedostepne dla grup).
     *
     * PATCH /api/variants/{variant}/status
     */
    public function updateStatus(Request $request, Variant $variant)
    {
        if ($variant->isGroup()) {
            return response()->json([
                'message' => 'Grupy nie maja statusu produkcji.',
            ], 422);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(VariantStatus::class)],
        ]);

        $variant->update(['status' => $validated['status']]);

        return response()->json($variant);
    }

    // =========================================================================
    // REVIEW PROTOTYPE
    // =========================================================================

    /**
     * Recenzja prototypu (zatwierdzenie / odrzucenie).
     *
     * POST /api/variants/{variant}/review
     */
    public function reviewPrototype(Request $request, Variant $variant)
    {
        if ($variant->isGroup()) {
            return response()->json(['message' => 'Grupy nie moga byc prototypami.'], 422);
        }

        if (!$variant->isPrototype()) {
            return response()->json(['message' => 'To nie jest prototyp.'], 400);
        }

        $validated = $request->validate([
            'action'         => 'required|in:approve,reject',
            'feedback_notes' => 'nullable|string',
        ]);

        $variant->update([
            'is_approved'    => $validated['action'] === 'approve',
            'feedback_notes' => $validated['feedback_notes'] ?? $variant->feedback_notes,
        ]);

        return response()->json($variant);
    }

    // =========================================================================
    // DUPLICATE
    // =========================================================================

    /**
     * Duplikuj grupe lub wariant.
     *
     * POST /api/variants/{variant}/duplicate
     *
     * Dla GRUPY:
     *   { relation: "sibling", name, description?, copy_children }
     *   - Tworzy nowa grupe (kolejna litera)
     *   - copy_children=true → kopiuje cale drzewo wariantow rekurencyjnie
     *
     * Dla WARIANTU:
     *   { relation: "sibling"|"child", name, quantity, type, description?,
     *     copy_quotation, copy_materials }
     *   - sibling: nowy wariant w tej samej grupie
     *   - child: podwariant tego wariantu
     */
    public function duplicate(Request $request, Variant $variant)
    {
        try {
            if ($variant->isGroup()) {
                return $this->duplicateGroup($request, $variant);
            } else {
                return $this->duplicateVariant($request, $variant);
            }
        } catch (\Exception $e) {
            Log::error('Variant duplicate error: ' . $e->getMessage(), [
                'variant_id' => $variant->id,
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Blad podczas duplikowania',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Duplikuj GRUPE.
     *
     * Zawsze jako sibling (kolejna litera). Grupy nie moga byc dzieclmi innych grup.
     * Z copy_children=true kopiuje cale drzewo wariantow wewnatrz grupy.
     */
    private function duplicateGroup(Request $request, Variant $group)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'copy_children' => 'boolean',
        ]);

        return DB::transaction(function () use ($group, $validated) {
            $order = $group->order;

            // Nastepna wolna litera dla grupy top-level
            $existingLetters = $order->variants()
                ->whereNull('parent_variant_id')
                ->whereRaw('LENGTH(variant_number) = 1')
                ->pluck('variant_number')
                ->toArray();

            $newLetter = 'A';
            while (in_array($newLetter, $existingLetters)) {
                $newLetter = chr(ord($newLetter) + 1);
            }

            // Utwórz nowa grupe
            $newGroup = $order->variants()->create([
                'is_group'         => true,
                'parent_variant_id' => null,
                'variant_number'   => $newLetter,
                'name'             => $validated['name'],
                'description'      => $validated['description'] ?? $group->description,
                'quantity'         => 0,
                'type'             => VariantType::SERIAL,
                'status'           => VariantStatus::DRAFT,
            ]);

            // Kopiuj dzieci jesli copy_children=true
            if (!empty($validated['copy_children'])) {
                $group->load('childVariants');

                foreach ($group->childVariants as $child) {
                    $this->copyVariantUnderNewParent($child, $newGroup, $order, $newLetter);
                }
            }

            $newGroup->load('childVariants');

            return response()->json([
                'message'      => 'Grupa zduplikowana pomyslnie',
                'variant'      => $newGroup,
                'new_letter'   => $newLetter,
                'children_copied' => !empty($validated['copy_children'])
                    ? $newGroup->childVariants->count()
                    : 0,
            ], 201);
        });
    }

    /**
     * Rekurencyjnie kopiuje wariant (i jego dzieci) pod nowego rodzica.
     * Numer wariantu: podmien prefiks starego rodzica na nowy.
     *
     * Przyklad:
     *   Stary: A1 (rodzic A), Nowy rodzic B → B1
     *   Stary: A1_1 (rodzic A1), Nowy rodzic B1 → B1_1
     *
     * @param Variant $source      Zrodlowy wariant do skopiowania
     * @param Variant $newParent   Nowy rodzic (nowa grupa lub wariant)
     * @param Order   $order       Zamowienie docelowe
     * @param string  $oldPrefix   Stary prefix do podmiany (np. "A")
     */
    private function copyVariantUnderNewParent(
        Variant $source,
        Variant $newParent,
        Order $order,
        string $oldPrefix
    ): Variant {
        // Podmien pierwszy segment numeru (liter) na nowy prefix grupy
        $newParentNumber = $newParent->variant_number;
        $sourceNumber    = $source->variant_number;

        // Nowy numer: podmien stary prefix na nowy
        // A1 → B1, A1_1 → B1_1, A2 → B2
        $newNumber = $newParentNumber . substr($sourceNumber, strlen($oldPrefix));

        $newVariant = $order->variants()->create([
            'is_group'         => false,
            'parent_variant_id' => $newParent->id,
            'variant_number'   => $newNumber,
            'name'             => $source->name,
            'description'      => $source->description,
            'quantity'         => $source->quantity,
            'type'             => $source->type,
            'status'           => VariantStatus::DRAFT,
            'is_approved'      => false,
        ]);

        // Rekurencyjnie kopiuj dzieci
        $source->load('childVariants');
        foreach ($source->childVariants as $grandChild) {
            $this->copyVariantUnderNewParent($grandChild, $newVariant, $order, $oldPrefix);
        }

        return $newVariant;
    }

    /**
     * Duplikuj WARIANT jako brat (sibling) lub dziecko (child).
     */
    private function duplicateVariant(Request $request, Variant $variant)
    {
        $validated = $request->validate([
            'relation'       => 'required|in:sibling,child',
            'name'           => 'required|string|max:255',
            'quantity'       => 'required|integer|min:1',
            'type'           => ['required', Rule::enum(VariantType::class)],
            'copy_quotation' => 'boolean',
            'copy_materials' => 'boolean',
            'description'    => 'nullable|string',
        ]);

        return DB::transaction(function () use ($variant, $validated) {
            $order = $variant->order;

            // Oblicz numer i parent_id dla nowego wariantu
            if ($validated['relation'] === 'sibling') {
                $variantNumber  = $this->nextSiblingNumber($order, $variant);
                $parentVariantId = $variant->parent_variant_id;
            } else {
                $variantNumber  = $this->nextChildNumber($variant);
                $parentVariantId = $variant->id;
            }

            // Utwórz nowy wariant
            $newVariant = $order->variants()->create([
                'is_group'         => false,
                'parent_variant_id' => $parentVariantId,
                'variant_number'   => $variantNumber,
                'name'             => $validated['name'],
                'quantity'         => $validated['quantity'],
                'type'             => $validated['type'],
                'description'      => $validated['description'] ?? $variant->description,
                'status'           => VariantStatus::DRAFT,
                'is_approved'      => false,
            ]);

            // Kopiuj materialy
            if (!empty($validated['copy_materials'])) {
                foreach ($variant->materials()->get() as $material) {
                    $newVariant->materials()->create([
                        'assortment_id'         => $material->assortment_id,
                        'quantity'              => $material->quantity,
                        'unit'                  => $material->unit,
                        'unit_price'            => $material->unit_price,
                        'total_cost'            => $material->total_cost,
                        'status'                => MaterialStatus::NOT_ORDERED,
                        'quantity_in_stock'     => 0,
                        'quantity_ordered'      => 0,
                        'expected_delivery_date' => null,
                        'ordered_at'            => null,
                        'received_at'           => null,
                        'supplier'              => $material->supplier,
                        'notes'                 => $material->notes,
                    ]);
                }
            }

            // Kopiuj wycene
            if (!empty($validated['copy_quotation'])) {
                $sourceQuotation = $variant->approvedQuotation
                    ?? $variant->quotations()->with(['items.materials', 'items.services'])
                        ->orderBy('version_number', 'desc')
                        ->first();

                if ($sourceQuotation) {
                    if (!$sourceQuotation->relationLoaded('items')) {
                        $sourceQuotation->load(['items.materials', 'items.services']);
                    }

                    $newQuotation = $newVariant->quotations()->create([
                        'version_number'       => 1,
                        'total_materials_cost' => $sourceQuotation->total_materials_cost,
                        'total_services_cost'  => $sourceQuotation->total_services_cost,
                        'total_net'            => $sourceQuotation->total_net,
                        'total_gross'          => $sourceQuotation->total_gross,
                        'margin_percent'       => $sourceQuotation->margin_percent,
                        'notes'                => 'Skopiowane z ' . $variant->variant_number
                            . ($sourceQuotation->notes ? '. ' . $sourceQuotation->notes : ''),
                        'is_approved'          => false,
                        'approved_at'          => null,
                        'approved_by'          => null,
                    ]);

                    foreach ($sourceQuotation->items as $item) {
                        $newItem = $newQuotation->items()->create([
                            'materials_cost' => $item->materials_cost,
                            'services_cost'  => $item->services_cost,
                            'subtotal'       => $item->subtotal,
                        ]);

                        foreach ($item->materials as $mat) {
                            $newItem->materials()->create([
                                'assortment_item_id' => $mat->assortment_item_id,
                                'quantity'           => $mat->quantity,
                                'unit'               => $mat->unit,
                                'unit_price'         => $mat->unit_price,
                                'total_cost'         => $mat->total_cost,
                                'notes'              => $mat->notes,
                            ]);
                        }

                        foreach ($item->services as $svc) {
                            $newItem->services()->create([
                                'assortment_item_id'  => $svc->assortment_item_id,
                                'estimated_quantity'  => $svc->estimated_quantity,
                                'estimated_time_hours' => $svc->estimated_time_hours,
                                'unit'               => $svc->unit,
                                'unit_price'         => $svc->unit_price,
                                'total_cost'         => $svc->total_cost,
                                'notes'              => $svc->notes,
                            ]);
                        }
                    }
                }
            }

            $newVariant->load(['parentVariant', 'materials', 'quotations']);

            return response()->json([
                'message'        => 'Wariant zduplikowany pomyslnie',
                'variant'        => $newVariant,
                'variant_number' => $variantNumber,
                'relation'       => $validated['relation'],
            ], 201);
        });
    }

    // =========================================================================
    // HELPERS — NUMERACJA
    // =========================================================================

    /**
     * Nastepna wolna litera/numer dla BRATA zrodlowego wariantu.
     *
     * Jezeli zrodlo jest top-level → nastepna wolna litera wsrod grup (B, C, D...)
     * Jezeli zrodlo jest dzieckiem → nastepny numer wsrod rodzenstwa (A2, A3...)
     */
    private function nextSiblingNumber(Order $order, Variant $source): string
    {
        if ($source->parent_variant_id === null) {
            // Top-level: nastepna wolna litera
            $existing = $order->variants()
                ->whereNull('parent_variant_id')
                ->whereRaw('LENGTH(variant_number) = 1')
                ->pluck('variant_number')
                ->toArray();

            $letter = 'A';
            while (in_array($letter, $existing)) {
                $letter = chr(ord($letter) + 1);
            }
            return $letter;
        }

        // Dziecko: nastepny numer wsrod dzieci tego samego rodzica
        $parent = Variant::findOrFail($source->parent_variant_id);
        return $this->nextChildNumber($parent);
    }

    /**
     * Nastepny numer dziecka dla danego rodzica.
     *
     * Regula separatorow:
     *   Rodzic top-level (A, B)  → A1, A2, A3    (bez separatora)
     *   Rodzic 1-poziom (A1, B2) → A1_1, A1_2    (separator _)
     *
     * Selektor cyfry (czy rodzic juz "ma cyfre") decyduje o separatorze.
     */
    private function nextChildNumber(Variant $parent): string
    {
        $parentNumber = $parent->variant_number;

        // Jezeli rodzic zawiera cyfre — uzywamy _ jako separatora
        $isDeepParent = (bool) preg_match('/\d/', $parentNumber);
        $separator    = $isDeepParent ? '_' : '';

        $children = Variant::where('parent_variant_id', $parent->id)
            ->pluck('variant_number')
            ->toArray();

        if (empty($children)) {
            return $parentNumber . $separator . '1';
        }

        $prefix = $parentNumber . $separator;
        $maxNum = 0;

        foreach ($children as $childNumber) {
            if (str_starts_with($childNumber, $prefix)) {
                $suffix = substr($childNumber, strlen($prefix));
                if (ctype_digit($suffix)) {
                    $maxNum = max($maxNum, (int) $suffix);
                }
            }
        }

        return $prefix . ($maxNum + 1);
    }
}
