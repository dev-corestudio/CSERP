import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { quotationService } from '@/services/quotationService'
import type { AssortmentItem } from '@/types'

export const useQuotationsStore = defineStore('quotations', () => {
  // =========================================================================
  // STATE
  // =========================================================================

  const quotations = ref<any[]>([])
  const currentQuotation = ref<any>(null)
  const materials = ref<AssortmentItem[]>([])
  const services = ref<AssortmentItem[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // =========================================================================
  // COMPUTED
  // =========================================================================

  /** Zatwierdzona wycena (jeśli istnieje) */
  const approvedQuotation = computed(() => {
    if (!Array.isArray(quotations.value)) return null
    return quotations.value.find(q => q.is_approved) ?? null
  })

  /** Najwyższy numer wersji na liście */
  const latestVersion = computed(() => {
    if (!Array.isArray(quotations.value) || quotations.value.length === 0) return 0
    return Math.max(...quotations.value.map(q => q.version_number))
  })

  // =========================================================================
  // ACTIONS — POBIERANIE
  // =========================================================================

  /** Pobierz wszystkie wyceny wariantu */
  const fetchQuotations = async (variantId: number | string) => {
    loading.value = true
    error.value = null
    try {
      const response = await quotationService.getAll(variantId)
      quotations.value = response
    } catch (err: any) {
      console.error('Fetch quotations error:', err)
      error.value = err.response?.data?.message || 'Błąd pobierania wycen'
      quotations.value = []
    } finally {
      loading.value = false
    }
  }

  /** Pobierz szczegóły pojedynczej wyceny */
  const fetchQuotation = async (id: number | string) => {
    loading.value = true
    error.value = null
    try {
      const response = await quotationService.getById(id)
      currentQuotation.value = response
      return currentQuotation.value
    } catch (err: any) {
      console.error('Fetch quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd pobierania wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // ACTIONS — ZAPIS / EDYCJA
  // =========================================================================

  /** Utwórz nową wycenę dla wariantu */
  const createQuotation = async (variantId: number | string, data: any) => {
    loading.value = true
    error.value = null
    try {
      const newQuotation = await quotationService.create(variantId, data)
      if (Array.isArray(quotations.value)) {
        quotations.value.push(newQuotation)
      } else {
        quotations.value = [newQuotation]
      }
      return newQuotation
    } catch (err: any) {
      console.error('Create quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd tworzenia wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  /** Zaktualizuj istniejącą wycenę */
  const updateQuotation = async (id: number | string, data: any) => {
    loading.value = true
    error.value = null
    try {
      const updatedQuotation = await quotationService.update(id, data)

      // Aktualizuj lokalną listę
      if (Array.isArray(quotations.value)) {
        const index = quotations.value.findIndex(q => q.id === id)
        if (index !== -1) {
          quotations.value[index] = updatedQuotation
        }
      }

      // Jeśli edytujemy aktualnie otwartą wycenę, zaktualizuj ją też
      if (currentQuotation.value?.id === id) {
        currentQuotation.value = updatedQuotation
      }

      return updatedQuotation
    } catch (err: any) {
      console.error('Update quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd aktualizacji wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  /** Zatwierdź wycenę */
  const approveQuotation = async (id: number | string) => {
    loading.value = true
    error.value = null
    try {
      const approved = await quotationService.approve(id)

      // Odznacz wszystkie, zatwierdź tę jedną
      if (Array.isArray(quotations.value)) {
        quotations.value = quotations.value.map(q => ({
          ...q,
          is_approved: q.id === id
        }))
      }

      return approved
    } catch (err: any) {
      console.error('Approve quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd zatwierdzania wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  /** Usuń wycenę */
  const deleteQuotation = async (id: number | string) => {
    loading.value = true
    error.value = null
    try {
      await quotationService.delete(id)

      if (Array.isArray(quotations.value)) {
        const numId = Number(id)
        quotations.value = quotations.value.filter(q => q.id !== numId)
      }
    } catch (err: any) {
      console.error('Delete quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd usuwania wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // ACTIONS — DUPLIKOWANIE
  // =========================================================================

  /**
   * Duplikuj wycenę — tworzy nową wersję z tymi samymi pozycjami.
   * Nowa wersja jest zawsze niezatwierdzona.
   */
  const duplicateQuotation = async (quotationId: number | string) => {
    loading.value = true
    error.value = null
    try {
      const result = await quotationService.duplicate(quotationId)

      // Dodaj nową wersję do lokalnej listy
      if (Array.isArray(quotations.value)) {
        quotations.value.push(result.quotation)
      }

      return result.quotation
    } catch (err: any) {
      console.error('Duplicate quotation error:', err)
      error.value = err.response?.data?.message || 'Błąd duplikowania wyceny'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // ACTIONS — EKSPORT MATERIAŁÓW
  // =========================================================================

  /**
   * Eksportuj materiały z zatwierdzonej wyceny do listy materiałów wariantu.
   *
   * @param quotationId  - ID wyceny (musi być zatwierdzona)
   * @param mode         - Tryb obsługi duplikatów:
   *                        'skip'    – pomija istniejące (domyślny)
   *                        'merge'   – sumuje ilości z istniejącymi
   *                        'replace' – nadpisuje istniejące
   */
  const exportMaterials = async (
    quotationId: number | string,
    mode: 'skip' | 'merge' | 'replace' = 'skip'
  ) => {
    loading.value = true
    error.value = null
    try {
      const result = await quotationService.exportMaterials(quotationId, mode)
      return result
    } catch (err: any) {
      console.error('Export materials error:', err)
      error.value = err.response?.data?.message || 'Błąd eksportu materiałów'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // ACTIONS — KATALOG (ASORTYMENT)
  // =========================================================================

  /** Pobierz listę materiałów z katalogu */
  const fetchMaterials = async () => {
    try {
      materials.value = await quotationService.getMaterials()
    } catch (err) {
      console.error('Fetch materials error:', err)
      materials.value = []
    }
  }

  /** Pobierz listę usług z katalogu */
  const fetchServices = async () => {
    try {
      services.value = await quotationService.getServices()
    } catch (err) {
      console.error('Fetch services error:', err)
      services.value = []
    }
  }

  // =========================================================================
  // ACTIONS — PDF
  // =========================================================================

  /** Pobierz PDF wyceny jako blob */
  const downloadPdf = async (id: number | string) => {
    try {
      return await quotationService.downloadPDF(id)
    } catch (err) {
      console.error('Download PDF error:', err)
      throw err
    }
  }

  // =========================================================================
  // RETURN
  // =========================================================================

  return {
    // State
    quotations,
    currentQuotation,
    materials,
    services,
    loading,
    error,

    // Computed
    approvedQuotation,
    latestVersion,

    // Actions
    fetchQuotations,
    fetchQuotation,
    createQuotation,
    updateQuotation,
    approveQuotation,
    deleteQuotation,
    duplicateQuotation,
    exportMaterials,
    fetchMaterials,
    fetchServices,
    downloadPdf,
  }
})