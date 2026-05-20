import http from '@/shared/api/http'
import type { LoginFormInterface } from '@/types/auth'
import type { UserInterface } from '@/types/user'

export const adminAuthService = {
  login(payload: LoginFormInterface) {
    return http.post('admin/login', payload).then((res) => res.data)
  },

  fetchUser(): Promise<UserInterface> {
    return http.get('admin/me').then((res) => res.data.data)
  },

  logout() {
    return http.post('admin/logout')
  },
}
