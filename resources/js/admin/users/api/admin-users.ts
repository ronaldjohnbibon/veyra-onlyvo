import http from '@/shared/api/http'
import type {
  AdminUser,
  AdminUserParams,
  AdminUserPasswordReset,
  AdminUserPayload,
} from '@/admin/users/types'

interface AdminUserCollectionResponse {
  data: AdminUser[]
  pagination?: {
    total: number
  }
}

interface AdminUserResponse {
  data: AdminUser
}

interface AdminUserPasswordResetResponse {
  data: AdminUserPasswordReset
}

export const adminUserService = {
  index(params: AdminUserParams = {}) {
    return http
      .get<AdminUserCollectionResponse>('admin/admin-users', { params })
      .then((response) => response.data)
  },

  store(payload: AdminUserPayload) {
    return http
      .post<AdminUserResponse>('admin/admin-users', payload)
      .then((response) => response.data)
  },

  update(id: number | string, payload: AdminUserPayload) {
    return http
      .put<AdminUserResponse>(`admin/admin-users/${id}`, payload)
      .then((response) => response.data)
  },

  deactivate(id: number | string) {
    return http
      .post<AdminUserResponse>(`admin/admin-users/${id}/deactivate`)
      .then((response) => response.data)
  },

  reactivate(id: number | string) {
    return http
      .post<AdminUserResponse>(`admin/admin-users/${id}/reactivate`)
      .then((response) => response.data)
  },

  passwordReset(id: number | string) {
    return http
      .post<AdminUserPasswordResetResponse>(`admin/admin-users/${id}/password-reset`)
      .then((response) => response.data)
  },

  destroy(id: number | string) {
    return http.delete(`admin/admin-users/${id}`).then((response) => response.data)
  },
}
