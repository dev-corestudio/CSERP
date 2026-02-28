<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Variant;
use App\Services\SeriesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * OrderSeriesController — zarządzanie seriami zamówień
 *
 * Seria = kolejne uruchomienie produkcji dla tego samego numeru zamówienia.
 *
 * Endpointy:
 *   GET  /api/orders/{order}/series          → lista wszystkich serii dla order_number
 *   GET  /api/orders/{order}/series/variants  → warianty z serii do selektora kopiowania
 *   POST /api/orders/{order}/series/create    → utwórz nową serię (pusta lub z kopiowaniem)
 */
class OrderSeriesController extends Controller
{
    public function __construct(
        protected SeriesService $seriesService
    ) {
    }

    // =========================================================================
    // LISTA SERII
    // =========================================================================

    /**
     * Pobierz wszystkie serie dla danego numeru zamówienia.
     *
     * Wejście: dowolne zamówienie z tego samego order_number.
     * Wyjście: lista wszystkich serii (Z/0001/0001, Z/0001/0002, ...),
     *          posortowana rosnąco.
     *
     * GET /api/orders/{order}/series
     */
    public function index(Order $order): JsonResponse
    {
        try {
            $series = $this->seriesService->getAllSeriesForOrderNumber($order->order_number);

            return response()->json([
                'order_number' => $order->order_number,
                'data' => $series,
                'count' => $series->count(),
            ]);
        } catch (\Exception $e) {
            Log::error("OrderSeriesController::index error: {$e->getMessage()}");
            return response()->json([
                'message' => 'Błąd podczas pobierania serii zamówienia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SELEKTOR WARIANTÓW DO KOPIOWANIA
    // =========================================================================

    /**
     * Pobierz warianty z danej serii do wyświetlenia w selektorze kopiowania.
     *
     * Zwraca warianty z informacją:
     *  - czy mają wycenę (zatwierdzoną lub nie)
     *  - czy mają materiały
     *  - podstawowe dane (nazwa, ilość, status)
     *
     * GET /api/orders/{order}/series/variants
     */
    public function variantsForSelector(Order $order): JsonResponse
    {
        try {
            $variants = $this->seriesService->getVariantsForCopySelector($order);

            return response()->json([
                'order_id' => $order->id,
                'full_order_number' => $order->full_order_number,
                'data' => $variants,
            ]);
        } catch (\Exception $e) {
            Log::error("OrderSeriesController::variantsForSelector error: {$e->getMessage()}");
            return response()->json([
                'message' => 'Błąd podczas pobierania wariantów do selektora',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // TWORZENIE NOWEJ SERII
    // =========================================================================

    /**
     * Utwórz nową serię dla zamówienia.
     *
     * Tryby działania:
     *
     * 1. PUSTA SERIA (bez copy_from_order_id lub bez variants):
     *    {
     *      "description":           "Seria letnia 2025",
     *      "planned_delivery_date": "2025-08-01",
     *      "priority":              "high"
     *    }
     *
     * 2. SERIA Z KOPIOWANIEM WARIANTÓW:
     *    {
     *      "description":            "Seria Q3 2025",
     *      "planned_delivery_date":  "2025-09-15",
     *      "copy_from_order_id":     42,      ← ID serii źródłowej (musi mieć ten sam order_number)
     *      "variants": [
     *        {
     *          "source_variant_id": 5,
     *          "copy_quotation":    true,    ← kopiuj wycenę (zatwierdzoną lub najnowszą)
     *          "copy_materials":    false    ← kopiuj materiały wariantu
     *        },
     *        {
     *          "source_variant_id": 7,
     *          "copy_quotation":    true,
     *          "copy_materials":    true
     *        }
     *      ]
     *    }
     *
     * POST /api/orders/{order}/series/create
     *
     * Parametr {order} = dowolne zamówienie z tego samego order_number
     * (zazwyczaj aktualna seria, z której widoku user klika "Nowa seria").
     */
    public function create(Request $request, Order $order): JsonResponse
    {
        // Walidacja danych wejściowych
        $validated = $request->validate([
            'description' => 'nullable|string|max:1000',
            'planned_delivery_date' => 'nullable|date',
            'priority' => 'nullable|string|in:low,normal,high,urgent',

            // Opcjonalne — tylko gdy kopiujemy z innej serii
            'copy_from_order_id' => 'nullable|integer|exists:orders,id',

            // Lista wariantów do skopiowania
            'variants' => 'nullable|array',
            'variants.*.source_variant_id' => [
                'required_with:variants',
                'integer',
                'exists:variants,id',
            ],
            'variants.*.copy_quotation' => 'nullable|boolean',
            'variants.*.copy_materials' => 'nullable|boolean',
        ]);

        try {
            // Jeśli podano copy_from_order_id — sprawdź czy ma ten sam order_number
            $sourceForCopy = null;
            if (!empty($validated['copy_from_order_id'])) {
                $sourceForCopy = Order::findOrFail($validated['copy_from_order_id']);

                if ($sourceForCopy->order_number !== $order->order_number) {
                    return response()->json([
                        'message' => "Seria źródłowa (#{$sourceForCopy->id}, " .
                            "{$sourceForCopy->full_order_number}) ma inny numer zamówienia " .
                            "niż {$order->full_order_number}. " .
                            "Można kopiować tylko z tej samej grupy serii.",
                        'errors' => [
                            'copy_from_order_id' => [
                                'Seria źródłowa musi mieć ten sam numer zamówienia.'
                            ]
                        ]
                    ], 422);
                }

                // Sprawdź czy wszystkie warianty do skopiowania należą do copy_from_order_id
                if (!empty($validated['variants'])) {
                    $sourceVariantIds = Variant::where('order_id', $sourceForCopy->id)
                        ->pluck('id')
                        ->toArray();

                    foreach ($validated['variants'] as $variantConfig) {
                        if (!in_array($variantConfig['source_variant_id'], $sourceVariantIds)) {
                            return response()->json([
                                'message' => "Wariant #{$variantConfig['source_variant_id']} " .
                                    "nie należy do serii #{$sourceForCopy->id} " .
                                    "({$sourceForCopy->full_order_number}).",
                                'errors' => [
                                    'variants' => [
                                        "Wariant #{$variantConfig['source_variant_id']} " .
                                        "nie należy do wybranej serii źródłowej."
                                    ]
                                ]
                            ], 422);
                        }
                    }
                }
            }

            // Dane nowego zamówienia
            $orderData = [
                'description' => $validated['description'],
                'planned_delivery_date' => $validated['planned_delivery_date'] ?? null,
                'priority' => $validated['priority'] ?? 'normal',
            ];

            // Warianty do skopiowania (null = pusta seria)
            $variantsToCopy = !empty($validated['variants']) ? $validated['variants'] : null;

            // Tworzymy nową serię — zawsze na podstawie order_number z $order
            // (niezależnie od tego, którą serię user wskazał jako źródło kopii)
            $newOrder = $this->seriesService->createNewSeries(
                sourceOrder: $order,
                orderData: $orderData,
                variantsToCopy: $variantsToCopy
            );

            // Przygotuj opis tego co zostało zrobione
            $summary = $this->buildCreationSummary($newOrder, $variantsToCopy, $sourceForCopy);

            return response()->json([
                'message' => "Nowa seria {$newOrder->full_order_number} utworzona pomyślnie.",
                'data' => $newOrder,
                'summary' => $summary,
            ], 201);

        } catch (\InvalidArgumentException $e) {
            // Błędy walidacji biznesowej z SeriesService
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("OrderSeriesController::create error: {$e->getMessage()}", [
                'order_id' => $order->id,
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Błąd podczas tworzenia nowej serii',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // POMOCNICZE
    // =========================================================================

    /**
     * Zbuduj czytelne podsumowanie operacji tworzenia serii.
     * Używane do informowania frontendu co zostało wykonane.
     */
    private function buildCreationSummary(
        Order $newOrder,
        ?array $variantsToCopy,
        ?Order $sourceOrder
    ): array {
        $summary = [
            'new_order_id' => $newOrder->id,
            'new_full_order_number' => $newOrder->full_order_number,
            'variants_created' => $newOrder->variants->count(),
            'copied_from' => $sourceOrder?->full_order_number,
        ];

        if (!empty($variantsToCopy)) {
            $withQuotation = collect($variantsToCopy)->where('copy_quotation', true)->count();
            $withMaterials = collect($variantsToCopy)->where('copy_materials', true)->count();

            $summary['copy_details'] = [
                'variants_requested' => count($variantsToCopy),
                'with_quotation_copy' => $withQuotation,
                'with_materials_copy' => $withMaterials,
            ];
        }

        return $summary;
    }
}
