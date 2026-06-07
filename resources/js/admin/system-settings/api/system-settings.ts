import http from '@/shared/api/http'
import type {
  SystemSettingGroup,
  SystemSettingEmailTestResult,
  SystemSettingHistoryParams,
  SystemSettingHistoryPayload,
  SystemSettingMaintenancePreview,
  SystemSettingSmtpTestResult,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/admin/system-settings/types'

interface SystemSettingsResponse {
  data: {
    groups: SystemSettingGroup[]
    values: SystemSettingsValues
    history: SystemSettingHistoryPayload
  }
}

interface SystemSettingHistoryResponse {
  data: SystemSettingHistoryPayload
}

interface SystemSettingImageUploadResponse {
  data: {
    key: string
    url: string
    path: string
  }
}

interface SystemSettingSmtpTestResponse {
  data: SystemSettingSmtpTestResult
}

interface SystemSettingEmailTestResponse {
  data: SystemSettingEmailTestResult
}

interface SystemSettingMaintenancePreviewResponse {
  data: SystemSettingMaintenancePreview
}

export const adminSystemSettingService = {
  index() {
    return http
      .get<SystemSettingsResponse>('admin/system-settings')
      .then((response) => response.data)
  },

  update(settings: SystemSettingsPayload) {
    return http
      .put<SystemSettingsResponse>('admin/system-settings', { settings })
      .then((response) => response.data)
  },

  history(params: SystemSettingHistoryParams = {}) {
    return http
      .get<SystemSettingHistoryResponse>('admin/system-settings/history', { params })
      .then((response) => response.data)
  },

  uploadImage(key: string, image: File) {
    const payload = new FormData()
    payload.append('key', key)
    payload.append('image', image)

    return http
      .post<SystemSettingImageUploadResponse>('admin/system-settings/images', payload, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then((response) => response.data)
  },

  testSmtp() {
    return http
      .post<SystemSettingSmtpTestResponse>('admin/system-settings/test-smtp')
      .then((response) => response.data)
  },

  testEmail(recipient: string) {
    return http
      .post<SystemSettingEmailTestResponse>('admin/system-settings/test-email', { recipient })
      .then((response) => response.data)
  },

  maintenancePreview(settings: SystemSettingsPayload, path = '/') {
    return http
      .post<SystemSettingMaintenancePreviewResponse>('admin/system-settings/maintenance-preview', {
        settings,
        path,
      })
      .then((response) => response.data)
  },

  restoreHistory(id: string) {
    return http
      .post<SystemSettingsResponse>(`admin/system-settings/history/${id}/restore`)
      .then((response) => response.data)
  },

  exportSettings() {
    return http
      .get<Blob>('admin/system-settings/export', {
        responseType: 'blob',
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },

  backupSettings() {
    return http
      .get<Blob>('admin/system-settings/backup', {
        responseType: 'blob',
        headers: { 'X-Silent-Request': 'true' },
      })
      .then((response) => response.data)
  },
}
