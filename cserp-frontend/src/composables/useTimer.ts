import { ref, computed, onUnmounted } from 'vue'
import { timerService } from '@/services/timerService'
import { formatDuration, useFormatters } from '@/composables/useFormatters'

// -----------------------------------------------------------------------------
// TYPY
// -----------------------------------------------------------------------------

export interface TimerVarianceResult {
  actual_hours: number
  estimated_hours: number
  variance_percent: number
  time_variance: number
  actual_cost: number
  cost_variance: number
}

export interface TimerTask {
  estimated_time_hours: number
  [key: string]: any
}

// -----------------------------------------------------------------------------
// IMPLEMENTACJA
// -----------------------------------------------------------------------------

/**
 * Composable do sterowania timerem dla już uruchomionego zadania.
 *
 * Obsługuje: pause / resume / stop + lokalny interval (co 1 sekundę).
 *
 * START nowego zadania jest poza tym composable — realizuje go WorkstationSelect.vue
 * przez timerService.startWork(variantId, workstationId, serviceId).
 *
 * Typowy przepływ Timer.vue:
 *   onMounted → timerService.getTaskDetails(taskId)
 *             → initFromTaskData(task, current_duration_seconds)
 *             → startInterval()
 *
 *   Kliknięcie STOP → stop() → timerService.stop(taskId) → wyniki wariancji
 */
export function useTimer(taskId: number | { value: number }) {

  // Stan
  const isRunning = ref(false)
  const isPaused = ref(false)
  const elapsedSeconds = ref(0)
  const estimatedSeconds = ref(0)
  const taskData = ref<TimerTask | null>(null)
  const variance = ref<TimerVarianceResult | null>(null)

  let intervalId: ReturnType<typeof setInterval> | null = null

  const { varianceColor } = useFormatters()

  // ---
  // Pomocniki
  // ---

  const getTaskId = (): number => {
    return typeof taskId === 'object' && 'value' in taskId ? taskId.value : taskId
  }

  const startInterval = () => {
    if (intervalId) clearInterval(intervalId)
    intervalId = setInterval(() => {
      elapsedSeconds.value++
    }, 1000)
  }

  // ---
  // Computed
  // ---

  // Czas sformatowany HH:MM:SS
  const formattedTime = computed(() => formatDuration(elapsedSeconds.value))

  // Szacowany czas sformatowany HH:MM:SS
  const formattedEstimatedTime = computed(() => formatDuration(estimatedSeconds.value))

  // Procent postępu względem szacowanego czasu (max 100%)
  const progressPercent = computed(() => {
    if (estimatedSeconds.value === 0) return 0
    const percent = (elapsedSeconds.value / estimatedSeconds.value) * 100
    return Math.min(Math.round(percent), 100)
  })

  // Kolor paska postępu (zmienia się gdy zbliżamy się do limitu i przekraczamy)
  const progressColor = computed(() => {
    if (progressPercent.value < 50) return 'success'
    if (progressPercent.value < 90) return 'warning'
    if (progressPercent.value < 100) return 'orange'
    return 'error'
  })

  // Kolor wariancji po zakończeniu (delegacja do useFormatters)
  const varianceColorComputed = computed(() => {
    return varianceColor(variance.value?.variance_percent ?? null)
  })

  // ---
  // Akcje
  // ---

  /**
   * Wstrzymaj zadanie (przerwa).
   * Zatrzymuje lokalny interval.
   */
  const pause = async (): Promise<void> => {
    try {
      await timerService.pause(getTaskId())
      isPaused.value = true
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }
    } catch (error) {
      console.error('Błąd podczas pauzy timera:', error)
      throw error
    }
  }

  /**
   * Wznów zadanie po przerwie.
   * Wznawia lokalny interval.
   */
  const resume = async (): Promise<void> => {
    try {
      await timerService.resume(getTaskId())
      isPaused.value = false
      startInterval()
    } catch (error) {
      console.error('Błąd podczas wznowienia timera:', error)
      throw error
    }
  }

  /**
   * Zakończ zadanie i pobierz wyniki wariancji.
   * Zatrzymuje lokalny interval.
   */
  const stop = async (): Promise<TimerVarianceResult> => {
    try {
      const result = await timerService.stop(getTaskId())
      isRunning.value = false
      isPaused.value = false
      variance.value = result
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }
      return result
    } catch (error) {
      console.error('Błąd podczas stop timera:', error)
      throw error
    }
  }

  /**
   * Zainicjalizuj composable danymi z backendu po timerService.getTaskDetails().
   *
   * Ustawia elapsed zsynchronizowany z backendem i startuje lokalny interval.
   * Wywołuj z onMounted Timer.vue.
   */
  const initFromTaskData = (task: TimerTask, currentDurationSeconds: number) => {
    taskData.value = task
    estimatedSeconds.value = Math.floor(task.estimated_time_hours * 3600)
    elapsedSeconds.value = currentDurationSeconds
    isRunning.value = true
    isPaused.value = false
    startInterval()
  }

  // Cleanup przy odmontowaniu komponentu
  onUnmounted(() => {
    if (intervalId) {
      clearInterval(intervalId)
    }
  })

  return {
    // Stan
    isRunning,
    isPaused,
    elapsedSeconds,
    estimatedSeconds,
    taskData,
    variance,

    // Computed
    formattedTime,
    formattedEstimatedTime,
    progressPercent,
    progressColor,
    varianceColorComputed,

    // Akcje
    pause,
    resume,
    stop,
    initFromTaskData,
  }
}