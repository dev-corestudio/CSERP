import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { variantService } from '@/services/variantService'
import type { Variant } from '@/types'

export const useVariantsStore = defineStore('variants', () => {
  // =========================================================================
  // STATE
  // =========================================================================

  /** Płaska lista wszystkich elementów (grupy + warianty) dla zamówienia */
  const variants = ref<Variant[]>([])
  const currentVariant = ref<Variant | null>(null)
  const prototypes = ref<any[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // =========================================================================
  // COMPUTED — PODZIAŁ NA GRUPY I WARIANTY
  // =========================================================================

  /**
   * Tylko elementy będące grupami (quantity === 0, is_group === true).
   * Grupy to kontenery — nie są produkowane.
   */
  const groups = computed(() =>
    variants.value.filter(v => v.is_group || v.quantity === 0)
  )

  /**
   * Tylko elementy będące wariantami (quantity >= 1).
   * Warianty mają lifecycle produkcji.
   */
  const onlyVariants = computed(() =>
    variants.value.filter(v => !v.is_group && v.quantity > 0)
  )

  // =========================================================================
  // POBIERANIE
  // =========================================================================

  /** Pobierz wszystkie elementy (grupy + warianty) dla zamówienia */
  const fetchVariants = async (orderId: number | string) => {
    loading.value = true
    error.value = null
    try {
      const response = await variantService.getAll(orderId)
      variants.value = response
    } catch (err: any) {
      console.error('Fetch variants error:', err)
      error.value = err.response?.data?.message || 'Błąd pobierania wariantów'
      variants.value = []
    } finally {
      loading.value = false
    }
  }

  /** Pobierz szczegóły pojedynczego elementu (grupy lub wariantu) */
  const fetchVariant = async (id: number | string) => {
    loading.value = true
    error.value = null
    try {
      const response = await variantService.getById(id)
      currentVariant.value = response
      return currentVariant.value
    } catch (err: any) {
      console.error('Fetch variant error:', err)
      error.value = err.response?.data?.message || 'Błąd pobierania wariantu'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // TWORZENIE
  // =========================================================================

  /**
   * Utwórz nową GRUPĘ dla zamówienia.
   * Backend automatycznie nadaje kolejną literę (A, B, C...) i ustawia quantity=0.
   */
  const createGroup = async (
    orderId: number | string,
    data: { name: string; description?: string }
  ) => {
    loading.value = true
    error.value = null
    try {
      const newGroup = await variantService.createGroup(orderId, data)
      variants.value.push(newGroup)
      return newGroup
    } catch (err: any) {
      console.error('Create group error:', err)
      error.value = err.response?.data?.message || 'Błąd tworzenia grupy'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Utwórz WARIANT jako dziecko grupy lub wariantu.
   * Backend nadaje numer: A → A1, A2; A1 → A1_1, A1_2.
   */
  const createChildVariant = async (
    orderId: number | string,
    parentId: number | string,
    data: {
      name: string
      quantity: number
      type: 'SERIAL' | 'PROTOTYPE'
      description?: string
    }
  ) => {
    loading.value = true
    error.value = null
    try {
      const newVariant = await variantService.createChild(orderId, parentId, data)
      variants.value.push(newVariant)
      return newVariant
    } catch (err: any) {
      console.error('Create child variant error:', err)
      error.value = err.response?.data?.message || 'Błąd tworzenia wariantu'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // EDYCJA
  // =========================================================================

  /** Zaktualizuj grupę lub wariant */
  const updateVariant = async (id: number | string, data: Partial<Variant>) => {
    loading.value = true
    error.value = null
    try {
      const updated = await variantService.update(id, data)

      // Aktualizuj w liście
      if (Array.isArray(variants.value)) {
        const index = variants.value.findIndex(l => l.id === Number(id))
        if (index !== -1) variants.value[index] = updated
      }

      // Aktualizuj bieżący jeśli to on
      if (currentVariant.value?.id === Number(id)) {
        currentVariant.value = updated
      }

      return updated
    } catch (err: any) {
      console.error('Update variant error:', err)
      error.value = err.response?.data?.message || 'Błąd aktualizacji'
      throw err
    } finally {
      loading.value = false
    }
  }

  /** Zmień status wariantu (nie dot. grup) */
  const updateStatus = async (id: number | string, status: string) => {
    loading.value = true
    error.value = null
    try {
      const updated = await variantService.updateStatus(id, status)

      if (Array.isArray(variants.value)) {
        const numId = Number(id)
        const index = variants.value.findIndex(l => l.id === numId)
        if (index !== -1) variants.value[index] = updated
      }
      if (currentVariant.value?.id === Number(id)) {
        currentVariant.value = updated
      }

      return updated
    } catch (err: any) {
      console.error('Update status error:', err)
      error.value = err.response?.data?.message || 'Błąd zmiany statusu'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // USUWANIE
  // =========================================================================

  /**
   * Usuń wariant lub grupę.
   *
   * @param id     - ID elementu
   * @param force  - Wymagane dla grup z dziećmi (kasuje całe drzewo rekurencyjnie)
   */
  const deleteVariant = async (id: number | string, force = false) => {
    loading.value = true
    error.value = null
    try {
      await variantService.delete(id, force)

      // Usuń z listy — przy force=true usuń też wszystkich potomków
      if (force) {
        // Rekurencyjnie wyznacz ID wszystkich potomków
        const toRemove = new Set<number>()
        const collectDescendants = (parentId: number) => {
          toRemove.add(parentId)
          variants.value
            .filter(v => v.parent_variant_id === parentId)
            .forEach(child => collectDescendants(child.id))
        }
        collectDescendants(Number(id))
        variants.value = variants.value.filter(v => !toRemove.has(v.id))
      } else {
        const numId = Number(id)
        variants.value = variants.value.filter(l => l.id !== numId)
      }
    } catch (err: any) {
      console.error('Delete variant error:', err)
      error.value = err.response?.data?.message || 'Błąd usuwania'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // RECENZJA PROTOTYPU
  // =========================================================================

  /** Zatwierdź lub odrzuć prototyp z opcjonalnym feedbackiem */
  const reviewPrototype = async (
    id: number | string,
    action: 'approve' | 'reject',
    feedback: string
  ) => {
    loading.value = true
    try {
      const updated = await variantService.reviewPrototype(id, action, feedback)
      if (currentVariant.value?.id === Number(id)) {
        currentVariant.value = updated
      }
      return updated
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Błąd recenzji prototypu'
      throw err
    } finally {
      loading.value = false
    }
  }

  // =========================================================================
  // POMOCNICZE
  // =========================================================================

  /**
   * Zwróć dzieci (warianty) należące do danej grupy/wariantu.
   */
  const getChildren = (parentId: number): Variant[] =>
    variants.value.filter(v => v.parent_variant_id === parentId)

  return {
    // Stan
    variants,
    currentVariant,
    prototypes,
    loading,
    error,

    // Computed
    groups,
    onlyVariants,

    // Pobieranie
    fetchVariants,
    fetchVariant,

    // Tworzenie
    createGroup,
    createChildVariant,

    // Edycja
    updateVariant,
    updateStatus,

    // Usuwanie
    deleteVariant,

    // Recenzja
    reviewPrototype,

    // Pomocnicze
    getChildren,
  }
})