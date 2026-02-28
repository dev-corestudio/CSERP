import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { orderService } from '@/services/orderService'
import { useSharedApiState } from '@/composables/useApiAction'
import type { Order } from '@/types'

export const useOrdersStore = defineStore('orders', () => {
  // ---------------------------------------------------------------------------
  // STATE
  // ---------------------------------------------------------------------------

  const orders = ref<Order[]>([])
  const currentOrder = ref<Order | null>(null)

  // Wspólny stan loading/error dla wszystkich akcji tego store
  // PRZED: loading i error były ref(false)/ref(null) definiowane osobno,
  //        a każda z 5 akcji miała identyczny try/catch/finally
  const { loading, error, wrapAction } = useSharedApiState()

  // ---------------------------------------------------------------------------
  // COMPUTED
  // ---------------------------------------------------------------------------

  const activeOrders = computed(() =>
    orders.value.filter(o => !['completed', 'cancelled'].includes(o.overall_status))
  )

  const completedOrders = computed(() =>
    orders.value.filter(o => o.overall_status === 'completed')
  )

  // ---------------------------------------------------------------------------
  // ACTIONS (przez wrapAction — automatyczny loading/error handling)
  // ---------------------------------------------------------------------------

  /** Pobierz listę zamówień z opcjonalnymi filtrami */
  const fetchOrders = wrapAction(
    async (params: Record<string, any> = {}) => {
      const response = await orderService.getAll(params)
      orders.value = Array.isArray(response.data) ? response.data : []
      return orders.value
    },
    'Błąd pobierania zamówień'
  )

  /** Pobierz szczegóły jednego zamówienia */
  const fetchOrder = wrapAction(
    async (id: number | string) => {
      const response = await orderService.getById(id)
      currentOrder.value = response
      return currentOrder.value
    },
    'Błąd pobierania zamówienia'
  )

  /** Utwórz nowe zamówienie */
  const createOrder = wrapAction(
    async (orderData: Partial<Order>) => {
      const newOrder = await orderService.create(orderData)
      orders.value.unshift(newOrder)
      return newOrder
    },
    'Błąd tworzenia zamówienia'
  )

  /** Zaktualizuj istniejące zamówienie */
  const updateOrder = wrapAction(
    async (id: number | string, data: Partial<Order>) => {
      const updated = await orderService.update(id, data)

      // Aktualizuj w liście
      const index = orders.value.findIndex(o => o.id === Number(id))
      if (index !== -1) orders.value[index] = updated

      // Aktualizuj bieżące (jeśli to ono)
      if (currentOrder.value?.id === Number(id)) currentOrder.value = updated

      return updated
    },
    'Błąd aktualizacji zamówienia'
  )

  /** Usuń zamówienie */
  const deleteOrder = wrapAction(
    async (id: number | string) => {
      await orderService.delete(id)
      orders.value = orders.value.filter(o => o.id !== Number(id))
    },
    'Błąd usuwania zamówienia'
  )

  return {
    // State
    orders,
    currentOrder,
    loading,
    error,

    // Computed
    activeOrders,
    completedOrders,

    // Actions
    fetchOrders,
    fetchOrder,
    createOrder,
    updateOrder,
    deleteOrder,
  }
})