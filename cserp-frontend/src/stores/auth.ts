import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authService } from '@/services/authService'
import { UserRole } from '@/enums/UserRole' // Upewnij się, że masz ten enum
import router from '@/router'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  const canManageSystem = computed(() => {
    if (!user.value?.role) return false
    const role = user.value.role.toUpperCase()
    return [
      UserRole.ADMIN,
      UserRole.PROJECT_MANAGER,
      UserRole.TRADER,
      UserRole.LOGISTICS_SPECIALIST
    ].includes(role)
  })

  const isWorker = computed(() => {
    if (!user.value?.role) return false
    return user.value.role.toUpperCase() === UserRole.PRODUCTION_EMPLOYEE
  })

  const isAdmin = computed(() => {
    if (!user.value?.role) return false
    return user.value.role.toUpperCase() === UserRole.ADMIN
  })

  const initialize = async (): Promise<boolean> => {
    if (!token.value) return false
    try {
      await fetchUser()
      return true
    } catch (err) {
      await logout()
      return false
    }
  }

  const login = async (email: string, password: string): Promise<boolean> => {
    loading.value = true
    error.value = null
    try {
      const data = await authService.login(email, password)
      if (!data.token || !data.user) throw new Error('Błąd odpowiedzi')

      token.value = data.token
      user.value = data.user

      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('login_mode', 'standard')

      return true
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Błąd logowania'
      return false
    } finally {
      loading.value = false
    }
  }

  const loginWithPin = async (pin: string): Promise<boolean> => {
    loading.value = true
    error.value = null
    try {
      const data = await authService.loginPin(pin)

      if (!data.token || !data.user) throw new Error('Błąd odpowiedzi')

      token.value = data.token
      user.value = data.user

      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('login_mode', 'pin')

      return true
    } catch (err: any) {
      console.error(err)
      error.value = err.response?.data?.message || 'Nieprawidłowy kod PIN'
      return false
    } finally {
      loading.value = false
    }
  }

  const logout = async () => {
    try {
      const targetRoute = 'Login'
      // const currentMode = localStorage.getItem('login_mode')

      // if (!currentMode || currentMode === 'pin') {
      //   targetRoute = 'LoginPin'
      // } else {
      //   targetRoute = 'Login'
      // }

      if (token.value) await authService.logout()

      router.push({ name: targetRoute })

    } catch (err) {
      console.error('Logout error', err)
    } finally {
      token.value = null
      user.value = null
      error.value = null
      localStorage.removeItem('auth_token')
    }
  }

  const fetchUser = async () => {
    if (!token.value) throw new Error('Brak tokena')
    const userData = await authService.getUser()
    user.value = userData
    return userData
  }

  const clearSession = () => {
    token.value = null
    user.value = null
    error.value = null
    localStorage.removeItem('auth_token')
  }

  return {
    user,
    token,
    loading,
    error,
    isAuthenticated,
    isAdmin,
    canManageSystem,
    isWorker,
    initialize,
    login,
    loginWithPin,
    logout,
    fetchUser,
    clearSession
  }
})