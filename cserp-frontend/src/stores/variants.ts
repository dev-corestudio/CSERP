import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { variantService } from '@/services/variantService'
import { useSharedApiState } from '@/composables/useApiAction'
import type { Variant } from '@/types'

export const useVariantsStore = defineStore('variants', () => {
  // =========================================================================
  // STATE
  // =========================================================================

  /** Płaska lista wszystkich elementów (grupy + warianty) dla zamówienia */
  const variants = ref<Variant[]>([])
  const currentVariant = ref<Variant | null>(null)
  const { loading, error, wrapAction } = useSharedApiState()

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
  const fetchVariants = wrapAction(async (orderId: number | string) => {
    variants.value = await variantService.getAll(orderId)
  }, 'Błąd pobierania wariantów')

  /** Pobierz szczegóły pojedynczego elementu (grupy lub wariantu) */
  const fetchVariant = wrapAction(async (id: number | string) => {
    const response = await variantService.getById(id)
    currentVariant.value = response
    return currentVariant.value
  }, 'Błąd pobierania wariantu')

  // =========================================================================
  // TWORZENIE
  // =========================================================================

  /**
   * Utwórz nową GRUPĘ dla zamówienia.
   * Backend automatycznie nadaje kolejną literę (A, B, C...) i ustawia quantity=0.
   */
  const createGroup = wrapAction(async (
    orderId: number | string,
    data: { name: string; description?: string }
  ) => {
    const newGroup = await variantService.createGroup(orderId, data)
    variants.value.push(newGroup)
    return newGroup
  }, 'Błąd tworzenia grupy')

  /**
   * Utwórz WARIANT jako dziecko grupy lub wariantu.
   * Backend nadaje numer: A → A1, A2; A1 → A1_1, A1_2.
   */
  const createChildVariant = wrapAction(async (
    orderId: number | string,
    parentId: number | string,
    data: {
      name: string
      quantity: number
      type: 'SERIAL' | 'PROTOTYPE'
      description?: string
      priority?: 'LOW' | 'NORMAL' | 'HIGH' | 'URGENT'
    }
  ) => {
    const newVariant = await variantService.createChild(orderId, parentId, data)
    variants.value.push(newVariant)
    return newVariant
  }, 'Błąd tworzenia wariantu')

  // =========================================================================
  // EDYCJA
  // =========================================================================

  /** Zaktualizuj grupę lub wariant */
  const updateVariant = wrapAction(async (id: number | string, data: Partial<Variant>) => {
    const updated = await variantService.update(id, data)

    const index = variants.value.findIndex(l => l.id === Number(id))
    if (index !== -1) variants.value[index] = updated

    if (currentVariant.value?.id === Number(id)) {
      currentVariant.value = updated
    }

    return updated
  }, 'Błąd aktualizacji')

  /** Zmień status wariantu (nie dot. grup) */
  const updateStatus = wrapAction(async (id: number | string, status: string) => {
    const updated = await variantService.updateStatus(id, status)

    const numId = Number(id)
    const index = variants.value.findIndex(l => l.id === numId)
    if (index !== -1) variants.value[index] = updated

    if (currentVariant.value?.id === numId) {
      currentVariant.value = updated
    }

    return updated
  }, 'Błąd zmiany statusu')

  // =========================================================================
  // USUWANIE
  // =========================================================================

  /**
   * Usuń wariant lub grupę.
   *
   * @param id     - ID elementu
   * @param force  - Wymagane dla grup z dziećmi (kasuje całe drzewo rekurencyjnie)
   */
  const deleteVariant = wrapAction(async (id: number | string, force = false) => {
    await variantService.delete(id, force)

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
  }, 'Błąd usuwania')

  // =========================================================================
  // RECENZJA PROTOTYPU
  // =========================================================================

  /** Zatwierdź lub odrzuć prototyp z opcjonalnym feedbackiem */
  const reviewPrototype = wrapAction(async (
    id: number | string,
    action: 'approve' | 'reject',
    feedback: string
  ) => {
    const updated = await variantService.reviewPrototype(id, action, feedback)
    if (currentVariant.value?.id === Number(id)) {
      currentVariant.value = updated
    }
    return updated
  }, 'Błąd recenzji prototypu')

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
