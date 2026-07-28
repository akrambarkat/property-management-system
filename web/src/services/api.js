import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import router from '@/router'
import { isMockMode, getMockResponse } from './mockProxy'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api/v1',
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json'
  },
  timeout: 5000
})

api.interceptors.request.use((config) => {
  const authStore = useAuthStore()
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (isMockMode() || !error.response) {
      const { config } = error
      if (config) {
        try {
          const mockResponse = await getMockResponse(config.method, config.url)
          return mockResponse
        } catch {}
      }
    }
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.logout()
      router.push({ name: 'Login' })
    }
    return Promise.reject(error)
  }
)

export function setAuthToken(token) {
  if (token) {
    api.defaults.headers.common['Authorization'] = `Bearer ${token}`
  } else {
    delete api.defaults.headers.common['Authorization']
  }
}

export default api
