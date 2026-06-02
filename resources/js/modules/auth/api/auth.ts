import http from '@/shared/api/http'
import type {
  ForgotPasswordFormInterface,
  LoginFormInterface,
  RegisterFormInterface,
  ResetPasswordFormInterface,
} from '@/types/auth'
import type { UserInterface } from '@/types/user'

interface ApiEnvelope<T> {
  data: T
  message?: string
}

interface TenantRegisterData {
  subdomain?: string
}

interface TenantLoginData {
  user: UserInterface
  tenant_token: string
}

export const authService = {
  register(payload: RegisterFormInterface): Promise<ApiEnvelope<TenantRegisterData>> {
    return http.post<ApiEnvelope<TenantRegisterData>>('register', payload).then((res) => res.data)
  },

  login(payload: LoginFormInterface): Promise<ApiEnvelope<TenantLoginData>> {
    return http.post<ApiEnvelope<TenantLoginData>>('login', payload).then((res) => res.data)
  },

  fetchUser(): Promise<UserInterface> {
    return http.get<ApiEnvelope<UserInterface>>('me').then((res) => res.data.data)
  },

  logout() {
    return http.post('logout')
  },

  forgotPassword(payload: ForgotPasswordFormInterface): Promise<ApiEnvelope<null>> {
    return http.post<ApiEnvelope<null>>('forgot-password', payload).then((res) => res.data)
  },

  resetPassword(payload: ResetPasswordFormInterface): Promise<ApiEnvelope<null>> {
    return http.post<ApiEnvelope<null>>('reset-password', payload).then((res) => res.data)
  },
}
