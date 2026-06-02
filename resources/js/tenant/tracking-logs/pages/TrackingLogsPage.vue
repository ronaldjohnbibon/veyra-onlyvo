<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { formatDisplayDate } from '@/shared/utils/date'
import { useTrackingLogStore } from '@/tenant/tracking-logs/tracking-log-store'
import type {
  TrackingLogEventType,
  TrackingLogParams,
  TrackingLogRecord,
} from '@/shared/types/tracking-logs'
import { computed, onMounted } from 'vue'

type SortDirection = 'asc' | 'desc' | ''
type TrackingLogSort = NonNullable<TrackingLogParams['sort']>

const trackingLogStore = useTrackingLogStore()
const sortableColumns = ['created_at', 'event_type', 'event_name', 'tenant', 'template'] as const

const columns = [
  {
    key: 'created_at',
    label: 'Created At',
    sortable: true,
    headerClass: 'min-w-44',
    cellClass: 'min-w-44 align-top text-sm whitespace-nowrap',
  },
  {
    key: 'event_type',
    label: 'Event Type',
    sortable: true,
    headerClass: 'min-w-40',
    cellClass: 'min-w-40 align-top',
  },
  {
    key: 'event_name',
    label: 'Event Name',
    sortable: true,
    headerClass: 'min-w-48',
    cellClass: 'min-w-48 align-top',
  },
  {
    key: 'tenant_name',
    label: 'Tenant',
    sortable: true,
    sortKey: 'tenant',
    headerClass: 'min-w-44',
    cellClass: 'min-w-44 align-top',
  },
  {
    key: 'website_template',
    label: 'Website / Template',
    sortable: true,
    sortKey: 'template',
    headerClass: 'min-w-56',
    cellClass: 'min-w-56 align-top',
  },
  {
    key: 'landing_page_url',
    label: 'Landing Page URL',
    headerClass: 'min-w-72',
    cellClass: 'min-w-72 align-top',
  },
  {
    key: 'visitor_identifier',
    label: 'Visitor Identifier',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top font-mono text-xs',
  },
  {
    key: 'session_identifier',
    label: 'Session Identifier',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top font-mono text-xs',
  },
  {
    key: 'referrer_url',
    label: 'Referrer URL',
    headerClass: 'min-w-72',
    cellClass: 'min-w-72 align-top',
  },
  {
    key: 'utm_source',
    label: 'UTM Source',
    headerClass: 'min-w-32',
    cellClass: 'min-w-32 align-top',
  },
  {
    key: 'utm_medium',
    label: 'UTM Medium',
    headerClass: 'min-w-32',
    cellClass: 'min-w-32 align-top',
  },
  {
    key: 'utm_campaign',
    label: 'UTM Campaign',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top',
  },
  {
    key: 'device_type',
    label: 'Device',
    headerClass: 'min-w-28',
    cellClass: 'min-w-28 align-top',
  },
  {
    key: 'browser',
    label: 'Browser',
    headerClass: 'min-w-28',
    cellClass: 'min-w-28 align-top',
  },
  {
    key: 'operating_system',
    label: 'OS',
    headerClass: 'min-w-32',
    cellClass: 'min-w-32 align-top',
  },
  {
    key: 'ip_address',
    label: 'IP Address',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top',
  },
  {
    key: 'country_location',
    label: 'Country / Location',
    headerClass: 'min-w-40',
    cellClass: 'min-w-40 align-top',
  },
  {
    key: 'conversion_status',
    label: 'Conversion Status',
    headerClass: 'min-w-40',
    cellClass: 'min-w-40 align-top',
  },
] as const

const page = computed(() => trackingLogStore.params.page ?? 1)
const pageSize = computed(() => trackingLogStore.params.pageSize ?? 15)
const search = computed(() => trackingLogStore.params.search ?? '')
const sortKey = computed(() => trackingLogStore.params.sort ?? '')
const sortDirection = computed(() => trackingLogStore.params.direction ?? '')
const fromFilter = computed({
  get: () => trackingLogStore.params.from ?? '',
  set: (value: string | number) => {
    trackingLogStore.index({ from: String(value), page: 1 })
  },
})
const toFilter = computed({
  get: () => trackingLogStore.params.to ?? '',
  set: (value: string | number) => {
    trackingLogStore.index({ to: String(value), page: 1 })
  },
})
const eventTypeFilter = computed({
  get: () => trackingLogStore.params.event_type ?? '',
  set: (value: TrackingLogEventType) => {
    trackingLogStore.index({ event_type: value, page: 1 })
  },
})
const templateFilter = computed({
  get: () => trackingLogStore.params.template_id ?? '',
  set: (value: string) => {
    trackingLogStore.index({ template_id: value, page: 1 })
  },
})
const conversionStatusFilter = computed({
  get: () => trackingLogStore.params.conversion_status ?? '',
  set: (value: string) => {
    trackingLogStore.index({ conversion_status: value, page: 1 })
  },
})

const emptyText = (value?: string | null): string => {
  return value && value.trim() !== '' ? value : '-'
}

const shortHash = (value?: string | null): string => {
  return value ? value.slice(0, 12) : '-'
}

const formatDateTime = (value?: string | null): string => {
  return formatDisplayDate(value, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const statusLabel = (value?: string | null): string => {
  if (!value) return '-'

  return value.replace(/_/g, ' ').replace(/\w\S*/g, (word) => {
    return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()
  })
}

const eventBadgeVariant = (eventType: TrackingLogRecord['event_type']) => {
  if (eventType === 'website_visit') return 'info'
  if (eventType === 'cta_view') return 'neutral'
  if (eventType === 'cta_click') return 'warning'
  if (eventType === 'form_submission') return 'secondary'
  if (eventType === 'conversion') return 'success'

  return 'outline'
}

const statusBadgeVariant = (status?: string | null) => {
  if (!status) return 'outline'
  if (status === 'success') return 'success'
  if (status === 'new') return 'info'

  return 'secondary'
}

const updateSort = (key: string, direction: SortDirection): void => {
  const sort = sortableColumns.includes(key as TrackingLogSort)
    ? (key as TrackingLogSort)
    : 'created_at'

  // Clearing a column sort returns the table to newest logs first.
  trackingLogStore.index({
    direction: direction || 'desc',
    page: 1,
    sort: direction ? sort : 'created_at',
  })
}

onMounted(() => {
  trackingLogStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Tracking Logs</h2>
          <p class="module-container-description">
            Raw website visits, CTA activity, form submissions, and conversions.
          </p>
        </div>
      </div>

      <BaseTable
        :columns="columns"
        :data="trackingLogStore.logs"
        :empty-text="'No tracking logs found.'"
        :loading="trackingLogStore.loading"
        :loading-text="'Loading tracking logs...'"
        :page="page"
        :page-size="pageSize"
        :search="search"
        :sort-direction="sortDirection"
        :sort-key="sortKey"
        :total="trackingLogStore.total"
        search-placeholder="Search logs..."
        with-page-size
        with-search
        @update:page="trackingLogStore.index({ page: $event })"
        @update:page-size="trackingLogStore.index({ page: 1, pageSize: $event })"
        @update:search="trackingLogStore.index({ page: 1, search: $event })"
        @update:sort="updateSort"
      >
        <template #filters>
          <Input v-model="fromFilter" type="date" class="h-9 min-w-36 sm:max-w-40" />
          <Input v-model="toFilter" type="date" class="h-9 min-w-36 sm:max-w-40" />

          <NativeSelect v-model="eventTypeFilter" class="h-9 min-w-40">
            <option value="">All events</option>
            <option
              v-for="option in trackingLogStore.filters.event_types"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </NativeSelect>

          <NativeSelect v-model="templateFilter" class="h-9 min-w-48">
            <option value="">All templates</option>
            <option
              v-for="template in trackingLogStore.filters.templates"
              :key="template.id"
              :value="template.id"
            >
              {{ template.name }}
            </option>
          </NativeSelect>

          <NativeSelect v-model="conversionStatusFilter" class="h-9 min-w-40">
            <option value="">All statuses</option>
            <option
              v-for="status in trackingLogStore.filters.conversion_statuses"
              :key="status.value"
              :value="status.value"
            >
              {{ status.label }}
            </option>
          </NativeSelect>

          <Button
            type="button"
            size="sm"
            variant="cancel"
            :disabled="trackingLogStore.loading"
            @click="trackingLogStore.reset"
          >
            Reset
          </Button>
        </template>

        <template #cell-created_at="{ row }">
          {{ formatDateTime(row.created_at) }}
        </template>

        <template #cell-event_type="{ row }">
          <Badge :variant="eventBadgeVariant(row.event_type)">
            {{ row.event_type_label }}
          </Badge>
        </template>

        <template #cell-event_name="{ row }">
          <span class="block max-w-44 truncate" :title="row.event_name">
            {{ row.event_name }}
          </span>
        </template>

        <template #cell-tenant_name="{ row }">
          <span class="block max-w-40 truncate" :title="row.tenant_name">
            {{ emptyText(row.tenant_name) }}
          </span>
        </template>

        <template #cell-website_template="{ row }">
          <span class="block max-w-52 truncate" :title="row.website_template">
            {{ row.website_template }}
          </span>
        </template>

        <template #cell-landing_page_url="{ row }">
          <span class="block max-w-72 truncate" :title="row.landing_page_url ?? ''">
            {{ emptyText(row.landing_page_url) }}
          </span>
        </template>

        <template #cell-visitor_identifier="{ row }">
          {{ shortHash(row.visitor_identifier) }}
        </template>

        <template #cell-session_identifier="{ row }">
          {{ shortHash(row.session_identifier) }}
        </template>

        <template #cell-referrer_url="{ row }">
          <span class="block max-w-72 truncate" :title="row.referrer_url ?? ''">
            {{ emptyText(row.referrer_url) }}
          </span>
        </template>

        <template #cell-utm_source="{ row }">
          {{ emptyText(row.utm_source) }}
        </template>

        <template #cell-utm_medium="{ row }">
          {{ emptyText(row.utm_medium) }}
        </template>

        <template #cell-utm_campaign="{ row }">
          {{ emptyText(row.utm_campaign) }}
        </template>

        <template #cell-device_type="{ row }">
          {{ emptyText(row.device_type) }}
        </template>

        <template #cell-browser="{ row }">
          {{ emptyText(row.browser) }}
        </template>

        <template #cell-operating_system="{ row }">
          {{ emptyText(row.operating_system) }}
        </template>

        <template #cell-ip_address="{ row }">
          {{ emptyText(row.ip_address) }}
        </template>

        <template #cell-country_location="{ row }">
          {{ emptyText(row.country_location) }}
        </template>

        <template #cell-conversion_status="{ row }">
          <Badge v-if="row.conversion_status" :variant="statusBadgeVariant(row.conversion_status)">
            {{ statusLabel(row.conversion_status) }}
          </Badge>
          <span v-else>-</span>
        </template>
      </BaseTable>
    </div>
  </div>
</template>
