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
import { useAdminAuditLogStore } from '@/admin/audit-logs/audit-log-store'
import type { AuditLogParams, AuditLogRecord } from '@/admin/audit-logs/types'
import { Download, FileClock, Search } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const auditStore = useAdminAuditLogStore()
const detailsOpen = ref(false)

const columns = [
  { key: 'occurred_at', label: 'Timestamp', sortable: true },
  { key: 'actor', label: 'Actor', sortable: true },
  { key: 'entity', label: 'Entity', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'ip_address', label: 'IP Address', sortable: true },
  { key: 'summary', label: 'Change' },
] as const

const page = computed(() => auditStore.params.page ?? 1)
const pageSize = computed(() => auditStore.params.pageSize ?? 15)
const search = computed(() => auditStore.params.search ?? '')
const sortKey = computed(() => auditStore.params.sort ?? '')
const sortDirection = computed(() => auditStore.params.direction ?? '')

const updateFilters = (params: Partial<AuditLogParams>): void => {
  auditStore.index({ ...params, page: 1 })
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
  if (action.includes('deleted') || action.includes('deactivated')) return 'destructive'
  if (action.includes('created') || action.includes('published') || action.includes('login')) {
    return 'success'
  }
  if (action.includes('restored') || action.includes('rolled_back')) return 'warning'
  if (action.includes('updated') || action.includes('changed')) return 'info'

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
  downloadBlob(await auditStore.exportLogs(), 'audit-logs.csv')
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
            Search and review platform actions across tenants, templates, design requests, settings,
            navigation, and admin sessions.
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
        empty-text="Tracked administrative actions will appear here after the audit table is migrated."
        search-placeholder="Search actor, entity, action, or IP..."
        @details="openDetails"
        @update:page="auditStore.index({ page: $event })"
        @update:page-size="auditStore.index({ pageSize: $event, page: 1 })"
        @update:search="auditStore.index({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect
            :model-value="auditStore.params.entity_type ?? ''"
            class="h-9 min-w-44"
            @update:model-value="updateFilters({ entity_type: String($event ?? '') })"
          >
            <option value="">All entities</option>
            <option value="tenant">Tenants</option>
            <option value="template">Templates</option>
            <option value="design_request">Design requests</option>
            <option value="system_setting">System settings</option>
            <option value="sidebar">Sidebars</option>
            <option value="admin_session">Admin sessions</option>
          </NativeSelect>

          <NativeSelect
            :model-value="auditStore.params.action ?? ''"
            class="h-9 min-w-44"
            @update:model-value="updateFilters({ action: String($event ?? '') })"
          >
            <option value="">All actions</option>
            <option value="tenant.created">Tenant created</option>
            <option value="tenant.updated">Tenant updated</option>
            <option value="tenant.deleted">Tenant deleted</option>
            <option value="tenant.status_changed">Tenant status changed</option>
            <option value="template.updated">Template updated</option>
            <option value="template.published">Template published</option>
            <option value="design_request.updated">Design request updated</option>
            <option value="system_setting.updated">System setting updated</option>
            <option value="sidebar.updated">Sidebar updated</option>
            <option value="admin.login">Admin login</option>
            <option value="admin.logout">Admin logout</option>
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

        <template #cell-action="{ row }">
          <Badge :variant="actionVariant(row.action)">{{ labelFor(row.action) }}</Badge>
        </template>

        <template #cell-ip_address="{ row }">
          <code class="text-xs">{{ row.ip_address || 'Not captured' }}</code>
        </template>

        <template #cell-summary="{ row }">
          <div class="flex items-center gap-2 text-xs text-muted-foreground">
            <Search class="size-3" />
            <span class="max-w-64 truncate">
              {{ compactValue(row.previous_value) }} -> {{ compactValue(row.new_value) }}
            </span>
          </div>
        </template>
      </BaseTable>

      <Dialog :open="detailsOpen" @update:open="detailsOpen = $event">
        <DialogScrollContent class="sm:max-w-4xl">
          <DialogHeader>
            <DialogTitle>Audit Log Detail</DialogTitle>
            <DialogDescription>
              Actor, entity, timestamp, IP address, and recorded before/after values.
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
                <p class="text-xs text-muted-foreground">Action</p>
                <Badge class="mt-1" :variant="actionVariant(auditStore.selected.action)">
                  {{ labelFor(auditStore.selected.action) }}
                </Badge>
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
