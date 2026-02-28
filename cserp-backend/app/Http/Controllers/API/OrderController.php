<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Enums\ProductionStatus;
use App\Enums\PaymentStatus;
use App\Enums\OrderOverallStatus;
use App\Enums\OrderPriority;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    use Paginatable;

    // =========================================================================
    // NUMER ZAMÓWIENIA
    // =========================================================================

    /**
     * Zwraca następny wolny numer zamówienia (podgląd).
     * Używany przez frontend do wyświetlenia info — nie rezerwuje numeru.
     *
     * GET /api/orders/next-number
     */
    public function nextNumber()
    {
        $maxNumber = Order::max('order_number');
        $next = $maxNumber ? intval($maxNumber) + 1 : 1000;
        $formatted = str_pad((string) $next, 4, '0', STR_PAD_LEFT);

        return response()->json(['next_number' => $formatted]);
    }

    // =========================================================================
    // LISTA ZAMÓWIEŃ
    // =========================================================================

    /**
     * Lista zamówień z paginacją server-side.
     *
     * Query params:
     *   - page:         int    (domyślnie 1)
     *   - per_page:     int    (domyślnie 15, max 100)
     *   - sort_by:      string (created_at | order_number | planned_delivery_date | overall_status)
     *   - sort_dir:     string (asc | desc, domyślnie desc)
     *   - search:       string (szuka w order_number, description, customer.name)
     *   - status:       string (filtruje overall_status)
     *   - quick_filter: string (active | completed | all)
     *
     * GET /api/orders
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['customer', 'variants']);

            // Filtr statusu
            if ($request->filled('status') && $request->input('status') !== 'all') {
                $query->where('overall_status', $request->input('status'));
            }

            // Quick filter (aktywne/zakończone)
            if ($request->filled('quick_filter')) {
                $quickFilter = $request->input('quick_filter');

                if ($quickFilter === 'active') {
                    $query->whereIn('overall_status', [
                        'draft',
                        'quotation',
                        'prototype',
                        'production',
                        'delivery',
                        'DRAFT',
                        'QUOTATION',
                        'PROTOTYPE',
                        'PRODUCTION',
                        'DELIVERY',
                    ]);
                } elseif ($quickFilter === 'completed') {
                    $query->whereIn('overall_status', [
                        'completed',
                        'cancelled',
                        'COMPLETED',
                        'CANCELLED',
                    ]);
                }
                // 'all' — brak dodatkowego filtra
            }

            // Wyszukiwanie
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                });
            }

            // Sortowanie z whitelistą kolumn
            $this->applySorting($query, $request, [
                'created_at',
                'order_number',
                'planned_delivery_date',
                'overall_status',
            ], 'created_at', 'desc');

            // Paginacja
            $orders = $this->paginateQuery($query, $request);

            return response()->json($orders);
        } catch (\Exception $e) {
            \Log::error('Orders index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania zamówień',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // SZCZEGÓŁY ZAMÓWIENIA
    // =========================================================================

    /**
     * Szczegóły zamówienia z wariantami, prototypami i wycenami.
     *
     * GET /api/orders/{order}
     */
    public function show(Order $order)
    {
        try {
            $order->load([
                'customer',
                'variants.prototypes',
                'variants.productionOrder',
                'variants.approvedQuotation',
                'images'
            ]);

            return response()->json($order);
        } catch (\Exception $e) {
            \Log::error('Order show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania zamówienia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // TWORZENIE NOWEGO ZAMÓWIENIA
    // =========================================================================

    /**
     * Utwórz nowe zamówienie.
     *
     * Numer zamówienia (order_number) jest ZAWSZE generowany przez serwer —
     * user go nie podaje. Serwer bierze max(order_number) + 1.
     * Nowe zamówienie dostaje serię 0001.
     *
     * Przykład:
     *   Ostatni order_number w bazie: 1005
     *   Nowe zamówienie: order_number=1006, series=0001 → Z/1006/0001
     *
     * Body:
     * {
     *   "customer_id":           1,           (wymagane)
     *   "description":           "...",        (wymagane)
     *   "planned_delivery_date": "2025-12-01", (wymagane)
     *   "priority":              "normal"      (opcjonalne, domyślnie "normal")
     * }
     *
     * POST /api/orders
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'description' => 'required|string',
                'planned_delivery_date' => 'required|date',
                'priority' => ['nullable', Rule::enum(OrderPriority::class)],
            ]);

            DB::beginTransaction();

            // Serwer generuje kolejny numer zamówienia — user nie ma wpływu
            $maxNumber = Order::max('order_number');
            $nextNumber = $maxNumber ? intval($maxNumber) + 1 : 1000;
            $orderNumber = str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);

            // Pierwsze zamówienie z tym numerem → seria 0001
            $series = Order::generateSeries($orderNumber);

            $order = Order::create([
                'customer_id' => $validated['customer_id'],
                'order_number' => $orderNumber,
                'series' => $series,
                'description' => $validated['description'],
                'planned_delivery_date' => $validated['planned_delivery_date'],
                'priority' => $validated['priority'] ?? OrderPriority::NORMAL,
                'overall_status' => OrderOverallStatus::DRAFT,       // Nowe zamówienie startuje jako DRAFT
                'payment_status' => PaymentStatus::UNPAID,
            ]);

            DB::commit();

            $order->load('customer');

            return response()->json($order, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas tworzenia zamówienia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // AKTUALIZACJA ZAMÓWIENIA
    // =========================================================================

    /**
     * Aktualizuj dane zamówienia.
     * Nie można zmieniać order_number ani series przez ten endpoint.
     *
     * PUT /api/orders/{order}
     */
    public function update(Request $request, Order $order)
    {
        try {
            $validated = $request->validate([
                'customer_id' => 'sometimes|exists:customers,id',
                'description' => 'sometimes|string',
                'planned_delivery_date' => 'sometimes|date',
                'priority' => 'sometimes|string|in:low,normal,high,urgent',
                'overall_status' => 'sometimes|string',
            ]);

            // order_number i series są niezmieniane — integralność danych
            $order->update($validated);
            $order->load('customer');

            return response()->json($order);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Order update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas aktualizacji zamówienia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // USUWANIE ZAMÓWIENIA
    // =========================================================================

    /**
     * Usuń zamówienie.
     * Dozwolone tylko w statusie draft lub quotation (brak aktywnej produkcji).
     *
     * DELETE /api/orders/{order}
     */
    public function destroy(Order $order)
    {
        try {
            if (!in_array($order->overall_status->value ?? $order->overall_status, ['draft', 'quotation'])) {
                return response()->json([
                    'message' => 'Można usunąć tylko zamówienia w fazie szkicu lub wyceny'
                ], 403);
            }

            $orderNumber = $order->full_order_number;
            $order->delete();

            return response()->json([
                'message' => "Zamówienie {$orderNumber} usunięte pomyślnie"
            ]);
        } catch (\Exception $e) {
            \Log::error('Order delete error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas usuwania zamówienia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // =========================================================================
    // PODSUMOWANIE FINANSOWE
    // =========================================================================

    /**
     * Pełne podsumowanie finansowe zamówienia:
     *  - Suma zatwierdzonych wycen (netto/brutto) per wariant i łącznie
     *  - Rzeczywiste koszty usług z produkcji
     *  - Rzeczywiste koszty materiałów
     *  - Wariancja całkowita
     *
     * GET /api/orders/{order}/financial-summary
     */
    public function financialSummary(Order $order)
    {
        try {
            $order->load([
                'variants.approvedQuotation',
                'variants.productionOrder.services',
            ]);

            $variantsSummary = [];
            $totalApprovedNet = 0.0;
            $totalApprovedGross = 0.0;
            $totalApprovedMat = 0.0;
            $totalApprovedSvc = 0.0;
            $totalActualMat = 0.0;
            $totalActualSvc = 0.0;

            foreach ($order->variants as $variant) {
                $approvedQ = $variant->approvedQuotation;

                $approvedNet = $approvedQ ? (float) $approvedQ->total_net : 0.0;
                $approvedGross = $approvedQ ? (float) $approvedQ->total_gross : 0.0;
                $approvedMat = $approvedQ ? (float) $approvedQ->total_materials_cost : 0.0;
                $approvedSvc = $approvedQ ? (float) $approvedQ->total_services_cost : 0.0;

                // Koszty rzeczywiste materiałów — bezpośrednie zapytanie (brak błędów z castami)
                $actualMat = (float) \App\Models\VariantMaterial::where('variant_id', $variant->id)
                    ->sum('total_cost');

                // Koszty rzeczywiste usług — tylko COMPLETED i IN_PROGRESS
                $actualSvc = 0.0;
                if ($variant->productionOrder) {
                    $actualSvc = (float) \App\Models\ProductionService::where('production_order_id', $variant->productionOrder->id)
                        ->whereIn('status', [
                            ProductionStatus::COMPLETED->value,
                            ProductionStatus::IN_PROGRESS->value,
                        ])
                        ->whereNotNull('actual_cost')
                        ->sum('actual_cost');
                }

                $variantsSummary[] = [
                    'variant_id' => $variant->id,
                    'variant_number' => $variant->variant_number,
                    'variant_name' => $variant->name,
                    'has_approved_quotation' => (bool) $approvedQ,
                    'approved_net' => round($approvedNet, 2),
                    'approved_gross' => round($approvedGross, 2),
                    'approved_materials_cost' => round($approvedMat, 2),
                    'approved_services_cost' => round($approvedSvc, 2),
                    'actual_materials_cost' => round($actualMat, 2),
                    'actual_services_cost' => round($actualSvc, 2),
                    'actual_total' => round($actualMat + $actualSvc, 2),
                    'variance' => round(($actualMat + $actualSvc) - $approvedGross, 2),
                ];

                $totalApprovedNet += $approvedNet;
                $totalApprovedGross += $approvedGross;
                $totalApprovedMat += $approvedMat;
                $totalApprovedSvc += $approvedSvc;
                $totalActualMat += $actualMat;
                $totalActualSvc += $actualSvc;
            }

            $totalActual = $totalActualMat + $totalActualSvc;
            $totalVariance = $totalActual - $totalApprovedGross;

            return response()->json([
                'order_id' => $order->id,
                'order_number' => $order->full_order_number,

                'total_approved_net' => round($totalApprovedNet, 2),
                'total_approved_gross' => round($totalApprovedGross, 2),
                'total_approved_materials' => round($totalApprovedMat, 2),
                'total_approved_services' => round($totalApprovedSvc, 2),

                'total_actual_materials' => round($totalActualMat, 2),
                'total_actual_services' => round($totalActualSvc, 2),
                'total_actual' => round($totalActual, 2),

                'total_variance' => round($totalVariance, 2),
                'variance_percent' => $totalApprovedGross > 0
                    ? round(($totalVariance / $totalApprovedGross) * 100, 2)
                    : null,

                'variants' => $variantsSummary,
            ]);

        } catch (\Exception $e) {
            \Log::error('Order financial summary error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania podsumowania finansowego',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
