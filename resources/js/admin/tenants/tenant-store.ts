import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { adminTenantService } from './api/tenants'
import type { TenantParams, TenantPayload, TenantRecord } from '@/shared/types/tenants'

export const useAdminTenantStore = defineStore('admin-tenants', () => {
  const tenants = ref<TenantRecord[]>([])
  const tenant = ref<TenantRecord | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const total = ref(0)
  const params = ref<TenantParams>({
    page: 1,
    pageSize: 15,
    search: '',
    sort: 'created_at',
    direction: 'desc',
    status: '',
  })

  const index = async (newParams: Partial<TenantParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminTenantService.index(params.value)
      tenants.value = response.data ?? []
      total.value = response.pagination?.total ?? tenants.value.length
    } finally {
      loading.value = false
    }
  }

  const save = async (payload: TenantPayload, id?: string | null): Promise<TenantRecord> => {
    try {
      loading.value = true
      errors.value = {}
      const response = id
        ? await adminTenantService.update(id, payload)
        : await adminTenantService.store(payload)

      tenant.value = response.data
      await index()

      return response.data
    } catch (err) {
      // Keep validation errors keyed by field for the tenant form.
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const deactivate = async (id: string): Promise<void> => {
    try {
      loading.value = true
      tenant.value = (await adminTenantService.deactivate(id)).data
      await index()
    } finally {
      loading.value = false
    }
  }

  const reactivate = async (id: string): Promise<void> => {
    try {
      loading.value = true
      tenant.value = (await adminTenantService.reactivate(id)).data
      await index()
    } finally {
      loading.value = false
    }
  }

  const destroy = async (id: string): Promise<void> => {
    try {
      loading.value = true
      await adminTenantService.destroy(id)
      await index()
    } finally {
      loading.value = false
    }
  }

  return {
    deactivate,
    destroy,
    errors,
    index,
    loading,
    params,
    reactivate,
    save,
    tenant,
    tenants,
    total,
  }
})
