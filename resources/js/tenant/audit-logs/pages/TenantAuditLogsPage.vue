<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
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
import { useTenantAuditLogStore } from '@/tenant/audit-logs/audit-log-store'
import type { AuditLogParams, AuditLogRecord } from '@/tenant/audit-logs/types'
import { Download, FileClock, Search } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const auditStore = useTenantAuditLogStore()
const detailsOpen = ref(false)

const columns = [
  { key: 'occurred_at', label: 'Time', sortable: true },
  { key: 'category', label: 'Category', sortable: true },
  { key: 'severity', label: 'Severity', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'actor', label: 'Actor', sortable: true },
  { key: 'entity', label: 'Entity', sortable: true },
  { key: 'ip_address', label: 'IP Address', sortable: true },
  { key: 'summary', label: 'Summary' },
] as const

const page = computed(() => auditStore.params.page ?? 1)
const pageSize = computed(() => auditStore.params.pageSize ?? 15)
const search = computed(() => auditStore.params.search ?? '')
const sortKey = computed(() => auditStore.params.sort ?? '')
const sortDirection = computed(() => auditStore.params.direction ?? '')

const updateFilters = (params: Partial<AuditLogParams>): void => {
  auditStore.index({ ...params, page: 1 })
}

const updateCategory = (value?: unknown): void => {
  updateFilters({ category: String(value ?? '') as AuditLogParams['category'] })
}

const updateSeverity = (value?: unknown): void => {
  updateFilters({ severity: String(value ?? '') as AuditLogParams['severity'] })
}

const updateSort = (key: string, direction: SortDirection): void => {
  auditStore.index({
    sort: direction ? (key as AuditLogParams['sort']) : 'occurred_at',
    direction: direction || 'desc',
    page: 1,
  })
}

const labelFor = (value: string): string => {
  return value.replace(/[._-]/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const actorLabel = (log: AuditLogRecord): string => {
  return log.actor.name || log.actor.email || labelFor(log.actor.type || 'system')
}

const entityLabel = (log: AuditLogRecord): string => {
  return log.entity.label || log.entity.id || labelFor(log.entity.type)
}

const actionVariant = (
  action: string
): 'success' | 'warning' | 'destructive' | 'info' | 'neutral' | 'outline' => {
  if (action.includes('deleted') || action.includes('deactivated') || action.includes('failed')) {
    return 'destructive'
  }
  if (action.includes('created') || action.includes('published') || action.includes('login')) {
    return 'success'
  }
  if (action.includes('restored') || action.includes('reset')) return 'warning'
  if (action.includes('updated') || action.includes('changed')) return 'info'

  return 'outline'
}

const severityVariant = (
  severity: string
): 'success' | 'warning' | 'destructive' | 'info' | 'neutral' | 'outline' => {
  if (severity === 'critical' || severity === 'error') return 'destructive'
  if (severity === 'warning') return 'warning'
  return 'outline'
}

const compactValue = (value: unknown): string => {
  if (value === null || value === undefined) return 'None'
  if (typeof value !== 'object') return String(value)

  const keys = Object.keys(value as Record<string, unknown>)
  if (!keys.length) return 'Empty'

  return keys.slice(0, 3).join(', ') + (keys.length > 3 ? ` +${keys.length - 3}` : '')
}

const formatJson = (value: unknown): string => {
  if (value === null || value === undefined) return 'null'

  return JSON.stringify(value, null, 2)
}

const openDetails = async (log: AuditLogRecord): Promise<void> => {
  await auditStore.show(log.id)
  detailsOpen.value = true
}

const downloadBlob = (blob: Blob, filename: string): void => {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

const exportLogs = async (): Promise<void> => {
  downloadBlob(await auditStore.exportLogs(), 'tenant-audit-logs.csv')
}

onMounted(() => {
  auditStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Audit Logs</h2>
          <p class="module-container-description">
            Review workspace authentication, settings, content, navigation, request, file, and
            notification events.
          </p>
        </div>
        <Button variant="navigate" size="sm" :disabled="auditStore.exporting" @click="exportLogs">
          <Download class="size-4" />
          Export
        </Button>
      </div>

      <BaseTable
        :columns="columns"
        :data="auditStore.logs"
        :page="page"
        :page-size="pageSize"
        :total="auditStore.total"
        :search="search"
        :loading="auditStore.loading"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        with-search
        with-details
        with-page-size
        empty-title="No audit logs found"
        empty-text="Workspace activity and audit events will appear here after the audit table is migrated."
        search-placeholder="Search actor, entity, action, or IP..."
        @details="openDetails"
        @update:page="auditStore.index({ page: $event })"
        @update:page-size="auditStore.index({ pageSize: $event, page: 1 })"
        @update:search="auditStore.index({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect
            :model-value="auditStore.params.category ?? ''"
            class="h-9 min-w-40"
            @update:model-value="updateCategory"
          >
            <option value="">All categories</option>
            <option value="activity">Activity</option>
            <option value="auth">Auth</option>
            <option value="audit">Audit</option>
            <option value="error">Error</option>
            <option value="notification">Notification</option>
            <option value="system">System</option>
            <option value="file_upload">File upload</option>
            <option value="permission">Permission</option>
            <option value="transaction">Transaction</option>
          </NativeSelect>

          <NativeSelect
            :model-value="auditStore.params.severity ?? ''"
            class="h-9 min-w-36"
            @update:model-value="updateSeverity"
          >
            <option value="">All severities</option>
            <option value="info">Info</option>
            <option value="warning">Warning</option>
            <option value="error">Error</option>
            <option value="critical">Critical</option>
          </NativeSelect>

          <NativeSelect
            :model-value="auditStore.params.entity_type ?? ''"
            class="h-9 min-w-44"
            @update:model-value="updateFilters({ entity_type: String($event ?? '') })"
          >
            <option value="">All entities</option>
            <option value="tenant_user">Users</option>
            <option value="template">Templates</option>
            <option value="post">Posts</option>
            <option value="lead">Leads</option>
            <option value="design_request">Design requests</option>
            <option value="tenant_system_setting">System settings</option>
            <option value="sidebar">Sidebars</option>
            <option value="tenant_session">Sessions</option>
          </NativeSelect>

          <Input
            :model-value="auditStore.params.actor ?? ''"
            class="h-9 min-w-44 sm:max-w-52"
            placeholder="Actor"
            @update:model-value="updateFilters({ actor: String($event ?? '') })"
          />
          <Input
            :model-value="auditStore.params.ip_address ?? ''"
            class="h-9 min-w-36 sm:max-w-44"
            placeholder="IP address"
            @update:model-value="updateFilters({ ip_address: String($event ?? '') })"
          />
          <Input
            :model-value="auditStore.params.date_from ?? ''"
            class="h-9 min-w-36 sm:max-w-40"
            type="date"
            @update:model-value="updateFilters({ date_from: String($event ?? '') })"
          />
          <Input
            :model-value="auditStore.params.date_to ?? ''"
            class="h-9 min-w-36 sm:max-w-40"
            type="date"
            @update:model-value="updateFilters({ date_to: String($event ?? '') })"
          />
        </template>

        <template #empty-icon>
          <FileClock class="size-5" />
        </template>

        <template #cell-occurred_at="{ row }">
          <span class="whitespace-nowrap text-sm">
            {{ formatDisplayDate(row.occurred_at) || 'Unknown' }}
          </span>
        </template>

        <template #cell-category="{ row }">
          <Badge variant="neutral">{{ labelFor(row.category) }}</Badge>
        </template>

        <template #cell-severity="{ row }">
          <Badge :variant="severityVariant(row.severity)">{{ labelFor(row.severity) }}</Badge>
        </template>

        <template #cell-action="{ row }">
          <Badge :variant="actionVariant(row.action)">{{ labelFor(row.action) }}</Badge>
        </template>

        <template #cell-actor="{ row }">
          <div>
            <p class="text-sm font-medium">{{ actorLabel(row) }}</p>
            <p class="text-xs text-muted-foreground">{{ row.actor.email || row.actor.type }}</p>
          </div>
        </template>

        <template #cell-entity="{ row }">
          <div>
            <p class="max-w-64 truncate text-sm font-medium">{{ entityLabel(row) }}</p>
            <p class="text-xs text-muted-foreground">{{ labelFor(row.entity.type) }}</p>
          </div>
        </template>

        <template #cell-ip_address="{ row }">
          <code class="text-xs">{{ row.ip_address || 'Not captured' }}</code>
        </template>

        <template #cell-summary="{ row }">
          <div class="flex items-center gap-2 text-xs text-muted-foreground">
            <Search class="size-3" />
            <span class="max-w-64 truncate">{{ row.summary || compactValue(row.metadata) }}</span>
          </div>
        </template>
      </BaseTable>

      <Dialog :open="detailsOpen" @update:open="detailsOpen = $event">
        <DialogScrollContent class="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>Audit Log Detail</DialogTitle>
            <DialogDescription>
              Actor, entity, timestamp, request context, and recorded before/after values.
            </DialogDescription>
          </DialogHeader>

          <div v-if="auditStore.selected" class="space-y-4">
            <div class="grid gap-3 md:grid-cols-2">
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Actor</p>
                <p class="mt-1 text-sm font-medium">{{ actorLabel(auditStore.selected) }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ auditStore.selected.actor.email || auditStore.selected.actor.type }}
                </p>
              </div>
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Entity</p>
                <p class="mt-1 text-sm font-medium">{{ entityLabel(auditStore.selected) }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ labelFor(auditStore.selected.entity.type) }}
                  <span v-if="auditStore.selected.entity.id">
                    / {{ auditStore.selected.entity.id }}
                  </span>
                </p>
              </div>
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Category and Severity</p>
                <div class="mt-1 flex flex-wrap gap-2">
                  <Badge variant="neutral">{{ labelFor(auditStore.selected.category) }}</Badge>
                  <Badge :variant="severityVariant(auditStore.selected.severity)">
                    {{ labelFor(auditStore.selected.severity) }}
                  </Badge>
                </div>
              </div>
              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Timestamp and IP</p>
                <p class="mt-1 text-sm font-medium">
                  {{ formatDisplayDate(auditStore.selected.occurred_at) || 'Unknown' }}
                </p>
                <p class="text-xs text-muted-foreground">
                  {{ auditStore.selected.ip_address || 'IP address not captured' }}
                </p>
              </div>
              <div class="rounded border p-3 md:col-span-2">
                <p class="text-xs text-muted-foreground">Action</p>
                <Badge class="mt-1" :variant="actionVariant(auditStore.selected.action)">
                  {{ labelFor(auditStore.selected.action) }}
                </Badge>
                <p class="mt-2 text-xs text-muted-foreground">
                  {{ auditStore.selected.summary }}
                </p>
              </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
              <section class="rounded border">
                <div class="border-b px-3 py-2 text-sm font-medium">Previous Value</div>
                <pre class="max-h-96 overflow-auto p-3 text-xs">{{
                  formatJson(auditStore.selected.previous_value)
                }}</pre>
              </section>
              <section class="rounded border">
                <div class="border-b px-3 py-2 text-sm font-medium">New Value</div>
                <pre class="max-h-96 overflow-auto p-3 text-xs">{{
                  formatJson(auditStore.selected.new_value)
                }}</pre>
              </section>
            </div>

            <section class="rounded border">
              <div class="border-b px-3 py-2 text-sm font-medium">Metadata</div>
              <pre class="max-h-60 overflow-auto p-3 text-xs">{{
                formatJson(auditStore.selected.metadata)
              }}</pre>
            </section>
          </div>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
