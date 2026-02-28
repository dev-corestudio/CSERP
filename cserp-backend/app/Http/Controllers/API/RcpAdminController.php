<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductionService;
use App\Models\ServiceTimeLog;
use App\Enums\EventType;
use App\Enums\ProductionStatus;
use App\Enums\WorkstationStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RcpAdminController extends Controller
{
    /**
     * Lista zadań produkcyjnych (z filtrowaniem)
     */
    public function index(Request $request)
    {
        $query = ProductionService::with([
            'workstation',
            'assignedWorker',
            'productionOrder.variant.order.customer'
        ]);

        // Filtrowanie Status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filtrowanie Pracownik
        if ($request->has('worker_id')) {
            $query->where('assigned_to_user_id', $request->worker_id);
        }

        // Filtrowanie Stanowisko
        if ($request->has('workstation_id')) {
            $query->where('workstation_id', $request->workstation_id);
        }

        // Filtrowanie Zakres Dat (Poprawiona logika przecięcia przedziałów)
// Szukamy zadań, które "trwały" w zadanym okresie [date_from, date_to]

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom && $dateTo) {
            $query->where(function ($q) use ($dateFrom, $dateTo) {
                // Zadanie zaczęło się przed końcem filtra ORAZ (skończyło się po początku filtra LUB wciąż trwa)
                $q->where('actual_start_date', '<=', $dateTo)
                    ->where(function ($subQ) use ($dateFrom) {
                        $subQ->where('actual_end_date', '>=', $dateFrom)
                            ->orWhereNull('actual_end_date');
                    });
            });
        } elseif ($dateFrom) {
            // Tylko Data OD: Zadanie skończyło się po tej dacie lub wciąż trwa
            $query->where(function ($q) use ($dateFrom) {
                $q->where('actual_end_date', '>=', $dateFrom)
                    ->orWhereNull('actual_end_date');
            });
        } elseif ($dateTo) {
            // Tylko Data DO: Zadanie zaczęło się przed tą datą
            $query->where('actual_start_date', '<=', $dateTo);
        }

        // Wyszukiwanie Tekstowe
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('service_name', 'like', "%{$search}%")
                    ->orWhereHas('productionOrder.variant', function ($lq) use ($search) {
                        $lq->where('name', 'like', "%{$search}%")
                            ->orWhere('variant_number', 'like', "%{$search}%")
                            ->orWhereHas('order', function ($oq) use ($search) {
                                $oq->where('order_number', 'like', "%{$search}%")
                                    ->orWhereHas('customer', function ($cq) use ($search) {
                                        $cq->where('name', 'like', "%{$search}%");
                                    });
                            });
                    });
            });
        }

        // Sortowanie
        $query->orderBy('updated_at', 'desc');

        return response()->json($query->paginate(20));
    }

    public function update(Request $request, ProductionService $task)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ProductionStatus::class)],
            'assigned_to_user_id' => 'nullable|exists:users,id',
            'actual_time_hours' => 'nullable|numeric|min:0',
            'actual_quantity' => 'nullable|numeric|min:0',
            'worker_notes' => 'nullable|string',
            'actual_start_date' => 'nullable|date',
            'actual_end_date' => 'nullable|date',
        ]);

        $wasCancelled = $task->status !== ProductionStatus::CANCELLED && $validated['status'] === ProductionStatus::CANCELLED->value;

        $task->fill($validated);

        if ($request->filled('actual_start_date') && $request->filled('actual_end_date') && !$request->filled('actual_time_hours')) {
            $start = Carbon::parse($validated['actual_start_date']);
            $end = Carbon::parse($validated['actual_end_date']);
            $diffHours = abs($start->diffInMinutes($end) / 60);
            $task->actual_time_hours = round($diffHours, 2);
        }

        $task->save();

        if ($wasCancelled) {
            $this->logCancellation($task);
        }

        if ($task->wasChanged('actual_time_hours') || $task->wasChanged('actual_quantity')) {
            $this->recalculateCosts($task);
        }

        return response()->json($task->load(['workstation', 'assignedWorker']));
    }

    public function getLogs(ProductionService $task)
    {
        $logs = $task->timeLogs()->with('user')->orderBy('event_timestamp', 'asc')->get();
        return response()->json($logs);
    }

    public function storeLog(Request $request, ProductionService $task)
    {
        $validated = $request->validate([
            'event_type' => ['required', Rule::enum(EventType::class)],
            'event_timestamp' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'elapsed_seconds' => 'nullable|integer|min:0',
        ]);

        $log = $task->timeLogs()->create([
            'event_type' => $validated['event_type'],
            'event_timestamp' => $validated['event_timestamp'],
            'user_id' => $validated['user_id'],
            'elapsed_seconds' => $validated['elapsed_seconds'] ?? 0,
        ]);

        return response()->json($log);
    }

    public function updateLog(Request $request, ServiceTimeLog $log)
    {
        $validated = $request->validate([
            'event_timestamp' => 'required|date',
            'event_type' => ['required', Rule::enum(EventType::class)],
            'elapsed_seconds' => 'nullable|integer|min:0',
        ]);

        $log->update($validated);
        return response()->json($log);
    }

    public function destroyLog(ServiceTimeLog $log)
    {
        $log->delete();
        return response()->json(['message' => 'Log usunięty']);
    }

    protected function recalculateCosts(ProductionService $task)
    {
        $cost = 0;
        if ($task->estimated_time_hours > 0) {
            $cost = $task->actual_time_hours * $task->unit_price;
        } else {
            $cost = $task->actual_quantity * $task->unit_price;
        }

        $task->update(['actual_cost' => $cost]);

        $order = $task->productionOrder;
        if ($order) {
            $totalActual = $order->services()->sum('actual_cost');
            $order->update(['total_actual_cost' => $totalActual]);
        }
    }

    protected function logCancellation(ProductionService $task)
    {
        $lastLog = $task->timeLogs()->latest('event_timestamp')->first();

        if ($lastLog && in_array($lastLog->event_type, [EventType::START, EventType::RESUME])) {
            $now = Carbon::now();
            $elapsed = 0;

            $task->timeLogs()->create([
                'event_type' => EventType::STOP,
                'event_timestamp' => $now,
                'user_id' => auth()->id() ?? $task->assigned_to_user_id,
                'elapsed_seconds' => $elapsed,
            ]);

            if ($task->workstation && $task->workstation->current_task_id === $task->id) {
                $task->workstation->update([
                    'status' => WorkstationStatus::IDLE,
                    'current_task_id' => null
                ]);
            }
        }
    }
}
