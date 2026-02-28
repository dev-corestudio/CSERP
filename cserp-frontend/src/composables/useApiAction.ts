import { ref } from 'vue'

// -----------------------------------------------------------------------------
// TYPY
// -----------------------------------------------------------------------------

export interface ApiActionReturn {
    loading: ReturnType<typeof ref<boolean>>
    error: ReturnType<typeof ref<string | null>>
    /** Uruchom akcję z automatyczną obsługą loading/error */
    run: <T>(action: () => Promise<T>, options?: RunOptions) => Promise<T | undefined>
    /** Wersja skrócona: opakowuje funkcję i zwraca nową, gotową do użycia */
    withLoading: <T>(action: () => Promise<T>, options?: RunOptions) => () => Promise<T | undefined>
}

export interface RunOptions {
    /** Domyślna wiadomość błędu gdy API nie zwróci message */
    fallbackError?: string
    /** Czy nie logować błędu do konsoli */
    silent?: boolean
    /** Callback wywoływany po błędzie (np. do pokazania snackbar) */
    onError?: (message: string) => void
}

// -----------------------------------------------------------------------------
// IMPLEMENTACJA
// -----------------------------------------------------------------------------

/**
 * Zarządza stanem loading/error dla jednej operacji API.
 * Używaj jednego `useApiAction()` per "typ operacji" w store,
 * lub jednego dla wielu podobnych akcji.
 *
 * @example
 * // W store Pinia:
 * const listAction = useApiAction()
 * const saveAction = useApiAction()
 *
 * const fetchItems = listAction.withLoading(async () => {
 *   items.value = await itemService.getAll()
 * }, { fallbackError: 'Błąd pobierania listy' })
 *
 * const saveItem = saveAction.withLoading(async (data) => {
 *   const created = await itemService.create(data)
 *   items.value.push(created)
 *   return created
 * }, { fallbackError: 'Błąd zapisu' })
 */
export function useApiAction(): ApiActionReturn {
    const loading = ref(false)
    const error = ref<string | null>(null)

    const run = async <T>(
        action: () => Promise<T>,
        options: RunOptions = {}
    ): Promise<T | undefined> => {
        const {
            fallbackError = 'Wystąpił błąd',
            silent = false,
            onError,
        } = options

        loading.value = true
        error.value = null

        try {
            return await action()
        } catch (err: any) {
            const message = err?.response?.data?.message
                || err?.message
                || fallbackError

            error.value = message

            if (!silent) {
                console.error('[useApiAction]', message, err)
            }

            if (onError) {
                onError(message)
            }

            // Re-throw żeby wywołujący mógł obsłużyć błąd (np. w komponencie)
            throw err
        } finally {
            loading.value = false
        }
    }

    const withLoading = <T>(
        action: () => Promise<T>,
        options: RunOptions = {}
    ) => {
        return () => run(action, options)
    }

    return { loading, error, run, withLoading }
}

// =============================================================================
// useSharedApiState — dla stores z jednym wspólnym loading/error
// =============================================================================
// Gdy store potrzebuje JEDNEGO globalnego loading (jak orders.ts, quotations.ts),
// to narzędzie upraszcza zapis akcji przez wrapping.
//
// @example
// export const useOrdersStore = defineStore('orders', () => {
//   const orders = ref([])
//   const { loading, error, wrapAction } = useSharedApiState()
//
//   const fetchOrders = wrapAction(async () => {
//     orders.value = await orderService.getAll()
//   }, 'Błąd pobierania zamówień')
//
//   const createOrder = wrapAction(async (data) => {
//     const order = await orderService.create(data)
//     orders.value.unshift(order)
//     return order
//   }, 'Błąd tworzenia zamówienia')
//
//   return { orders, loading, error, fetchOrders, createOrder }
// })
// =============================================================================

export function useSharedApiState() {
    const loading = ref(false)
    const error = ref<string | null>(null)

    /**
     * Opakowuje akcję async w loading/error handling
     * Zwraca nową funkcję gotową do użycia w store
     */
    function wrapAction<TArgs extends any[], TReturn>(
        action: (...args: TArgs) => Promise<TReturn>,
        fallbackError = 'Wystąpił błąd'
    ): (...args: TArgs) => Promise<TReturn> {
        return async (...args: TArgs): Promise<TReturn> => {
            loading.value = true
            error.value = null

            try {
                return await action(...args)
            } catch (err: any) {
                const message = err?.response?.data?.message || err?.message || fallbackError
                error.value = message
                console.error(`[Store Action] ${fallbackError}:`, err)
                throw err
            } finally {
                loading.value = false
            }
        }
    }

    return { loading, error, wrapAction }
}