import http from '@/shared/api/http'
import type {
  ForgotPasswordFormInterface,
  LoginFormInterface,
  RegisterFormInterface,
  ResetPasswordFormInterface,
} from '@/types/auth'
import type { UserInterface } from '@/types/user'

export const authService = {
  register(payload: RegisterFormInterface) {
    return http.post('register', payload).then((res) => res.data)
  },

  login(payload: LoginFormInterface) {
    return http.post('login', payload).then((res) => res.data)
  },

  fetchUser(): Promise<UserInterface> {
    return http.get('me').then((res) => res.data.data)
  },

  logout() {
    return http.post('logout')
  },

  forgotPassword(payload: ForgotPasswordFormInterface) {
    return http.post('forgot-password', payload).then((res) => res.data)
  },

  resetPassword(payload: ResetPasswordFormInterface) {
    return http.post('reset-password', payload).then((res) => res.data)
  },
}
