import api from './api'
import type { User } from '@/types'

interface LoginResponse {
  token: string;
  user: User;
}

export const authService = {
  async login(email: string, password: string): Promise<LoginResponse> {
    const response = await api.post<LoginResponse>('/login', { email, password })
    return response.data
  },

  async logout(): Promise<void> {
    await api.post('/logout')
  },

  async getUser(): Promise<User> {
    const response = await api.get<User>('/user')
    return response.data
  },

  async loginPin(pin: string): Promise<LoginResponse> {
    const response = await api.post<LoginResponse>('/auth/login-pin', { pin })
    return response.data
  }
}