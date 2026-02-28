/**
 * useServerTable — Composable do obsługi v-data-table-server.
 *
 * Zarządza stanem paginacji, sortowaniem, wyszukiwaniem i pobieraniem danych.
 * Współpracuje z backendowym traitem Paginatable.
 *
 * Użycie:
 *   const { items, totalItems, loading, options, search, fetchData, updateOptions } =
 *     useServerTable('/orders', { quick_filter: quickFilter });
 */
import { ref, watch, type Ref } from "vue";
import { debounce } from "lodash";
import api from "@/services/api";

interface ServerTableOptions {
    page: number;
    itemsPerPage: number;
    sortBy: Array<{ key: string; order: "asc" | "desc" }>;
}

interface UseServerTableConfig {
    defaultPerPage?: number;
    defaultSortBy?: string;
    defaultSortDir?: "asc" | "desc";
    /** Dodatkowe reaktywne filtry — obiekt ref, którego wartości zostaną dołączone do query params */
    extraFilters?: Ref<Record<string, any>>;
    /** Callback po udanym pobraniu — np. do aktualizacji statystyk */
    onSuccess?: (response: any) => void;
}

export function useServerTable(endpoint: string, config: UseServerTableConfig = {}) {
    const {
        defaultPerPage = 15,
        defaultSortBy = "created_at",
        defaultSortDir = "desc",
        extraFilters,
        onSuccess,
    } = config;

    const items: Ref<any[]> = ref([]);
    const totalItems = ref(0);
    const loading = ref(false);
    const error: Ref<string | null> = ref(null);
    const search = ref("");

    const options = ref<ServerTableOptions>({
        page: 1,
        itemsPerPage: defaultPerPage,
        sortBy: [{ key: defaultSortBy, order: defaultSortDir }],
    });

    /**
     * Pobierz dane z API na podstawie aktualnych opcji tabeli i filtrów.
     */
    const fetchData = async () => {
        try {
            loading.value = true;
            error.value = null;

            const sort = options.value.sortBy[0];
            const params: Record<string, any> = {
                page: options.value.page,
                per_page: options.value.itemsPerPage,
                sort_by: sort?.key || defaultSortBy,
                sort_dir: sort?.order || defaultSortDir,
            };

            // Dodaj search jeśli niepusty
            if (search.value) {
                params.search = search.value;
            }

            // Dołącz dodatkowe filtry (reactive)
            if (extraFilters?.value) {
                for (const [key, value] of Object.entries(extraFilters.value)) {
                    if (value !== null && value !== undefined && value !== "" && value !== "all") {
                        params[key] = value;
                    }
                }
            }

            const response = await api.get(endpoint, { params });

            // Laravel paginator zwraca: { data: [...], total: N, current_page: N, ... }
            const payload = response.data;

            if (payload.data && typeof payload.total === "number") {
                // Standardowa odpowiedź paginatora Laravela
                items.value = payload.data;
                totalItems.value = payload.total;
            } else if (Array.isArray(payload)) {
                // Fallback — odpowiedź bez paginacji (backward compatibility)
                items.value = payload;
                totalItems.value = payload.length;
            } else if (Array.isArray(payload.data)) {
                // Odpowiedź owinięta w { data: [...] } bez paginacji
                items.value = payload.data;
                totalItems.value = payload.data.length;
            }

            onSuccess?.(payload);
        } catch (err: any) {
            console.error(`Błąd pobierania ${endpoint}:`, err);
            error.value = err.response?.data?.message || "Nie udało się pobrać danych";
            items.value = [];
            totalItems.value = 0;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Handler dla @update:options z v-data-table-server.
     */
    const updateOptions = (newOptions: ServerTableOptions) => {
        options.value = newOptions;
        fetchData();
    };

    /**
     * Debounced search — resetuje na stronę 1.
     */
    const debouncedSearch = debounce(() => {
        options.value.page = 1;
        fetchData();
    }, 400);

    // Watch na search → debounced fetch
    watch(search, () => {
        debouncedSearch();
    });

    // Watch na extra filters → natychmiastowe przeładowanie (reset strony)
    if (extraFilters) {
        watch(
            extraFilters,
            () => {
                options.value.page = 1;
                fetchData();
            },
            { deep: true }
        );
    }

    return {
        items,
        totalItems,
        loading,
        error,
        search,
        options,
        fetchData,
        updateOptions,
    };
}