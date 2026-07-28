import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(JSON.parse(localStorage.getItem('user') || 'null'))

  const isAuthenticated = computed(() => !!token.value)
  const currentUser = computed(() => user.value)
  const isSuperAdmin = computed(() => user.value?.role === 'super_admin')
  const isEmployee = computed(() => user.value?.role === 'employee')
  const isGuard = computed(() => user.value?.role === 'guard')

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('token', newToken)
    localStorage.setItem('user', JSON.stringify(newUser))
  }

  function setUser(newUser) {
    user.value = newUser
    localStorage.setItem('user', JSON.stringify(newUser))
  }

  function logout() {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
  }

  return {
    token,
    user,
    isAuthenticated,
    currentUser,
    isSuperAdmin,
    isEmployee,
    isGuard,
    setAuth,
    setUser,
    logout
  }
})
