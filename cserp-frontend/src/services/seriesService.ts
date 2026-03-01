// src/services/seriesService.ts
// Serwis API do zarządzania seriami zamówień
import api from './api'

// ─── Typy odpowiedzi API ───────────────────────────────────────────────────────

/** Pojedyncza seria na liście */
export interface SeriesListItem {
    id: number
    full_project_number: string
    project_number: string
    series: string
    description: string | null
    overall_status: string
    payment_status: string
    priority: string
    created_at: string
    planned_delivery_date: string | null
    variants_count: number
    customer: {
        id: number
        name: string
    }
}

/** Wariant dostępny do kopiowania (dla selektora) */
export interface VariantForCopy {
    id: number
    variant_number: string
    name: string
    type: string
    status: string
    quantity: number
    has_quotation: boolean
    has_approved_quotation: boolean
    has_materials: boolean
    materials_count: number
    quotation_info: {
        version_number: number
        is_approved: boolean
        total_gross: number
    } | null
}

/** Konfiguracja kopiowania pojedynczego wariantu */
export interface VariantCopyConfig {
    source_variant_id: number
    copy_quotation: boolean
    copy_materials: boolean
}

/** Payload do tworzenia nowej serii */
export interface CreateSeriesPayload {
    description?: string
    planned_delivery_date?: string | null
    priority?: string
    copy_from_project_id?: number | null
    variants?: VariantCopyConfig[]
}

/** Odpowiedź z API po utworzeniu serii */
export interface CreateSeriesResponse {
    message: string
    data: any
    summary: {
        new_project_id: number
        new_full_project_number: string
        variants_created: number
        copied_from: string | null
        copy_details: {
            variants_requested: number
            with_quotation_copy: number
            with_materials_copy: number
        } | null
    }
}

// ─── Serwis ───────────────────────────────────────────────────────────────────

export const seriesService = {
    /**
     * Pobierz wszystkie serie dla numeru zamówienia (np. Z/0001)
     * Używa ID dowolnej serii z tej grupy
     */
    async getAllSeries(orderId: number | string): Promise<SeriesListItem[]> {
        const response = await api.get(`/projects/${orderId}/series`)
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    /**
     * Pobierz warianty z danej serii do selektora kopiowania
     */
    async getVariantsForSelector(orderId: number | string): Promise<VariantForCopy[]> {
        const response = await api.get(`/projects/${orderId}/series/variants`)
        const data = response.data
        if (Array.isArray(data)) return data
        return (data as any).data || []
    },

    /**
     * Utwórz nową serię - opcjonalnie z kopiowaniem wariantów
     */
    async createSeries(
        orderId: number | string,
        payload: CreateSeriesPayload
    ): Promise<CreateSeriesResponse> {
        const response = await api.post(`/projects/${orderId}/series/create`, payload)
        return response.data
    }
}

export default seriesService