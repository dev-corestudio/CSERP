import api from './api'

export interface AssortmentItem {
  id: number
  name: string
  type: string
  unit: string
  default_price: number
}

export type ExportMaterialsMode = 'skip' | 'merge' | 'replace'

export interface ExportMaterialsResult {
  message: string
  stats: {
    exported: number
    skipped: number
    merged: number
    replaced: number
  }
}

export const quotationService = {
  /**
   * Pobierz wszystkie wyceny dla wariantu
   */
  async getAll(variantId: number | string): Promise<any[]> {
    const response = await api.get(`/variants/${variantId}/quotations`)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  /**
   * Pobierz szczegóły wyceny
   */
  async getById(id: number | string): Promise<any> {
    const response = await api.get(`/quotations/${id}`)
    const data = response.data
    return (data as any).data || data
  },

  /**
   * Utwórz nową wycenę dla wariantu
   */
  async create(variantId: number | string, data: any): Promise<any> {
    const response = await api.post(`/variants/${variantId}/quotations`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Zaktualizuj wycenę (tylko niezatwierdzone)
   */
  async update(id: number | string, data: any): Promise<any> {
    const response = await api.put(`/quotations/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Zatwierdź wycenę
   */
  async approve(id: number | string): Promise<any> {
    const response = await api.patch(`/quotations/${id}/approve`)
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Usuń wycenę
   */
  async delete(id: number | string): Promise<void> {
    await api.delete(`/quotations/${id}`)
  },

  /**
   * Pobierz PDF wyceny jako blob
   */
  async downloadPDF(id: number | string): Promise<any> {
    const response = await api.get(`/quotations/${id}/pdf`, {
      responseType: 'blob'
    })
    return response.data
  },

  /**
   * Duplikuj wycenę jako nową wersję w tym samym wariancie
   */
  async duplicate(quotationId: number | string): Promise<any> {
    const response = await api.post(`/quotations/${quotationId}/duplicate`)
    return response.data
  },

  /**
   * Eksportuj materiały z zatwierdzonej wyceny do listy materiałów wariantu.
   *
   * @param quotationId - ID wyceny (musi być zatwierdzona)
   * @param mode        - Tryb obsługi duplikatów:
   *                       'skip'    – pomija istniejące (domyślny)
   *                       'merge'   – sumuje ilości z istniejącymi
   *                       'replace' – nadpisuje istniejące
   */
  async exportMaterials(
    quotationId: number | string,
    mode: 'skip' | 'merge' | 'replace' = 'skip'
  ): Promise<ExportMaterialsResult> {
    const response = await api.post(
      `/quotations/${quotationId}/export-materials?mode=${mode}`
    )
    return response.data
  },

  /**
   * Pobierz listę materiałów z katalogu (do formularzy wycen)
   */
  async getMaterials(): Promise<AssortmentItem[]> {
    const response = await api.get('/assortment-materials')
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  /**
   * Pobierz listę usług z katalogu (do formularzy wycen)
   */
  async getServices(): Promise<AssortmentItem[]> {
    const response = await api.get('/assortment-services')
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  }
}