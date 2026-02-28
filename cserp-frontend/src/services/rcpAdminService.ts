import api from './api'
export const rcpAdminService = {
    // Pobierz listę zadań (z paginacją i filtrami)
    async getTasks(params: any = {}): Promise<any> {
        const response = await api.get('/admin/rcp/tasks', { params })
        return response.data
    },
    // Aktualizuj zadanie (status, przypisanie, notatki, daty)
    async updateTask(taskId: number | string, data: any): Promise<any> {
        const response = await api.put(`/admin/rcp/tasks/${taskId}`, data)
        return response.data
    },
    // Pobierz logi czasu dla konkretnego zadania
    async getTimeLogs(taskId: number | string): Promise<any[]> {
        const response = await api.get(`/admin/rcp/tasks/${taskId}/logs`)
        return response.data
    },
    // Dodaj nowy wpis do logu (korekta ręczna)
    async addTimeLog(taskId: number | string, data: any): Promise<any> {
        const response = await api.post(`/admin/rcp/tasks/${taskId}/logs`, data)
        return response.data
    },
    // Edytuj wpis w logu
    async updateTimeLog(logId: number | string, data: any): Promise<any> {
        const response = await api.put(`/admin/rcp/logs/${logId}`, data)
        return response.data
    },
    // Usuń wpis z logu
    async deleteTimeLog(logId: number | string): Promise<any> {
        const response = await api.delete(`/admin/rcp/logs/${logId}`)
        return response.data
    }
}

export default rcpAdminService;