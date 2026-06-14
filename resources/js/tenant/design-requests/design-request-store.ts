import { defineStore } from 'pinia'
import { ref } from 'vue'
import { designRequestService } from './api/design-requests'
import { validationErrorsFrom } from '@/shared/api/errors'
import type {
  DesignRequestActionPayload,
  DesignRequestCommentPayload,
  DesignRequestParams,
  DesignRequestPayload,
  DesignRequestRecord,
} from '@/shared/types/design-requests'

export const useDesignRequestStore = defineStore('tenant-design-requests', () => {
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

  const cleanParams = (value: DesignRequestParams): DesignRequestParams =>
    Object.fromEntries(
      Object.entries(value).filter(
        ([, entry]) => entry !== '' && entry !== null && entry !== undefined
      )
    ) as DesignRequestParams

  const index = async (newParams: Partial<DesignRequestParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await designRequestService.index(cleanParams(params.value))
      requests.value = response.data ?? []
      total.value = response.pagination?.total ?? requests.value.length
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      request.value = (await designRequestService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const store = async (payload: DesignRequestPayload): Promise<DesignRequestRecord> => {
    try {
      loading.value = true
      errors.value = {}
      request.value = (await designRequestService.store(payload)).data
      await index({ page: 1 })
      return request.value
    } catch (err) {
      // Keep Laravel validation errors keyed by field for the request form.
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
      request.value = (await designRequestService.comment(id, payload)).data
      await index()
      return request.value
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const action = async (
    id: string,
    payload: DesignRequestActionPayload
  ): Promise<DesignRequestRecord> => {
    try {
      loading.value = true
      errors.value = {}
      request.value = (await designRequestService.action(id, payload)).data
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
    action,
    comment,
    errors,
    index,
    loading,
    params,
    request,
    requests,
    show,
    store,
    total,
  }
})
