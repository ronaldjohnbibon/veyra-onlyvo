<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { formatDisplayDate } from '@/shared/utils/date'
import { useAdminLeadStore } from '@/admin/leads/lead-store'
import type { AdminLead, AdminLeadParams, AdminLeadStatus } from '@/admin/leads/types'
import {
  BarChart3,
  CheckCircle2,
  Download,
  ExternalLink,
  Link2,
  MousePointerClick,
  ShieldAlert,
  XCircle,
} from 'lucide-vue-next'
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

type SortDirection = 'asc' | 'desc' | ''

const leadStore = useAdminLeadStore()

const columns = [
  { key: 'summary', label: 'Submission', sortable: false },
  { key: 'tenant', label: 'Tenant', sortable: true, sortKey: 'tenant' },
  { key: 'template', label: 'Template', sortable: true, sortKey: 'template' },
  { key: 'cta_type', label: 'CTA Type', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'created_at', label: 'Submitted', sortable: true },
  { key: 'links', label: 'Links' },
  { key: 'actions', label: '', headerClass: 'w-[255px]', cellClass: 'text-right' },
] as const

const page = computed(() => leadStore.params.page ?? 1)
const pageSize = computed(() => leadStore.params.pageSize ?? 15)
const search = computed(() => leadStore.params.search ?? '')
const sortKey = computed(() => leadStore.params.sort ?? '')
const sortDirection = computed(() => leadStore.params.direction ?? '')
const meta = computed(() => leadStore.meta)
const filteredTemplates = computed(() => {
  const tenantId = leadStore.params.tenant_id

  if (!tenantId) return meta.value?.filters.templates ?? []

  return (meta.value?.filters.templates ?? []).filter((template) => template.tenant_id === tenantId)
})
const maxTrend = computed(() =>
  Math.max(1, ...(meta.value?.trends.map((trend) => trend.total) ?? [1]))
)

const updateFilters = (params: Partial<AdminLeadParams>): void => {
  leadStore.index({ ...params, page: 1 })
}

const updateSort = (key: string, direction: SortDirection): void => {
  leadStore.index({
    sort: direction ? (key as AdminLeadParams['sort']) : 'created_at',
    direction: direction || 'desc',
    page: 1,
  })
}

const statusVariant = (
  status: AdminLeadStatus
): 'success' | 'warning' | 'destructive' | 'neutral' | 'outline' => {
  if (status === 'closed') return 'success'
  if (status === 'contacted') return 'warning'
  if (status === 'spam') return 'destructive'
  if (status === 'archived') return 'neutral'

  return 'outline'
}

const labelFor = (value: string): string => {
  return value.replace(/[_-]/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const payloadPreview = (lead: AdminLead): string[] => {
  return Object.entries(lead.payload ?? {})
    .filter(([, value]) => ['string', 'number', 'boolean'].includes(typeof value))
    .slice(0, 3)
    .map(([key, value]) => `${labelFor(key)}: ${String(value)}`)
}

const exportLeads = async (): Promise<void> => {
  const blob = await leadStore.exportLeads()
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = 'admin-leads.csv'
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
          <h2 class="module-container-title">Leads / Submissions</h2>
          <p class="module-container-description">
            Monitor CTA submissions, conversion quality, and tenant follow-up across the platform.
          </p>
        </div>
        <Button variant="navigate" size="sm" :disabled="leadStore.exporting" @click="exportLeads">
          <Download class="size-4" />
          Export
        </Button>
      </div>

      <div class="mb-4 grid gap-3 md:grid-cols-2 xl:grid-cols-7">
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Total</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.summary.total ?? 0 }}</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">New</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.summary.new ?? 0 }}</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Contacted</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.summary.contacted ?? 0 }}</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Closed</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.summary.closed ?? 0 }}</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Spam</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.summary.spam ?? 0 }}</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Contact rate</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.conversions.contact_rate ?? 0 }}%</p>
          </CardContent>
        </Card>
        <Card class="py-4">
          <CardContent class="px-4">
            <p class="text-xs text-muted-foreground">Close rate</p>
            <p class="mt-1 text-2xl font-semibold">{{ meta?.conversions.close_rate ?? 0 }}%</p>
          </CardContent>
        </Card>
      </div>

      <div class="mb-4 grid gap-4 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <Card class="gap-4 py-4">
          <CardHeader class="px-4">
            <CardTitle class="flex items-center gap-2 text-sm">
              <BarChart3 class="size-4" />
              Lead Volume Trends
            </CardTitle>
          </CardHeader>
          <CardContent class="px-4">
            <div v-if="meta?.trends.length" class="flex h-44 items-end gap-2">
              <div
                v-for="trend in meta.trends"
                :key="trend.date"
                class="flex min-w-8 flex-1 flex-col items-center justify-end gap-2"
              >
                <div
                  class="w-full rounded-t bg-primary/70"
                  :style="{ height: `${Math.max(8, (trend.total / maxTrend) * 150)}px` }"
                  :title="`${trend.date}: ${trend.total}`"
                />
                <span class="text-[10px] text-muted-foreground">
                  {{ trend.date.slice(5) }}
                </span>
              </div>
            </div>
            <p v-else class="rounded border bg-muted/40 p-3 text-sm text-muted-foreground">
              No lead volume data for the current filters.
            </p>
          </CardContent>
        </Card>

        <Card class="gap-4 py-4">
          <CardHeader class="px-4">
            <CardTitle class="flex items-center gap-2 text-sm">
              <MousePointerClick class="size-4" />
              Conversion Summaries
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 px-4">
            <div
              v-for="cta in meta?.conversions.by_cta_type ?? []"
              :key="cta.cta_type"
              class="flex items-center justify-between gap-3 rounded border p-3"
            >
              <span class="text-sm font-medium">{{ cta.label }}</span>
              <Badge variant="outline">{{ cta.total }}</Badge>
            </div>
            <p
              v-if="!meta?.conversions.by_cta_type.length"
              class="rounded border bg-muted/40 p-3 text-sm text-muted-foreground"
            >
              No CTA submissions match the current filters.
            </p>
          </CardContent>
        </Card>
      </div>

      <BaseTable
        :columns="columns"
        :data="leadStore.leads"
        :page="page"
        :page-size="pageSize"
        :total="leadStore.total"
        :search="search"
        :loading="leadStore.loading"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        with-search
        with-page-size
        empty-title="No submissions found"
        empty-text="CTA submissions will appear here after visitors submit tenant forms."
        search-placeholder="Search tenants, templates, payloads..."
        @update:page="leadStore.index({ page: $event })"
        @update:page-size="leadStore.index({ pageSize: $event, page: 1 })"
        @update:search="updateFilters({ search: $event })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect
            v-field-help="'Filter submissions by tenant.'"
            :model-value="leadStore.params.tenant_id"
            class="h-9 min-w-44"
            @update:model-value="updateFilters({ tenant_id: String($event), template_id: '' })"
          >
            <option value="">All tenants</option>
            <option
              v-for="tenant in meta?.filters.tenants ?? []"
              :key="tenant.id"
              :value="tenant.id"
            >
              {{ tenant.name }}
            </option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter submissions by tenant template.'"
            :model-value="leadStore.params.template_id"
            class="h-9 min-w-48"
            @update:model-value="updateFilters({ template_id: String($event) })"
          >
            <option value="">All templates</option>
            <option v-for="template in filteredTemplates" :key="template.id" :value="template.id">
              {{ template.name }}
            </option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter submissions by CTA type.'"
            :model-value="leadStore.params.cta_type"
            class="h-9 min-w-40"
            @update:model-value="updateFilters({ cta_type: String($event) })"
          >
            <option value="">All CTA types</option>
            <option
              v-for="type in meta?.filters.cta_types ?? []"
              :key="type.value"
              :value="type.value"
            >
              {{ type.label }}
            </option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter submissions by follow-up status.'"
            :model-value="leadStore.params.status"
            class="h-9 min-w-36"
            @update:model-value="updateFilters({ status: String($event) as AdminLeadStatus | '' })"
          >
            <option value="">All statuses</option>
            <option
              v-for="status in meta?.filters.statuses ?? []"
              :key="status.value"
              :value="status.value"
            >
              {{ status.label }}
            </option>
          </NativeSelect>

          <Input
            v-field-help="'Show submissions from this date onward.'"
            :model-value="leadStore.params.from"
            class="h-9 min-w-36"
            type="date"
            @update:model-value="updateFilters({ from: String($event) })"
          />
          <Input
            v-field-help="'Show submissions up to this date.'"
            :model-value="leadStore.params.to"
            class="h-9 min-w-36"
            type="date"
            @update:model-value="updateFilters({ to: String($event) })"
          />
        </template>

        <template #cell-summary="{ row }">
          <div class="max-w-md">
            <p class="font-medium text-foreground">{{ row.summary }}</p>
            <div class="mt-1 flex flex-wrap gap-1 text-xs text-muted-foreground">
              <span
                v-for="item in payloadPreview(row)"
                :key="item"
                class="rounded bg-muted px-1.5 py-0.5"
              >
                {{ item }}
              </span>
            </div>
          </div>
        </template>

        <template #cell-tenant="{ row }">
          <RouterLink
            v-if="row.links.tenant"
            :to="row.links.tenant"
            class="text-sm font-medium text-primary hover:underline"
          >
            {{ row.tenant_name || 'Tenant' }}
          </RouterLink>
          <span v-else class="text-sm text-muted-foreground">Unknown tenant</span>
        </template>

        <template #cell-template="{ row }">
          <div class="text-sm">{{ row.template_name || 'Template' }}</div>
          <div class="text-xs text-muted-foreground">{{ row.template_slug }}</div>
        </template>

        <template #cell-cta_type="{ row }">
          <Badge variant="outline">{{ labelFor(row.cta_type) }}</Badge>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="statusVariant(row.status)">{{ labelFor(row.status) }}</Badge>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-muted-foreground">{{ formatDisplayDate(row.created_at) }}</span>
        </template>

        <template #cell-links="{ row }">
          <div class="flex flex-wrap gap-1">
            <a
              v-if="row.links.public_site"
              :href="row.links.public_site"
              target="_blank"
              rel="noreferrer"
              title="Open public site"
            >
              <Button size="xs" variant="navigate" type="button">
                <ExternalLink class="size-3" />
                Site
              </Button>
            </a>
            <a
              v-if="row.links.tracking_logs"
              :href="row.links.tracking_logs"
              target="_blank"
              rel="noreferrer"
              title="Open tenant tracking logs"
            >
              <Button size="xs" variant="navigate" type="button">
                <Link2 class="size-3" />
                Logs
              </Button>
            </a>
            <a
              v-if="row.links.analytics"
              :href="row.links.analytics"
              target="_blank"
              rel="noreferrer"
              title="Open tenant analytics"
            >
              <Button size="xs" variant="navigate" type="button">
                <BarChart3 class="size-3" />
                Analytics
              </Button>
            </a>
          </div>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-2">
            <Button
              size="xs"
              variant="publish"
              type="button"
              @click.stop="leadStore.updateStatus(row.id, 'contacted')"
            >
              <CheckCircle2 class="size-3" />
              Contacted
            </Button>
            <Button
              size="xs"
              variant="update"
              type="button"
              @click.stop="leadStore.updateStatus(row.id, 'closed')"
            >
              <XCircle class="size-3" />
              Closed
            </Button>
            <Button
              size="xs"
              variant="delete"
              type="button"
              @click.stop="leadStore.updateStatus(row.id, 'spam')"
            >
              <ShieldAlert class="size-3" />
              Spam
            </Button>
          </div>
        </template>
      </BaseTable>
    </div>
  </div>
</template>
