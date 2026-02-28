<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\VariantMaterial;
use App\Models\Assortment;
use App\Enums\MaterialStatus;
use App\Enums\AssortmentType;
use App\Enums\AssortmentUnit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class VariantMaterialController extends Controller
{
    /**
     * Lista materiałów wariantu (produkcja seryjna)
     *
     * GET /api/variants/{variant}/materials
     */
    public function index(Variant $variant): JsonResponse
    {
        $materials = $variant->materials()
            ->with('assortment')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => $materials,
            'summary' => $variant->materials_status_summary,
            'total_cost' => $variant->total_materials_cost,
        ]);
    }

    /**
     * Dodaj materiał do wariantu
     *
     * POST /api/variants/{variant}/materials
     */
    public function store(Request $request, Variant $variant): JsonResponse
    {
        $validated = $request->validate([
            'assortment_id' => 'required|exists:assortment,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string|max:10',
            'unit_price' => 'required|numeric|min:0',
            'status' => ['nullable', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'quantity_in_stock' => 'nullable|numeric|min:0',
            'quantity_ordered' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Automatyczne obliczenie total_cost
        $validated['total_cost'] = $validated['quantity'] * $validated['unit_price'];
        $validated['status'] = $validated['status'] ?? MaterialStatus::NOT_ORDERED->value;

        $material = $variant->materials()->create($validated);
        $material->load('assortment');

        return response()->json($material, 201);
    }

    /**
     * Szczegóły materiału
     *
     * GET /api/variant-materials/{material}
     */
    public function show(VariantMaterial $material): JsonResponse
    {
        $material->load('assortment', 'variant');
        return response()->json($material);
    }

    /**
     * Aktualizuj materiał wariantu
     *
     * PUT /api/variant-materials/{material}
     */
    public function update(Request $request, VariantMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'assortment_id' => 'sometimes|exists:assortment,id',
            'quantity' => 'sometimes|numeric|min:0.01',
            'unit' => 'sometimes|string|max:10',
            'unit_price' => 'sometimes|numeric|min:0',
            'status' => ['sometimes', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'received_at' => 'nullable|date',
            'quantity_in_stock' => 'sometimes|numeric|min:0',
            'quantity_ordered' => 'sometimes|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Przelicz koszt jeśli zmieniono ilość lub cenę
        $quantity = $validated['quantity'] ?? $material->quantity;
        $unitPrice = $validated['unit_price'] ?? $material->unit_price;
        $validated['total_cost'] = $quantity * $unitPrice;

        $material->update($validated);
        $material->load('assortment');

        return response()->json($material);
    }

    /**
     * Usuń materiał wariantu
     *
     * DELETE /api/variant-materials/{material}
     */
    public function destroy(VariantMaterial $material): JsonResponse
    {
        $material->delete();

        return response()->json([
            'message' => 'Materiał usunięty pomyślnie'
        ]);
    }

    /**
     * Zmień status materiału (szybka akcja)
     *
     * PATCH /api/variant-materials/{material}/status
     */
    public function updateStatus(Request $request, VariantMaterial $material): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(MaterialStatus::class)],
            'expected_delivery_date' => 'nullable|date',
            'ordered_at' => 'nullable|date',
            'received_at' => 'nullable|date',
            'quantity_in_stock' => 'nullable|numeric|min:0',
            'quantity_ordered' => 'nullable|numeric|min:0',
        ]);

        // Auto-fill dat w zależności od statusu
        $status = MaterialStatus::from($validated['status']);

        if ($status === MaterialStatus::ORDERED && !isset($validated['ordered_at'])) {
            $validated['ordered_at'] = now()->toDateString();
        }

        if ($status === MaterialStatus::IN_STOCK && !isset($validated['received_at'])) {
            $validated['received_at'] = now()->toDateString();
            $validated['quantity_in_stock'] = $material->quantity;
        }

        $material->update($validated);
        $material->load('assortment');

        return response()->json($material);
    }

    /**
     * Oznacz wszystkie materiały wariantu jako zamówione
     *
     * POST /api/variants/{variant}/materials/mark-all-ordered
     */
    public function markAllOrdered(Variant $variant): JsonResponse
    {
        $variant->materials()
            ->where('status', MaterialStatus::NOT_ORDERED)
            ->update([
                'status' => MaterialStatus::ORDERED,
                'ordered_at' => now()->toDateString(),
            ]);

        return response()->json([
            'message' => 'Wszystkie niezamówione materiały oznaczono jako zamówione',
            'summary' => $variant->fresh()->materials_status_summary,
        ]);
    }
    /**
     * Masowe dodawanie materiałów
     * Tworzy asortyment, jeśli nie istnieje.
     */
    /**
     * Masowe dodawanie materiałów
     * Tworzy asortyment, jeśli nie istnieje.
     */
    public function batchStore(Request $request, Variant $variant): JsonResponse
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit' => 'required|string|max:10',
            'items.*.unit_price' => 'nullable|numeric|min:0', // NOWE
        ]);

        $createdMaterials = [];

        \DB::transaction(function () use ($request, $variant, &$createdMaterials) {
            foreach ($request->items as $itemData) {
                // 1. Sprawdź, czy podano cenę w imporcie
                $importedPrice = isset($itemData['unit_price']) ? (float) $itemData['unit_price'] : null;

                // 2. Znajdź lub utwórz Asortyment
                $assortment = Assortment::firstOrCreate(
                    [
                        'name' => $itemData['name'],
                        'type' => AssortmentType::MATERIAL
                    ],
                    [
                        'category' => 'Importowane',
                        'unit' => AssortmentUnit::tryFrom($itemData['unit']) ?? AssortmentUnit::SZT,
                        // Jeśli nowy asortyment i podano cenę, ustaw ją jako domyślną
                        'default_price' => $importedPrice ?? 0,
                        'description' => 'Utworzono automatycznie z importu',
                        'is_active' => true
                    ]
                );

                // 3. Ustal cenę dla tego konkretnego materiału wariantu
                // Priorytet: Cena z importu -> Cena domyślna asortymentu -> 0
                $unitPrice = $importedPrice ?? $assortment->default_price;

                // Opcjonalnie: Jeśli asortyment istniał, ale miał cenę 0, a teraz importujemy z ceną - można zaktualizować asortyment
                if ($assortment->default_price == 0 && $importedPrice !== null) {
                    $assortment->update(['default_price' => $importedPrice]);
                }

                $quantity = $itemData['quantity'];
                $totalCost = $quantity * $unitPrice;

                // 4. Utwórz materiał wariantu
                $material = $variant->materials()->create([
                    'assortment_id' => $assortment->id,
                    'quantity' => $quantity,
                    'unit' => $itemData['unit'],
                    'unit_price' => $unitPrice,
                    'total_cost' => $totalCost,
                    'status' => MaterialStatus::NOT_ORDERED,
                ]);

                $createdMaterials[] = $material;
            }
        });

        return response()->json([
            'message' => 'Pomyślnie zaimportowano ' . count($createdMaterials) . ' pozycji.',
            'count' => count($createdMaterials)
        ], 201);
    }
}
