<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Checkbox } from '@/shared/components/ui/checkbox'
import {
  Dialog,
  DialogDescription,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { formatDisplayDate } from '@/shared/utils/date'
import { useTrackingLogStore } from '@/tenant/tracking-logs/tracking-log-store'
import type {
  TrackingLogEventType,
  TrackingLogParams,
  TrackingLogRecord,
} from '@/shared/types/tracking-logs'
import {
  Activity,
  Download,
  Eye,
  Filter,
  Globe2,
  MousePointerClick,
  RotateCcw,
  SlidersHorizontal,
  UserRound,
} from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''
type TrackingLogSort = NonNullable<TrackingLogParams['sort']>

interface RawColumn {
  key: string
  label: string
  sortable?: boolean
  sortKey?: string
  headerClass?: string
  cellClass?: string
}

interface SavedFilter {
  label: string
  params: TrackingLogParams
}

const trackingLogStore = useTrackingLogStore()
const sortableColumns = ['created_at', 'event_type', 'event_name', 'tenant', 'template'] as const
const selectedLog = ref<TrackingLogRecord | null>(null)
const detailsOpen = ref(false)
const columnsOpen = ref(false)
const savedFilterName = ref('')
const savedFilters = ref<SavedFilter[]>([])

const rawColumns: RawColumn[] = [
  {
    key: 'created_at',
    label: 'Time',
    sortable: true,
    headerClass: 'min-w-44',
    cellClass: 'min-w-44 align-top text-sm whitespace-nowrap',
  },
  {
    key: 'event_type',
    label: 'Activity',
    sortable: true,
    headerClass: 'min-w-40',
    cellClass: 'min-w-40 align-top',
  },
  {
    key: 'event_name',
    label: 'Name',
    sortable: true,
    headerClass: 'min-w-48',
    cellClass: 'min-w-48 align-top',
  },
  {
    key: 'website_template',
    label: 'Website',
    sortable: true,
    sortKey: 'template',
    headerClass: 'min-w-56',
    cellClass: 'min-w-56 align-top',
  },
  {
    key: 'landing_page_url',
    label: 'Page',
    headerClass: 'min-w-72',
    cellClass: 'min-w-72 align-top',
  },
  {
    key: 'visitor_label',
    label: 'Visitor',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top font-mono text-xs',
  },
  {
    key: 'session_label',
    label: 'Session',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top font-mono text-xs',
  },
  {
    key: 'referrer_url',
    label: 'Referrer',
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
  { key: 'device_type', label: 'Device', headerClass: 'min-w-28', cellClass: 'min-w-28 align-top' },
  { key: 'browser', label: 'Browser', headerClass: 'min-w-28', cellClass: 'min-w-28 align-top' },
  {
    key: 'operating_system',
    label: 'OS',
    headerClass: 'min-w-32',
    cellClass: 'min-w-32 align-top',
  },
  {
    key: 'ip_address_label',
    label: 'IP',
    headerClass: 'min-w-32',
    cellClass: 'min-w-32 align-top font-mono text-xs',
  },
  {
    key: 'conversion_status',
    label: 'Status',
    headerClass: 'min-w-36',
    cellClass: 'min-w-36 align-top',
  },
  { key: 'actions', label: '', headerClass: 'w-20 text-right', cellClass: 'text-right align-top' },
]

const defaultVisibleColumns = [
  'created_at',
  'event_type',
  'event_name',
  'website_template',
  'landing_page_url',
  'visitor_label',
  'device_type',
  'browser',
  'conversion_status',
  'actions',
]

const visibleColumnKeys = ref<string[]>([...defaultVisibleColumns])

const columns = computed(() => {
  return rawColumns.filter((column) => visibleColumnKeys.value.includes(column.key))
})

const page = computed(() => trackingLogStore.params.page ?? 1)
const pageSize = computed(() => trackingLogStore.params.pageSize ?? 15)
const search = computed(() => trackingLogStore.params.search ?? '')
const sortKey = computed(() => trackingLogStore.params.sort ?? '')
const sortDirection = computed(() => trackingLogStore.params.direction ?? '')
const fromFilter = computed({
  get: () => trackingLogStore.params.from ?? '',
  set: (value: string | number) => trackingLogStore.index({ from: String(value), page: 1 }),
})
const toFilter = computed({
  get: () => trackingLogStore.params.to ?? '',
  set: (value: string | number) => trackingLogStore.index({ to: String(value), page: 1 }),
})
const eventTypeFilter = computed({
  get: () => trackingLogStore.params.event_type ?? '',
  set: (value: TrackingLogEventType) => trackingLogStore.index({ event_type: value, page: 1 }),
})
const templateFilter = computed({
  get: () => trackingLogStore.params.template_id ?? '',
  set: (value: string) => trackingLogStore.index({ template_id: value, page: 1 }),
})
const conversionStatusFilter = computed({
  get: () => trackingLogStore.params.conversion_status ?? '',
  set: (value: string) => trackingLogStore.index({ conversion_status: value, page: 1 }),
})

const totalEvents = computed(() => trackingLogStore.total)
const conversionsOnPage = computed(() => {
  return trackingLogStore.logs.filter((log) =>
    ['conversion', 'form_submission'].includes(log.event_type)
  ).length
})
const visitorsOnPage = computed(() => {
  return new Set(trackingLogStore.logs.map((log) => log.visitor_label).filter(Boolean)).size
})
const pagesOnPage = computed(() => {
  return new Set(trackingLogStore.logs.map((log) => log.landing_page_url).filter(Boolean)).size
})

const visitorGroups = computed(() => groupByCount('visitor_label', 'Unknown visitor').slice(0, 5))
const pageGroups = computed(() => groupByCount('landing_page_url', 'No page captured').slice(0, 5))

const emptyText = computed(() => {
  if (
    trackingLogStore.params.search ||
    trackingLogStore.params.event_type ||
    trackingLogStore.params.template_id
  ) {
    return 'No activity matches the current filters. Try clearing filters or widening the date range.'
  }

  return 'No tracking activity has been recorded yet. Visits, CTA events, and submissions will appear here after visitors interact with the public site.'
})

const emptyValue = (value?: string | null): string => {
  return value && value.trim() !== '' ? value : '-'
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

const eventIcon = (eventType: TrackingLogRecord['event_type']) => {
  if (eventType === 'website_visit') return Globe2
  if (eventType === 'cta_click' || eventType === 'cta_view') return MousePointerClick
  if (eventType === 'conversion' || eventType === 'form_submission') return Activity

  return Activity
}

const groupByCount = (key: keyof TrackingLogRecord, fallback: string) => {
  const counts = new Map<string, number>()

  trackingLogStore.logs.forEach((log) => {
    const value = String(log[key] || fallback)
    counts.set(value, (counts.get(value) ?? 0) + 1)
  })

  return Array.from(counts.entries())
    .map(([label, total]) => ({ label, total }))
    .sort((a, b) => b.total - a.total)
}

const toggleColumn = (key: string, value: boolean): void => {
  if (key === 'actions') return

  visibleColumnKeys.value = value
    ? Array.from(new Set([...visibleColumnKeys.value, key]))
    : visibleColumnKeys.value.filter((columnKey) => columnKey !== key)

  localStorage.setItem('tenant.trackingLogs.columns', JSON.stringify(visibleColumnKeys.value))
}

const openDetails = (log: TrackingLogRecord): void => {
  selectedLog.value = log
  detailsOpen.value = true
}

const updateSort = (key: string, direction: SortDirection): void => {
  const sort = sortableColumns.includes(key as TrackingLogSort)
    ? (key as TrackingLogSort)
    : 'created_at'

  trackingLogStore.index({
    direction: direction || 'desc',
    page: 1,
    sort: direction ? sort : 'created_at',
  })
}

const exportLogs = async (): Promise<void> => {
  await trackingLogStore.exportCsv()
}

const saveCurrentFilter = (): void => {
  const label = savedFilterName.value.trim()

  if (!label) return

  const params = { ...trackingLogStore.params, page: 1 }
  const next = [
    { label, params },
    ...savedFilters.value.filter((filter) => filter.label !== label),
  ].slice(0, 6)

  savedFilters.value = next
  savedFilterName.value = ''
  localStorage.setItem('tenant.trackingLogs.savedFilters', JSON.stringify(next))
}

const applySavedFilter = async (event: Event): Promise<void> => {
  const index = Number((event.target as HTMLSelectElement).value)
  const filter = savedFilters.value[index]

  if (!filter) return

  await trackingLogStore.index(filter.params)
}

const resetFilters = async (): Promise<void> => {
  await trackingLogStore.reset()
}

onMounted(() => {
  try {
    const columns = JSON.parse(localStorage.getItem('tenant.trackingLogs.columns') || '[]')
    if (Array.isArray(columns) && columns.length) {
      visibleColumnKeys.value = Array.from(new Set([...columns, 'actions']))
    }
  } catch {
    visibleColumnKeys.value = [...defaultVisibleColumns]
  }

  try {
    const filters = JSON.parse(localStorage.getItem('tenant.trackingLogs.savedFilters') || '[]')
    savedFilters.value = Array.isArray(filters) ? filters.slice(0, 6) : []
  } catch {
    savedFilters.value = []
  }

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
            Review visitor activity, CTA engagement, submissions, and the raw logs behind them.
          </p>
        </div>
      </div>

      <div class="mb-4 grid gap-3 md:grid-cols-4">
        <Card>
          <CardHeader class="space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Matching Events</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-2xl font-semibold">{{ totalEvents }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Across the current filters</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground"
              >Visitors On Page</CardTitle
            >
          </CardHeader>
          <CardContent>
            <p class="text-2xl font-semibold">{{ visitorsOnPage }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Privacy-safe visitor groups</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">Pages On Page</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="text-2xl font-semibold">{{ pagesOnPage }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Grouped from visible results</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground"
              >Conversions On Page</CardTitle
            >
          </CardHeader>
          <CardContent>
            <p class="text-2xl font-semibold">{{ conversionsOnPage }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Submissions and completed goals</p>
          </CardContent>
        </Card>
      </div>

      <div class="mb-4 grid gap-4 xl:grid-cols-[minmax(0,1.3fr)_minmax(20rem,0.7fr)]">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Activity Feed</CardTitle>
          </CardHeader>
          <CardContent>
            <div
              v-if="!trackingLogStore.logs.length"
              class="rounded border bg-muted/40 p-6 text-center text-sm text-muted-foreground"
            >
              {{ emptyText }}
            </div>
            <div v-else class="space-y-3">
              <button
                v-for="log in trackingLogStore.logs.slice(0, 12)"
                :key="log.id"
                type="button"
                class="flex w-full gap-3 rounded border bg-background p-3 text-left transition hover:border-primary hover:bg-muted/40"
                @click="openDetails(log)"
              >
                <span class="mt-0.5 rounded bg-muted p-2">
                  <component :is="eventIcon(log.event_type)" class="size-4 text-muted-foreground" />
                </span>
                <span class="min-w-0 flex-1">
                  <span class="flex flex-wrap items-center gap-2">
                    <span class="font-medium text-foreground">{{
                      log.activity_title || log.event_name
                    }}</span>
                    <Badge :variant="eventBadgeVariant(log.event_type)">
                      {{ log.event_type_label }}
                    </Badge>
                  </span>
                  <span class="mt-1 block truncate text-sm text-muted-foreground">
                    {{ log.activity_summary || log.website_template }}
                  </span>
                  <span class="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                    <span>{{ formatDateTime(log.created_at) }}</span>
                    <span>{{ log.visitor_label || 'Unknown visitor' }}</span>
                    <span>{{ log.device_type || 'Unknown device' }}</span>
                  </span>
                </span>
              </button>
            </div>
          </CardContent>
        </Card>

        <div class="grid gap-4">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Visitor Groups</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div v-if="!visitorGroups.length" class="text-sm text-muted-foreground">
                Visitor groups appear after tracked visits or CTA events.
              </div>
              <div
                v-for="group in visitorGroups"
                v-else
                :key="group.label"
                class="flex items-center justify-between gap-3 text-sm"
              >
                <span class="flex min-w-0 items-center gap-2 truncate">
                  <UserRound class="size-4 text-muted-foreground" />
                  <span class="truncate">{{ group.label }}</span>
                </span>
                <span class="font-medium">{{ group.total }}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle class="text-base">Page Groups</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div v-if="!pageGroups.length" class="text-sm text-muted-foreground">
                Page groups appear after visits are recorded.
              </div>
              <div
                v-for="group in pageGroups"
                v-else
                :key="group.label"
                class="flex items-center justify-between gap-3 text-sm"
              >
                <span class="min-w-0 truncate" :title="group.label">{{ group.label }}</span>
                <span class="font-medium">{{ group.total }}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="mb-4 space-y-3 rounded border bg-card p-3">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-end">
          <div class="w-full sm:max-w-40">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">From</label>
            <Input
              v-field-help="'Show logs from this date onward.'"
              v-model="fromFilter"
              type="date"
              class="h-9"
            />
          </div>
          <div class="w-full sm:max-w-40">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">To</label>
            <Input
              v-field-help="'Show logs up to this date.'"
              v-model="toFilter"
              type="date"
              class="h-9"
            />
          </div>
          <NativeSelect
            v-field-help="'Filter logs by tracked event type.'"
            v-model="eventTypeFilter"
            class="h-9 min-w-40"
          >
            <option value="">All events</option>
            <option
              v-for="option in trackingLogStore.filters.event_types"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </NativeSelect>
          <NativeSelect
            v-field-help="'Filter logs by the related website template.'"
            v-model="templateFilter"
            class="h-9 min-w-48"
          >
            <option value="">All templates</option>
            <option
              v-for="template in trackingLogStore.filters.templates"
              :key="template.id"
              :value="template.id"
            >
              {{ template.name }}
            </option>
          </NativeSelect>
          <NativeSelect
            v-field-help="'Filter logs by conversion status.'"
            v-model="conversionStatusFilter"
            class="h-9 min-w-40"
          >
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
            @click="resetFilters"
          >
            <RotateCcw class="size-4" />
            Reset
          </Button>
          <Button
            type="button"
            size="sm"
            variant="navigate"
            :disabled="trackingLogStore.loading"
            @click="exportLogs"
          >
            <Download class="size-4" />
            Export
          </Button>
        </div>

        <div class="flex flex-col gap-2 lg:flex-row lg:items-end">
          <div class="flex flex-1 flex-col gap-2 sm:flex-row">
            <Input
              v-field-help="'Name this filter set before saving it.'"
              v-model="savedFilterName"
              class="h-9 sm:max-w-56"
              placeholder="Save filter name"
            />
            <Button type="button" size="sm" variant="update" class="h-9" @click="saveCurrentFilter">
              <Filter class="size-4" />
              Save Filter
            </Button>
          </div>
          <NativeSelect
            v-if="savedFilters.length"
            v-field-help="'Apply a saved tracking-log filter.'"
            class="h-9 min-w-60"
            @change="applySavedFilter"
          >
            <option value="">Apply saved filter</option>
            <option v-for="(filter, index) in savedFilters" :key="filter.label" :value="index">
              {{ filter.label }}
            </option>
          </NativeSelect>
          <Button
            type="button"
            size="sm"
            variant="outline"
            class="h-9"
            @click="columnsOpen = !columnsOpen"
          >
            <SlidersHorizontal class="size-4" />
            Columns
          </Button>
        </div>

        <div
          v-if="columnsOpen"
          class="grid gap-2 rounded border bg-background p-3 sm:grid-cols-2 lg:grid-cols-4"
        >
          <label
            v-for="column in rawColumns.filter((column) => column.key !== 'actions')"
            :key="column.key"
            class="flex items-center gap-2 text-sm"
          >
            <Checkbox
              :model-value="visibleColumnKeys.includes(column.key)"
              @update:model-value="toggleColumn(column.key, Boolean($event))"
            />
            <span>{{ column.label }}</span>
          </label>
        </div>
      </div>

      <BaseTable
        :columns="columns"
        :data="trackingLogStore.logs"
        :empty-text="emptyText"
        :loading="trackingLogStore.loading"
        :loading-text="'Loading tracking logs...'"
        :page="page"
        :page-size="pageSize"
        :search="search"
        :sort-direction="sortDirection"
        :sort-key="sortKey"
        :total="trackingLogStore.total"
        search-placeholder="Search raw logs..."
        with-page-size
        with-search
        @update:page="trackingLogStore.index({ page: $event })"
        @update:page-size="trackingLogStore.index({ page: 1, pageSize: $event })"
        @update:search="trackingLogStore.index({ page: 1, search: $event })"
        @update:sort="updateSort"
      >
        <template #cell-created_at="{ row }">{{ formatDateTime(row.created_at) }}</template>
        <template #cell-event_type="{ row }">
          <Badge :variant="eventBadgeVariant(row.event_type)">{{ row.event_type_label }}</Badge>
        </template>
        <template #cell-event_name="{ row }">
          <span class="block max-w-44 truncate" :title="row.event_name">{{ row.event_name }}</span>
        </template>
        <template #cell-website_template="{ row }">
          <span class="block max-w-52 truncate" :title="row.website_template">{{
            row.website_template
          }}</span>
        </template>
        <template #cell-landing_page_url="{ row }">
          <span class="block max-w-72 truncate" :title="row.landing_page_url ?? ''">{{
            emptyValue(row.landing_page_url)
          }}</span>
        </template>
        <template #cell-visitor_label="{ row }">{{ emptyValue(row.visitor_label) }}</template>
        <template #cell-session_label="{ row }">{{ emptyValue(row.session_label) }}</template>
        <template #cell-referrer_url="{ row }">
          <span class="block max-w-72 truncate" :title="row.referrer_url ?? ''">{{
            emptyValue(row.referrer_url)
          }}</span>
        </template>
        <template #cell-utm_source="{ row }">{{ emptyValue(row.utm_source) }}</template>
        <template #cell-utm_medium="{ row }">{{ emptyValue(row.utm_medium) }}</template>
        <template #cell-utm_campaign="{ row }">{{ emptyValue(row.utm_campaign) }}</template>
        <template #cell-device_type="{ row }">{{ emptyValue(row.device_type) }}</template>
        <template #cell-browser="{ row }">{{ emptyValue(row.browser) }}</template>
        <template #cell-operating_system="{ row }">{{ emptyValue(row.operating_system) }}</template>
        <template #cell-ip_address_label="{ row }">{{ emptyValue(row.ip_address_label) }}</template>
        <template #cell-conversion_status="{ row }">
          <Badge v-if="row.conversion_status" :variant="statusBadgeVariant(row.conversion_status)">
            {{ statusLabel(row.conversion_status) }}
          </Badge>
          <span v-else>-</span>
        </template>
        <template #cell-actions="{ row }">
          <Button
            variant="navigate"
            size="sm"
            type="button"
            aria-label="View activity"
            title="View activity"
            @click="openDetails(row)"
          >
            <Eye class="size-4" />
          </Button>
        </template>
      </BaseTable>

      <Dialog :open="detailsOpen" @update:open="detailsOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{{ selectedLog?.activity_title || selectedLog?.event_name }}</DialogTitle>
            <DialogDescription>{{ formatDateTime(selectedLog?.created_at) }}</DialogDescription>
          </DialogHeader>

          <div v-if="selectedLog" class="grid gap-4 md:grid-cols-2">
            <div class="rounded border p-4 md:col-span-2">
              <Badge :variant="eventBadgeVariant(selectedLog.event_type)">
                {{ selectedLog.event_type_label }}
              </Badge>
              <p class="mt-3 text-sm leading-6 text-muted-foreground">
                {{ selectedLog.activity_summary }}
              </p>
            </div>

            <div class="space-y-2 rounded border p-4">
              <h3 class="text-sm font-semibold">Visitor</h3>
              <p class="text-sm text-muted-foreground">
                {{ selectedLog.visitor_label || 'Unknown visitor' }}
              </p>
              <p class="text-sm text-muted-foreground">
                Session: {{ selectedLog.session_label || 'Unknown session' }}
              </p>
              <p class="text-sm text-muted-foreground">
                IP: {{ selectedLog.ip_address_label || 'Not captured' }}
              </p>
            </div>

            <div class="space-y-2 rounded border p-4">
              <h3 class="text-sm font-semibold">Device</h3>
              <p class="text-sm text-muted-foreground">
                {{ selectedLog.device_type || 'Unknown device' }}
              </p>
              <p class="text-sm text-muted-foreground">
                {{ selectedLog.browser || 'Unknown browser' }}
              </p>
              <p class="text-sm text-muted-foreground">
                {{ selectedLog.operating_system || 'Unknown OS' }}
              </p>
            </div>

            <div class="space-y-2 rounded border p-4 md:col-span-2">
              <h3 class="text-sm font-semibold">Page and Campaign</h3>
              <p class="break-all text-sm text-muted-foreground">
                Page: {{ emptyValue(selectedLog.landing_page_url) }}
              </p>
              <p class="break-all text-sm text-muted-foreground">
                Referrer: {{ emptyValue(selectedLog.referrer_url) }}
              </p>
              <p class="text-sm text-muted-foreground">
                UTM: {{ emptyValue(selectedLog.utm_source) }} /
                {{ emptyValue(selectedLog.utm_medium) }} /
                {{ emptyValue(selectedLog.utm_campaign) }}
              </p>
            </div>

            <div class="space-y-2 rounded border p-4 md:col-span-2">
              <h3 class="text-sm font-semibold">Raw Log Reference</h3>
              <p class="break-all text-xs text-muted-foreground">
                Source: {{ selectedLog.source }}
              </p>
              <p class="break-all text-xs text-muted-foreground">
                Source ID: {{ selectedLog.source_id }}
              </p>
              <p class="break-all text-xs text-muted-foreground">
                Template ID: {{ emptyValue(selectedLog.template_id) }}
              </p>
            </div>
          </div>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
