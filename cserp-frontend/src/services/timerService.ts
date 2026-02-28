import api from './api'

export const timerService = {

  /**
   * Rozpocznij pracę — tworzy lub wznawia zadanie (firstOrCreate po stronie backendu).
   *
   * POST /api/rcp/start
   *
   * @returns { current_task_id, task, message }
   */
  async startWork(variantId: number, workstationId: number, serviceId: number): Promise<any> {
    const response = await api.post('/rcp/start', {
      variant_id: variantId,
      workstation_id: workstationId,
      service_id: serviceId,
    })
    return response.data
  },

  /**
   * Sprawdź czy zalogowany pracownik ma aktywne lub wstrzymane zadanie.
   * Używane przy montowaniu WorkstationSelect.vue — przekierowanie jeśli trwa zadanie.
   *
   * GET /api/rcp/active-task
   *
   * @returns { has_active_task: boolean, task_id?: number }
   */
  async checkActiveTask(): Promise<{ has_active_task: boolean; task_id?: number }> {
    const response = await api.get('/rcp/active-task')
    return response.data
  },

  /**
   * Pobierz szczegóły zadania z obliczonym bieżącym czasem trwania.
   * Używane przy montowaniu Timer.vue (sync stanu po odświeżeniu strony).
   *
   * GET /api/rcp/tasks/{taskId}
   *
   * @returns task + current_duration_seconds (czas netto od START minus pauzy)
   */
  async getTaskDetails(taskId: number | string): Promise<any> {
    const response = await api.get(`/rcp/tasks/${taskId}`)
    return response.data
  },

  /**
   * Wstrzymaj pracę (przerwa).
   *
   * POST /api/rcp/pause/{taskId}
   */
  async pause(taskId: number | string): Promise<any> {
    const response = await api.post(`/rcp/pause/${taskId}`)
    return response.data
  },

  /**
   * Wznów pracę po przerwie.
   *
   * POST /api/rcp/resume/{taskId}
   */
  async resume(taskId: number | string): Promise<any> {
    const response = await api.post(`/rcp/resume/${taskId}`)
    return response.data
  },

  /**
   * Zakończ pracę i odbierz dane o wariancji czasu.
   *
   * POST /api/rcp/stop/{taskId}
   *
   * @returns { actual_hours, estimated_hours, variance_percent, time_variance,
   *            actual_cost, cost_variance, task, message }
   */
  async stop(taskId: number | string): Promise<any> {
    const response = await api.post(`/rcp/stop/${taskId}`)
    return response.data
  },
}