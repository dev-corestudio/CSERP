<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Traits\Paginatable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    use Paginatable;

    /**
     * Lista klientów z paginacją server-side.
     *
     * Query params:
     *   - page, per_page, sort_by, sort_dir  → paginacja/sortowanie
     *   - search:    string (nazwa, email, NIP, telefon, adres)
     *   - type:      string (B2B|B2C|all)
     *   - is_active: string (true|false|all) — filtr aktywności
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Customer::query();

            // Filtrowanie po typie klienta
            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            // Filtrowanie po statusie aktywności
            if ($request->filled('is_active') && $request->input('is_active') !== 'all') {
                $query->where('is_active', $request->boolean('is_active'));
            }

            // Wyszukiwanie
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            }

            // Dołącz statystyki zamówień
            $query->withCount('orders');

            // Sortowanie z whitelistą
            $this->applySorting($query, $request, [
                'name',
                'email',
                'type',
                'created_at',
                'orders_count',
            ], 'name', 'asc');

            // Paginacja
            $customers = $this->paginateQuery($query, $request);

            return response()->json($customers);
        } catch (\Exception $e) {
            \Log::error('Customers index error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania klientów',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Utwórz nowego klienta
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:B2B,B2C',
                'nip' => 'nullable|string|max:10|unique:customers,nip',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'is_active' => 'boolean',
            ]);

            // Ustaw domyślne wartości
            $validated['is_active'] = $validated['is_active'] ?? true;

            $customer = Customer::create($validated);

            return response()->json([
                'data' => $customer,
                'message' => 'Klient utworzony pomyślnie'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Customer store error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas tworzenia klienta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pobierz szczegóły klienta
     *
     * OPTYMALIZACJA (pkt 2):
     *
     * BYŁO (8 zapytań SQL):
     *   1. load(['orders' => limit(20)])     — 1 query
     *   2. loadCount('orders')               — 1 query (redundantne!)
     *   3. $customer->orders()->count()      — 1 query (to samo co #2!)
     *   4. ->whereNotIn(...)->count()        — 1 query
     *   5. ->where('completed')->count()     — 1 query
     *   6. ->where('cancelled')->count()     — 1 query
     *   7. ->where('paid')->count()          — 1 query
     *   8. ->whereIn('unpaid','partial')     — 1 query
     *
     * JEST (1 zapytanie SQL):
     *   1. load(['orders']) — ładujemy WSZYSTKIE zamówienia (lekki select, bez limitu)
     *   2. Statystyki liczymy z kolekcji w PHP (0 dodatkowych query)
     *   3. Do frontendu wysyłamy tylko ostatnie 20 (setRelation → slice kolekcji)
     *
     * Klient w systemie MES ma zwykle dziesiątki/setki zamówień — załadowanie
     * lekkiego selecta jest tańsze niż 8 osobnych roundtripów do bazy.
     */
    public function show(Customer $customer): JsonResponse
    {
        try {
            // ── 1 query: załaduj WSZYSTKIE zamówienia (lekkie kolumny) ──
            $customer->load([
                'orders' => function ($query) {
                    $query->select(
                        'id',
                        'customer_id',
                        'order_number',
                        'series',
                        'description',
                        'overall_status',
                        'payment_status',
                        'created_at'
                    )->orderBy('created_at', 'desc');
                }
            ]);

            // ── 0 query: oblicz statystyki z załadowanej kolekcji ──
            $allOrders = $customer->orders;

            $stats = [
                'total_orders' => $allOrders->count(),
                'active_orders' => $allOrders->whereNotIn('overall_status', ['completed', 'cancelled'])->count(),
                'completed_orders' => $allOrders->where('overall_status', 'completed')->count(),
                'cancelled_orders' => $allOrders->where('overall_status', 'cancelled')->count(),
                'paid_orders' => $allOrders->where('payment_status', 'paid')->count(),
                'unpaid_orders' => $allOrders->whereIn('payment_status', ['unpaid', 'partial'])->count(),
            ];

            $customer->stats = $stats;

            // ── Podmień relację na ostatnie 20 (frontend nie potrzebuje setek) ──
            $customer->setRelation('orders', $allOrders->take(20)->values());

            return response()->json([
                'data' => $customer,
                'message' => 'Dane klienta pobrane pomyślnie'
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer show error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania klienta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aktualizuj dane klienta
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:B2B,B2C',
                'nip' => [
                    'nullable',
                    'string',
                    'max:10',
                    Rule::unique('customers', 'nip')->ignore($customer->id)
                ],
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string|max:500',
                'is_active' => 'boolean',
            ]);

            $customer->update($validated);

            return response()->json([
                'data' => $customer->fresh(),
                'message' => 'Dane klienta zaktualizowane pomyślnie'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Błąd walidacji',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Customer update error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas aktualizacji klienta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usuń klienta
     */
    public function destroy(Customer $customer): JsonResponse
    {
        try {
            // Sprawdź czy klient ma zamówienia
            if ($customer->orders()->exists()) {
                return response()->json([
                    'message' => 'Nie można usunąć klienta z przypisanymi zamówieniami. Dezaktywuj klienta zamiast usuwać.'
                ], 422);
            }

            $customer->delete();

            return response()->json([
                'message' => 'Klient usunięty pomyślnie'
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer destroy error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas usuwania klienta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Przełącz status aktywności klienta
     */
    public function toggleActive(Customer $customer): JsonResponse
    {
        try {
            $customer->update([
                'is_active' => !$customer->is_active
            ]);

            return response()->json([
                'data' => $customer->fresh(),
                'message' => $customer->is_active
                    ? 'Klient aktywowany pomyślnie'
                    : 'Klient dezaktywowany pomyślnie'
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer toggle active error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas zmiany statusu klienta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pobierz statystyki klienta
     *
     * OPTYMALIZACJA (pkt 1): 1 zapytanie SQL zamiast ~15
     *
     * Tutaj NIE ładujemy zamówień do pamięci — ten endpoint służy wyłącznie
     * do statystyk, więc zagregowany SQL jest optymalny (baza policzy szybciej
     * niż PHP, a nie transferujemy niepotrzebnych danych).
     */
    public function statistics(Customer $customer): JsonResponse
    {
        try {
            $row = DB::table('orders')
                ->where('customer_id', $customer->id)
                ->selectRaw('
                    COUNT(*) as total_orders,

                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_draft,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_quotation,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_prototype,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_production,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_delivery,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_completed,
                    COUNT(CASE WHEN overall_status = ? THEN 1 END) as status_cancelled,

                    COUNT(CASE WHEN payment_status = ? THEN 1 END) as payment_paid,
                    COUNT(CASE WHEN payment_status = ? THEN 1 END) as payment_partial,
                    COUNT(CASE WHEN payment_status = ? THEN 1 END) as payment_unpaid,
                    COUNT(CASE WHEN payment_status = ? THEN 1 END) as payment_overdue,

                    MIN(created_at) as first_order_date,
                    MAX(created_at) as last_order_date
                ', [
                    'draft',
                    'quotation',
                    'prototype',
                    'production',
                    'delivery',
                    'completed',
                    'cancelled',
                    'paid',
                    'partial',
                    'unpaid',
                    'overdue',
                ])
                ->first();

            $stats = [
                'total_orders' => (int) $row->total_orders,
                'orders_by_status' => [
                    'brief' => (int) $row->status_draft,
                    'quotation' => (int) $row->status_quotation,
                    'prototype' => (int) $row->status_prototype,
                    'production' => (int) $row->status_production,
                    'delivery' => (int) $row->status_delivery,
                    'completed' => (int) $row->status_completed,
                    'cancelled' => (int) $row->status_cancelled,
                ],
                'payment_stats' => [
                    'paid' => (int) $row->payment_paid,
                    'partial' => (int) $row->payment_partial,
                    'unpaid' => (int) $row->payment_unpaid,
                    'overdue' => (int) $row->payment_overdue,
                ],
                'first_order_date' => $row->first_order_date,
                'last_order_date' => $row->last_order_date,
            ];

            return response()->json([
                'data' => $stats,
                'message' => 'Statystyki klienta pobrane pomyślnie'
            ]);
        } catch (\Exception $e) {
            \Log::error('Customer statistics error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania statystyk',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pobierz listę klientów do selecta (uproszczona)
     */
    public function forSelect(): JsonResponse
    {
        try {
            $customers = Customer::where('is_active', true)
                ->orderBy('name')
                ->select('id', 'name', 'type', 'nip', 'email')
                ->get();

            return response()->json($customers);
        } catch (\Exception $e) {
            \Log::error('Customers forSelect error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Błąd podczas pobierania klientów',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
