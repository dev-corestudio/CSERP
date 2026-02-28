import api from './api'
import type { Workstation, User } from '@/types'

export const workstationService = {
    async getAll(): Promise<Workstation[]> {
        const response = await api.get('/workstations')
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    async getById(id: number | string): Promise<Workstation> {
        const response = await api.get(`/workstations/${id}`)
        const data = response.data
        return (data as any).data || data
    },

    async create(data: Partial<Workstation>): Promise<Workstation> {
        const response = await api.post('/workstations', data)
        const resData = response.data
        return (resData as any).data || resData
    },

    async update(id: number | string, data: Partial<Workstation>): Promise<Workstation> {
        const response = await api.put(`/workstations/${id}`, data)
        const resData = response.data
        return (resData as any).data || resData
    },

    async delete(id: number | string): Promise<void> {
        await api.delete(`/workstations/${id}`)
    },

    async getWorkers(): Promise<User[]> {
        const response = await api.get('/workers-list')
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    // --- USŁUGI ---

    async getAssignedServices(workstationId: number | string): Promise<any[]> {
        const response = await api.get(`/workstations/${workstationId}/services`)
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    async attachService(workstationId: number | string, assortmentId: number | string): Promise<any> {
        const response = await api.post(`/workstations/${workstationId}/services`, {
            assortment_id: assortmentId
        })
        return response.data
    },

    async detachService(workstationId: number | string, assortmentId: number | string): Promise<any> {
        const response = await api.delete(`/workstations/${workstationId}/services/${assortmentId}`)
        return response.data
    }
}