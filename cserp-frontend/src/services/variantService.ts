import api from './api'
import type { Variant } from '@/types'

export const variantService = {
  // =========================================================================
  // POBIERANIE
  // =========================================================================

  /**
   * Pobierz wszystkie warianty (grupy i warianty) dla projektu.
   * Backend zwraca płaską listę — grupy mają is_group=true, quantity=0.
   */
  async getAll(projectId: number | string): Promise<Variant[]> {
    const response = await api.get(`/projects/${projectId}/variants`)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  /**
   * Pobierz szczegóły wariantu lub grupy po ID.
   */
  async getById(id: number | string): Promise<Variant> {
    const response = await api.get(`/variants/${id}`)
    const data = response.data
    return (data as any).data || data
  },

  // =========================================================================
  // TWORZENIE
  // =========================================================================

  /**
   * Utwórz nową GRUPĘ dla projektu.
   * Backend automatycznie nadaje kolejną literę (A, B, C…) i ustawia quantity=0.
   *
   * Payload: { name, description? }
   */
  async createGroup(projectId: number | string, data: { name: string; description?: string }): Promise<Variant> {
    const response = await api.post(`/projects/${projectId}/variants`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Utwórz WARIANT w istniejącej grupie (parent = grupa lub wariant).
   * Backend nadaje kolejny numer wg reguły: A → A1, A2; A1 → A1_1, A1_2.
   *
   * Payload: { name, quantity (≥1), type (SERIAL|PROTOTYPE), description? }
   */
  async createChild(
    projectId: number | string,
    parentId: number | string,
    data: {
      name: string
      quantity: number
      type: 'SERIAL' | 'PROTOTYPE'
      description?: string
    }
  ): Promise<Variant> {
    const response = await api.post(`/projects/${projectId}/variants/${parentId}/children`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  // =========================================================================
  // EDYCJA
  // =========================================================================

  /**
   * Aktualizuj grupę lub wariant.
   * Backend blokuje konwersję typu (grupa ↔ wariant) — quantity musi być spójne.
   */
  async update(id: number | string, data: Partial<Variant>): Promise<Variant> {
    const response = await api.put(`/variants/${id}`, data)
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Zmień status wariantu (tylko dla wariantów — grup nie dotyczą statusy).
   */
  async updateStatus(id: number | string, status: string): Promise<Variant> {
    const response = await api.patch(`/variants/${id}/status`, { status })
    const resData = response.data
    return (resData as any).data || resData
  },

  // =========================================================================
  // USUWANIE
  // =========================================================================

  /**
   * Usuń wariant lub grupę.
   *
   * Grupy z dziećmi wymagają force=true — bez tego backend zwraca 422.
   * Z force=true kasuje grupę rekurencyjnie wraz z całym drzewem.
   */
  async delete(id: number | string, force = false): Promise<void> {
    await api.delete(`/variants/${id}`, {
      params: force ? { force: true } : undefined,
    })
  },

  // =========================================================================
  // DUPLIKOWANIE
  // =========================================================================

  /**
   * Duplikuj wariant lub grupę.
   *
   * Dla WARIANTU:
   *   relation: 'sibling' | 'child'
   *   name, quantity, type, copy_quotation, copy_materials, description?
   *
   * Dla GRUPY (is_group=true):
   *   relation: 'sibling' (jedyna opcja — nie można tworzyć sub-grup)
   *   name, copy_children (kopiuje całe drzewo wariantów grupy)
   *
   * Backend zwraca { variant: Variant, ... }
   */
  async duplicate(
    id: number | string,
    payload: {
      relation: 'sibling' | 'child'
      name?: string
      quantity?: number
      type?: string
      description?: string
      copy_quotation?: boolean
      copy_materials?: boolean
      copy_children?: boolean
    }
  ): Promise<any> {
    const response = await api.post(`/variants/${id}/duplicate`, payload)
    return response.data
  },

  // =========================================================================
  // PROTOTYPY
  // =========================================================================

  /**
   * Prześlij recenzję prototypu (zatwierdzenie/odrzucenie).
   */
  async reviewPrototype(
    id: number | string,
    action: 'approve' | 'reject',
    feedback: string
  ): Promise<Variant> {
    const response = await api.post(`/variants/${id}/review`, {
      action,
      feedback_notes: feedback,
    })
    const resData = response.data
    return (resData as any).data || resData
  },

  /**
   * Pobierz historię prototypów wariantu.
   */
  async getPrototypes(variantId: number | string): Promise<any[]> {
    const response = await api.get(`/variants/${variantId}/prototypes`)
    const data = response.data
    if (Array.isArray(data)) return data
    return (data as any).data || []
  },

  /**
   * Utwórz nowy prototyp.
   */
  async createPrototype(variantId: number | string, data: any): Promise<any> {
    const response = await api.post(`/variants/${variantId}/prototypes`, data)
    return response.data
  },
}

export default variantService