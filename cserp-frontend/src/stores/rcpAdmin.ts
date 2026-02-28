import { defineStore } from 'pinia'
import { ref } from 'vue'
import rcpAdminService from '@/services/rcpAdminService'
export const useRcpAdminStore = defineStore('rcpAdmin', () => {
    const tasks = ref<any[]>([])
    const totalTasks = ref(0)
    const loading = ref(false)
    const currentPage = ref(1)
    const lastPage = ref(1)
    // Filtry (zaktualizowane o zakres dat)
    const filters = ref({
        status: 'all',
        search: '',
        worker_id: null,
        workstation_id: null,
        date_from: null,
        date_to: null
    })
    const fetchTasks = async (page = 1) => {
        loading.value = true
        try {
            const params = {
                page,
                ...filters.value
            }
            // Usuń puste filtry
            if (!params.search) delete params.search
            if (params.status === 'all') delete params.status
            if (!params.worker_id) delete params.worker_id
            if (!params.workstation_id) delete params.workstation_id
            if (!params.date_from) delete params.date_from
            if (!params.date_to) delete params.date_to

            const response = await rcpAdminService.getTasks(params)

            tasks.value = response.data
            totalTasks.value = response.total
            currentPage.value = response.current_page
            lastPage.value = response.last_page
        } catch (error) {
            console.error('Błąd pobierania zadań RCP:', error)
        } finally {
            loading.value = false
        }
    }
    const updateFilters = (newFilters: any) => {
        filters.value = { ...filters.value, ...newFilters }
        fetchTasks(1) // Reset do strony 1 przy zmianie filtrów
    }
    return {
        tasks,
        totalTasks,
        loading,
        currentPage,
        lastPage,
        filters,
        fetchTasks,
        updateFilters
    }
})