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
import FieldHelp from '@/shared/components/FieldHelp.vue'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useDesignRequestStore } from '@/tenant/design-requests/design-request-store'
import type { DesignRequestRecord, DesignRequestStatus } from '@/shared/types/design-requests'
import { Eye, FileText, Link2, Send } from 'lucide-vue-next'
import { computed, onMounted, reactive, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const designRequestStore = useDesignRequestStore()
const createDialogOpen = ref(false)
const detailsDialogOpen = ref(false)
const selectedRequest = ref<DesignRequestRecord | null>(null)
const selectedFiles = ref<File[]>([])
const referenceLinksText = ref('')
const formError = ref('')

const columns = [
  { key: 'title', label: 'Request', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'files', label: 'Files' },
  { key: 'created_at', label: 'Submitted', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-24', cellClass: 'text-right' },
] as const

const form = reactive({
  title: '',
  description: '',
  notes: '',
  reference_links: [] as string[],
  mockup_concept: '',
  files: [] as File[],
})

const firstFieldError = (field: string): string => {
  const directError = designRequestStore.errors[field]?.[0]

  if (directError) {
    return directError
  }

  // Laravel returns array item errors with keys like reference_links.0.
  const nestedKey = Object.keys(designRequestStore.errors).find((key) =>
    key.startsWith(`${field}.`)
  )

  return nestedKey ? (designRequestStore.errors[nestedKey]?.[0] ?? '') : ''
}

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
const referenceLinksError = computed(() => firstFieldError('reference_links'))
const filesError = computed(() => firstFieldError('files'))

const resetForm = (): void => {
  form.title = ''
  form.description = ''
  form.notes = ''
  form.reference_links = []
  form.mockup_concept = ''
  form.files = []
  selectedFiles.value = []
  referenceLinksText.value = ''
  formError.value = ''
  designRequestStore.errors = {}
}

const openCreateDialog = (): void => {
  resetForm()
  createDialogOpen.value = true
}

const openDetailsDialog = (request: DesignRequestRecord): void => {
  selectedRequest.value = request
  detailsDialogOpen.value = true
}

const parseReferenceLinks = (): string[] => {
  return referenceLinksText.value
    .split(/\r\n|\r|\n/)
    .map((link) => link.trim())
    .filter(Boolean)
}

const handleFiles = (event: Event): void => {
  const input = event.target as HTMLInputElement
  selectedFiles.value = Array.from(input.files ?? [])
  form.files = selectedFiles.value
}

const submitRequest = async (): Promise<void> => {
  form.reference_links = parseReferenceLinks()
  form.files = selectedFiles.value
  formError.value = ''

  try {
    await designRequestStore.store({ ...form })
    createDialogOpen.value = false
  } catch {
    formError.value = 'Please check the request details and try again.'
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
            Submit custom website design ideas, mockups, files, and reference links.
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
        with-create
        with-details
        with-page-size
        create-label="Create Request"
        empty-text="No design requests found."
        search-placeholder="Search requests..."
        @create="openCreateDialog"
        @details="openDetailsDialog"
        @update:page="designRequestStore.index({ page: $event })"
        @update:page-size="designRequestStore.index({ pageSize: $event, page: 1 })"
        @update:search="designRequestStore.index({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <FieldHelp description="Filter requests by review status.">
            <NativeSelect v-model="statusFilter" class="h-9 min-w-40">
              <option value="">All statuses</option>
              <option value="pending">Pending</option>
              <option value="under_review">Under Review</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="completed">Completed</option>
            </NativeSelect>
          </FieldHelp>
        </template>

        <template #cell-title="{ row }">
          <div class="font-medium text-foreground">{{ row.title }}</div>
          <div class="line-clamp-1 text-xs text-muted-foreground">
            {{ row.description }}
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
            aria-label="View request"
            title="View request"
            @click.stop="openDetailsDialog(row)"
          >
            <Eye class="size-4" />
          </Button>
        </template>
      </BaseTable>

      <Dialog :open="createDialogOpen" @update:open="createDialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
          <DialogHeader>
            <DialogTitle>Create Design Request</DialogTitle>
            <DialogDescription>
              Share the website design direction, references, files, and optional concept notes.
            </DialogDescription>
          </DialogHeader>

          <form class="space-y-5" @submit.prevent="submitRequest">
            <FieldSet>
              <FieldGroup>
                <Field>
                  <FieldLabel for="design-title">Title</FieldLabel>
                  <FieldHelp description="Enter a short name for this design request.">
                    <Input id="design-title" v-model="form.title" placeholder="Homepage refresh" />
                  </FieldHelp>
                  <FieldError v-if="designRequestStore.errors.title">
                    {{ designRequestStore.errors.title[0] }}
                  </FieldError>
                </Field>

                <Field>
                  <FieldLabel for="design-description">Description</FieldLabel>
                  <FieldHelp description="Describe the website or template changes you need.">
                    <Textarea
                      id="design-description"
                      v-model="form.description"
                      class="min-h-32"
                      placeholder="Describe the website or template you want."
                    />
                  </FieldHelp>
                  <FieldError v-if="designRequestStore.errors.description">
                    {{ designRequestStore.errors.description[0] }}
                  </FieldError>
                </Field>

                <Field>
                  <FieldLabel for="design-notes">Notes / Instructions</FieldLabel>
                  <FieldHelp
                    description="Add optional brand, layout, color, or content instructions."
                  >
                    <Textarea
                      id="design-notes"
                      v-model="form.notes"
                      class="min-h-24"
                      placeholder="Brand tone, layout preferences, required sections, or color notes."
                    />
                  </FieldHelp>
                </Field>

                <Field>
                  <FieldLabel for="design-links">Reference Links</FieldLabel>
                  <FieldHelp description="Add helpful website references, one URL per line.">
                    <Textarea
                      id="design-links"
                      v-model="referenceLinksText"
                      class="min-h-24"
                      placeholder="Add one URL per line."
                    />
                  </FieldHelp>
                  <FieldError v-if="referenceLinksError">
                    {{ referenceLinksError }}
                  </FieldError>
                </Field>

                <Field>
                  <FieldLabel for="design-concept">Mockup / Template Concept</FieldLabel>
                  <FieldHelp
                    description="Outline any mockup idea or template concept you already have."
                  >
                    <Textarea
                      id="design-concept"
                      v-model="form.mockup_concept"
                      class="min-h-32"
                      placeholder="Outline your own concept before submitting."
                    />
                  </FieldHelp>
                </Field>

                <Field>
                  <FieldLabel for="design-files">Mockup Files / Images</FieldLabel>
                  <FieldHelp
                    description="Upload mockups, images, documents, or presentation files."
                  >
                    <Input
                      id="design-files"
                      type="file"
                      multiple
                      accept="image/*,.svg,.pdf,.doc,.docx,.ppt,.pptx"
                      class="cursor-pointer text-muted-foreground"
                      :disabled="designRequestStore.loading"
                      @change="handleFiles"
                    />
                  </FieldHelp>
                  <FieldError v-if="filesError">
                    {{ filesError }}
                  </FieldError>
                  <div v-if="selectedFiles.length" class="space-y-1 text-sm text-muted-foreground">
                    <div
                      v-for="file in selectedFiles"
                      :key="`${file.name}-${file.size}`"
                      class="flex items-center gap-2"
                    >
                      <FileText class="size-4" />
                      <span>{{ file.name }} ({{ formatFileSize(file.size) }})</span>
                    </div>
                  </div>
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
              <Button type="button" variant="cancel" @click="createDialogOpen = false">
                Cancel
              </Button>
              <Button type="submit" variant="create" :disabled="designRequestStore.loading">
                <Send class="size-4" />
                Submit Request
              </Button>
            </DialogFooter>
          </form>
        </DialogScrollContent>
      </Dialog>

      <Dialog :open="detailsDialogOpen" @update:open="detailsDialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
          <DialogHeader>
            <DialogTitle>{{ selectedRequest?.title }}</DialogTitle>
            <DialogDescription>
              Submitted {{ formatDisplayDate(selectedRequest?.created_at) }}
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedRequest" class="space-y-5">
            <div class="flex flex-wrap items-center gap-2">
              <Badge :variant="getStatusBadgeVariant(selectedRequest.status)">
                {{ getStatusLabel(selectedRequest.status) }}
              </Badge>
              <span class="text-sm text-muted-foreground">
                {{ selectedRequest.files.length }} attached files
              </span>
            </div>

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

            <div v-if="selectedRequest.files.length" class="space-y-2">
              <h3 class="text-sm font-semibold">Files</h3>
              <div class="grid gap-2 sm:grid-cols-2">
                <a
                  v-for="file in selectedRequest.files"
                  :key="file.id"
                  :href="file.url"
                  target="_blank"
                  rel="noreferrer"
                  class="flex items-center gap-2 rounded border p-3 text-sm hover:bg-muted"
                >
                  <FileText class="size-4" />
                  <span class="min-w-0 flex-1 truncate">{{ file.name }}</span>
                  <span class="text-xs text-muted-foreground">{{ formatFileSize(file.size) }}</span>
                </a>
              </div>
            </div>

            <div v-if="selectedRequest.admin_remarks" class="space-y-1 rounded border p-3">
              <h3 class="text-sm font-semibold">Admin Feedback</h3>
              <p class="whitespace-pre-line text-sm text-muted-foreground">
                {{ selectedRequest.admin_remarks }}
              </p>
            </div>
          </div>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
