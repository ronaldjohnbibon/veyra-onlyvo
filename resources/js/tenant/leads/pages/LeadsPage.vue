<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shared/components/ui/table'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useLeadStore } from '@/tenant/leads/lead-store'
import type { LeadParams, LeadRecord, LeadStatus } from '@/tenant/leads/types'
import { Archive, CheckCircle2, Download, ExternalLink, Inbox, RotateCcw } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''
type LeadSort = NonNullable<LeadParams['sort']>

const leadStore = useLeadStore()
const detailsOpen = ref(false)
const selectedLead = computed(() => leadStore.lead)

const columns = [
  { key: 'summary', label: 'Submission', sortable: true, sortKey: 'created_at' },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'template_name', label: 'Template', sortable: true, sortKey: 'template' },
  { key: 'cta_type', label: 'CTA Type', sortable: true },
  { key: 'created_at', label: 'Submitted', sortable: true, headerClass: 'w-44' },
  { key: 'actions', label: '', headerClass: 'w-40', cellClass: 'text-right' },
] as const

const sortableColumns = ['created_at', 'status', 'cta_type', 'template'] as const

const statusCards = computed(() => [
  {
    label: 'New',
    value: leadStore.statusCounts.new,
    status: 'new' as LeadStatus,
    description: 'Need first follow-up',
  },
  {
    label: 'Contacted',
    value: leadStore.statusCounts.contacted,
    status: 'contacted' as LeadStatus,
    description: 'Follow-up started',
  },
  {
    label: 'Archived',
    value: leadStore.statusCounts.archived,
    status: 'archived' as LeadStatus,
    description: 'Closed or not relevant',
  },
])

const payloadEntries = computed(() => {
  const payload = selectedLead.value?.payload ?? {}

  return Object.entries(payload).map(([key, value]) => ({
    key,
    label: labelFor(key),
    value: displayPayloadValue(value),
  }))
})

const page = computed(() => leadStore.params.page ?? 1)
const pageSize = computed(() => leadStore.params.pageSize ?? 15)
const search = computed(() => leadStore.params.search ?? '')
const sortKey = computed(() => leadStore.params.sort ?? '')
const sortDirection = computed(() => leadStore.params.direction ?? '')
const statusFilter = computed({
  get: () => leadStore.params.status ?? '',
  set: (value: LeadStatus | '') => {
    leadStore.index({ status: value, page: 1 })
  },
})
const templateFilter = computed({
  get: () => leadStore.params.template_id ?? '',
  set: (value: string) => {
    leadStore.index({ template_id: value, page: 1 })
  },
})
const ctaTypeFilter = computed({
  get: () => leadStore.params.cta_type ?? '',
  set: (value: string) => {
    leadStore.index({ cta_type: value, page: 1 })
  },
})
const fromFilter = computed({
  get: () => leadStore.params.from ?? '',
  set: (value: string | number) => {
    leadStore.index({ from: String(value), page: 1 })
  },
})
const toFilter = computed({
  get: () => leadStore.params.to ?? '',
  set: (value: string | number) => {
    leadStore.index({ to: String(value), page: 1 })
  },
})
const hasActiveFilters = computed(() =>
  Boolean(
    statusFilter.value ||
      templateFilter.value ||
      ctaTypeFilter.value ||
      fromFilter.value ||
      toFilter.value ||
      search.value
  )
)
const emptyTitle = computed(() => {
  if (hasActiveFilters.value) return 'No submissions match these filters'

  return 'No submissions yet'
})
const emptyDescription = computed(() => {
  if (hasActiveFilters.value) {
    return 'Clear filters or broaden the date range to find more form submissions.'
  }

  return 'When visitors submit a CTA form on a published website, new leads will appear here for follow-up.'
})

const labelFor = (value: string): string => {
  return value
    .replace(/_/g, ' ')
    .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
}

const displayPayloadValue = (value: unknown): string => {
  if (value === null || value === undefined) return '-'
  if (Array.isArray(value)) return value.map((item) => displayPayloadValue(item)).join(', ')
  if (typeof value === 'object') return JSON.stringify(value)

  return String(value)
}

const updateSort = (key: string, direction: SortDirection): void => {
  const sort = sortableColumns.includes(key as LeadSort) ? (key as LeadSort) : 'created_at'

  leadStore.index({
    direction: direction || 'desc',
    page: 1,
    sort: direction ? sort : 'created_at',
  })
}

const openDetails = async (lead: LeadRecord): Promise<void> => {
  await leadStore.show(lead.id)
  detailsOpen.value = true
}

const updateStatus = async (lead: LeadRecord, status: LeadStatus): Promise<void> => {
  await leadStore.updateStatus(lead.id, status)
}

const updateSelectedStatus = async (status: LeadStatus): Promise<void> => {
  if (!selectedLead.value) return

  await updateStatus(selectedLead.value, status)
}

const exportLeads = async (): Promise<void> => {
  const blob = await leadStore.exportCsv()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = 'tenant-leads.csv'
  link.click()
  URL.revokeObjectURL(url)
}

onMounted(() => {
  leadStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Leads</h2>
          <p class="module-container-description">
            Review website form submissions, follow up, and keep lead status organized.
          </p>
        </div>
        <Button variant="navigate" size="sm" :disabled="leadStore.exporting" @click="exportLeads">
          <Download class="size-4" />
          {{ leadStore.exporting ? 'Exporting...' : 'Export CSV' }}
        </Button>
      </div>

      <div class="mb-4 grid gap-3 md:grid-cols-3">
        <Card
          v-for="card in statusCards"
          :key="card.status"
          class="cursor-pointer transition hover:border-primary/60"
          :class="{ 'border-primary ring-2 ring-primary/15': statusFilter === card.status }"
          @click="statusFilter = statusFilter === card.status ? '' : card.status"
        >
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">
              {{ card.label }}
            </CardTitle>
            <Inbox class="size-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ card.value }}</div>
            <p class="mt-1 text-xs text-muted-foreground">{{ card.description }}</p>
          </CardContent>
        </Card>
      </div>

      <BaseTable
        :columns="columns"
        :data="leadStore.leads"
        :empty-text="
          statusFilter
            ? `No ${labelFor(statusFilter).toLowerCase()} submissions found.`
            : 'No form submissions yet.'
        "
        :empty-title="emptyTitle"
        :loading="leadStore.loading"
        :page="page"
        :page-size="pageSize"
        :search="search"
        :sort-direction="sortDirection"
        :sort-key="sortKey"
        :total="leadStore.total"
        loading-text="Loading leads..."
        row-key="id"
        search-placeholder="Search names, emails, templates, or payload..."
        with-details
        with-page-size
        with-search
        @details="openDetails"
        @update:page="leadStore.index({ page: $event })"
        @update:page-size="leadStore.index({ page: 1, pageSize: $event })"
        @update:search="leadStore.index({ page: 1, search: $event })"
        @update:sort="updateSort"
      >
        <template #empty-icon>
          <Inbox class="size-5" />
        </template>

        <template #empty-description>
          {{ emptyDescription }}
        </template>

        <template #empty-actions>
          <div class="flex flex-col gap-2 sm:flex-row">
            <Button
              v-if="hasActiveFilters"
              type="button"
              variant="navigate"
              size="sm"
              @click="leadStore.reset"
            >
              <RotateCcw class="size-4" />
              Reset Filters
            </Button>
            <Button v-else as-child variant="navigate" size="sm">
              <RouterLink to="/template-builder">Open Template Builder</RouterLink>
            </Button>
          </div>
        </template>

        <template #filters>
          <NativeSelect
            v-field-help="'Filter leads by follow-up status.'"
            v-model="statusFilter"
            class="h-9 min-w-36"
          >
            <option value="">All statuses</option>
            <option
              v-for="status in leadStore.filters.statuses"
              :key="status.value"
              :value="status.value"
            >
              {{ status.label }}
            </option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter leads by the website template that received the submission.'"
            v-model="templateFilter"
            class="h-9 min-w-48"
          >
            <option value="">All templates</option>
            <option
              v-for="template in leadStore.filters.templates"
              :key="template.id"
              :value="template.id"
            >
              {{ template.name }}
            </option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter leads by CTA or form type.'"
            v-model="ctaTypeFilter"
            class="h-9 min-w-44"
          >
            <option value="">All CTA types</option>
            <option v-for="type in leadStore.filters.cta_types" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </NativeSelect>

          <Input
            v-field-help="'Show submissions from this date onward.'"
            v-model="fromFilter"
            type="date"
            class="h-9 min-w-36 sm:max-w-40"
          />

          <Input
            v-field-help="'Show submissions up to this date.'"
            v-model="toFilter"
            type="date"
            class="h-9 min-w-36 sm:max-w-40"
          />

          <Button type="button" size="sm" variant="cancel" :disabled="leadStore.loading" @click="leadStore.reset">
            <RotateCcw class="size-4" />
            Reset
          </Button>
        </template>

        <template #cell-summary="{ row }">
          <div class="min-w-0">
            <div class="font-medium text-foreground">{{ row.summary }}</div>
            <div class="mt-1 text-xs text-muted-foreground">
              {{ formatDisplayDate(row.created_at, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
            </div>
          </div>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="getStatusBadgeVariant(row.status)">
            {{ getStatusLabel(row.status) }}
          </Badge>
        </template>

        <template #cell-template_name="{ row }">
          <div class="max-w-52 truncate" :title="row.template_name || ''">
            {{ row.template_name || 'Untitled site' }}
          </div>
        </template>

        <template #cell-cta_type="{ row }">
          <span class="text-sm text-muted-foreground">{{ labelFor(row.cta_type) }}</span>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-muted-foreground">
            {{ formatDisplayDate(row.created_at) }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-2">
            <Button
              v-if="row.status !== 'contacted'"
              variant="update"
              size="sm"
              type="button"
              title="Mark contacted"
              aria-label="Mark contacted"
              @click.stop="updateStatus(row, 'contacted')"
            >
              <CheckCircle2 class="size-4" />
            </Button>
            <Button
              v-if="row.status !== 'archived'"
              variant="delete"
              size="sm"
              type="button"
              title="Archive lead"
              aria-label="Archive lead"
              @click.stop="updateStatus(row, 'archived')"
            >
              <Archive class="size-4" />
            </Button>
          </div>
        </template>
      </BaseTable>

      <Dialog :open="detailsOpen" @update:open="detailsOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{{ selectedLead?.summary || 'Lead details' }}</DialogTitle>
            <DialogDescription>
              Submitted {{ formatDisplayDate(selectedLead?.created_at) }} from
              {{ selectedLead?.template_name || 'a website form' }}.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedLead" class="space-y-5">
            <div class="flex flex-wrap items-center gap-2">
              <Badge :variant="getStatusBadgeVariant(selectedLead.status)">
                {{ getStatusLabel(selectedLead.status) }}
              </Badge>
              <Badge variant="outline">{{ labelFor(selectedLead.cta_type) }}</Badge>
              <Button
                v-if="selectedLead.template_url"
                as-child
                variant="navigate"
                size="sm"
                class="ml-auto"
              >
                <a :href="selectedLead.template_url" target="_blank" rel="noreferrer">
                  <ExternalLink class="size-4" />
                  Open Site
                </a>
              </Button>
            </div>

            <div class="rounded border bg-muted/20 p-3">
              <p class="text-sm font-semibold">Follow-up workflow</p>
              <p class="mt-1 text-sm text-muted-foreground">
                Review the submitted details, contact the lead using the payload information, then
                mark it contacted. Archive submissions that no longer need follow-up.
              </p>
            </div>

            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead class="w-48">Field</TableHead>
                    <TableHead>Submitted Value</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!payloadEntries.length">
                    <TableCell colspan="2" class="h-24 text-center text-muted-foreground">
                      No payload fields were submitted.
                    </TableCell>
                  </TableRow>
                  <TableRow v-for="entry in payloadEntries" v-else :key="entry.key">
                    <TableCell class="font-medium">{{ entry.label }}</TableCell>
                    <TableCell class="whitespace-pre-wrap text-muted-foreground">
                      {{ entry.value }}
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>

          <DialogFooter class="gap-2 sm:justify-between">
            <Button
              v-if="selectedLead?.status !== 'new'"
              type="button"
              variant="navigate"
              :disabled="leadStore.loading"
              @click="updateSelectedStatus('new')"
            >
              Mark New
            </Button>
            <div class="flex flex-col gap-2 sm:flex-row">
              <Button
                type="button"
                variant="update"
                :disabled="leadStore.loading || selectedLead?.status === 'contacted'"
                @click="updateSelectedStatus('contacted')"
              >
                <CheckCircle2 class="size-4" />
                Mark Contacted
              </Button>
              <Button
                type="button"
                variant="delete"
                :disabled="leadStore.loading || selectedLead?.status === 'archived'"
                @click="updateSelectedStatus('archived')"
              >
                <Archive class="size-4" />
                Archive
              </Button>
            </div>
          </DialogFooter>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
