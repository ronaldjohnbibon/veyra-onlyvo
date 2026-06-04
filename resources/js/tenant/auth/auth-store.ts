import { defineStore } from 'pinia'
import { computed, reactive, ref } from 'vue'
import router from '@/router'
import { authService } from '@/tenant/auth/api/auth'
import { apiMessageFrom, validationErrorsFrom } from '@/shared/api/errors'
import type {
  ForgotPasswordFormInterface,
  LoginFormInterface,
  RegisterFormInterface,
  ResetPasswordFormInterface,
} from '@/shared/types/auth'
import type { UserInterface } from '@/shared/types/user'

export const useAuthStore = defineStore('tenant-auth', () => {
  const user = ref<UserInterface | null>(null)
  const token = ref<string | null>(localStorage.getItem('tenant_token'))
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const message = ref('')

  const registerForm = reactive<RegisterFormInterface>({
    email: '',
    phone: '',
    name: '',
    password: '',
    password_confirmation: '',
  })

  const loginForm = reactive<LoginFormInterface>({
    email: '',
    password: '',
  })

  const forgotPasswordForm = reactive<ForgotPasswordFormInterface>({
    email: '',
  })

  const resetPasswordForm = reactive<ResetPasswordFormInterface>({
    token: '',
    email: '',
    password: '',
    password_confirmation: '',
  })

  const setToken = (newToken: string | null): void => {
    token.value = newToken

    if (newToken) {
      localStorage.setItem('tenant_token', newToken)
      return
    }

    localStorage.removeItem('tenant_token')
  }

  const redirectToTenantLogin = async (subdomain?: string): Promise<void> => {
    if (!subdomain) {
      await router.push({ name: 'TenantLogin' })
      return
    }

    const { protocol, hostname, port } = window.location
    const localHosts = ['localhost', '127.0.0.1']
    const baseHost = localHosts.includes(hostname)
      ? 'localhost'
      : hostname.endsWith('.localhost')
        ? 'localhost'
        : hostname.split('.').slice(1).join('.') || hostname
    const targetPort = port ? `:${port}` : ''

    window.location.assign(`${protocol}//${subdomain}.${baseHost}${targetPort}/login`)
  }

  const login = async (): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      message.value = ''

      const data = await authService.login(loginForm)
      setToken(data.data.tenant_token)
      user.value = data.data.user

      await router.push({ name: 'tenant.dashboard' })
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to log in.')
    } finally {
      loading.value = false
    }
  }

  const register = async (): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      message.value = ''

      const data = await authService.register(registerForm)
      await redirectToTenantLogin(data.data?.subdomain)
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to create account.')
    } finally {
      loading.value = false
    }
  }

  // Loads the current tenant user when a token exists.
  const fetchUser = async (): Promise<void> => {
    if (!token.value) return

    try {
      loading.value = true
      user.value = await authService.fetchUser()
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
      await authService.logout()
    } finally {
      setToken(null)
      user.value = null
      loading.value = false
      await router.push({ name: 'TenantLogin' })
    }
  }

  const init = async (): Promise<void> => {
    if (token.value && !user.value) {
      await fetchUser()
    }
  }

  const forgotPassword = async (): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      message.value = ''

      const data = await authService.forgotPassword(forgotPasswordForm)
      message.value = data.message ?? 'Password reset link sent.'
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to send reset link.')
    } finally {
      loading.value = false
    }
  }

  const resetPassword = async (): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      message.value = ''

      const data = await authService.resetPassword(resetPasswordForm)
      message.value = data.message ?? 'Password reset successfully.'
      await router.push({ name: 'TenantLogin' })
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to reset password.')
    } finally {
      loading.value = false
    }
  }

  return {
    isAuthenticated: computed(() => !!token.value),
    token,
    user,
    loading,
    errors,
    message,
    registerForm,
    loginForm,
    forgotPasswordForm,
    resetPasswordForm,
    init,
    register,
    login,
    fetchUser,
    logout,
    forgotPassword,
    resetPassword,
  }
})
