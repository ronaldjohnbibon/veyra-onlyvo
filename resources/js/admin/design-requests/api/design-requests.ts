import http from '@/shared/api/http'
import type {
  AdminDesignRequestPayload,
  DesignRequestBoardColumn,
  DesignRequestCommentPayload,
  DesignRequestConversionPayload,
  DesignRequestLinkPayload,
  DesignRequestParams,
  DesignRequestRecord,
  DesignRequestWorkload,
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

interface DesignRequestBoardResponse {
  data: {
    columns: DesignRequestBoardColumn[]
  }
}

interface DesignRequestWorkloadResponse {
  data: DesignRequestWorkload
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

  board(params: DesignRequestParams = {}) {
    return http
      .get<DesignRequestBoardResponse>('admin/design-requests/board', { params })
      .then((response) => response.data)
  },

  workload() {
    return http
      .get<DesignRequestWorkloadResponse>('admin/design-requests/workload')
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

  convertTemplateImprovement(id: string, payload: DesignRequestConversionPayload) {
    return http
      .post<DesignRequestResponse>(
        `admin/design-requests/${id}/convert-template-improvement`,
        payload
      )
      .then((response) => response.data)
  },

  convertCatalogChange(id: string, payload: DesignRequestConversionPayload) {
    return http
      .post<DesignRequestResponse>(`admin/design-requests/${id}/convert-catalog-change`, payload)
      .then((response) => response.data)
  },

  linkCompletedWork(id: string, payload: DesignRequestLinkPayload) {
    return http
      .post<DesignRequestResponse>(`admin/design-requests/${id}/link-completed-work`, payload)
      .then((response) => response.data)
  },

  notify(id: string) {
    return http
      .post<DesignRequestResponse>(`admin/design-requests/${id}/notify`)
      .then((response) => response.data)
  },
}
