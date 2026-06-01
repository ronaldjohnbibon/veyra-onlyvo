<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect } from '@/components/ui/native-select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { formatDisplayDate } from '@/lib/date'
import { useAnalyticsStore } from '@/modules/analytics/analytics-store'
import type {
  AnalyticsCtaTotalRow,
  AnalyticsDailyTotal,
  AnalyticsEventTypeRow,
  AnalyticsPeriod,
  AnalyticsTotalRow,
} from '@/types/analytics'
import {
  BarChart3,
  CalendarDays,
  Eye,
  MousePointerClick,
  Percent,
  Target,
  Users,
} from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

const analyticsStore = useAnalyticsStore()
const customFrom = ref('')
const customTo = ref('')

const dashboard = computed(() => analyticsStore.dashboard)
const summary = computed(() => dashboard.value?.summary)

const summaryCards = computed(() => [
  {
    label: 'Total Visits',
    value: summary.value?.total_visits ?? 0,
    icon: Eye,
  },
  {
    label: 'Unique Visitors',
    value: summary.value?.unique_visitors ?? 0,
    icon: Users,
  },
  {
    label: 'Total CTA Events',
    value: summary.value?.total_cta_events ?? 0,
    icon: MousePointerClick,
  },
  {
    label: 'Unique CTA Visitors',
    value: summary.value?.unique_cta_visitors ?? 0,
    icon: Target,
  },
  {
    label: "Today's CTA Events",
    value: summary.value?.today_cta_events ?? 0,
    icon: CalendarDays,
  },
  {
    label: 'Last 7 Days CTA Events',
    value: summary.value?.last_7_days_cta_events ?? 0,
    icon: BarChart3,
  },
  {
    label: 'Last 30 Days CTA Events',
    value: summary.value?.last_30_days_cta_events ?? 0,
    icon: BarChart3,
  },
  {
    label: 'Overall Conversion Rate',
    value: dashboard.value?.conversions.overall_conversion_rate ?? 0,
    icon: Percent,
    percent: true,
  },
])

const period = computed({
  get: () => analyticsStore.params.period ?? 'last_7_days',
  set: (value: AnalyticsPeriod) => {
    if (value === 'custom') {
      analyticsStore.params.period = value
      return
    }

    analyticsStore.setPeriod(value)
  },
})

const topPages = computed(() => dashboard.value?.top_pages.data ?? [])
const referrers = computed(() => dashboard.value?.top_referrers.data ?? [])
const topCtas = computed(() => dashboard.value?.top_ctas.data ?? [])
const ctaEventTypes = computed(() => dashboard.value?.cta_events_by_type ?? [])
const conversions = computed(() => dashboard.value?.conversions)
const pagePagination = computed(() => dashboard.value?.top_pages.pagination)
const referrerPagination = computed(() => dashboard.value?.top_referrers.pagination)
const ctaPagination = computed(() => dashboard.value?.top_ctas.pagination)

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat().format(value)
}

const formatPercent = (value: number): string => {
  return `${new Intl.NumberFormat(undefined, { maximumFractionDigits: 2 }).format(value)}%`
}

const titleCase = (value: string): string => {
  return value
    .replace(/_/g, ' ')
    .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
}

const chartPoints = (items: AnalyticsDailyTotal[]): string => {
  if (!items.length) return ''

  const max = Math.max(1, ...items.map((item) => item.total))
  const width = 560
  const height = 150
  const left = 20
  const top = 20
  const step = items.length > 1 ? width / (items.length - 1) : 0

  return items
    .map((item, index) => {
      const x = left + step * index
      const y = top + height - (item.total / max) * height

      return `${x},${y}`
    })
    .join(' ')
}

const chartLabels = (items: AnalyticsDailyTotal[]): AnalyticsDailyTotal[] => {
  if (items.length <= 7) return items

  const interval = Math.ceil(items.length / 6)

  return items.filter(
    (_, index) => index === 0 || index === items.length - 1 || index % interval === 0
  )
}

const maxTotal = (items: AnalyticsDailyTotal[]): number => {
  return Math.max(0, ...items.map((item) => item.total))
}

const maxCtaTotal = (items: AnalyticsCtaTotalRow[]): number => {
  return Math.max(1, ...items.map((item) => item.total))
}

const maxTypeTotal = (items: AnalyticsEventTypeRow[]): number => {
  return Math.max(1, ...items.map((item) => item.total))
}

const applyCustomRange = async (): Promise<void> => {
  await analyticsStore.index({
    period: 'custom',
    from: customFrom.value,
    to: customTo.value,
    top_pages_page: 1,
    referrers_page: 1,
    top_ctas_page: 1,
  })
}

const updatePage = async (
  key: 'top_pages_page' | 'referrers_page' | 'top_ctas_page',
  page: number
): Promise<void> => {
  await analyticsStore.index({ [key]: page })
}

const rowLabel = (row: AnalyticsTotalRow): string => {
  return row.value || 'Direct / unknown'
}

onMounted(() => {
  analyticsStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Analytics</h2>
          <p class="module-container-description">
            Public website visits, CTA engagement, and conversion performance.
          </p>
        </div>
      </div>

      <div class="mb-4 flex flex-col gap-3 rounded border bg-card p-3 sm:flex-row sm:items-end">
        <div class="w-full sm:max-w-48">
          <label class="mb-1 block text-xs font-medium text-muted-foreground">Date Range</label>
          <NativeSelect v-model="period" class="h-9">
            <option value="today">Today</option>
            <option value="last_7_days">Last 7 Days</option>
            <option value="last_30_days">Last 30 Days</option>
            <option value="custom">Custom Date Range</option>
          </NativeSelect>
        </div>

        <template v-if="period === 'custom'">
          <div class="w-full sm:max-w-44">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">From</label>
            <Input v-model="customFrom" type="date" class="h-9" />
          </div>
          <div class="w-full sm:max-w-44">
            <label class="mb-1 block text-xs font-medium text-muted-foreground">To</label>
            <Input v-model="customTo" type="date" class="h-9" />
          </div>
          <Button type="button" size="sm" class="h-9" @click="applyCustomRange">Apply</Button>
        </template>
      </div>

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <Card v-for="card in summaryCards" :key="card.label">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">
              {{ card.label }}
            </CardTitle>
            <component :is="card.icon" class="size-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">
              {{ card.percent ? formatPercent(card.value) : formatNumber(card.value) }}
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Daily Visits</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="h-72">
              <svg viewBox="0 0 600 220" class="h-full w-full" role="img">
                <line x1="20" y1="170" x2="580" y2="170" class="stroke-border" />
                <line x1="20" y1="20" x2="20" y2="170" class="stroke-border" />
                <polyline
                  :points="chartPoints(dashboard?.daily_visits ?? [])"
                  fill="none"
                  stroke="var(--chart-1)"
                  stroke-width="4"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <text x="20" y="14" class="fill-muted-foreground text-[12px]">
                  {{ maxTotal(dashboard?.daily_visits ?? []) }}
                </text>
                <g v-for="item in chartLabels(dashboard?.daily_visits ?? [])" :key="item.date">
                  <text
                    :x="
                      20 +
                      ((dashboard?.daily_visits ?? []).findIndex((row) => row.date === item.date) /
                        Math.max(1, (dashboard?.daily_visits.length ?? 1) - 1)) *
                        560
                    "
                    y="198"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[11px]"
                  >
                    {{ formatDisplayDate(item.date, { month: 'short', day: 'numeric' }) }}
                  </text>
                </g>
              </svg>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Daily Unique Visitors</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="h-72">
              <svg viewBox="0 0 600 220" class="h-full w-full" role="img">
                <line x1="20" y1="170" x2="580" y2="170" class="stroke-border" />
                <line x1="20" y1="20" x2="20" y2="170" class="stroke-border" />
                <polyline
                  :points="chartPoints(dashboard?.daily_uniques ?? [])"
                  fill="none"
                  stroke="var(--chart-2)"
                  stroke-width="4"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <text x="20" y="14" class="fill-muted-foreground text-[12px]">
                  {{ maxTotal(dashboard?.daily_uniques ?? []) }}
                </text>
                <g v-for="item in chartLabels(dashboard?.daily_uniques ?? [])" :key="item.date">
                  <text
                    :x="
                      20 +
                      ((dashboard?.daily_uniques ?? []).findIndex((row) => row.date === item.date) /
                        Math.max(1, (dashboard?.daily_uniques.length ?? 1) - 1)) *
                        560
                    "
                    y="198"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[11px]"
                  >
                    {{ formatDisplayDate(item.date, { month: 'short', day: 'numeric' }) }}
                  </text>
                </g>
              </svg>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Daily CTA Events</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="h-72">
              <svg viewBox="0 0 600 220" class="h-full w-full" role="img">
                <line x1="20" y1="170" x2="580" y2="170" class="stroke-border" />
                <line x1="20" y1="20" x2="20" y2="170" class="stroke-border" />
                <polyline
                  :points="chartPoints(dashboard?.daily_cta_events ?? [])"
                  fill="none"
                  stroke="var(--chart-3)"
                  stroke-width="4"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
                <text x="20" y="14" class="fill-muted-foreground text-[12px]">
                  {{ maxTotal(dashboard?.daily_cta_events ?? []) }}
                </text>
                <g v-for="item in chartLabels(dashboard?.daily_cta_events ?? [])" :key="item.date">
                  <text
                    :x="
                      20 +
                      ((dashboard?.daily_cta_events ?? []).findIndex(
                        (row) => row.date === item.date
                      ) /
                        Math.max(1, (dashboard?.daily_cta_events.length ?? 1) - 1)) *
                        560
                    "
                    y="198"
                    text-anchor="middle"
                    class="fill-muted-foreground text-[11px]"
                  >
                    {{ formatDisplayDate(item.date, { month: 'short', day: 'numeric' }) }}
                  </text>
                </g>
              </svg>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">CTA Events By Type</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div
              v-if="!ctaEventTypes.length"
              class="py-16 text-center text-sm text-muted-foreground"
            >
              No CTA events found.
            </div>
            <div v-for="row in ctaEventTypes" v-else :key="row.event_type" class="space-y-1.5">
              <div class="flex items-center justify-between gap-3 text-sm">
                <span class="font-medium">{{ titleCase(row.event_type) }}</span>
                <span class="text-muted-foreground">{{ formatNumber(row.total) }}</span>
              </div>
              <div class="h-2 rounded bg-muted">
                <div
                  class="h-2 rounded bg-[var(--chart-3)]"
                  :style="{ width: `${(row.total / maxTypeTotal(ctaEventTypes)) * 100}%` }"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Top Performing CTAs</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div v-if="!topCtas.length" class="py-16 text-center text-sm text-muted-foreground">
              No CTA events found.
            </div>
            <div
              v-for="row in topCtas.slice(0, 8)"
              v-else
              :key="row.cta_identifier"
              class="space-y-1.5"
            >
              <div class="flex items-center justify-between gap-3 text-sm">
                <span class="truncate font-medium" :title="row.cta_label">{{ row.cta_label }}</span>
                <span class="text-muted-foreground">{{ formatNumber(row.total) }}</span>
              </div>
              <div class="h-2 rounded bg-muted">
                <div
                  class="h-2 rounded bg-[var(--chart-4)]"
                  :style="{ width: `${(row.total / maxCtaTotal(topCtas)) * 100}%` }"
                />
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Top CTAs</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>CTA Label</TableHead>
                    <TableHead>CTA Type</TableHead>
                    <TableHead class="w-28 text-right">Total Events</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!topCtas.length">
                    <TableCell :colspan="3" class="h-24 text-center text-muted-foreground">
                      No CTA events found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in topCtas" :key="row.cta_identifier">
                      <TableCell>
                        <span class="block max-w-md truncate" :title="row.cta_label">
                          {{ row.cta_label }}
                        </span>
                      </TableCell>
                      <TableCell>{{ titleCase(row.cta_type) }}</TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
              <span>{{ ctaPagination?.total ?? 0 }} CTAs</span>
              <div class="flex gap-2">
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="(ctaPagination?.current_page ?? 1) <= 1 || analyticsStore.loading"
                  @click="updatePage('top_ctas_page', (ctaPagination?.current_page ?? 1) - 1)"
                >
                  Previous
                </Button>
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="
                    (ctaPagination?.current_page ?? 1) >= (ctaPagination?.last_page ?? 1) ||
                    analyticsStore.loading
                  "
                  @click="updatePage('top_ctas_page', (ctaPagination?.current_page ?? 1) + 1)"
                >
                  Next
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">CTA Event Types</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Event Type</TableHead>
                    <TableHead class="w-28 text-right">Total Events</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!ctaEventTypes.length">
                    <TableCell :colspan="2" class="h-24 text-center text-muted-foreground">
                      No CTA event types found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in ctaEventTypes" :key="row.event_type">
                      <TableCell>{{ titleCase(row.event_type) }}</TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Conversion Rate Per CTA</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="grid gap-3 sm:grid-cols-3">
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Total Visits</p>
                <p class="mt-1 text-xl font-semibold">
                  {{ formatNumber(conversions?.total_visits ?? 0) }}
                </p>
              </div>
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Total CTA Events</p>
                <p class="mt-1 text-xl font-semibold">
                  {{ formatNumber(conversions?.total_cta_events ?? 0) }}
                </p>
              </div>
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Overall Rate</p>
                <p class="mt-1 text-xl font-semibold">
                  {{ formatPercent(conversions?.overall_conversion_rate ?? 0) }}
                </p>
              </div>
            </div>

            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>CTA</TableHead>
                    <TableHead class="w-28 text-right">Events</TableHead>
                    <TableHead class="w-28 text-right">Rate</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!(conversions?.per_cta.length ?? 0)">
                    <TableCell :colspan="3" class="h-24 text-center text-muted-foreground">
                      No CTA conversions found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in conversions?.per_cta ?? []" :key="row.cta_identifier">
                      <TableCell>
                        <span class="block max-w-md truncate" :title="row.cta_label">
                          {{ row.cta_label }}
                        </span>
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total_events) }}
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatPercent(row.conversion_rate) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Conversion Rate Per Page</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Page URL</TableHead>
                    <TableHead class="w-24 text-right">Visits</TableHead>
                    <TableHead class="w-24 text-right">Events</TableHead>
                    <TableHead class="w-24 text-right">Rate</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!(conversions?.per_page.length ?? 0)">
                    <TableCell :colspan="4" class="h-24 text-center text-muted-foreground">
                      No page conversions found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in conversions?.per_page ?? []" :key="row.url">
                      <TableCell>
                        <span class="block max-w-md truncate" :title="row.url">
                          {{ row.url }}
                        </span>
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total_visits) }}
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total_events) }}
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatPercent(row.conversion_rate) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle class="text-base">Top Pages</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Page URL</TableHead>
                    <TableHead class="w-28 text-right">Total Visits</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!topPages.length">
                    <TableCell :colspan="2" class="h-24 text-center text-muted-foreground">
                      No page visits found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in topPages" :key="row.value">
                      <TableCell>
                        <span class="block max-w-xl truncate" :title="row.value">
                          {{ row.value }}
                        </span>
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
              <span>{{ pagePagination?.total ?? 0 }} pages</span>
              <div class="flex gap-2">
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="(pagePagination?.current_page ?? 1) <= 1 || analyticsStore.loading"
                  @click="updatePage('top_pages_page', (pagePagination?.current_page ?? 1) - 1)"
                >
                  Previous
                </Button>
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="
                    (pagePagination?.current_page ?? 1) >= (pagePagination?.last_page ?? 1) ||
                    analyticsStore.loading
                  "
                  @click="updatePage('top_pages_page', (pagePagination?.current_page ?? 1) + 1)"
                >
                  Next
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle class="text-base">Referrers</CardTitle>
          </CardHeader>
          <CardContent class="space-y-3">
            <div class="overflow-hidden rounded border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Referrer URL</TableHead>
                    <TableHead class="w-28 text-right">Total Visits</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow v-if="!referrers.length">
                    <TableCell :colspan="2" class="h-24 text-center text-muted-foreground">
                      No referrers found.
                    </TableCell>
                  </TableRow>
                  <template v-else>
                    <TableRow v-for="row in referrers" :key="row.value">
                      <TableCell>
                        <span class="block max-w-xl truncate" :title="rowLabel(row)">
                          {{ rowLabel(row) }}
                        </span>
                      </TableCell>
                      <TableCell class="text-right font-medium">
                        {{ formatNumber(row.total) }}
                      </TableCell>
                    </TableRow>
                  </template>
                </TableBody>
              </Table>
            </div>

            <div class="flex items-center justify-between text-sm text-muted-foreground">
              <span>{{ referrerPagination?.total ?? 0 }} referrers</span>
              <div class="flex gap-2">
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="(referrerPagination?.current_page ?? 1) <= 1 || analyticsStore.loading"
                  @click="updatePage('referrers_page', (referrerPagination?.current_page ?? 1) - 1)"
                >
                  Previous
                </Button>
                <Button
                  size="sm"
                  variant="navigate"
                  :disabled="
                    (referrerPagination?.current_page ?? 1) >=
                      (referrerPagination?.last_page ?? 1) || analyticsStore.loading
                  "
                  @click="updatePage('referrers_page', (referrerPagination?.current_page ?? 1) + 1)"
                >
                  Next
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
