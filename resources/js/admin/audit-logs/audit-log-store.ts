import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminAuditLogService } from '@/admin/audit-logs/api/audit-logs'
import type { AuditLogParams, AuditLogRecord } from '@/admin/audit-logs/types'

export const useAdminAuditLogStore = defineStore('admin-audit-logs', () => {
  const logs = ref<AuditLogRecord[]>([])
  const selected = ref<AuditLogRecord | null>(null)
  const loading = ref(false)
  const exporting = ref(false)
  const total = ref(0)
  const params = ref<AuditLogParams>({
    page: 1,
    pageSize: 15,
    search: '',
    actor: '',
    entity_type: '',
    action: '',
    ip_address: '',
    date_from: '',
    date_to: '',
    sort: 'occurred_at',
    direction: 'desc',
  })

  const index = async (newParams: Partial<AuditLogParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminAuditLogService.index(params.value)
      logs.value = response.data ?? []
      total.value = response.pagination?.total ?? logs.value.length
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      selected.value = (await adminAuditLogService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const exportLogs = async (): Promise<Blob> => {
    try {
      exporting.value = true
      return await adminAuditLogService.export(params.value)
    } finally {
      exporting.value = false
    }
  }

  return {
    exporting,
    exportLogs,
    index,
    loading,
    logs,
    params,
    selected,
    show,
    total,
  }
})
