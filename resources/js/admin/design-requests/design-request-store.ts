import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminDesignRequestService } from './api/design-requests'
import { validationErrorsFrom } from '@/shared/api/errors'
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

export const useAdminDesignRequestStore = defineStore('admin-design-requests', () => {
  const requests = ref<DesignRequestRecord[]>([])
  const request = ref<DesignRequestRecord | null>(null)
  const boardColumns = ref<DesignRequestBoardColumn[]>([])
  const workload = ref<DesignRequestWorkload | null>(null)
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

  const board = async (newParams: Partial<DesignRequestParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }
      boardColumns.value = (await adminDesignRequestService.board(params.value)).data.columns
    } finally {
      loading.value = false
    }
  }

  const loadWorkload = async (): Promise<void> => {
    try {
      loading.value = true
      workload.value = (await adminDesignRequestService.workload()).data
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
      await Promise.all([index(), board(), loadWorkload()])
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

  const convertTemplateImprovement = async (
    id: string,
    payload: DesignRequestConversionPayload
  ): Promise<DesignRequestRecord> => {
    return action(() => adminDesignRequestService.convertTemplateImprovement(id, payload))
  }

  const convertCatalogChange = async (
    id: string,
    payload: DesignRequestConversionPayload
  ): Promise<DesignRequestRecord> => {
    return action(() => adminDesignRequestService.convertCatalogChange(id, payload))
  }

  const linkCompletedWork = async (
    id: string,
    payload: DesignRequestLinkPayload
  ): Promise<DesignRequestRecord> => {
    return action(() => adminDesignRequestService.linkCompletedWork(id, payload))
  }

  const notify = async (id: string): Promise<DesignRequestRecord> => {
    return action(() => adminDesignRequestService.notify(id))
  }

  const action = async (
    callback: () => Promise<{ data: DesignRequestRecord }>
  ): Promise<DesignRequestRecord> => {
    try {
      loading.value = true
      errors.value = {}
      request.value = (await callback()).data
      await Promise.all([index(), board(), loadWorkload()])

      return request.value
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    board,
    boardColumns,
    comment,
    convertCatalogChange,
    convertTemplateImprovement,
    errors,
    index,
    linkCompletedWork,
    loadWorkload,
    loading,
    notify,
    params,
    request,
    requests,
    show,
    total,
    update,
    workload,
  }
})
