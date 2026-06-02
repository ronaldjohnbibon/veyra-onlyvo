<script setup lang="ts" generic="TData extends object">
import { Button } from '@/shared/components/ui/button'
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
import { cn } from '@/shared/utils/utils'
import { ArrowDown, ArrowUp, ArrowUpDown, Plus } from 'lucide-vue-next'
import { computed, ref, watch, type HTMLAttributes } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

interface BaseTableColumn {
  key: string
  label: string
  sortable?: boolean
  sortKey?: string
  headerClass?: HTMLAttributes['class']
  cellClass?: HTMLAttributes['class']
}

const props = withDefaults(
  defineProps<{
    columns: readonly BaseTableColumn[]
    data: TData[]
    page: number
    pageSize: number
    total: number
    search?: string
    loading?: boolean
    withSearch?: boolean
    withCreate?: boolean
    createDisabled?: boolean
    withDetails?: boolean
    withPageSize?: boolean
    createLabel?: string
    emptyText?: string
    loadingText?: string
    rowKey?: string | ((row: TData) => string | number)
    searchPlaceholder?: string
    pageSizeOptions?: number[]
    sortKey?: string
    sortDirection?: SortDirection
  }>(),
  {
    createLabel: 'Create',
    createDisabled: false,
    emptyText: 'No results.',
    loadingText: 'Loading...',
    pageSizeOptions: () => [10, 15, 25, 50],
    rowKey: 'id',
    search: '',
    searchPlaceholder: 'Search...',
    sortDirection: '',
    sortKey: '',
    withCreate: false,
    withDetails: false,
    withPageSize: false,
    withSearch: false,
  }
)

const emit = defineEmits<{
  create: []
  details: [value: TData]
  'update:page': [page: number]
  'update:page-size': [size: number]
  'update:search': [value: string]
  'update:sort': [key: string, direction: SortDirection]
}>()

const searchTerm = ref(props.search)
let searchTimer: ReturnType<typeof setTimeout> | null = null

watch(
  () => props.search,
  (value) => {
    if (value !== searchTerm.value) {
      searchTerm.value = value
    }
  }
)

watch(searchTerm, (value) => {
  if (searchTimer) {
    clearTimeout(searchTimer)
  }

  searchTimer = setTimeout(() => {
    if (value !== props.search) {
      emit('update:search', value)
    }
  }, 500)
})

const pageCount = computed(() => {
  if (!props.pageSize) return 1

  return Math.max(1, Math.ceil(props.total / props.pageSize))
})

const pageNumbers = computed<(number | string)[]>(() => {
  const total = pageCount.value
  const current = Math.min(Math.max(props.page, 1), total)

  if (total <= 7) {
    return Array.from({ length: total }, (_, index) => index + 1)
  }

  const pages: (number | string)[] = [1]
  const start = Math.max(2, current - 1)
  const end = Math.min(total - 1, current + 1)

  if (start > 2) {
    pages.push('...')
  }

  for (let page = start; page <= end; page += 1) {
    pages.push(page)
  }

  if (end < total - 1) {
    pages.push('...')
  }

  pages.push(total)

  return pages
})

const visibleColspan = computed(() => Math.max(props.columns.length, 1))

const goToPage = (page: number): void => {
  const target = Math.min(Math.max(page, 1), pageCount.value)
  emit('update:page', target)
}

const getRowKey = (row: TData, index: number): string | number => {
  if (typeof props.rowKey === 'function') {
    return props.rowKey(row)
  }

  return ((row as Record<string, unknown>)[props.rowKey] as string | number | undefined) ?? index
}

const getCellValue = (row: TData, key: string): unknown => {
  return (row as Record<string, unknown>)[key]
}

const getColumnSortKey = (column: BaseTableColumn): string => {
  return column.sortKey ?? column.key
}

const getNextSortDirection = (column: BaseTableColumn): SortDirection => {
  const key = getColumnSortKey(column)

  if (props.sortKey !== key) return 'asc'
  if (props.sortDirection === 'asc') return 'desc'
  if (props.sortDirection === 'desc') return ''

  return 'asc'
}

const sortColumn = (column: BaseTableColumn): void => {
  if (!column.sortable) return

  emit('update:sort', getColumnSortKey(column), getNextSortDirection(column))
}

const updatePageSize = (event: Event): void => {
  emit('update:page-size', Number((event.target as HTMLSelectElement).value))
}
</script>

<template>
  <div class="space-y-4">
    <div
      v-if="withSearch || withCreate || withPageSize || $slots.filters"
      class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
    >
      <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
        <Input
          v-if="withSearch"
          v-model="searchTerm"
          type="search"
          :placeholder="searchPlaceholder"
          class="sm:max-w-sm"
        />
        <slot name="filters" />
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <NativeSelect
          v-if="withPageSize"
          :model-value="String(pageSize)"
          class="h-8"
          @change="updatePageSize"
        >
          <option v-for="option in pageSizeOptions" :key="option" :value="option">
            {{ option }} rows
          </option>
        </NativeSelect>

        <Button
          v-if="withCreate"
          variant="create"
          size="sm"
          type="button"
          :disabled="createDisabled"
          @click="emit('create')"
        >
          <Plus class="size-3" />
          {{ createLabel }}
        </Button>
      </div>
    </div>

    <div class="overflow-x-auto rounded border bg-background">
      <Table>
        <TableHeader>
          <TableRow>
            <TableHead v-for="column in columns" :key="column.key" :class="column.headerClass">
              <button
                v-if="column.sortable"
                type="button"
                class="inline-flex items-center gap-1 rounded text-left hover:text-foreground"
                @click="sortColumn(column)"
              >
                <slot :name="`header-${column.key}`" :column="column">
                  {{ column.label }}
                </slot>
                <ArrowUp
                  v-if="sortKey === getColumnSortKey(column) && sortDirection === 'asc'"
                  class="size-3"
                />
                <ArrowDown
                  v-else-if="sortKey === getColumnSortKey(column) && sortDirection === 'desc'"
                  class="size-3"
                />
                <ArrowUpDown v-else class="size-3 opacity-60" />
              </button>
              <slot v-else :name="`header-${column.key}`" :column="column">
                {{ column.label }}
              </slot>
            </TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <TableRow v-if="loading">
            <TableCell :colspan="visibleColspan" class="h-24 text-center text-muted-foreground">
              {{ loadingText }}
            </TableCell>
          </TableRow>

          <TableRow v-else-if="!data.length">
            <TableCell :colspan="visibleColspan" class="h-24 text-center text-muted-foreground">
              {{ emptyText }}
            </TableCell>
          </TableRow>

          <template v-else>
            <TableRow
              v-for="(row, index) in data"
              :key="getRowKey(row, index)"
              :class="cn(withDetails && 'cursor-pointer')"
              @click="withDetails && emit('details', row)"
            >
              <TableCell v-for="column in columns" :key="column.key" :class="column.cellClass">
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :value="getCellValue(row, column.key)"
                  :column="column"
                >
                  {{ getCellValue(row, column.key) }}
                </slot>
              </TableCell>
            </TableRow>
          </template>
        </TableBody>
      </Table>
    </div>

    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
      <p class="text-sm text-muted-foreground">Showing {{ data.length }} of {{ total }} results</p>

      <div class="flex items-center justify-end gap-1">
        <Button
          size="sm"
          variant="navigate"
          :disabled="page <= 1 || loading"
          @click="goToPage(page - 1)"
        >
          Previous
        </Button>

        <template v-for="(pageNumber, index) in pageNumbers" :key="`${pageNumber}-${index}`">
          <Button
            v-if="typeof pageNumber === 'number'"
            class="h-8 w-8 p-0"
            size="sm"
            :variant="page === pageNumber ? 'default' : 'navigate'"
            :disabled="loading"
            @click="goToPage(pageNumber)"
          >
            {{ pageNumber }}
          </Button>
          <span v-else class="px-2 text-sm text-muted-foreground">{{ pageNumber }}</span>
        </template>

        <Button
          size="sm"
          variant="navigate"
          :disabled="page >= pageCount || loading"
          @click="goToPage(page + 1)"
        >
          Next
        </Button>
      </div>
    </div>
  </div>
</template>
