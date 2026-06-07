import { defineStore } from 'pinia'
import { ref } from 'vue'
import { validationErrorsFrom } from '@/shared/api/errors'
import { adminSystemSettingService } from '@/admin/system-settings/api/system-settings'
import { useFieldDescriptions } from '@/shared/composables/useFieldDescriptions'
import type {
  SystemSettingEmailTestResult,
  SystemSettingGroup,
  SystemSettingHistoryParams,
  SystemSettingHistoryRecord,
  SystemSettingMaintenancePreview,
  SystemSettingSmtpTestResult,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/admin/system-settings/types'

export const useAdminSystemSettingStore = defineStore('admin-system-settings', () => {
  const { setShowFieldDescriptions } = useFieldDescriptions()
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
  const operationLoading = ref(false)
  const smtpTestResult = ref<SystemSettingSmtpTestResult | null>(null)
  const emailTestResult = ref<SystemSettingEmailTestResult | null>(null)
  const maintenancePreviewResult = ref<SystemSettingMaintenancePreview | null>(null)
  const errors = ref<Record<string, string[]>>({})

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      const response = await adminSystemSettingService.index()
      groups.value = response.data.groups
      history.value = response.data.history.data
      historyTotal.value = response.data.history.pagination.total
      values.value = response.data.values
      setShowFieldDescriptions(values.value['general.show_field_descriptions'])
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
      history.value = response.data.history.data
      historyTotal.value = response.data.history.pagination.total
      values.value = response.data.values
      setShowFieldDescriptions(values.value['general.show_field_descriptions'])
    } catch (err) {
      // Keep grouped Laravel validation errors beside their fields.
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
      const response = await adminSystemSettingService.history(historyParams.value)
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

      return (await adminSystemSettingService.uploadImage(key, image)).data.url
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const testSmtp = async (): Promise<SystemSettingSmtpTestResult> => {
    try {
      operationLoading.value = true
      const response = await adminSystemSettingService.testSmtp()
      smtpTestResult.value = response.data

      return response.data
    } finally {
      operationLoading.value = false
    }
  }

  const testEmail = async (recipient: string): Promise<SystemSettingEmailTestResult> => {
    try {
      operationLoading.value = true
      errors.value = {}
      const response = await adminSystemSettingService.testEmail(recipient)
      emailTestResult.value = response.data

      return response.data
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      operationLoading.value = false
    }
  }

  const previewMaintenance = async (
    payload: SystemSettingsPayload,
    path: string
  ): Promise<SystemSettingMaintenancePreview> => {
    try {
      operationLoading.value = true
      const response = await adminSystemSettingService.maintenancePreview(payload, path)
      maintenancePreviewResult.value = response.data

      return response.data
    } finally {
      operationLoading.value = false
    }
  }

  const restoreHistory = async (id: string): Promise<void> => {
    try {
      operationLoading.value = true
      const response = await adminSystemSettingService.restoreHistory(id)
      groups.value = response.data.groups
      history.value = response.data.history.data
      historyTotal.value = response.data.history.pagination.total
      values.value = response.data.values
      setShowFieldDescriptions(values.value['general.show_field_descriptions'])
    } finally {
      operationLoading.value = false
    }
  }

  const exportSettings = (): Promise<Blob> => adminSystemSettingService.exportSettings()

  const backupSettings = (): Promise<Blob> => adminSystemSettingService.backupSettings()

  return {
    backupSettings,
    emailTestResult,
    errors,
    exportSettings,
    groups,
    history,
    historyParams,
    historyTotal,
    index,
    loadHistory,
    loading,
    maintenancePreviewResult,
    operationLoading,
    previewMaintenance,
    restoreHistory,
    smtpTestResult,
    testEmail,
    testSmtp,
    update,
    uploadImage,
    values,
  }
})
