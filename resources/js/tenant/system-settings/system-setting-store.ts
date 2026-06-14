import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { tenantSystemSettingService } from '@/tenant/system-settings/api/system-settings'
import type {
  SystemSettingGroup,
  SystemSettingHistoryParams,
  SystemSettingHistoryRecord,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/tenant/system-settings/types'

export const useTenantSystemSettingStore = defineStore('tenant-system-settings', () => {
  const groups = ref<SystemSettingGroup[]>([])
  const history = ref<SystemSettingHistoryRecord[]>([])
  const historyTotal = ref(0)
  const historyParams = ref<SystemSettingHistoryParams>({
    page: 1,
    pageSize: 15,
    search: '',
    sort: 'changed_at',
    direction: 'desc',
    action: '',
    setting_key: '',
  })
  const values = ref<SystemSettingsValues>({})
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})

  const cleanHistoryParams = (value: SystemSettingHistoryParams): SystemSettingHistoryParams =>
    Object.fromEntries(
      Object.entries(value).filter(
        ([, entry]) => entry !== '' && entry !== null && entry !== undefined
      )
    ) as SystemSettingHistoryParams

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      const response = await tenantSystemSettingService.index()
      groups.value = response.data.groups
      history.value = response.data.history.data
      historyTotal.value = response.data.history.pagination.total
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
      history.value = response.data.history.data
      historyTotal.value = response.data.history.pagination.total
      values.value = response.data.values
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const loadHistory = async (
    newParams: Partial<SystemSettingHistoryParams> = {}
  ): Promise<void> => {
    try {
      loading.value = true
      historyParams.value = { ...historyParams.value, ...newParams }
      const response = await tenantSystemSettingService.history(
        cleanHistoryParams(historyParams.value)
      )
      history.value = response.data.data
      historyTotal.value = response.data.pagination.total
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
    history,
    historyParams,
    historyTotal,
    index,
    loadHistory,
    loading,
    update,
    uploadImage,
    values,
  }
})
