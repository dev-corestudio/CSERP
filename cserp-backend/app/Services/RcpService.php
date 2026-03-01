<?php

namespace App\Services;

use App\Models\ProductionService;
use App\Models\ServiceTimeLog;
use App\Models\Workstation;
use App\Models\Variant;
use App\Models\Assortment;
use App\Enums\ProductionStatus;
use App\Enums\WorkstationStatus;
use App\Enums\EventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RcpService
{
    // =========================================================================
    // HELPER — bezpieczne pobranie Unix timestamp z logu
    // =========================================================================

    /**
     * Zwraca Unix timestamp (int) z logu ServiceTimeLog.
     *
     * Używamy getRawOriginal() + strtotime() zamiast ->event_timestamp (Carbon cast).
     * Cast 'datetime' może tworzyć Carbon bez jawnej strefy czasowej, co powoduje
     * błędne diffInSeconds gdy APP_TIMEZONE != 'UTC'.
     *
     * MySQL timestamp column przechowuje czas w strefie sesji bazy danych.
     * Dodajemy ten sam suffix ' UTC' do obu timestampów (start i now) — offset
     * ewentualnie jest taki sam dla obu, więc RÓŻNICA jest zawsze poprawna.
     */
    private function logToUnixTimestamp(ServiceTimeLog $log): int
    {
        $raw = $log->getRawOriginal('event_timestamp'); // np. "2026-02-23 21:07:23"
        return (int) strtotime($raw . ' UTC');
    }

    // =========================================================================
    // START
    // =========================================================================

    /**
     * Rozpocznij pracę na stanowisku.
     *
     * Logika wyszukiwania zadania:
     *   PLANNED lub PAUSED → wznów (nie twórz nowego)
     *   COMPLETED lub brak  → utwórz nowy rekord
     *
     * PLANNED jest używany jako status "nowe, nierozpoczęte zadanie z wyceny".
     * PAUSED  oznacza zadanie które było uruchomione i zostało wstrzymane.
     * COMPLETED jest celowo pomijany — ponowny start = nowy wpis w bazie.
     */
    public function startWork(int $variantId, int $workstationId, int $serviceId, int $userId): array
    {
        return DB::transaction(function () use ($variantId, $workstationId, $serviceId, $userId) {
            // lockForUpdate() — zapobiega race condition gdy dwóch pracowników
            // jednocześnie próbuje zająć to samo stanowisko
            $workstation = Workstation::lockForUpdate()->findOrFail($workstationId);
            $variant = Variant::findOrFail($variantId);
            $service = Assortment::findOrFail($serviceId);

            // Sprawdź dostępność stanowiska
            if ($workstation->status !== WorkstationStatus::IDLE) {
                $currentTask = $workstation->currentTask;

                // Rzutowanie do int — Eloquent może zwrócić string z bazy
                $isSameContext = $currentTask
                    && (int) $currentTask->production_order_id === (int) optional($variant->productionOrder)->id
                    && $currentTask->service_name === $service->name
                    && (int) $currentTask->assigned_to_user_id === $userId;

                if ($isSameContext) {
                    // Idempotentne — podwójne kliknięcie, zwróć bez zmian
                    return [
                        'task' => $currentTask->load(['workstation', 'assignedWorker']),
                        'message' => 'Zadanie już trwa',
                        'current_task_id' => $currentTask->id,
                    ];
                }

                throw new \Exception('Stanowisko jest zajęte przez inne zadanie');
            }

            $productionOrder = $variant->productionOrder;
            if (!$productionOrder) {
                throw new \Exception('Brak zlecenia produkcyjnego dla tego wariantu');
            }

            // Szukaj tylko PLANNED lub PAUSED — COMPLETED celowo wykluczone
            $task = ProductionService::where('production_order_id', $productionOrder->id)
                ->where('service_name', $service->name)
                ->where('workstation_id', $workstationId)
                ->whereIn('status', [ProductionStatus::PLANNED, ProductionStatus::PAUSED])
                ->latest('id')
                ->first();

            if (!$task) {
                // Nowy przebieg — każdy start po COMPLETED tworzy oddzielny rekord
                $task = ProductionService::create([
                    'production_order_id' => $productionOrder->id,
                    'step_number' => $productionOrder->services()->count() + 1,
                    'service_name' => $service->name,
                    'workstation_id' => $workstationId,
                    'assigned_to_user_id' => $userId,
                    'estimated_quantity' => $service->default_quantity ?? 1,
                    'estimated_time_hours' => $service->estimated_time ?? 1,
                    'unit_price' => $service->default_price,
                    'estimated_cost' => ($service->default_quantity ?? 1) * $service->default_price,
                    'status' => ProductionStatus::PLANNED,
                    'actual_start_date' => now(),
                ]);
            }

            $task->update([
                'status' => ProductionStatus::IN_PROGRESS,
                // Zachowaj oryginalną datę startu przy wznowieniu po PAUSED
                'actual_start_date' => $task->actual_start_date ?? now(),
                'assigned_to_user_id' => $userId,
            ]);

            $workstation->update([
                'status' => WorkstationStatus::ACTIVE,
                'current_task_id' => $task->id,
            ]);

            ServiceTimeLog::create([
                'production_service_id' => $task->id,
                'user_id' => $userId,
                'event_type' => EventType::START,
                'event_timestamp' => now(),
                'elapsed_seconds' => 0,
            ]);

            Log::info("RCP: Zadanie #{$task->id} ({$service->name}) — START, użytkownik #{$userId}");

            return [
                'task' => $task->load(['workstation', 'assignedWorker']),
                'message' => 'Praca rozpoczęta',
                'current_task_id' => $task->id,
            ];
        });
    }

    // =========================================================================
    // STOP
    // =========================================================================

    /**
     * Zakończ pracę i oblicz wariancję czasu.
     *
     * Wzór kosztu: actual_cost = actual_time_hours × unit_price
     *
     * Obliczanie czasu przez Unix timestamps (time() i strtotime(raw . ' UTC')):
     *   Eliminuje wszelkie problemy ze strefami czasowymi Carbon/Eloquent.
     *   Różnica Unix timestamps jest zawsze poprawna nawet przy niezgodności TZ,
     *   ponieważ ten sam offset jest dodany do obu timestamp'ów.
     */
    public function stopWork(int $taskId): array
    {
        return DB::transaction(function () use ($taskId) {
            // lockForUpdate() — zapobiega zdublowanemu STOP przy podwójnym kliknięciu
            $task = ProductionService::lockForUpdate()->findOrFail($taskId);

            if (!in_array($task->status, [ProductionStatus::IN_PROGRESS, ProductionStatus::PAUSED])) {
                throw new \Exception('Zadanie nie jest aktywne ani wstrzymane');
            }

            // Ostatni log START
            $startLog = ServiceTimeLog::where('production_service_id', $taskId)
                ->where('event_type', EventType::START)
                ->latest('event_timestamp')
                ->first();

            if (!$startLog) {
                throw new \Exception('Brak logu startu — nie można obliczyć czasu');
            }

            $startUnix = $this->logToUnixTimestamp($startLog);
            $nowUnix = time();
            $totalElapsed = max(0, $nowUnix - $startUnix);

            // Czas zakończonych pauz po ostatnim START
            $totalPauseSeconds = $this->calculatePauseSeconds($taskId, $startUnix);

            // Jeśli wciąż w pauzie — dolicz trwającą pauzę
            if ($task->status === ProductionStatus::PAUSED) {
                $openPause = ServiceTimeLog::where('production_service_id', $taskId)
                    ->where('event_type', EventType::PAUSE)
                    ->whereNull('elapsed_seconds')
                    ->latest('event_timestamp')
                    ->first();

                if ($openPause) {
                    $totalPauseSeconds += max(0, $nowUnix - $this->logToUnixTimestamp($openPause));
                }
            }

            // Czas netto (bez przerw)
            $actualSeconds = max(0, $totalElapsed - $totalPauseSeconds);
            $actualHours = round($actualSeconds / 3600, 2);

            // Wariancja czasu
            $estimatedHours = (float) $task->estimated_time_hours;
            $timeVariance = $actualHours - $estimatedHours;
            $variancePercent = $estimatedHours > 0
                ? ($timeVariance / $estimatedHours) * 100
                : 0;

            // Koszt rzeczywisty = czas × stawka
            $actualCost = round($actualHours * (float) $task->unit_price, 2);
            $costVariance = round($actualCost - (float) $task->estimated_cost, 2);

            $task->update([
                'status' => ProductionStatus::COMPLETED,
                'actual_end_date' => now(),
                'actual_time_hours' => $actualHours,
                'actual_cost' => $actualCost,
                'time_variance_hours' => round($timeVariance, 2),
                'cost_variance' => $costVariance,
                'variance_percent' => round($variancePercent, 2),
                'total_pause_duration_seconds' => $totalPauseSeconds,
            ]);

            if ($task->workstation) {
                $task->workstation->update([
                    'status' => WorkstationStatus::IDLE,
                    'current_task_id' => null,
                ]);
            }

            ServiceTimeLog::create([
                'production_service_id' => $task->id,
                'user_id' => $task->assigned_to_user_id,
                'event_type' => EventType::STOP,
                'event_timestamp' => now(),
                'elapsed_seconds' => $actualSeconds,
            ]);

            // Zaktualizuj łączny koszt rzeczywisty zlecenia
            $productionOrder = $task->productionOrder;
            if ($productionOrder) {
                $productionOrder->update([
                    'total_actual_cost' => $productionOrder->services()
                        ->whereNotNull('actual_cost')
                        ->sum('actual_cost'),
                ]);
            }

            Log::info(
                "RCP: Zadanie #{$task->id} — STOP. " .
                "Czas: {$actualHours}h (est: {$estimatedHours}h), " .
                "koszt: {$actualCost} zł, wariancja: " . round($variancePercent, 1) . '%'
            );

            return [
                'task' => $task,
                'actual_hours' => $actualHours,
                'estimated_hours' => $estimatedHours,
                'time_variance' => round($timeVariance, 2),
                'variance_percent' => round($variancePercent, 2),
                'actual_cost' => $actualCost,
                'cost_variance' => $costVariance,
                'message' => 'Praca zakończona',
            ];
        });
    }

    // =========================================================================
    // PAUSE
    // =========================================================================

    /**
     * Wstrzymaj pracę.
     * Stanowisko dostaje status PAUSED — zablokowane dla innych, ale nie pracuje.
     */
    public function pauseWork(int $taskId): array
    {
        return DB::transaction(function () use ($taskId) {
            $task = ProductionService::lockForUpdate()->findOrFail($taskId);

            if ($task->status !== ProductionStatus::IN_PROGRESS) {
                throw new \Exception('Można wstrzymać tylko zadanie w toku');
            }

            $task->update(['status' => ProductionStatus::PAUSED]);

            if ($task->workstation) {
                $task->workstation->update(['status' => WorkstationStatus::PAUSED]);
            }

            // elapsed_seconds = null — czas pauzy obliczany przy RESUME przez parowanie
            ServiceTimeLog::create([
                'production_service_id' => $task->id,
                'user_id' => $task->assigned_to_user_id,
                'event_type' => EventType::PAUSE,
                'event_timestamp' => now(),
                'elapsed_seconds' => null,
            ]);

            return ['task' => $task, 'message' => 'Praca wstrzymana'];
        });
    }

    // =========================================================================
    // RESUME
    // =========================================================================

    /**
     * Wznów pracę po przerwie.
     * Zamyka ostatnią otwartą pauzę — zapisuje jej czas trwania.
     */
    public function resumeWork(int $taskId): array
    {
        return DB::transaction(function () use ($taskId) {
            $task = ProductionService::lockForUpdate()->findOrFail($taskId);

            if ($task->status !== ProductionStatus::PAUSED) {
                throw new \Exception('Można wznowić tylko wstrzymane zadanie');
            }

            $openPause = ServiceTimeLog::where('production_service_id', $taskId)
                ->where('event_type', EventType::PAUSE)
                ->whereNull('elapsed_seconds')
                ->latest('event_timestamp')
                ->first();

            if (!$openPause) {
                throw new \Exception('Brak logu pauzy — nie można wyliczyć czasu przerwy');
            }

            $pauseDuration = max(0, time() - $this->logToUnixTimestamp($openPause));
            $openPause->update(['elapsed_seconds' => $pauseDuration]);

            $task->update(['status' => ProductionStatus::IN_PROGRESS]);

            if ($task->workstation) {
                $task->workstation->update(['status' => WorkstationStatus::ACTIVE]);
            }

            ServiceTimeLog::create([
                'production_service_id' => $task->id,
                'user_id' => $task->assigned_to_user_id,
                'event_type' => EventType::RESUME,
                'event_timestamp' => now(),
                'elapsed_seconds' => null,
            ]);

            return ['task' => $task, 'message' => 'Praca wznowiona'];
        });
    }

    // =========================================================================
    // PRYWATNE
    // =========================================================================

    /**
     * Oblicza łączny czas zakończonych pauz (pary PAUSE→RESUME) po danym Unix timestamp.
     *
     * Algorytm: sortuje PAUSE i RESUME chronologicznie, paruje je indeksem (i-ta PAUSE → i-ty RESUME).
     * Niezamknięte pauzy (bez RESUME) są pomijane — obsługuje je wywołujący (stopWork).
     */
    private function calculatePauseSeconds(int $taskId, int $afterUnix): int
    {
        // gmdate() zwraca UTC string — zgodny z tym co przechowuje MySQL timestamp
        $afterStr = gmdate('Y-m-d H:i:s', $afterUnix);

        $pauseLogs = ServiceTimeLog::where('production_service_id', $taskId)
            ->where('event_type', EventType::PAUSE)
            ->where('event_timestamp', '>', $afterStr)
            ->orderBy('event_timestamp')
            ->get();

        $resumeLogs = ServiceTimeLog::where('production_service_id', $taskId)
            ->where('event_type', EventType::RESUME)
            ->where('event_timestamp', '>', $afterStr)
            ->orderBy('event_timestamp')
            ->get();

        $total = 0;
        foreach ($pauseLogs as $i => $pause) {
            $resume = $resumeLogs->get($i);
            if ($resume) {
                $total += max(0, $this->logToUnixTimestamp($resume) - $this->logToUnixTimestamp($pause));
            }
        }

        return $total;
    }
}
