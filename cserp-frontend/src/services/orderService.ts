import api from './api'
import type { Order, ApiResponse, Customer } from '@/types'

export const orderService = {
  // Orders
  async getAll(params: any = {}): Promise<Order[]> {
    const response = await api.get<ApiResponse<Order[]>>('/orders', { params })
    // Obsługa różnych formatów odpowiedzi API (z wrapperem data lub bez)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  async getById(id: number | string): Promise<Order> {
    const response = await api.get<ApiResponse<Order>>(`/orders/${id}`)
    const data = response.data
    // Jeśli API zwraca obiekt { data: Order }, wyciągnij go
    return (data as any).data || data
  },

  // NOWA METODA: Pobierz następny wolny numer
  async getNextNumber(): Promise<string> {
    const response = await api.get<{ next_number: string }>('/orders/next-number')
    return response.data.next_number
  },

  async create(data: Partial<Order>): Promise<Order> {
    const response = await api.post<ApiResponse<Order>>('/orders', data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async update(id: number | string, data: Partial<Order>): Promise<Order> {
    const response = await api.put<ApiResponse<Order>>(`/orders/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async delete(id: number | string): Promise<void> {
    await api.delete(`/orders/${id}`)
  },

  // Customers
  async getCustomers(): Promise<Customer[]> {
    const response = await api.get<ApiResponse<Customer[]>>('/customers')
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  // Product Lines
  async getVariants(orderId: number | string): Promise<any[]> {
    const response = await api.get(`/orders/${orderId}/variants`)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  // Quotations
  async getQuotations(orderId: number | string): Promise<any[]> {
    const response = await api.get(`/orders/${orderId}/quotations`)
    const data = response.data
    return Array.isArray(data) ? data : (data as any).data || []
  },

  async createQuotation(orderId: number | string, data: any): Promise<any> {
    const response = await api.post(`/orders/${orderId}/quotations`, data)
    return response.data
  },

  async approveQuotation(quotationId: number | string): Promise<any> {
    const response = await api.patch(`/quotations/${quotationId}/approve`)
    return response.data
  }
}