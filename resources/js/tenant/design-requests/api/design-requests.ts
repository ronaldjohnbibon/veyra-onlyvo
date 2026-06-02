import http from '@/shared/api/http'
import type {
  DesignRequestParams,
  DesignRequestPayload,
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

const toFormData = (payload: DesignRequestPayload): FormData => {
  const formData = new FormData()

  formData.append('title', payload.title)
  formData.append('description', payload.description)
  if (payload.notes) formData.append('notes', payload.notes)
  if (payload.mockup_concept) formData.append('mockup_concept', payload.mockup_concept)

  payload.reference_links.forEach((link, index) => {
    formData.append(`reference_links[${index}]`, link)
  })

  payload.files?.forEach((file, index) => {
    formData.append(`files[${index}]`, file)
  })

  return formData
}

export const designRequestService = {
  index(params: DesignRequestParams = {}) {
    return http
      .get<DesignRequestCollectionResponse>('design-requests', { params })
      .then((response) => response.data)
  },

  show(id: string) {
    return http
      .get<DesignRequestResponse>(`design-requests/${id}`)
      .then((response) => response.data)
  },

  store(payload: DesignRequestPayload) {
    return http
      .post<DesignRequestResponse>('design-requests', toFormData(payload), {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      .then((response) => response.data)
  },
}
