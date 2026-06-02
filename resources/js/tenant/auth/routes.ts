import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'TenantLogin',
    component: () => import('@/tenant/auth/pages/LoginPage.vue'),
    meta: {
      title: 'Login - Login Here',
      description: 'Login Your Account',
    },
  },
  {
    path: '/register',
    name: 'TenantRegister',
    component: () => import('@/tenant/auth/pages/RegisterPage.vue'),
    meta: {
      title: 'Registration - Register Here',
      description: 'Register Your Account',
    },
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/tenant/auth/pages/ForgotPasswordPage.vue'),
    meta: {
      title: 'Forgot Password',
      description: 'Request a password reset link',
    },
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('@/tenant/auth/pages/ResetPasswordPage.vue'),
    meta: {
      title: 'Reset Password',
      description: 'Reset your account password',
    },
  },
]

export default routes
