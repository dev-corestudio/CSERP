<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Variant;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationItemMaterial;
use App\Models\QuotationItemService;
use App\Models\VariantMaterial;
use App\Enums\MaterialStatus;
use App\Enums\VariantStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Enums\PaymentStatus;
use App\Enums\OrderOverallStatus;
use App\Enums\OrderPriority;


/**
 * SeriesService — logika tworzenia nowych serii zamówień
 *
 * Seria = to samo zamówienie (ten sam order_number) produkowane kolejny raz.
 * Przykład: Z/0001/0001 → Z/0001/0002 → Z/0001/0003
 *
 * Przy tworzeniu nowej serii user może:
 *  - Stworzyć pustą serię (bez wariantów)
 *  - Skopiować wybrane warianty z poprzedniej serii
 *    - z opcją kopiowania wycen (quotations)
 *    - z opcją kopiowania materiałów (variant_materials)
 */
class SeriesService
{
    // =========================================================================
    // TWORZENIE NOWEJ SERII
    // =========================================================================

    /**
     * Utwórz nową serię dla danego zamówienia (tego samego order_number).
     *
     * @param Order $sourceOrder    - zamówienie źródłowe (ta sama seria lub dowolna)
     * @param array $orderData      - dane nowego zamówienia (description, planned_delivery_date, priority)
     * @param array|null $variantsToCopy - które warianty kopiować i co z nich brać
     *   Format:
     *   [
     *     ['source_variant_id' => 5, 'copy_quotation' => true,  'copy_materials' => false],
     *     ['source_variant_id' => 7, 'copy_quotation' => false, 'copy_materials' => true],
     *   ]
     *   null lub [] = pusta seria (żadnych wariantów)
     *
     * @return Order - nowo utworzone zamówienie (nowa seria)
     */
    public function createNewSeries(
        Order $sourceOrder,
        array $orderData,
        ?array $variantsToCopy = null
    ): Order {
        return DB::transaction(function () use ($sourceOrder, $orderData, $variantsToCopy) {

            // Użyj tego samego numeru zamówienia, wygeneruj kolejną serię
            $newSeries = Order::generateSeries($sourceOrder->order_number);

            // Utwórz nowe zamówienie (nową serię)
            $newOrder = Order::create([
                'customer_id' => $sourceOrder->customer_id,
                'order_number' => $sourceOrder->order_number,
                'series' => $newSeries,
                'description' => $orderData['description'] ?? $sourceOrder->description,
                'planned_delivery_date' => $orderData['planned_delivery_date'] ?? null,
                'priority' => isset($orderData['priority'])
                    ? OrderPriority::from(strtoupper($orderData['priority']))
                    : OrderPriority::NORMAL,
                'overall_status' => OrderOverallStatus::DRAFT,
                'payment_status' => PaymentStatus::UNPAID,
            ]);

            Log::info(
                "SeriesService: Utworzono nową serię #{$newOrder->id} " .
                "({$newOrder->full_order_number}) na podstawie #{$sourceOrder->id} " .
                "({$sourceOrder->full_order_number})"
            );

            // Jeśli user podał warianty do skopiowania - kopiuj je
            if (!empty($variantsToCopy)) {
                foreach ($variantsToCopy as $variantConfig) {
                    $this->copyVariantToOrder(
                        sourceVariantId: $variantConfig['source_variant_id'],
                        targetOrder: $newOrder,
                        copyQuotation: (bool) ($variantConfig['copy_quotation'] ?? false),
                        copyMaterials: (bool) ($variantConfig['copy_materials'] ?? false)
                    );
                }
            }

            // Załaduj relacje dla odpowiedzi
            $newOrder->load(['customer', 'variants']);

            return $newOrder;
        });
    }

    // =========================================================================
    // KOPIOWANIE WARIANTU
    // =========================================================================

    /**
     * Skopiuj wariant z jednego zamówienia do innego.
     *
     * Zawsze kopiuje podstawowe dane wariantu (nazwa, opis, ilość, typ).
     * Opcjonalnie: wycena + materiały.
     *
     * @param int   $sourceVariantId  - ID wariantu źródłowego
     * @param Order $targetOrder      - zamówienie docelowe (nowa seria)
     * @param bool  $copyQuotation    - czy kopiować zatwierdzoną wycenę (lub najnowszą)
     * @param bool  $copyMaterials    - czy kopiować listę materiałów wariantu
     *
     * @return Variant - nowo utworzony wariant
     */
    public function copyVariantToOrder(
        int $sourceVariantId,
        Order $targetOrder,
        bool $copyQuotation = false,
        bool $copyMaterials = false
    ): Variant {
        // Załaduj wariant źródłowy z potrzebnymi relacjami
        $sourceVariant = Variant::with([
            'quotations.items.materials',
            'quotations.items.services',
            'materials',
        ])->findOrFail($sourceVariantId);

        // Sprawdź czy wariant należy do zamówienia z tym samym order_number
        // (zabezpieczenie przed kopiowaniem między różnymi zamówieniami)
        $sourceOrder = $sourceVariant->order;
        if ($sourceOrder->order_number !== $targetOrder->order_number) {
            throw new \InvalidArgumentException(
                "Wariant #{$sourceVariantId} należy do zamówienia " .
                "{$sourceOrder->full_order_number}, " .
                "które ma inny numer niż docelowe {$targetOrder->full_order_number}."
            );
        }

        // Ustal kolejny numer wariantu w zamówieniu docelowym
        $nextVariantNumber = $this->getNextVariantNumber($targetOrder);

        // Utwórz nowy wariant (reset statusu - zaczyna od nowa)
        $newVariant = Variant::create([
            'order_id' => $targetOrder->id,
            'parent_variant_id' => null,
            'variant_number' => $nextVariantNumber,
            'name' => $sourceVariant->name,
            'description' => $sourceVariant->description,
            'quantity' => $sourceVariant->quantity,
            'type' => $sourceVariant->type,
            'status' => VariantStatus::QUOTATION, // zawsze zaczyna od wyceny
            'is_approved' => false,
            'feedback_notes' => null,
            'approved_prototype_id' => null,
        ]);

        Log::info(
            "SeriesService: Skopiowano wariant #{$sourceVariant->id} " .
            "({$sourceVariant->name}) → #{$newVariant->id} " .
            "w zamówieniu #{$targetOrder->id} ({$targetOrder->full_order_number})"
        );

        // Kopiuj wycenę (jeśli zażądano)
        if ($copyQuotation) {
            $this->copyBestQuotation($sourceVariant, $newVariant);
        }

        // Kopiuj materiały (jeśli zażądano)
        if ($copyMaterials) {
            $this->copyVariantMaterials($sourceVariant, $newVariant);
        }

        return $newVariant->fresh(['quotations', 'materials']);
    }

    // =========================================================================
    // KOPIOWANIE WYCENY
    // =========================================================================

    /**
     * Skopiuj wycenę z wariantu źródłowego do docelowego.
     *
     * Priorytet wyboru źródła:
     *  1. Zatwierdzona wycena (is_approved = true) — najlepsza jakość danych
     *  2. Najnowsza wycena (max version_number) — jeśli żadna nie zatwierdzona
     *
     * Nowa wycena tworzona jest jako wersja 1, NIE zatwierdzona
     * (nowa seria = nowa wycena do zatwierdzenia).
     *
     * @param Variant $sourceVariant
     * @param Variant $targetVariant
     * @return Quotation|null - nowa wycena lub null jeśli źródło nie ma wycen
     */
    private function copyBestQuotation(Variant $sourceVariant, Variant $targetVariant): ?Quotation
    {
        // Wybierz najlepszą wycenę źródłową
        $sourceQuotation = $sourceVariant->quotations()
            ->with(['items.materials', 'items.services'])
            ->where('is_approved', true)
            ->first();

        // Jeśli brak zatwierdzonej — weź najnowszą
        if (!$sourceQuotation) {
            $sourceQuotation = $sourceVariant->quotations()
                ->with(['items.materials', 'items.services'])
                ->orderByDesc('version_number')
                ->first();
        }

        if (!$sourceQuotation) {
            Log::warning(
                "SeriesService: Wariant #{$sourceVariant->id} nie ma żadnej wyceny do skopiowania."
            );
            return null;
        }

        // Utwórz nową wycenę (wersja 1, niezatwierdzona)
        $newQuotation = Quotation::create([
            'variant_id' => $targetVariant->id,
            'version_number' => 1,
            'total_materials_cost' => $sourceQuotation->total_materials_cost,
            'total_services_cost' => $sourceQuotation->total_services_cost,
            'total_net' => $sourceQuotation->total_net,
            'total_gross' => $sourceQuotation->total_gross,
            'margin_percent' => $sourceQuotation->margin_percent,
            'is_approved' => false,  // Zawsze niezatwierdzona w nowej serii
            'approved_at' => null,
            'approved_by_user_id' => null,
            'notes' => $this->buildCopyNote($sourceQuotation, $sourceVariant),
        ]);

        // Kopiuj pozycje wyceny (items)
        foreach ($sourceQuotation->items as $sourceItem) {
            $newItem = QuotationItem::create([
                'quotation_id' => $newQuotation->id,
                'materials_cost' => $sourceItem->materials_cost,
                'services_cost' => $sourceItem->services_cost,
                'subtotal' => $sourceItem->subtotal,
            ]);

            // Kopiuj materiały pozycji
            foreach ($sourceItem->materials as $material) {
                QuotationItemMaterial::create([
                    'quotation_item_id' => $newItem->id,
                    'assortment_item_id' => $material->assortment_item_id,
                    'quantity' => $material->quantity,
                    'unit' => $material->unit,
                    'unit_price' => $material->unit_price,
                    'total_cost' => $material->total_cost,
                    'notes' => $material->notes,
                ]);
            }

            // Kopiuj usługi pozycji
            foreach ($sourceItem->services as $service) {
                QuotationItemService::create([
                    'quotation_item_id' => $newItem->id,
                    'assortment_item_id' => $service->assortment_item_id,
                    'estimated_quantity' => $service->estimated_quantity,
                    'estimated_time_hours' => $service->estimated_time_hours,
                    'unit' => $service->unit,
                    'unit_price' => $service->unit_price,
                    'total_cost' => $service->total_cost,
                    'notes' => $service->notes,
                ]);
            }
        }

        Log::info(
            "SeriesService: Skopiowano wycenę #{$sourceQuotation->id} " .
            "(v{$sourceQuotation->version_number}) → #{$newQuotation->id} (v1) " .
            "dla wariantu #{$targetVariant->id}"
        );

        return $newQuotation;
    }

    // =========================================================================
    // KOPIOWANIE MATERIAŁÓW
    // =========================================================================

    /**
     * Skopiuj materiały wariantu (variant_materials) ze źródłowego do docelowego.
     *
     * Kopiuje tylko definicję (asortyment, ilość, cena) — bez statusów logistycznych.
     * Nowe materiały zaczynają od NOT_ORDERED (nowa seria = nowe zamówienie materiałów).
     *
     * @param Variant $sourceVariant
     * @param Variant $targetVariant
     * @return int - liczba skopiowanych materiałów
     */
    private function copyVariantMaterials(Variant $sourceVariant, Variant $targetVariant): int
    {
        $sourceMaterials = $sourceVariant->materials;

        if ($sourceMaterials->isEmpty()) {
            Log::warning(
                "SeriesService: Wariant #{$sourceVariant->id} nie ma materiałów do skopiowania."
            );
            return 0;
        }

        $count = 0;

        foreach ($sourceMaterials as $sourceMaterial) {
            VariantMaterial::create([
                'variant_id' => $targetVariant->id,
                'assortment_id' => $sourceMaterial->assortment_id,
                'quantity' => $sourceMaterial->quantity,
                'unit' => $sourceMaterial->unit,
                'unit_price' => $sourceMaterial->unit_price,
                'total_cost' => $sourceMaterial->total_cost,
                // Resetuj status logistyczny — nowa seria, nowe zamówienie
                'status' => MaterialStatus::NOT_ORDERED,
                'notes' => $sourceMaterial->notes,
                // Daty i ilości — zerowane (nowa dostawa)
                'expected_delivery_date' => null,
                'ordered_at' => null,
                'received_at' => null,
                'quantity_in_stock' => 0,
                'quantity_ordered' => 0,
                'supplier' => $sourceMaterial->supplier,
            ]);
            $count++;
        }

        Log::info(
            "SeriesService: Skopiowano {$count} materiałów " .
            "z wariantu #{$sourceVariant->id} → #{$targetVariant->id}"
        );

        return $count;
    }

    // =========================================================================
    // POMOCNICZE
    // =========================================================================

    /**
     * Pobierz kolejny numer wariantu dla danego zamówienia.
     * Liczymy istniejące warianty i dodajemy 1.
     */
    private function getNextVariantNumber(Order $order): int
    {
        $maxNumber = Variant::where('order_id', $order->id)->max('variant_number');
        return $maxNumber ? $maxNumber + 1 : 1;
    }

    /**
     * Zbuduj notatkę informującą o źródle kopii wyceny.
     */
    private function buildCopyNote(Quotation $sourceQuotation, Variant $sourceVariant): string
    {
        $sourceOrder = $sourceVariant->order;
        return "[Kopia z {$sourceOrder->full_order_number}, " .
            "wariant #{$sourceVariant->variant_number} ({$sourceVariant->name}), " .
            "wycena v{$sourceQuotation->version_number}" .
            ($sourceQuotation->is_approved ? ', zatwierdzona' : '') .
            "]";
    }

    // =========================================================================
    // POBIERANIE SERII
    // =========================================================================

    /**
     * Pobierz wszystkie serie dla danego numeru zamówienia.
     * Zwraca kolekcję zamówień posortowanych rosnąco po serii.
     *
     * @param string $orderNumber - 4-cyfrowy numer zamówienia
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllSeriesForOrderNumber(string $orderNumber)
    {
        return Order::with(['customer', 'variants'])
            ->where('order_number', $orderNumber)
            ->orderBy('series', 'asc')
            ->get();
    }

    /**
     * Pobierz warianty z zamówienia sformatowane do wyświetlenia w selektorze kopiowania.
     * Zawiera informację o tym, czy wariant ma wycenę i materiały.
     *
     * @param Order $order
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVariantsForCopySelector(Order $order)
    {
        return Variant::with(['approvedQuotation', 'materials'])
            ->where('order_id', $order->id)
            ->orderBy('variant_number')
            ->get()
            ->map(function (Variant $variant) {
                return [
                    'id' => $variant->id,
                    'variant_number' => $variant->variant_number,
                    'name' => $variant->name,
                    'quantity' => $variant->quantity,
                    'status' => $variant->status,
                    'type' => $variant->type,
                    // Czy jest co kopiować?
                    'has_quotation' => $variant->quotations()->exists(),
                    'has_approved_quotation' => $variant->approvedQuotation !== null,
                    'has_materials' => $variant->materials->isNotEmpty(),
                    'materials_count' => $variant->materials->count(),
                    // Info o najlepszej wycenie
                    'quotation_info' => $this->getQuotationInfo($variant),
                ];
            });
    }

    /**
     * Pomocnicza — informacje o wycenie wariantu dla selektora.
     */
    private function getQuotationInfo(Variant $variant): ?array
    {
        $approved = $variant->approvedQuotation;
        if ($approved) {
            return [
                'version' => $approved->version_number,
                'total_gross' => $approved->total_gross,
                'is_approved' => true,
            ];
        }

        $latest = $variant->quotations()->orderByDesc('version_number')->first();
        if ($latest) {
            return [
                'version' => $latest->version_number,
                'total_gross' => $latest->total_gross,
                'is_approved' => false,
            ];
        }

        return null;
    }
}
