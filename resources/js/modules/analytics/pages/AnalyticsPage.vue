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
import type { AnalyticsDailyTotal, AnalyticsPeriod, AnalyticsTotalRow } from '@/types/analytics'
import { BarChart3, CalendarDays, Eye, MousePointerClick, Users } from 'lucide-vue-next'
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
    label: "Today's Visits",
    value: summary.value?.today_visits ?? 0,
    icon: MousePointerClick,
  },
  {
    label: "Today's Unique Visitors",
    value: summary.value?.today_unique_visitors ?? 0,
    icon: CalendarDays,
  },
  {
    label: 'Last 7 Days Visits',
    value: summary.value?.last_7_days_visits ?? 0,
    icon: BarChart3,
  },
  {
    label: 'Last 30 Days Visits',
    value: summary.value?.last_30_days_visits ?? 0,
    icon: BarChart3,
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
const pagePagination = computed(() => dashboard.value?.top_pages.pagination)
const referrerPagination = computed(() => dashboard.value?.top_referrers.pagination)

const formatNumber = (value: number): string => {
  return new Intl.NumberFormat().format(value)
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

const applyCustomRange = async (): Promise<void> => {
  await analyticsStore.index({
    period: 'custom',
    from: customFrom.value,
    to: customTo.value,
    top_pages_page: 1,
    referrers_page: 1,
  })
}

const updatePage = async (
  key: 'top_pages_page' | 'referrers_page',
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
            Public website visits, daily unique visitors, top pages, and referrers.
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

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
        <Card v-for="card in summaryCards" :key="card.label">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">
              {{ card.label }}
            </CardTitle>
            <component :is="card.icon" class="size-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ formatNumber(card.value) }}</div>
          </CardContent>
        </Card>
      </div>

      <div class="mt-4 grid gap-4 xl:grid-cols-2">
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
