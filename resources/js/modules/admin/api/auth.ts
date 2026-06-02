import http from '@/shared/api/http'
import type { LoginFormInterface } from '@/types/auth'
import type { UserInterface } from '@/types/user'

interface ApiEnvelope<T> {
  data: T
  message?: string
}

interface AdminLoginData {
  user: UserInterface
  admin_token: string
}

export const adminAuthService = {
  login(payload: LoginFormInterface): Promise<ApiEnvelope<AdminLoginData>> {
    return http.post<ApiEnvelope<AdminLoginData>>('admin/login', payload).then((res) => res.data)
  },

  fetchUser(): Promise<UserInterface> {
    return http.get<ApiEnvelope<UserInterface>>('admin/me').then((res) => res.data.data)
  },

  logout() {
    return http.post('admin/logout')
  },
}
