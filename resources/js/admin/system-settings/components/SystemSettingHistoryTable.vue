<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { NativeSelect } from '@/shared/components/ui/native-select'
import type {
  SystemSettingHistoryParams,
  SystemSettingHistoryRecord,
  SystemSettingValue,
} from '@/admin/system-settings/types'
import { RotateCcw } from 'lucide-vue-next'

type SortDirection = 'asc' | 'desc' | ''

const props = defineProps<{
  records: SystemSettingHistoryRecord[]
  total: number
  params: SystemSettingHistoryParams
  loading?: boolean
  actionOptions?: string[]
}>()

const emit = defineEmits<{
  load: [params: Partial<SystemSettingHistoryParams>]
  restore: [record: SystemSettingHistoryRecord]
}>()

const columns = [
  { key: 'setting_key', label: 'Setting', sortable: true },
  { key: 'action', label: 'Action', sortable: true },
  { key: 'previous_value', label: 'Previous' },
  { key: 'new_value', label: 'New' },
  { key: 'changed_by', label: 'Changed By', sortable: true, sortKey: 'changed_by' },
  { key: 'changed_at', label: 'Changed', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-[105px]', cellClass: 'text-right' },
] as const

const page = (): number => props.params.page ?? 1
const pageSize = (): number => props.params.pageSize ?? 15
const search = (): string => props.params.search ?? ''
const sortKey = (): string => props.params.sort ?? 'changed_at'
const sortDirection = (): SortDirection => props.params.direction ?? 'desc'
const actionFilter = (): string => props.params.action ?? ''

const actionLabel = (action: string): string => {
  return action.charAt(0).toUpperCase() + action.slice(1)
}

const actionVariant = (action: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
  if (action === 'created') return 'default'
  if (action === 'deleted') return 'destructive'

  return 'secondary'
}

const formatSettingValue = (value: SystemSettingValue): string => {
  if (value === null || value === '') return 'Empty'
  if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled'

  return String(value)
}

const changedBy = (record: SystemSettingHistoryRecord): string => {
  return record.changed_by.name || record.changed_by.email || 'System'
}

const formatChangedAt = (value: string | null): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

const updateSort = (key: string, direction: SortDirection): void => {
  emit('load', {
    sort: direction ? key : 'changed_at',
    direction: direction || 'desc',
    page: 1,
  })
}

const updateAction = (event: Event): void => {
  emit('load', {
    action: (event.target as HTMLSelectElement).value,
    page: 1,
  })
}
</script>

<template>
  <BaseTable
    :columns="columns"
    :data="records"
    :page="page()"
    :page-size="pageSize()"
    :total="total"
    :search="search()"
    :loading="loading"
    :sort-key="sortKey()"
    :sort-direction="sortDirection()"
    with-search
    with-page-size
    empty-text="No settings changes recorded yet."
    loading-text="Loading history..."
    search-placeholder="Search history..."
    @update:page="emit('load', { page: $event })"
    @update:page-size="emit('load', { pageSize: $event, page: 1 })"
    @update:search="emit('load', { search: $event, page: 1 })"
    @update:sort="updateSort"
  >
    <template #filters>
      <NativeSelect
        v-field-help="'Filter history by change type.'"
        :model-value="actionFilter()"
        class="h-9 min-w-36"
        @change="updateAction"
      >
        <option value="">All actions</option>
        <option
          v-for="action in actionOptions ?? [
            'created',
            'updated',
            'deleted',
            'restored',
            'tested',
            'exported',
            'backed_up',
          ]"
          :key="action"
          :value="action"
        >
          {{ actionLabel(action) }}
        </option>
      </NativeSelect>
    </template>

    <template #cell-setting_key="{ row }">
      <span class="break-all font-mono text-xs">{{ row.setting_key }}</span>
    </template>

    <template #cell-action="{ row }">
      <Badge :variant="actionVariant(row.action)">
        {{ actionLabel(row.action) }}
      </Badge>
    </template>

    <template #cell-previous_value="{ row }">
      <span class="line-clamp-3 break-words text-sm">
        {{ formatSettingValue(row.previous_value) }}
      </span>
    </template>

    <template #cell-new_value="{ row }">
      <span class="line-clamp-3 break-words text-sm">
        {{ formatSettingValue(row.new_value) }}
      </span>
    </template>

    <template #cell-changed_by="{ row }">
      <div class="text-sm">{{ changedBy(row) }}</div>
    </template>

    <template #cell-changed_at="{ row }">
      <span class="text-sm">{{ formatChangedAt(row.changed_at) }}</span>
    </template>

    <template #cell-actions="{ row }">
      <Button
        v-if="row.can_restore"
        v-field-help="'Restore this setting to its previous value.'"
        size="xs"
        variant="restore"
        type="button"
        @click.stop="emit('restore', row)"
      >
        <RotateCcw class="size-3" />
        Restore
      </Button>
    </template>
  </BaseTable>
</template>
