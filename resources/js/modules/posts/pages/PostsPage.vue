<script setup lang="ts">
import BaseTable from '@/components/BaseTable.vue'
import { usePostStore } from '@/modules/posts/post-store'
import { useTemplateStore } from '@/modules/templates/template-store'
import type { PostParams, PostRecord, PostStatus } from '@/types/posts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Globe2, Pencil, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const postStore = usePostStore()
const templateStore = useTemplateStore()
const route = useRoute()
const router = useRouter()
const selectedTemplateId = ref('')

const tableColumns = [
  { key: 'title', label: 'Title', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'published_at', label: 'Published', sortable: true },
  { key: 'actions', label: 'Actions', headerClass: 'w-40 text-right', cellClass: 'text-right' },
] as const

const selectedTemplate = computed(() => {
  return templateStore.templates.find((template) => template.id === selectedTemplateId.value)
})

const canCreate = computed(() => Boolean(selectedTemplateId.value))

const postRows = computed(() => {
  return selectedTemplateId.value ? postStore.posts : []
})

const emptyText = computed(() => {
  return `No posts found for ${selectedTemplate.value?.name || 'this template'}.`
})

const loadPosts = async (params: Partial<PostParams> = {}): Promise<void> => {
  if (!selectedTemplateId.value) return

  await postStore.index(selectedTemplateId.value, params)
}

const createPost = async (): Promise<void> => {
  if (!canCreate.value) return

  await router.push({ name: 'posts.create', params: { templateId: selectedTemplateId.value } })
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

const deletePost = async (post: PostRecord): Promise<void> => {
  if (!selectedTemplateId.value) return

  await postStore.destroy(selectedTemplateId.value, post.id)
}

const formatDate = (value?: string | null): string => {
  if (!value) return 'Unpublished'

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value))
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
            <NativeSelect v-model="selectedTemplateId" class="h-8 sm:max-w-56">
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
              :model-value="postStore.params.status ?? ''"
              class="h-8 sm:max-w-40"
              @change="updateStatus"
            >
              <NativeSelectOption value="">All statuses</NativeSelectOption>
              <NativeSelectOption value="draft">Draft</NativeSelectOption>
              <NativeSelectOption value="published">Published</NativeSelectOption>
            </NativeSelect>
          </template>

          <template #cell-title="{ row }">
            <div class="font-medium">{{ row.title }}</div>
            <div class="line-clamp-1 text-xs text-muted-foreground">
              {{ row.excerpt || row.slug }}
            </div>
          </template>

          <template #cell-status="{ row }">
            <Badge :variant="row.status === 'published' ? 'default' : 'secondary'">
              {{ row.status === 'published' ? 'Published' : 'Draft' }}
            </Badge>
          </template>

          <template #cell-published_at="{ row }">
            <span class="text-sm text-muted-foreground">{{ formatDate(row.published_at) }}</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex justify-end gap-2">
              <Button as-child variant="outline" size="sm" aria-label="Edit post" title="Edit post">
                <RouterLink
                  v-if="selectedTemplateId"
                  :to="{
                    name: 'posts.edit',
                    params: { templateId: selectedTemplateId, postId: row.id },
                  }"
                >
                  <Pencil class="size-4" />
                </RouterLink>
              </Button>
              <Button
                variant="outline"
                size="sm"
                type="button"
                :aria-label="row.status === 'published' ? 'Move to draft' : 'Publish post'"
                :disabled="postStore.loading"
                :title="row.status === 'published' ? 'Move to draft' : 'Publish post'"
                @click="togglePublish(row)"
              >
                <Globe2 class="size-4" />
              </Button>
              <Button
                variant="outline"
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
  </div>
</template>
