import api from './api'
import type { User } from '@/types'

export const userService = {
    async getAll(params: any = {}): Promise<User[]> {
        const response = await api.get('/users', { params })
        const data = response.data

        // FIX: Obsługa paginacji Laravel. Jeśli przychodzi obiekt z polem 'data', wyciągnij tablicę.
        if (Array.isArray(data)) {
            return data
        } else if (data && Array.isArray(data.data)) {
            return data.data
        }

        return []
    },

    async getById(id: number | string): Promise<User> {
        const response = await api.get(`/users/${id}`)
        return response.data
    },

    async create(data: Partial<User>): Promise<User> {
        const response = await api.post('/users', data)
        return response.data
    },

    async update(id: number | string, data: Partial<User>): Promise<User> {
        const response = await api.put(`/users/${id}`, data)
        return response.data
    },

    async delete(id: number | string): Promise<void> {
        await api.delete(`/users/${id}`)
    },

    async toggleActive(id: number | string): Promise<User> {
        const response = await api.patch(`/users/${id}/toggle-active`)
        return response.data.user
    }
}
