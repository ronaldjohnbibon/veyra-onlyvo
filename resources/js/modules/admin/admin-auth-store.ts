import { defineStore } from 'pinia'
import { computed, reactive, ref } from 'vue'
import { AxiosError } from 'axios'
import router from '@/router'
import { adminAuthService } from '@/modules/admin/api/auth'
import type { LoginFormInterface } from '@/types/auth'
import type { UserInterface } from '@/types/user'

interface ApiErrorResponse {
  errors?: Record<string, string[]>
  message?: string
}

export const useAdminAuthStore = defineStore('admin-auth', () => {
  const user = ref<UserInterface | null>(null)
  const token = ref<string | null>(localStorage.getItem('admin_token'))
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const message = ref('')

  const loginForm = reactive<LoginFormInterface>({
    email: '',
    password: '',
  })

  const setToken = (newToken: string | null): void => {
    token.value = newToken

    if (newToken) {
      localStorage.setItem('admin_token', newToken)
      return
    }

    localStorage.removeItem('admin_token')
  }

  const login = async (): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      message.value = ''

      const data = await adminAuthService.login(loginForm)
      setToken(data.data.token)
      user.value = data.data.user

      await router.push({ name: 'admin.dashboard' })
    } catch (err) {
      const axiosError = err as AxiosError<ApiErrorResponse>
      errors.value = axiosError.response?.data?.errors ?? {}
      message.value = axiosError.response?.data?.message ?? 'Unable to log in.'
    } finally {
      loading.value = false
    }
  }

  // Loads the current admin user when a token exists.
  const fetchUser = async (): Promise<void> => {
    if (!token.value) return

    try {
      loading.value = true
      user.value = await adminAuthService.fetchUser()
    } catch {
      setToken(null)
      user.value = null
    } finally {
      loading.value = false
    }
  }

  const logout = async (): Promise<void> => {
    try {
      loading.value = true
      await adminAuthService.logout()
    } finally {
      setToken(null)
      user.value = null
      loading.value = false
      await router.push({ name: 'AdminLogin' })
    }
  }

  const init = async (): Promise<void> => {
    if (token.value && !user.value) {
      await fetchUser()
    }
  }

  return {
    isAuthenticated: computed(() => !!token.value),
    token,
    user,
    loading,
    errors,
    message,
    loginForm,
    init,
    login,
    fetchUser,
    logout,
  }
})
