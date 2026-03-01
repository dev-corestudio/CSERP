import api from './api'
import type { Project, ApiResponse, Customer } from '@/types'

export const projectService = {
  // Projects
  async getAll(params: any = {}): Promise<Project[]> {
    const response = await api.get<ApiResponse<Project[]>>('/projects', { params })
    // Obsługa różnych formatów odpowiedzi API (z wrapperem data lub bez)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  async getById(id: number | string): Promise<Project> {
    const response = await api.get<ApiResponse<Project>>(`/projects/${id}`)
    const data = response.data
    // Jeśli API zwraca obiekt { data: Project }, wyciągnij go
    return (data as any).data || data
  },

  // NOWA METODA: Pobierz następny wolny numer
  async getNextNumber(): Promise<string> {
    const response = await api.get<{ next_number: string }>('/projects/next-number')
    return response.data.next_number
  },

  async create(data: Partial<Project>): Promise<Project> {
    const response = await api.post<ApiResponse<Project>>('/projects', data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async update(id: number | string, data: Partial<Project>): Promise<Project> {
    const response = await api.put<ApiResponse<Project>>(`/projects/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async delete(id: number | string): Promise<void> {
    await api.delete(`/projects/${id}`)
  },

  // Customers
  async getCustomers(): Promise<Customer[]> {
    const response = await api.get<ApiResponse<Customer[]>>('/customers')
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  // Variants
  async getVariants(projectId: number | string): Promise<any[]> {
    const response = await api.get(`/projects/${projectId}/variants`)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  // Quotations
  async getQuotations(projectId: number | string): Promise<any[]> {
    const response = await api.get(`/projects/${projectId}/quotations`)
    const data = response.data
    return Array.isArray(data) ? data : (data as any).data || []
  },

  async createQuotation(projectId: number | string, data: any): Promise<any> {
    const response = await api.post(`/projects/${projectId}/quotations`, data)
    return response.data
  },

  async approveQuotation(quotationId: number | string): Promise<any> {
    const response = await api.patch(`/quotations/${quotationId}/approve`)
    return response.data
  }
}
