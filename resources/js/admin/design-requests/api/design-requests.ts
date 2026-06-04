import http from '@/shared/api/http'
import type {
  AdminDesignRequestPayload,
  DesignRequestCommentPayload,
  DesignRequestParams,
  DesignRequestRecord,
} from '@/shared/types/design-requests'

interface DesignRequestCollectionResponse {
  data: DesignRequestRecord[]
  pagination?: {
    total: number
  }
}

interface DesignRequestResponse {
  data: DesignRequestRecord
}

export const adminDesignRequestService = {
  index(params: DesignRequestParams = {}) {
    return http
      .get<DesignRequestCollectionResponse>('admin/design-requests', { params })
      .then((response) => response.data)
  },

  show(id: string) {
    return http
      .get<DesignRequestResponse>(`admin/design-requests/${id}`)
      .then((response) => response.data)
  },

  update(id: string, payload: AdminDesignRequestPayload) {
    return http
      .put<DesignRequestResponse>(`admin/design-requests/${id}`, payload)
      .then((response) => response.data)
  },

  comment(id: string, payload: DesignRequestCommentPayload) {
    return http
      .post<DesignRequestResponse>(`admin/design-requests/${id}/comments`, payload)
      .then((response) => response.data)
  },
}
