/**
 * usePersistedFilters — Composable do persystencji filtrów w localStorage.
 *
 * Tworzy reaktywny ref powiązany z localStorage. Przy inicjalizacji przywraca
 * zapisaną wartość, a przy każdej zmianie automatycznie ją zapisuje.
 * W przypadku obiektów przywrócona wartość jest scalana z domyślną,
 * aby nowe pola (dodane po zapisie) miały swoje wartości domyślne.
 *
 * Użycie:
 *   const filters = usePersistedFilters('projects:filters', { status: 'all', quick_filter: 'active' })
 */
import { ref, watch, type Ref } from "vue";

function mergeWithDefaults<T>(defaults: T, override?: any): T {
    if (typeof defaults === "object" && defaults !== null && !Array.isArray(defaults)) {
        return { ...(defaults as any), ...(override ?? {}) } as T;
    }
    return override !== undefined ? override : defaults;
}

export function usePersistedFilters<T>(storageKey: string, defaults: T): Ref<T> {
    let initial: T;
    try {
        const stored = localStorage.getItem(storageKey);
        // Scalaj z defaults, żeby nowe pola (dodane po zapisie) miały wartości domyślne
        initial = stored !== null ? mergeWithDefaults(defaults, JSON.parse(stored)) : mergeWithDefaults(defaults);
    } catch {
        initial = mergeWithDefaults(defaults);
    }

    const state = ref<T>(initial) as Ref<T>;

    watch(
        state,
        (val) => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(val));
            } catch {
                // ignore (np. tryb prywatny z blokadą storage)
            }
        },
        { deep: true }
    );

    return state;
}
