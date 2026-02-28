// src/stores/timer.ts
// =============================================================================
// STORE: timer
// =============================================================================
// Zmiany względem oryginału:
// - formattedTime computed → formatDuration z useFormatters
//   (eliminuje trzecią kopię logiki padStart: była tu, w useTimer.ts i VariantDetail.vue)
// - Dodana metoda setElapsed() do resync z backendem (np. po refresh strony)
// =============================================================================

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { formatDuration } from '@/composables/useFormatters'

export const useTimerStore = defineStore('timer', () => {
  // ---------------------------------------------------------------------------
  // STATE
  // ---------------------------------------------------------------------------

  const activeTask = ref<any>(null)
  const elapsedSeconds = ref(0)
  const isRunning = ref(false)
  const isPaused = ref(false)

  let intervalId: ReturnType<typeof setInterval> | null = null

  // ---------------------------------------------------------------------------
  // COMPUTED
  // ---------------------------------------------------------------------------

  // PRZED: ręczna logika padStart (identyczna kopia jak w useTimer.ts i VariantDetail.vue)
  // PO: jedna funkcja z useFormatters
  const formattedTime = computed(() => formatDuration(elapsedSeconds.value))

  // ---------------------------------------------------------------------------
  // ACTIONS
  // ---------------------------------------------------------------------------

  // Wewnętrzna funkcja uruchamiająca interval (DRY względem startTimer/resumeTimer)
  const _startInterval = () => {
    if (intervalId) clearInterval(intervalId)
    intervalId = setInterval(() => {
      elapsedSeconds.value++
    }, 1000)
  }

  /** Rozpocznij timer dla zadania */
  function startTimer(task: any): void {
    activeTask.value = task
    isRunning.value = true
    isPaused.value = false
    elapsedSeconds.value = 0

    _startInterval()
  }

  /** Wstrzymaj timer */
  function pauseTimer(): void {
    isPaused.value = true

    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  /** Wznów timer po przerwie */
  function resumeTimer(): void {
    isPaused.value = false
    _startInterval()
  }

  /** Zatrzymaj timer i wyczyść stan */
  function stopTimer(): void {
    isRunning.value = false
    isPaused.value = false
    activeTask.value = null
    elapsedSeconds.value = 0

    if (intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  /**
   * Ustaw czas z zewnątrz (np. po powrocie na stronę po refresh).
   * Backend może zwrócić aktualny elapsed_seconds — użyj tej metody
   * żeby zsynchronizować wyświetlany czas.
   */
  function setElapsed(seconds: number): void {
    elapsedSeconds.value = seconds
  }

  return {
    // State
    activeTask,
    elapsedSeconds,
    isRunning,
    isPaused,

    // Computed
    formattedTime,

    // Actions
    startTimer,
    pauseTimer,
    resumeTimer,
    stopTimer,
    setElapsed,
  }
})