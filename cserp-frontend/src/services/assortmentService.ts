import api from './api'
import type { AssortmentItem } from '@/types'

export const assortmentService = {
  async getAll(params: any = {}): Promise<AssortmentItem[]> {
    const response = await api.get('/assortment', { params })
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  async getById(id: number | string): Promise<AssortmentItem> {
    const response = await api.get(`/assortment/${id}`)
    const data = response.data
    return (data as any).data || data
  },

  async create(data: Partial<AssortmentItem>): Promise<AssortmentItem> {
    const response = await api.post('/assortment', data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async update(id: number | string, data: Partial<AssortmentItem>): Promise<AssortmentItem> {
    const response = await api.put(`/assortment/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  async delete(id: number | string): Promise<void> {
    await api.delete(`/assortment/${id}`)
  },

  async getCategories(type: string | null = null): Promise<string[]> {
    const response = await api.get('/assortment-categories', {
      params: type ? { type } : {}
    })
    return response.data
  },

  async toggleActive(id: number | string): Promise<AssortmentItem> {
    const response = await api.patch(`/assortment/${id}/toggle-active`)
    return response.data
  },

  async getHistory(id: number | string): Promise<any[]> {
    const response = await api.get(`/assortment/${id}/history`)
    return response.data
  },

  async batchCheckOrCreate(items: any[], type: 'MATERIAL' | 'SERVICE' = 'MATERIAL'): Promise<any[]> {
    const response = await api.post('/assortment/batch-check', {
      items,
      type
    })
    return response.data
  }
}