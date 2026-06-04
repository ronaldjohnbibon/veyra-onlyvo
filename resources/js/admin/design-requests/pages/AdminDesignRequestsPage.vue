<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useAdminDesignRequestStore } from '@/admin/design-requests/design-request-store'
import type {
  DesignRequestEventRecord,
  DesignRequestRecord,
  DesignRequestStatus,
} from '@/shared/types/design-requests'
import { Eye, FileText, Link2, MessageSquare, Save } from 'lucide-vue-next'
import { computed, onMounted, reactive, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const designRequestStore = useAdminDesignRequestStore()
const reviewDialogOpen = ref(false)
const selectedRequest = ref<DesignRequestRecord | null>(null)
const formError = ref('')
const commentText = ref('')
const collaborationError = ref('')

const columns = [
  { key: 'title', label: 'Request', sortable: true },
  { key: 'tenant_name', label: 'Tenant' },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'files', label: 'Files' },
  { key: 'created_at', label: 'Submitted', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-28', cellClass: 'text-right' },
] as const

const form = reactive({
  status: 'pending' as DesignRequestStatus,
  admin_remarks: '',
})

const page = computed(() => designRequestStore.params.page ?? 1)
const pageSize = computed(() => designRequestStore.params.pageSize ?? 15)
const search = computed(() => designRequestStore.params.search ?? '')
const sortKey = computed(() => designRequestStore.params.sort ?? '')
const sortDirection = computed(() => designRequestStore.params.direction ?? '')
const statusFilter = computed({
  get: () => designRequestStore.params.status ?? '',
  set: (value: DesignRequestStatus | '') => {
    designRequestStore.index({ status: value, page: 1 })
  },
})

const openReviewDialog = async (request: DesignRequestRecord): Promise<void> => {
  selectedRequest.value = request
  form.status = request.status
  form.admin_remarks = request.admin_remarks ?? ''
  formError.value = ''
  commentText.value = ''
  collaborationError.value = ''
  designRequestStore.errors = {}
  reviewDialogOpen.value = true
  await designRequestStore.show(request.id)
  selectedRequest.value = designRequestStore.request

  if (selectedRequest.value) {
    form.status = selectedRequest.value.status
    form.admin_remarks = selectedRequest.value.admin_remarks ?? ''
  }
}

const saveReview = async (): Promise<void> => {
  if (!selectedRequest.value) return

  try {
    formError.value = ''
    const updated = await designRequestStore.update(selectedRequest.value.id, { ...form })
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
  designRequestStore.index({
    sort: direction ? key : 'created_at',
    direction: direction || 'desc',
    page: 1,
  })
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
  designRequestStore.index()
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

      <BaseTable
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
        @update:page="designRequestStore.index({ page: $event })"
        @update:page-size="designRequestStore.index({ pageSize: $event, page: 1 })"
        @update:search="designRequestStore.index({ search: $event, page: 1 })"
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
                    <p v-if="event.message" class="mt-2 whitespace-pre-line text-sm text-muted-foreground">
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
