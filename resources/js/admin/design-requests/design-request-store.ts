import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminDesignRequestService } from './api/design-requests'
import { validationErrorsFrom } from '@/shared/api/errors'
import type {
  AdminDesignRequestPayload,
  DesignRequestCommentPayload,
  DesignRequestParams,
  DesignRequestRecord,
} from '@/shared/types/design-requests'

export const useAdminDesignRequestStore = defineStore('admin-design-requests', () => {
  const requests = ref<DesignRequestRecord[]>([])
  const request = ref<DesignRequestRecord | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const total = ref(0)
  const params = ref<DesignRequestParams>({
    page: 1,
    pageSize: 15,
    search: '',
    status: '',
  })

  const index = async (newParams: Partial<DesignRequestParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminDesignRequestService.index(params.value)
      requests.value = response.data ?? []
      total.value = response.pagination?.total ?? requests.value.length
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      request.value = (await adminDesignRequestService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const update = async (
    id: string,
    payload: AdminDesignRequestPayload
  ): Promise<DesignRequestRecord> => {
    try {
      loading.value = true
      errors.value = {}
      request.value = (await adminDesignRequestService.update(id, payload)).data
      await index()
      return request.value
    } catch (err) {
      // Keep Laravel validation errors keyed by field for the review form.
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const comment = async (
    id: string,
    payload: DesignRequestCommentPayload
  ): Promise<DesignRequestRecord> => {
    try {
      loading.value = true
      errors.value = {}
      request.value = (await adminDesignRequestService.comment(id, payload)).data
      await index()
      return request.value
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    comment,
    errors,
    index,
    loading,
    params,
    request,
    requests,
    show,
    total,
    update,
  }
})
