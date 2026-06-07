import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminLeadService } from '@/admin/leads/api/leads'
import type {
  AdminLead,
  AdminLeadMeta,
  AdminLeadPagination,
  AdminLeadParams,
  AdminLeadStatus,
} from '@/admin/leads/types'

export const useAdminLeadStore = defineStore('admin-leads', () => {
  const leads = ref<AdminLead[]>([])
  const meta = ref<AdminLeadMeta | null>(null)
  const loading = ref(false)
  const exporting = ref(false)
  const total = ref(0)
  const pagination = ref<AdminLeadPagination | null>(null)
  const params = ref<AdminLeadParams>({
    page: 1,
    pageSize: 15,
    search: '',
    sort: 'created_at',
    direction: 'desc',
    status: '',
    tenant_id: '',
    template_id: '',
    cta_type: '',
    from: '',
    to: '',
  })

  const index = async (newParams: Partial<AdminLeadParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const response = await adminLeadService.index(params.value)
      leads.value = response.data ?? []
      meta.value = response.meta
      pagination.value = response.pagination ?? null
      total.value = response.pagination?.total ?? leads.value.length
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id: string, status: AdminLeadStatus): Promise<void> => {
    try {
      loading.value = true
      await adminLeadService.updateStatus(id, status)
      await index()
    } finally {
      loading.value = false
    }
  }

  const exportLeads = async (): Promise<Blob> => {
    try {
      exporting.value = true
      return await adminLeadService.export(params.value)
    } finally {
      exporting.value = false
    }
  }

  return {
    exportLeads,
    exporting,
    index,
    leads,
    loading,
    meta,
    pagination,
    params,
    total,
    updateStatus,
  }
})
