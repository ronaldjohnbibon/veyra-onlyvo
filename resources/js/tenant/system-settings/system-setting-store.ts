import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { tenantSystemSettingService } from '@/tenant/system-settings/api/system-settings'
import type {
  SystemSettingGroup,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/shared/types/system-settings'

export const useTenantSystemSettingStore = defineStore('tenant-system-settings', () => {
  const groups = ref<SystemSettingGroup[]>([])
  const values = ref<SystemSettingsValues>({})
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      const response = await tenantSystemSettingService.index()
      groups.value = response.data.groups
      values.value = response.data.values
    } finally {
      loading.value = false
    }
  }

  const update = async (payload: SystemSettingsPayload): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      const response = await tenantSystemSettingService.update(payload)
      groups.value = response.data.groups
      values.value = response.data.values
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const uploadImage = async (key: string, image: File): Promise<string> => {
    try {
      loading.value = true
      errors.value = {}

      return (await tenantSystemSettingService.uploadImage(key, image)).data.url
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    errors,
    groups,
    index,
    loading,
    update,
    uploadImage,
    values,
  }
})
