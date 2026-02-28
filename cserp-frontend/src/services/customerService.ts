import api from './api'
import type { Customer, ApiResponse } from '@/types'

export const customerService = {
  async getAll(params: any = {}): Promise<Customer[]> {
    const response = await api.get < ApiResponse < Customer[] >> ('/customers', { params })
    const data = response.data
    // Obsługa różnych formatów odpowiedzi (wrapper 'data' lub bezpośrednia tablica)
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  async getForSelect(): Promise<Customer[]> {
    const response = await api.get < ApiResponse < Customer[] >> ('/customers/for-select')
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  async getById(id: number | string): Promise<Customer> {
    const response = await api.get < ApiResponse < Customer >> (`/customers/${id}`)
    const data = response.data
    return (data as any).data || data
  },

  async create(data: Partial<Customer>): Promise<Customer> {
    const response = await api.post < ApiResponse < Customer >> ('/customers', data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async update(id: number | string, data: Partial<Customer>): Promise<Customer> {
    const response = await api.put < ApiResponse < Customer >> (`/customers/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async delete(id: number | string): Promise<void> {
    await api.delete(`/customers/${id}`)
  },

  async toggleActive(id: number | string): Promise<Customer> {
    const response = await api.patch < ApiResponse < Customer >> (`/customers/${id}/toggle-active`)
    const resData = response.data
    return (resData as any).data || resData
  },

  async getStatistics(id: number | string): Promise<any> {
    const response = await api.get(`/customers/${id}/statistics`)
    return response.data
  }
}

export default customerService