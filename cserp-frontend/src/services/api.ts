import axios, { type AxiosInstance, type InternalAxiosRequestConfig, type AxiosError } from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'

const api: AxiosInstance = axios.create({
  baseURL: '/api', // Zmienione na relatywne dla Proxy
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  timeout: 10000
})

// Request interceptor
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    const token = localStorage.getItem('auth_token')

    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`
    }

    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => {
    return response
  },
  async (error: AxiosError) => {
    const originalRequest = error.config

    if (error.response?.status === 401 && originalRequest && !(originalRequest as any)._retry) {
      (originalRequest as any)._retry = true

      localStorage.removeItem('auth_token')

      const authStore = useAuthStore()
      authStore.token = null
      authStore.user = null

      if (router.currentRoute.value.name !== 'Login') {
        router.push({
          name: 'Login',
          query: { redirect: router.currentRoute.value.fullPath }
        })
      }

      return Promise.reject(error)
    }

    // Inne błędy
    if (error.response?.status === 403) {
      console.error('Brak uprawnień do tego zasobu')
    }
    if (error.response?.status === 419) {
      console.error('Token CSRF wygasł - odśwież stronę')
    }
    if (error.response?.status === 500) {
      console.error('Błąd serwera:', error.response.data)
    }

    return Promise.reject(error)
  }
)

export default api