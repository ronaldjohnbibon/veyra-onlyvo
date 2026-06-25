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
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useAdminDesignRequestStore } from '@/admin/design-requests/design-request-store'
import type {
  DesignRequestEventRecord,
  DesignRequestParams,
  DesignRequestPriority,
  DesignRequestRecord,
  DesignRequestStatus,
} from '@/shared/types/design-requests'
import {
  Bell,
  Columns3,
  Eye,
  FileText,
  Kanban,
  Link2,
  List,
  MessageSquare,
  Save,
  Sparkles,
} from 'lucide-vue-next'
import { computed, onMounted, reactive, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const designRequestStore = useAdminDesignRequestStore()
const reviewDialogOpen = ref(false)
const selectedRequest = ref<DesignRequestRecord | null>(null)
const formError = ref('')
const commentText = ref('')
const collaborationError = ref('')
const viewMode = ref<'list' | 'board'>('list')
const conversionSummary = ref('')
const conversionTarget = ref('')
const linkedTemplateId = ref('')
const linkedSiteUrl = ref('')
const actionError = ref('')

const columns = [
  { key: 'title', label: 'Request', sortable: true },
  { key: 'tenant_name', label: 'Tenant' },
  { key: 'priority', label: 'Priority', sortable: true },
  { key: 'assignee', label: 'Assignee' },
  { key: 'sla', label: 'SLA' },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'files', label: 'Files' },
  { key: 'created_at', label: 'Submitted', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-28', cellClass: 'text-right' },
] as const

const form = reactive({
  status: 'pending' as DesignRequestStatus,
  assigned_to: '' as number | string | '',
  priority: 'normal' as DesignRequestPriority,
  due_at: '',
  sla_due_at: '',
  admin_remarks: '',
  internal_notes: '',
})

const page = computed(() => designRequestStore.params.page ?? 1)
const pageSize = computed(() => designRequestStore.params.pageSize ?? 15)
const search = computed(() => designRequestStore.params.search ?? '')
const sortKey = computed(() => designRequestStore.params.sort ?? '')
const sortDirection = computed(() => designRequestStore.params.direction ?? '')
const statusFilter = computed({
  get: () => designRequestStore.params.status ?? '',
  set: (value: DesignRequestStatus | '') => {
    refreshRequests({ status: value, page: 1 })
  },
})
const priorityFilter = computed({
  get: () => designRequestStore.params.priority ?? '',
  set: (value: DesignRequestPriority | '') => {
    refreshRequests({ priority: value, page: 1 })
  },
})
const assigneeFilter = computed({
  get: () => designRequestStore.params.assigned_to ?? '',
  set: (value: number | string | '') => {
    refreshRequests({ assigned_to: value, page: 1 })
  },
})
const activeWorkload = computed(() => designRequestStore.workload)

const formatDateTimeInput = (value?: string | null): string => {
  if (!value) return ''

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return ''

  const pad = (part: number): string => String(part).padStart(2, '0')

  return (
    [date.getFullYear(), pad(date.getMonth() + 1), pad(date.getDate())].join('-') +
    `T${pad(date.getHours())}:${pad(date.getMinutes())}`
  )
}

const nullableInput = (value: string): string | null => value || null

const refreshRequests = async (params: Partial<DesignRequestParams> = {}): Promise<void> => {
  await Promise.all([designRequestStore.index(params), designRequestStore.board(params)])
}

const openReviewDialog = async (request: DesignRequestRecord): Promise<void> => {
  selectedRequest.value = request
  form.status = request.status
  form.assigned_to = request.assigned_to ?? ''
  form.priority = request.priority ?? 'normal'
  form.due_at = formatDateTimeInput(request.due_at)
  form.sla_due_at = formatDateTimeInput(request.sla_due_at)
  form.admin_remarks = request.admin_remarks ?? ''
  form.internal_notes = request.internal_notes ?? ''
  formError.value = ''
  commentText.value = ''
  conversionSummary.value = request.conversion_payload?.summary
    ? String(request.conversion_payload.summary)
    : ''
  conversionTarget.value = request.conversion_payload?.target_key
    ? String(request.conversion_payload.target_key)
    : ''
  linkedTemplateId.value = request.linked_template_id ?? ''
  linkedSiteUrl.value = request.linked_site_url ?? ''
  collaborationError.value = ''
  actionError.value = ''
  designRequestStore.errors = {}
  reviewDialogOpen.value = true
  await designRequestStore.show(request.id)
  selectedRequest.value = designRequestStore.request

  if (selectedRequest.value) {
    form.status = selectedRequest.value.status
    form.assigned_to = selectedRequest.value.assigned_to ?? ''
    form.priority = selectedRequest.value.priority ?? 'normal'
    form.due_at = formatDateTimeInput(selectedRequest.value.due_at)
    form.sla_due_at = formatDateTimeInput(selectedRequest.value.sla_due_at)
    form.admin_remarks = selectedRequest.value.admin_remarks ?? ''
    form.internal_notes = selectedRequest.value.internal_notes ?? ''
  }
}

const saveReview = async (): Promise<void> => {
  if (!selectedRequest.value) return

  try {
    formError.value = ''
    const updated = await designRequestStore.update(selectedRequest.value.id, {
      ...form,
      assigned_to: form.assigned_to || null,
      due_at: nullableInput(form.due_at),
      sla_due_at: nullableInput(form.sla_due_at),
    })
    selectedRequest.value = updated
    reviewDialogOpen.value = false
  } catch {
    formError.value = 'Please check the review details and try again.'
  }
}

const submitComment = async (): Promise<void> => {
  if (!selectedRequest.value || !commentText.value.trim()) return

  try {
    collaborationError.value = ''
    selectedRequest.value = await designRequestStore.comment(selectedRequest.value.id, {
      message: commentText.value.trim(),
    })
    commentText.value = ''
  } catch {
    collaborationError.value = 'Could not add the comment. Please try again.'
  }
}

const updateSort = (key: string, direction: SortDirection): void => {
  refreshRequests({
    sort: direction ? key : 'created_at',
    direction: direction || 'desc',
    page: 1,
  })
}

const priorityVariant = (
  priority: DesignRequestPriority
): 'destructive' | 'warning' | 'info' | 'outline' => {
  if (priority === 'urgent') return 'destructive'
  if (priority === 'high') return 'warning'
  if (priority === 'low') return 'outline'

  return 'info'
}

const slaVariant = (state?: string): 'destructive' | 'warning' | 'success' | 'outline' | 'info' => {
  if (state === 'overdue' || state === 'missed') return 'destructive'
  if (state === 'due_soon') return 'warning'
  if (state === 'met' || state === 'on_track') return 'success'

  return 'outline'
}

const convertRequest = async (type: 'template' | 'catalog'): Promise<void> => {
  if (!selectedRequest.value) return

  try {
    actionError.value = ''
    const payload = {
      summary: conversionSummary.value || selectedRequest.value.title,
      target_key: conversionTarget.value || null,
      changelog: conversionSummary.value || null,
    }
    selectedRequest.value =
      type === 'template'
        ? await designRequestStore.convertTemplateImprovement(selectedRequest.value.id, payload)
        : await designRequestStore.convertCatalogChange(selectedRequest.value.id, payload)
  } catch {
    actionError.value = 'Could not convert this request.'
  }
}

const linkCompletedWork = async (): Promise<void> => {
  if (!selectedRequest.value) return

  try {
    actionError.value = ''
    selectedRequest.value = await designRequestStore.linkCompletedWork(selectedRequest.value.id, {
      linked_template_id: linkedTemplateId.value || null,
      linked_site_url: linkedSiteUrl.value || null,
    })
  } catch {
    actionError.value = 'Could not link completed work.'
  }
}

const notifyTenant = async (): Promise<void> => {
  if (!selectedRequest.value) return

  try {
    actionError.value = ''
    selectedRequest.value = await designRequestStore.notify(selectedRequest.value.id)
  } catch {
    actionError.value = 'Could not send notification.'
  }
}

const formatFileSize = (size: number): string => {
  if (size < 1024 * 1024) return `${Math.max(1, Math.round(size / 1024))} KB`

  return `${(size / 1024 / 1024).toFixed(1)} MB`
}

const eventTitle = (event: DesignRequestEventRecord): string => {
  if (event.event_type === 'status_changed') {
    return `Status changed to ${getStatusLabel(event.to_status ?? '')}`
  }

  if (event.event_type === 'approval') return 'Approved by tenant'
  if (event.event_type === 'changes_requested') return 'Changes requested'
  if (event.event_type === 'feedback') return 'Admin feedback'
  if (event.event_type === 'comment') return 'Comment'
  if (event.event_type === 'assigned') return 'Assignment updated'
  if (event.event_type === 'priority_changed') return 'Priority updated'
  if (event.event_type === 'converted_to_template_improvement')
    return 'Converted to template improvement'
  if (event.event_type === 'converted_to_catalog_change') return 'Converted to catalog change'
  if (event.event_type === 'linked_completed_work') return 'Linked completed work'
  if (event.event_type === 'notification_marked') return 'Notification sent'
  if (event.event_type === 'created') return 'Request submitted'

  return event.event_type.replace(/_/g, ' ')
}

const actorLabel = (event: DesignRequestEventRecord): string => {
  if (event.actor_name) return event.actor_name
  if (event.actor_type === 'admin') return 'Admin'
  if (event.actor_type === 'tenant') return 'Tenant'

  return 'System'
}

onMounted(() => {
  Promise.all([
    designRequestStore.index(),
    designRequestStore.board(),
    designRequestStore.loadWorkload(),
  ])
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Design Requests</h2>
          <p class="module-container-description">
            Review tenant custom website design requests, files, statuses, and feedback.
          </p>
        </div>
      </div>

      <div class="mb-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_auto]">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
          <Card class="py-4">
            <CardContent class="px-4">
              <p class="text-xs text-muted-foreground">Unassigned</p>
              <p class="mt-1 text-2xl font-semibold">{{ activeWorkload?.unassigned ?? 0 }}</p>
            </CardContent>
          </Card>
          <Card v-for="assignee in activeWorkload?.assignees ?? []" :key="assignee.id" class="py-4">
            <CardContent class="px-4">
              <p class="truncate text-xs text-muted-foreground">{{ assignee.name }}</p>
              <div class="mt-1 flex items-end justify-between gap-2">
                <p class="text-2xl font-semibold">{{ assignee.assigned }}</p>
                <div class="flex gap-1">
                  <Badge v-if="assignee.urgent" variant="destructive"
                    >{{ assignee.urgent }} urgent</Badge
                  >
                  <Badge v-if="assignee.overdue" variant="warning"
                    >{{ assignee.overdue }} late</Badge
                  >
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="flex items-start justify-end gap-2">
          <Button
            size="sm"
            :variant="viewMode === 'list' ? 'default' : 'navigate'"
            type="button"
            @click="viewMode = 'list'"
          >
            <List class="size-4" />
            List
          </Button>
          <Button
            size="sm"
            :variant="viewMode === 'board' ? 'default' : 'navigate'"
            type="button"
            @click="viewMode = 'board'"
          >
            <Kanban class="size-4" />
            Board
          </Button>
        </div>
      </div>

      <BaseTable
        v-if="viewMode === 'list'"
        :columns="columns"
        :data="designRequestStore.requests"
        :page="page"
        :page-size="pageSize"
        :total="designRequestStore.total"
        :search="search"
        :loading="designRequestStore.loading"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        with-search
        with-details
        with-page-size
        empty-text="No design requests found."
        search-placeholder="Search requests..."
        @details="openReviewDialog"
        @update:page="refreshRequests({ page: $event })"
        @update:page-size="refreshRequests({ pageSize: $event, page: 1 })"
        @update:search="refreshRequests({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect
            v-field-help="'Filter requests by review status.'"
            v-model="statusFilter"
            class="h-9 min-w-40"
          >
            <option value="">All statuses</option>
            <option value="pending">Pending</option>
            <option value="under_review">Under Review</option>
            <option value="approved">Approved</option>
            <option value="changes_requested">Changes Requested</option>
            <option value="rejected">Rejected</option>
            <option value="completed">Completed</option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter requests by operational priority.'"
            v-model="priorityFilter"
            class="h-9 min-w-36"
          >
            <option value="">All priorities</option>
            <option value="urgent">Urgent</option>
            <option value="high">High</option>
            <option value="normal">Normal</option>
            <option value="low">Low</option>
          </NativeSelect>

          <NativeSelect
            v-field-help="'Filter requests by assigned admin.'"
            v-model="assigneeFilter"
            class="h-9 min-w-44"
          >
            <option value="">All assignees</option>
            <option value="">Unassigned and assigned</option>
            <option
              v-for="assignee in activeWorkload?.assignees ?? []"
              :key="assignee.id"
              :value="assignee.id"
            >
              {{ assignee.name }}
            </option>
          </NativeSelect>
        </template>

        <template #cell-title="{ row }">
          <div class="font-medium text-foreground">{{ row.title }}</div>
          <div class="line-clamp-1 text-xs text-muted-foreground">
            {{ row.description }}
          </div>
        </template>

        <template #cell-tenant_name="{ row }">
          <div class="text-sm">{{ row.tenant_name || 'Unknown tenant' }}</div>
          <div class="text-xs text-muted-foreground">
            {{ row.requester_name || 'No requester' }}
          </div>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="getStatusBadgeVariant(row.status)">
            {{ getStatusLabel(row.status) }}
          </Badge>
        </template>

        <template #cell-priority="{ row }">
          <Badge :variant="priorityVariant(row.priority)">
            {{ row.priority }}
          </Badge>
        </template>

        <template #cell-assignee="{ row }">
          <span class="text-sm text-muted-foreground">
            {{ row.assignee_name || 'Unassigned' }}
          </span>
        </template>

        <template #cell-sla="{ row }">
          <Badge :variant="slaVariant(row.sla?.state)">
            {{ row.sla?.label ?? 'No SLA' }}
          </Badge>
        </template>

        <template #cell-files="{ row }">
          <span class="text-sm text-muted-foreground">{{ row.files?.length ?? 0 }} files</span>
        </template>

        <template #cell-created_at="{ row }">
          <span class="text-sm text-muted-foreground">{{ formatDisplayDate(row.created_at) }}</span>
        </template>

        <template #cell-actions="{ row }">
          <Button
            variant="navigate"
            size="sm"
            type="button"
            aria-label="Review request"
            title="Review request"
            @click.stop="openReviewDialog(row)"
          >
            <Eye class="size-4" />
            Review
          </Button>
        </template>
      </BaseTable>

      <div v-else class="grid gap-3 xl:grid-cols-3 2xl:grid-cols-6">
        <Card
          v-for="column in designRequestStore.boardColumns"
          :key="column.status"
          class="min-h-72 py-4"
        >
          <CardHeader class="px-4">
            <CardTitle class="flex items-center justify-between gap-3 text-sm">
              <span>{{ column.label }}</span>
              <Badge variant="outline">{{ column.items.length }}</Badge>
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-2 px-4">
            <div
              v-for="request in column.items"
              :key="request.id"
              class="cursor-pointer rounded border bg-background p-3 transition hover:border-primary"
              @click="openReviewDialog(request)"
            >
              <div class="flex items-start justify-between gap-2">
                <p class="line-clamp-2 text-sm font-semibold">{{ request.title }}</p>
                <Badge :variant="priorityVariant(request.priority)">{{ request.priority }}</Badge>
              </div>
              <p class="mt-2 text-xs text-muted-foreground">{{ request.tenant_name }}</p>
              <div class="mt-3 flex flex-wrap gap-1">
                <Badge :variant="slaVariant(request.sla?.state)">
                  {{ request.sla?.label ?? 'No SLA' }}
                </Badge>
                <Badge variant="outline">{{ request.assignee_name || 'Unassigned' }}</Badge>
              </div>
            </div>
            <p
              v-if="!column.items.length"
              class="rounded border bg-muted/40 p-3 text-sm text-muted-foreground"
            >
              No requests.
            </p>
          </CardContent>
        </Card>
      </div>

      <Dialog :open="reviewDialogOpen" @update:open="reviewDialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-4xl">
          <DialogHeader>
            <DialogTitle>{{ selectedRequest?.title }}</DialogTitle>
            <DialogDescription>
              {{ selectedRequest?.tenant_name || 'Tenant' }} submitted this request
              {{ formatDisplayDate(selectedRequest?.created_at) }}.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedRequest" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-5">
              <div class="flex flex-wrap items-center gap-2">
                <Badge :variant="getStatusBadgeVariant(selectedRequest.status)">
                  {{ getStatusLabel(selectedRequest.status) }}
                </Badge>
                <Badge :variant="priorityVariant(selectedRequest.priority)">
                  {{ selectedRequest.priority }}
                </Badge>
                <Badge :variant="slaVariant(selectedRequest.sla?.state)">
                  {{ selectedRequest.sla?.label ?? 'No SLA' }}
                </Badge>
                <span class="text-sm text-muted-foreground">
                  {{ selectedRequest.files.length }} attached files
                </span>
              </div>
              <p class="rounded border bg-muted/40 p-3 text-sm leading-6 text-muted-foreground">
                {{ selectedRequest.status_explanation }}
              </p>

              <div class="space-y-1">
                <h3 class="text-sm font-semibold">Description</h3>
                <p class="whitespace-pre-line text-sm text-muted-foreground">
                  {{ selectedRequest.description }}
                </p>
              </div>

              <div v-if="selectedRequest.notes" class="space-y-1">
                <h3 class="text-sm font-semibold">Notes / Instructions</h3>
                <p class="whitespace-pre-line text-sm text-muted-foreground">
                  {{ selectedRequest.notes }}
                </p>
              </div>

              <div v-if="selectedRequest.mockup_concept" class="space-y-1">
                <h3 class="text-sm font-semibold">Mockup / Template Concept</h3>
                <p class="whitespace-pre-line text-sm text-muted-foreground">
                  {{ selectedRequest.mockup_concept }}
                </p>
              </div>

              <div v-if="selectedRequest.reference_links.length" class="space-y-2">
                <h3 class="text-sm font-semibold">Reference Links</h3>
                <div class="space-y-1">
                  <a
                    v-for="link in selectedRequest.reference_links"
                    :key="link"
                    :href="link"
                    target="_blank"
                    rel="noreferrer"
                    class="flex items-center gap-2 text-sm text-primary hover:underline"
                  >
                    <Link2 class="size-4" />
                    {{ link }}
                  </a>
                </div>
              </div>

              <div class="space-y-2">
                <h3 class="text-sm font-semibold">Uploaded Files</h3>
                <div v-if="selectedRequest.files.length" class="grid gap-2 sm:grid-cols-2">
                  <a
                    v-for="file in selectedRequest.files"
                    :key="file.id"
                    :href="file.url"
                    target="_blank"
                    rel="noreferrer"
                    class="overflow-hidden rounded border text-sm hover:bg-muted"
                  >
                    <img
                      v-if="file.is_image"
                      :src="file.url"
                      :alt="file.name"
                      class="aspect-video w-full object-cover"
                    />
                    <div class="flex items-center gap-2 p-3">
                      <FileText class="size-4" />
                      <span class="min-w-0 flex-1 truncate">{{ file.name }}</span>
                      <span class="text-xs text-muted-foreground">
                        {{ formatFileSize(file.size) }}
                      </span>
                    </div>
                  </a>
                </div>
                <p v-else class="rounded border bg-muted/40 p-3 text-sm text-muted-foreground">
                  No files were attached to this request.
                </p>
              </div>
            </div>

            <form class="space-y-4" @submit.prevent="saveReview">
              <FieldSet>
                <FieldGroup>
                  <Field>
                    <FieldLabel for="request-status">Status</FieldLabel>

                    <NativeSelect
                      v-field-help="'Choose the tenant-facing review status.'"
                      id="request-status"
                      v-model="form.status"
                    >
                      <option value="pending">Pending</option>
                      <option value="under_review">Under Review</option>
                      <option value="approved">Approved</option>
                      <option value="changes_requested">Changes Requested</option>
                      <option value="rejected">Rejected</option>
                      <option value="completed">Completed</option>
                    </NativeSelect>

                    <FieldError v-if="designRequestStore.errors.status">
                      {{ designRequestStore.errors.status[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="request-assignee">Assignment</FieldLabel>

                    <NativeSelect
                      v-field-help="'Assign this request to the admin responsible for follow-up.'"
                      id="request-assignee"
                      v-model="form.assigned_to"
                    >
                      <option value="">Unassigned</option>
                      <option
                        v-for="assignee in activeWorkload?.assignees ?? []"
                        :key="assignee.id"
                        :value="assignee.id"
                      >
                        {{ assignee.name }}
                      </option>
                    </NativeSelect>

                    <FieldError v-if="designRequestStore.errors.assigned_to">
                      {{ designRequestStore.errors.assigned_to[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="request-priority">Priority</FieldLabel>

                    <NativeSelect
                      v-field-help="'Set the operational urgency for triage and workload planning.'"
                      id="request-priority"
                      v-model="form.priority"
                    >
                      <option value="low">Low</option>
                      <option value="normal">Normal</option>
                      <option value="high">High</option>
                      <option value="urgent">Urgent</option>
                    </NativeSelect>

                    <FieldError v-if="designRequestStore.errors.priority">
                      {{ designRequestStore.errors.priority[0] }}
                    </FieldError>
                  </Field>

                  <div class="grid gap-3 sm:grid-cols-2">
                    <Field>
                      <FieldLabel for="request-due-at">Due Date</FieldLabel>
                      <Input id="request-due-at" v-model="form.due_at" type="datetime-local" />
                      <FieldError v-if="designRequestStore.errors.due_at">
                        {{ designRequestStore.errors.due_at[0] }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="request-sla-due-at">SLA Target</FieldLabel>
                      <Input
                        id="request-sla-due-at"
                        v-model="form.sla_due_at"
                        type="datetime-local"
                      />
                      <FieldError v-if="designRequestStore.errors.sla_due_at">
                        {{ designRequestStore.errors.sla_due_at[0] }}
                      </FieldError>
                    </Field>
                  </div>

                  <Field>
                    <FieldLabel for="admin-remarks">Admin Remarks / Feedback</FieldLabel>

                    <Textarea
                      v-field-help="'Add feedback or instructions the tenant can read.'"
                      id="admin-remarks"
                      v-model="form.admin_remarks"
                      class="min-h-44"
                      placeholder="Add notes for the tenant."
                    />

                    <FieldError v-if="designRequestStore.errors.admin_remarks">
                      {{ designRequestStore.errors.admin_remarks[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="internal-notes">Internal Notes</FieldLabel>

                    <Textarea
                      v-field-help="
                        'Keep internal implementation notes that are not tenant-facing.'
                      "
                      id="internal-notes"
                      v-model="form.internal_notes"
                      class="min-h-32"
                      placeholder="Internal plan, edge cases, or handoff notes."
                    />

                    <FieldError v-if="designRequestStore.errors.internal_notes">
                      {{ designRequestStore.errors.internal_notes[0] }}
                    </FieldError>
                  </Field>
                </FieldGroup>
              </FieldSet>

              <p
                v-if="formError"
                class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
              >
                {{ formError }}
              </p>

              <DialogFooter>
                <Button type="button" variant="cancel" @click="reviewDialogOpen = false">
                  Cancel
                </Button>
                <Button type="submit" variant="update" :disabled="designRequestStore.loading">
                  <Save class="size-4" />
                  Save Review
                </Button>
              </DialogFooter>

              <div class="rounded border bg-card p-4">
                <h3 class="text-sm font-semibold">Operational Actions</h3>
                <div class="mt-3 space-y-3">
                  <Textarea
                    v-field-help="
                      'Summarize the template or catalog work this request should become.'
                    "
                    v-model="conversionSummary"
                    class="min-h-20"
                    placeholder="Summarize the improvement or catalog change."
                  />
                  <Input
                    v-field-help="'Optional template key, catalog key, or implementation target.'"
                    v-model="conversionTarget"
                    placeholder="Optional target key"
                  />
                  <div class="grid gap-2 sm:grid-cols-2">
                    <Button type="button" variant="publish" @click="convertRequest('template')">
                      <Sparkles class="size-4" />
                      Template Improvement
                    </Button>
                    <Button type="button" variant="navigate" @click="convertRequest('catalog')">
                      <Columns3 class="size-4" />
                      Catalog Change
                    </Button>
                  </div>

                  <div class="grid gap-2">
                    <Input
                      v-field-help="'Paste a tenant template id after the work is completed.'"
                      v-model="linkedTemplateId"
                      placeholder="Linked tenant template id"
                    />
                    <Input
                      v-field-help="'Paste the completed tenant site URL.'"
                      v-model="linkedSiteUrl"
                      placeholder="https://tenant.example.com/site"
                    />
                    <Button type="button" variant="update" @click="linkCompletedWork">
                      <Link2 class="size-4" />
                      Link Completed Work
                    </Button>
                  </div>

                  <Button type="button" variant="secondary" class="w-full" @click="notifyTenant">
                    <Bell class="size-4" />
                    Send Tenant Notification
                  </Button>

                  <p v-if="selectedRequest.conversion_type" class="text-xs text-muted-foreground">
                    Converted as {{ selectedRequest.conversion_type.replace(/_/g, ' ') }}
                    {{ formatDisplayDate(selectedRequest.converted_at) }}.
                  </p>
                  <p v-if="selectedRequest.linked_site_url" class="text-xs text-muted-foreground">
                    Linked site: {{ selectedRequest.linked_site_url }}
                  </p>
                  <p
                    v-if="selectedRequest.notification_sent_at"
                    class="text-xs text-muted-foreground"
                  >
                    Notification sent
                    {{ formatDisplayDate(selectedRequest.notification_sent_at) }}.
                  </p>
                  <p
                    v-if="actionError"
                    class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
                  >
                    {{ actionError }}
                  </p>
                </div>
              </div>

              <div class="rounded border bg-card p-4">
                <h3 class="text-sm font-semibold">Discussion</h3>
                <Textarea
                  v-field-help="'Send a message to the tenant about this request.'"
                  v-model="commentText"
                  class="mt-3 min-h-24"
                  placeholder="Ask a question or add implementation notes."
                />
                <Button
                  type="button"
                  variant="update"
                  class="mt-3 w-full"
                  :disabled="designRequestStore.loading || !commentText.trim()"
                  @click="submitComment"
                >
                  <MessageSquare class="size-4" />
                  Add Comment
                </Button>
                <p
                  v-if="collaborationError"
                  class="mt-3 rounded border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
                >
                  {{ collaborationError }}
                </p>
              </div>

              <div class="rounded border bg-card p-4">
                <h3 class="text-sm font-semibold">Timeline</h3>
                <div v-if="selectedRequest.events?.length" class="mt-4 space-y-4">
                  <div
                    v-for="event in selectedRequest.events"
                    :key="event.id"
                    class="border-l pl-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <p class="text-sm font-medium">{{ eventTitle(event) }}</p>
                      <span class="text-xs text-muted-foreground">
                        {{ formatDisplayDate(event.created_at) }}
                      </span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                      {{ actorLabel(event) }}
                    </p>
                    <p
                      v-if="event.message"
                      class="mt-2 whitespace-pre-line text-sm text-muted-foreground"
                    >
                      {{ event.message }}
                    </p>
                  </div>
                </div>
                <p v-else class="mt-3 rounded border bg-muted/40 p-3 text-sm text-muted-foreground">
                  No timeline activity yet.
                </p>
              </div>
            </form>
          </div>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
