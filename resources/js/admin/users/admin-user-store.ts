import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { adminUserService } from '@/admin/users/api/admin-users'
import type {
  AdminUser,
  AdminUserParams,
  AdminUserPasswordReset,
  AdminUserPayload,
} from '@/admin/users/types'

export const useAdminUserStore = defineStore('admin-users', () => {
  const users = ref<AdminUser[]>([])
  const user = ref<AdminUser | null>(null)
  const reset = ref<AdminUserPasswordReset | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const total = ref(0)
  const params = ref<AdminUserParams>({
    page: 1,
    pageSize: 15,
    search: '',
    sort: 'created_at',
    direction: 'desc',
    status: '',
  })

  const index = async (newParams: Partial<AdminUserParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminUserService.index(params.value)
      users.value = response.data ?? []
      total.value = response.pagination?.total ?? users.value.length
    } finally {
      loading.value = false
    }
  }

  const save = async (
    payload: AdminUserPayload,
    id?: number | string | null
  ): Promise<AdminUser> => {
    try {
      loading.value = true
      errors.value = {}

      const response = id
        ? await adminUserService.update(id, payload)
        : await adminUserService.store(payload)

      user.value = response.data
      await index()

      return response.data
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deactivate = async (id: number | string): Promise<void> => {
    try {
      loading.value = true
      user.value = (await adminUserService.deactivate(id)).data
      await index()
    } finally {
      loading.value = false
    }
  }

  const reactivate = async (id: number | string): Promise<void> => {
    try {
      loading.value = true
      user.value = (await adminUserService.reactivate(id)).data
      await index()
    } finally {
      loading.value = false
    }
  }

  const generatePasswordReset = async (id: number | string): Promise<AdminUserPasswordReset> => {
    try {
      loading.value = true
      reset.value = (await adminUserService.passwordReset(id)).data
      await index()

      return reset.value
    } finally {
      loading.value = false
    }
  }

  const destroy = async (id: number | string): Promise<void> => {
    try {
      loading.value = true
      await adminUserService.destroy(id)
      await index()
    } finally {
      loading.value = false
    }
  }

  return {
    deactivate,
    destroy,
    errors,
    generatePasswordReset,
    index,
    loading,
    params,
    reactivate,
    reset,
    save,
    total,
    user,
    users,
  }
})
