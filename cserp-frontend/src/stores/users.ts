
import { defineStore } from 'pinia'
import { ref } from 'vue'
import { userService } from '@/services/userService'
import type { User } from '@/types'

export const useUsersStore = defineStore('users', () => {
    const items = ref<User[]>([])
    const loading = ref(false)
    const error = ref<string | null>(null)

    const fetchUsers = async (params: any = {}) => {
        loading.value = true
        error.value = null
        try {
            // Service teraz bezpiecznie zwraca tablicę, nawet przy paginacji
            const data = await userService.getAll(params)
            items.value = data
        } catch (err: any) {
            console.error(err)
            error.value = err.response?.data?.message || 'Błąd pobierania użytkowników'
            items.value = [] // Resetuj do pustej tablicy w przypadku błędu
        } finally {
            loading.value = false
        }
    }

    const createUser = async (data: Partial<User>) => {
        loading.value = true
        try {
            await userService.create(data)
            await fetchUsers()
        } catch (err: any) {
            throw err
        } finally {
            loading.value = false
        }
    }

    const updateUser = async (id: number | string, data: Partial<User>) => {
        loading.value = true
        try {
            await userService.update(id, data)
            await fetchUsers()
        } catch (err: any) {
            throw err
        } finally {
            loading.value = false
        }
    }

    const toggleActive = async (user: User) => {
        try {
            await userService.toggleActive(user.id)
            await fetchUsers()
        } catch (err: any) {
            throw err
        }
    }

    const deleteUser = async (id: number | string) => {
        loading.value = true
        try {
            await userService.delete(id)
            await fetchUsers()
        } catch (err: any) {
            throw err
        } finally {
            loading.value = false
        }
    }

    return {
        items,
        loading,
        error,
        fetchUsers,
        createUser,
        updateUser,
        toggleActive,
        deleteUser
    }
})
