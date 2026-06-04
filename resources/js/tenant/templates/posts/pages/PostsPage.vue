<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import PostFormDialog from '@/tenant/templates/posts/components/PostFormDialog.vue'
import { usePostStore } from '@/tenant/templates/posts/post-store'
import { useTemplateStore } from '@/tenant/templates/template-store'
import type { PostParams, PostRecord, PostStatus } from '@/shared/types/posts'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { Eye, Globe2, Pencil, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const postStore = usePostStore()
const templateStore = useTemplateStore()
const route = useRoute()
const selectedTemplateId = ref('')
const postDialogOpen = ref(false)
const selectedPost = ref<PostRecord | null>(null)

const tableColumns = [
  { key: 'title', label: 'Title', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'tags', label: 'Tags' },
  { key: 'published_at', label: 'Published', sortable: true },
  { key: 'actions', label: 'Actions', headerClass: 'w-52 text-right', cellClass: 'text-right' },
] as const

const selectedTemplate = computed(() => {
  return templateStore.templates.find((template) => template.id === selectedTemplateId.value)
})

const canCreate = computed(() => Boolean(selectedTemplateId.value))

const postRows = computed(() => {
  return selectedTemplateId.value ? postStore.posts : []
})

const emptyText = computed(() => {
  if (!selectedTemplateId.value) {
    return 'Select a template to manage its posts.'
  }

  return `No posts found for ${selectedTemplate.value?.name || 'this template'}.`
})

const loadPosts = async (params: Partial<PostParams> = {}): Promise<void> => {
  if (!selectedTemplateId.value) return

  await postStore.index(selectedTemplateId.value, params)
}

const createPost = async (): Promise<void> => {
  if (!canCreate.value) return

  selectedPost.value = null
  postDialogOpen.value = true
}

const editPost = (post: PostRecord): void => {
  selectedPost.value = post
  postDialogOpen.value = true
}

const updateStatus = (event: Event): void => {
  loadPosts({
    page: 1,
    status: (event.target as HTMLSelectElement).value as PostStatus | '',
  })
}

const updateSort = (sort: string, direction: 'asc' | 'desc' | ''): void => {
  loadPosts({
    direction: direction || undefined,
    page: 1,
    sort: direction ? sort : undefined,
  })
}

const togglePublish = async (post: PostRecord): Promise<void> => {
  if (!selectedTemplateId.value) return

  if (post.status === 'published') {
    await postStore.unpublish(selectedTemplateId.value, post.id)
    return
  }

  await postStore.publish(selectedTemplateId.value, post.id)
}

const previewPost = (post: PostRecord): void => {
  if (!selectedTemplate.value?.slug || post.status !== 'published') return

  window.open(`/${selectedTemplate.value.slug}/posts/${post.slug}`, '_blank', 'noopener')
}

const deletePost = async (post: PostRecord): Promise<void> => {
  if (!selectedTemplateId.value) return

  await postStore.destroy(selectedTemplateId.value, post.id)
}

onMounted(async () => {
  await templateStore.index({ pageSize: 100 })
  const queryTemplateId = typeof route.query.templateId === 'string' ? route.query.templateId : ''
  selectedTemplateId.value = queryTemplateId || templateStore.templates[0]?.id || ''
})

watch(selectedTemplateId, (templateId) => {
  if (templateId) {
    loadPosts({ page: 1 })
  }
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Posts</h2>
          <p class="module-container-description">
            Create and publish posts for the selected website template.
          </p>
        </div>
      </div>

      <div class="space-y-4">
        <BaseTable
          :columns="tableColumns"
          :create-disabled="!canCreate"
          :data="postRows"
          :empty-text="emptyText"
          :loading="postStore.loading"
          :page="postStore.params.page ?? 1"
          :page-size="postStore.params.pageSize ?? 15"
          :search="postStore.params.search ?? ''"
          :sort-direction="postStore.params.direction ?? ''"
          :sort-key="postStore.params.sort ?? ''"
          :total="postStore.total"
          create-label="Create"
          search-placeholder="Search posts"
          with-create
          with-page-size
          with-search
          @create="createPost"
          @update:page="loadPosts({ page: $event })"
          @update:page-size="loadPosts({ page: 1, pageSize: $event })"
          @update:search="loadPosts({ page: 1, search: $event })"
          @update:sort="updateSort"
        >
          <template #filters>
            <NativeSelect
              v-field-help="'Choose the template whose posts you want to manage.'"
              v-model="selectedTemplateId"
              class="h-8 sm:max-w-56"
            >
              <NativeSelectOption value="" disabled>Select template</NativeSelectOption>
              <NativeSelectOption
                v-for="template in templateStore.templates"
                :key="template.id"
                :value="template.id"
              >
                {{ template.name }}
              </NativeSelectOption>
            </NativeSelect>

            <NativeSelect
              v-field-help="'Filter posts by draft or published status.'"
              :model-value="postStore.params.status ?? ''"
              class="h-8 sm:max-w-40"
              @change="updateStatus"
            >
              <NativeSelectOption value="">All statuses</NativeSelectOption>
              <NativeSelectOption value="draft">Draft</NativeSelectOption>
              <NativeSelectOption value="scheduled">Scheduled</NativeSelectOption>
              <NativeSelectOption value="published">Published</NativeSelectOption>
            </NativeSelect>
          </template>

          <template #cell-title="{ row }">
            <div class="font-medium">{{ row.title }}</div>
            <div class="line-clamp-1 text-xs text-muted-foreground">
              /{{ row.slug }}
            </div>
            <div class="mt-1 line-clamp-1 text-xs text-muted-foreground">
              {{ row.excerpt || 'No excerpt yet' }}
            </div>
          </template>

          <template #cell-status="{ row }">
            <Badge :variant="getStatusBadgeVariant(row.status)">
              {{ getStatusLabel(row.status) }}
            </Badge>
          </template>

          <template #cell-tags="{ row }">
            <div v-if="row.tags?.length" class="flex flex-wrap gap-1">
              <Badge v-for="tag in row.tags.slice(0, 3)" :key="tag" variant="secondary">
                {{ tag }}
              </Badge>
              <Badge v-if="row.tags.length > 3" variant="outline">+{{ row.tags.length - 3 }}</Badge>
            </div>
            <span v-else class="text-sm text-muted-foreground">No tags</span>
          </template>

          <template #cell-published_at="{ row }">
            <span class="text-sm text-muted-foreground">
              {{
                row.published_at
                  ? `${row.status === 'scheduled' ? 'Scheduled' : 'Published'} ${formatDisplayDate(row.published_at)}`
                  : 'Unpublished'
              }}
            </span>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex justify-end gap-2">
              <Button
                variant="edit"
                size="sm"
                type="button"
                aria-label="Edit post"
                title="Edit post"
                @click="editPost(row)"
              >
                <Pencil class="size-4" />
              </Button>
              <Button
                variant="publish"
                size="sm"
                type="button"
                :aria-label="row.status === 'published' ? 'Move to draft' : 'Publish post now'"
                :disabled="postStore.loading"
                :title="row.status === 'published' ? 'Move to draft' : 'Publish post now'"
                @click="togglePublish(row)"
              >
                <Globe2 class="size-4" />
              </Button>
              <Button
                variant="navigate"
                size="sm"
                type="button"
                aria-label="Preview public post"
                :disabled="row.status !== 'published'"
                title="Preview public post"
                @click="previewPost(row)"
              >
                <Eye class="size-4" />
              </Button>
              <Button
                variant="delete"
                size="sm"
                type="button"
                aria-label="Delete post"
                :disabled="postStore.loading"
                title="Delete post"
                @click="deletePost(row)"
              >
                <Trash2 class="size-4" />
              </Button>
            </div>
          </template>
        </BaseTable>
      </div>
    </div>

    <PostFormDialog
      v-model:open="postDialogOpen"
      :post="selectedPost"
      :template-id="selectedTemplateId"
    />
  </div>
</template>
