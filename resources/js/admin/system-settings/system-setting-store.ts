import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { adminSystemSettingService } from '@/admin/system-settings/api/system-settings'
import type {
  SystemSettingGroup,
  SystemSettingHistoryRecord,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/shared/types/system-settings'

export const useAdminSystemSettingStore = defineStore('admin-system-settings', () => {
  const groups = ref<SystemSettingGroup[]>([])
  const history = ref<SystemSettingHistoryRecord[]>([])
  const values = ref<SystemSettingsValues>({})
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      const response = await adminSystemSettingService.index()
      groups.value = response.data.groups
      history.value = response.data.history
      values.value = response.data.values
    } finally {
      loading.value = false
    }
  }

  const update = async (payload: SystemSettingsPayload): Promise<void> => {
    try {
      loading.value = true
      errors.value = {}
      const response = await adminSystemSettingService.update(payload)
      groups.value = response.data.groups
      history.value = response.data.history
      values.value = response.data.values
    } catch (err) {
      // Keep grouped Laravel validation errors beside their fields.
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

      return (await adminSystemSettingService.uploadImage(key, image)).data.url
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
    history,
    index,
    loading,
    update,
    uploadImage,
    values,
  }
})
