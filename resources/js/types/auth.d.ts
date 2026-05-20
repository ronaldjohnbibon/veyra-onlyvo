export interface LoginFormInterface {
  email: string
  password: string
}

export interface RegisterFormInterface {
  email: string
  password: string
  password_confirmation: string
  phone: string | number
  name: string
}

export interface ForgotPasswordFormInterface {
  email: string
}

export interface ResetPasswordFormInterface {
  token: string
  email: string
  password: string
  password_confirmation: string
}
