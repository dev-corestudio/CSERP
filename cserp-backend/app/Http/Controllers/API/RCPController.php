<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\RcpService;
use App\Models\ProductionService;
use App\Models\ServiceTimeLog;
use App\Models\Variant;
use App\Enums\ProductionStatus;
use App\Enums\EventType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class RcpController extends Controller
{
    public function __construct(protected RcpService $rcpService)
    {
    }

    // =========================================================================
    // PANEL PRACOWNIKA — pomocnicze endpointy
    // =========================================================================

    /**
     * Sprawdź czy zalogowany użytkownik ma aktywne lub wstrzymane zadanie.
     *
     * GET /api/rcp/active-task
     */
    public function checkActiveTask(Request $request): JsonResponse
    {
        $task = ProductionService::where('assigned_to_user_id', $request->user()->id)
            ->whereIn('status', [ProductionStatus::IN_PROGRESS, ProductionStatus::PAUSED])
            ->latest('updated_at')
            ->first();

        if ($task) {
            return response()->json([
                'has_active_task' => true,
                'task_id' => $task->id,
            ]);
        }

        return response()->json(['has_active_task' => false]);
    }

    /**
     * Szczegóły zadania z obliczonym bieżącym czasem (do widoku timera).
     *
     * GET /api/rcp/tasks/{task}
     */
    public function getTaskDetails(ProductionService $task): JsonResponse
    {
        $task->load(['workstation', 'assignedWorker', 'productionOrder.variant.order.customer']);

        $elapsedSeconds = 0;

        $startLog = ServiceTimeLog::where('production_service_id', $task->id)
            ->where('event_type', EventType::START)
            ->latest('event_timestamp')
            ->first();

        if ($startLog) {
            $totalDuration = Carbon::parse($startLog->event_timestamp)->diffInSeconds(now());

            // Suma zakończonych pauz (z zapisanym elapsed_seconds)
            $totalPause = ServiceTimeLog::where('production_service_id', $task->id)
                ->where('event_type', EventType::PAUSE)
                ->whereNotNull('elapsed_seconds')
                ->sum('elapsed_seconds');

            // Jeśli zadanie jest teraz w pauzie — dolicz trwającą pauzę
            if ($task->status === ProductionStatus::PAUSED) {
                $openPauseLog = ServiceTimeLog::where('production_service_id', $task->id)
                    ->where('event_type', EventType::PAUSE)
                    ->whereNull('elapsed_seconds')
                    ->latest('event_timestamp')
                    ->first();

                if ($openPauseLog) {
                    $totalPause += Carbon::parse($openPauseLog->event_timestamp)->diffInSeconds(now());
                }
            }

            $elapsedSeconds = max(0, $totalDuration - $totalPause);
        }

        $taskData = $task->toArray();
        $taskData['current_duration_seconds'] = $elapsedSeconds;

        return response()->json($taskData);
    }

    /**
     * Lista wariantów dostępnych do produkcji (status PRODUCTION, z aktywnym zleceniem).
     *
     * GET /api/rcp/variants
     */
    public function getAvailableVariants(): JsonResponse
    {
        $variants = Variant::with(['order.customer'])
            ->where('status', 'PRODUCTION')
            // ->whereHas('productionOrder')
            ->get()
            ->map(fn($variant) => [
                'id' => $variant->id,
                'order_id' => $variant->order_id,
                'order_number' => $variant->order->order_number,
                'series' => $variant->order->series,
                'full_order_number' => $variant->order->full_order_number,
                'variant_number' => $variant->variant_number,
                'name' => $variant->name,
                'quantity' => $variant->quantity,
                'customer_name' => $variant->order->customer->name ?? 'Brak klienta',
                'priority' => $variant->order->priority ?? 'NORMAL',
                'status' => $variant->status,
            ]);

        return response()->json($variants);
    }

    // =========================================================================
    // TIMER — główne operacje
    // =========================================================================

    /**
     * Rozpocznij pracę (tworzy lub wznawia zadanie przez firstOrCreate).
     *
     * POST /api/rcp/start
     * Body: { variant_id, workstation_id, service_id }
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => 'required|integer|exists:variants,id',
            'workstation_id' => 'required|integer|exists:workstations,id',
            'service_id' => 'required|integer|exists:assortment,id',
        ]);

        try {
            $result = $this->rcpService->startWork(
                $validated['variant_id'],
                $validated['workstation_id'],
                $validated['service_id'],
                $request->user()->id
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Wstrzymaj pracę (pauza).
     *
     * POST /api/rcp/pause/{task}
     */
    public function pause(ProductionService $task): JsonResponse
    {
        try {
            $result = $this->rcpService->pauseWork($task->id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Wznów pracę po przerwie.
     *
     * POST /api/rcp/resume/{task}
     */
    public function resume(ProductionService $task): JsonResponse
    {
        try {
            $result = $this->rcpService->resumeWork($task->id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Zakończ pracę i oblicz wariancję.
     *
     * POST /api/rcp/stop/{task}
     */
    public function stop(ProductionService $task): JsonResponse
    {
        try {
            $result = $this->rcpService->stopWork($task->id);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
