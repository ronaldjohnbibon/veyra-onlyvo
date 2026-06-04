import { defineStore } from 'pinia'
import { ref } from 'vue'
import { leadService } from '@/tenant/leads/api/leads'
import type {
  LeadFilters,
  LeadMeta,
  LeadParams,
  LeadPagination,
  LeadRecord,
  LeadStatus,
  LeadStatusCounts,
} from '@/tenant/leads/types'

const defaultFilters = (): LeadFilters => ({
  templates: [],
  cta_types: [],
  statuses: [
    { value: 'new', label: 'New' },
    { value: 'contacted', label: 'Contacted' },
    { value: 'archived', label: 'Archived' },
  ],
})

const defaultCounts = (): LeadStatusCounts => ({
  new: 0,
  contacted: 0,
  archived: 0,
})

export const useLeadStore = defineStore('tenant-leads', () => {
  const leads = ref<LeadRecord[]>([])
  const lead = ref<LeadRecord | null>(null)
  const loading = ref(false)
  const exporting = ref(false)
  const total = ref(0)
  const pagination = ref<LeadPagination | null>(null)
  const filters = ref<LeadFilters>(defaultFilters())
  const statusCounts = ref<LeadStatusCounts>(defaultCounts())
  const params = ref<LeadParams>({
    page: 1,
    pageSize: 15,
    search: '',
    status: 'new',
    sort: 'created_at',
    direction: 'desc',
  })

  const applyMeta = (meta?: LeadMeta): void => {
    if (!meta) return

    filters.value = meta.filters ?? defaultFilters()
    statusCounts.value = meta.status_counts ?? defaultCounts()
  }

  const index = async (newParams: Partial<LeadParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const data = await leadService.index(params.value)
      leads.value = data.data ?? []
      pagination.value = data.pagination ?? null
      total.value = data.pagination?.total ?? leads.value.length
      applyMeta(data.meta)
    } finally {
      loading.value = false
    }
  }

  const show = async (id: string): Promise<void> => {
    try {
      loading.value = true
      lead.value = (await leadService.show(id)).data
    } finally {
      loading.value = false
    }
  }

  const updateStatus = async (id: string, status: LeadStatus): Promise<void> => {
    try {
      loading.value = true
      lead.value = (await leadService.updateStatus(id, status)).data
      await index()
    } finally {
      loading.value = false
    }
  }

  const exportCsv = async (): Promise<Blob> => {
    try {
      exporting.value = true
      return await leadService.export(params.value)
    } finally {
      exporting.value = false
    }
  }

  const reset = async (): Promise<void> => {
    await index({
      page: 1,
      search: '',
      status: '',
      template_id: '',
      cta_type: '',
      from: '',
      to: '',
      sort: 'created_at',
      direction: 'desc',
    })
  }

  return {
    exportCsv,
    exporting,
    filters,
    index,
    lead,
    leads,
    loading,
    pagination,
    params,
    reset,
    show,
    statusCounts,
    total,
    updateStatus,
  }
})
