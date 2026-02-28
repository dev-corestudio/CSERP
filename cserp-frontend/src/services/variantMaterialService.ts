import api from './api'

export interface VariantMaterial {
    id: number
    variant_id: number
    assortment_id: number
    quantity: number
    unit: string
    unit_price: number
    total_cost: number
    status: string
    expected_delivery_date: string | null
    ordered_at: string | null
    received_at: string | null
    quantity_in_stock: number
    quantity_ordered: number
    supplier: string | null
    notes: string | null
    assortment?: {
        id: number
        name: string
        type: string
        unit: string
        unit_price: number
    }
    created_at: string
    updated_at: string
}

export interface MaterialsSummary {
    total: number
    not_ordered: number
    ordered: number
    partially_in_stock: number
    in_stock: number
    all_ready: boolean
}

export interface VariantMaterialsResponse {
    materials: VariantMaterial[]
    summary: MaterialsSummary
    total_cost: number
}

export const variantMaterialService = {
    async getAll(variantId: number | string): Promise<VariantMaterialsResponse> {
        const response = await api.get(`/variants/${variantId}/materials`)
        const data = response.data
        return (data as any).data || data
    },

    async create(variantId: number | string, payload: Partial<VariantMaterial>): Promise<VariantMaterial> {
        const response = await api.post(`/variants/${variantId}/materials`, payload)
        const data = response.data
        return (data as any).data || data
    },

    async getById(materialId: number | string): Promise<VariantMaterial> {
        const response = await api.get(`/variant-materials/${materialId}`)
        const data = response.data
        return (data as any).data || data
    },

    async update(materialId: number | string, payload: Partial<VariantMaterial>): Promise<VariantMaterial> {
        const response = await api.put(`/variant-materials/${materialId}`, payload)
        const data = response.data
        return (data as any).data || data
    },

    async delete(materialId: number | string): Promise<void> {
        await api.delete(`/variant-materials/${materialId}`)
    },

    async updateStatus(materialId: number | string, status: string, extra: Record<string, any> = {}): Promise<VariantMaterial> {
        const response = await api.patch(`/variant-materials/${materialId}/status`, { status, ...extra })
        const data = response.data
        return (data as any).data || data
    },

    async markAllOrdered(variantId: number | string, payload: Record<string, any> = {}): Promise<any> {
        const response = await api.post(`/variants/${variantId}/materials/mark-all-ordered`, payload)
        return response.data
    },

    async batchCreate(variantId: number | string, items: any[]): Promise<any> {
        const response = await api.post(`/variants/${variantId}/materials/batch`, { items })
        return response.data
    }
}