import http from '@/shared/api/http'
import type {
  SystemSettingGroup,
  SystemSettingHistoryRecord,
  SystemSettingsPayload,
  SystemSettingsValues,
} from '@/shared/types/system-settings'

interface SystemSettingsResponse {
  data: {
    groups: SystemSettingGroup[]
    values: SystemSettingsValues
    history: SystemSettingHistoryRecord[]
  }
}

interface SystemSettingImageUploadResponse {
  data: {
    key: string
    url: string
    path: string
  }
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
}
