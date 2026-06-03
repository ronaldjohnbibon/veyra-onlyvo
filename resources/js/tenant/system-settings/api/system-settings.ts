import http from '@/shared/api/http'
import type {
  SystemSettingGroup,
  SystemSettingHistoryParams,
  SystemSettingHistoryPayload,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/tenant/system-settings/types'

interface TenantSystemSettingsResponse {
  data: {
    groups: SystemSettingGroup[]
    history: SystemSettingHistoryPayload
    values: SystemSettingsValues
  }
}

interface TenantSystemSettingHistoryResponse {
  data: SystemSettingHistoryPayload
}

interface TenantSystemSettingImageUploadResponse {
  data: {
    key: string
    url: string
    path: string
  }
}

export const tenantSystemSettingService = {
  index() {
    return http
      .get<TenantSystemSettingsResponse>('system-settings')
      .then((response) => response.data)
  },

  update(settings: SystemSettingsPayload) {
    return http
      .put<TenantSystemSettingsResponse>('system-settings', { settings })
      .then((response) => response.data)
  },

  history(params: SystemSettingHistoryParams = {}) {
    return http
      .get<TenantSystemSettingHistoryResponse>('system-settings/history', { params })
      .then((response) => response.data)
  },

  uploadImage(key: string, image: File) {
    const payload = new FormData()
    payload.append('key', key)
    payload.append('image', image)

    return http
      .post<TenantSystemSettingImageUploadResponse>('system-settings/images', payload, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
      .then((response) => response.data)
  },
}
