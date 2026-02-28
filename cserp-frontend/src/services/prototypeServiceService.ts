import api from './api'

export interface PrototypeServiceItem {
    id: number
    prototype_id: number
    step_number: number
    service_name: string
    workstation_id: number | null
    assigned_to_user_id: number | null
    estimated_quantity: number
    estimated_time_hours: number
    estimated_cost: number
    actual_quantity: number | null
    actual_time_hours: number | null
    actual_cost: number | null
    status: string
    actual_start_date: string | null
    actual_end_date: string | null
    total_pause_duration_seconds: number
    worker_notes: string | null
    workstation?: {
        id: number
        name: string
        type: string
    }
    assigned_worker?: {
        id: number
        name: string
    }
    created_at: string
    updated_at: string
}

export const prototypeServiceService = {
    async getAll(prototypeId: number | string): Promise<PrototypeServiceItem[]> {
        const response = await api.get(`/prototypes/${prototypeId}/services`)
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    async create(prototypeId: number | string, payload: Partial<PrototypeServiceItem>): Promise<PrototypeServiceItem> {
        const response = await api.post(`/prototypes/${prototypeId}/services`, payload)
        const data = response.data
        return (data as any).data || data
    },

    async update(serviceId: number | string, payload: Partial<PrototypeServiceItem>): Promise<PrototypeServiceItem> {
        const response = await api.put(`/prototype-services/${serviceId}`, payload)
        const data = response.data
        return (data as any).data || data
    },

    async delete(serviceId: number | string): Promise<void> {
        await api.delete(`/prototype-services/${serviceId}`)
    }
}