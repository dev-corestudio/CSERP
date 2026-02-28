import { defineStore } from 'pinia'
import { ref } from 'vue'
import { workstationService } from '@/services/workstationService'
import api from '@/services/api'
import type { Workstation, User } from '@/types'

export const useWorkstationStore = defineStore('workstations', () => {
    const items = ref < Workstation[] > ([])
    const myItems = ref < Workstation[] > ([])
    const workers = ref < User[] > ([])
    const currentWorkstationServices = ref < any[] > ([])
    const loading = ref(false)
    const error = ref < string | null > (null)

    const fetchWorkstations = async () => {
        loading.value = true
        try {
            const data = await workstationService.getAll()
            items.value = data
        } catch (err: any) {
            console.error(err)
            error.value = 'Błąd pobierania stanowisk'
        } finally {
            loading.value = false
        }
    }

    const fetchMyWorkstations = async () => {
        loading.value = true
        try {
            const response = await api.get('/workstations-my-list')
            const data = response.data
            myItems.value = Array.isArray(data) ? data : (data as any).data || []
        } catch (err) {
            console.error(err)
        } finally {
            loading.value = false
        }
    }

    const fetchWorkers = async () => {
        try {
            const response = await api.get('/workers-list')
            const data = response.data
            workers.value = Array.isArray(data) ? data : (data as any).data || []
        } catch (err) {
            console.error('Błąd pobierania pracowników:', err)
        }
    }

    const createWorkstation = async (data: Partial<Workstation>) => {
        loading.value = true
        try {
            await workstationService.create(data)
            await fetchWorkstations()
        } finally {
            loading.value = false
        }
    }

    const updateWorkstation = async (id: number | string, data: Partial<Workstation>) => {
        loading.value = true
        try {
            await workstationService.update(id, data)
            await fetchWorkstations()
        } finally {
            loading.value = false
        }
    }

    const deleteWorkstation = async (id: number | string) => {
        loading.value = true
        try {
            await workstationService.delete(id)
            // Rzutowanie na number dla filtra
            const numId = Number(id)
            items.value = items.value.filter(i => i.id !== numId)
        } finally {
            loading.value = false
        }
    }

    // --- USŁUGI ---
    const fetchWorkstationServices = async (workstationId: number | string) => {
        try {
            const services = await workstationService.getAssignedServices(workstationId)
            currentWorkstationServices.value = services
        } catch (err) {
            console.error('Błąd pobierania usług stanowiska:', err)
            currentWorkstationServices.value = []
        }
    }

    const attachService = async (workstationId: number | string, assortmentId: number | string) => {
        try {
            await workstationService.attachService(workstationId, assortmentId)
            await fetchWorkstationServices(workstationId)
        } catch (err) {
            throw err
        }
    }

    const detachService = async (workstationId: number | string, assortmentId: number | string) => {
        try {
            await workstationService.detachService(workstationId, assortmentId)
            await fetchWorkstationServices(workstationId)
        } catch (err) {
            throw err
        }
    }

    return {
        items,
        myItems,
        workers,
        currentWorkstationServices,
        loading,
        error,
        fetchWorkstations,
        fetchMyWorkstations,
        fetchWorkers,
        createWorkstation,
        updateWorkstation,
        deleteWorkstation,
        fetchWorkstationServices,
        attachService,
        detachService
    }
})