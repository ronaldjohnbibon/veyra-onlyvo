<script setup lang="ts">
import { usePostStore } from '@/modules/posts/post-store'
import { useTemplateStore } from '@/modules/templates/template-store'
import type { PostRecord, PostStatus } from '@/types/posts'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'
import { Edit, FilePlus2, Globe2, Search, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const postStore = usePostStore()
const templateStore = useTemplateStore()
const route = useRoute()
const selectedTemplateId = ref('')
const search = ref('')
const status = ref<PostStatus | ''>('')

const selectedTemplate = computed(() => {
  return templateStore.templates.find((template) => template.id === selectedTemplateId.value)
})

const canCreate = computed(() => Boolean(selectedTemplateId.value))

const loadPosts = async (): Promise<void> => {
  if (!selectedTemplateId.value) return

  await postStore.index(selectedTemplateId.value, {
    page: 1,
    search: search.value,
    status: status.value,
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
  await loadPosts()
})

watch(selectedTemplateId, loadPosts)
</script>

<template>
  <div class="flex flex-1 flex-col gap-4 p-4">
    <div class="module-heading-container">
      <div>
        <h2 class="module-container-title">Posts</h2>
        <p class="module-container-description">
          Create and publish posts for the selected website template.
        </p>
      </div>
      <Button as-child variant="create" :disabled="!canCreate">
        <RouterLink
          :to="
            canCreate
              ? { name: 'posts.create', params: { templateId: selectedTemplateId } }
              : { name: 'posts.index' }
          "
        >
          <FilePlus2 class="size-4" />
          Create
        </RouterLink>
      </Button>
    </div>

    <Card class="gap-4 py-4">
      <CardHeader class="px-4">
        <CardTitle class="text-sm">Filters</CardTitle>
      </CardHeader>
      <CardContent class="grid gap-3 px-4 md:grid-cols-[minmax(0,1fr)_12rem_12rem_auto]">
        <NativeSelect v-model="selectedTemplateId" class="w-full">
          <NativeSelectOption value="" disabled>Select template</NativeSelectOption>
          <NativeSelectOption
            v-for="template in templateStore.templates"
            :key="template.id"
            :value="template.id"
          >
            {{ template.name }}
          </NativeSelectOption>
        </NativeSelect>

        <NativeSelect v-model="status" class="w-full" @change="loadPosts">
          <NativeSelectOption value="">All statuses</NativeSelectOption>
          <NativeSelectOption value="draft">Draft</NativeSelectOption>
          <NativeSelectOption value="published">Published</NativeSelectOption>
        </NativeSelect>

        <Input
          v-model="search"
          placeholder="Search posts"
          @keyup.enter="loadPosts"
        />

        <Button variant="outline" type="button" @click="loadPosts">
          <Search class="size-4" />
          Search
        </Button>
      </CardContent>
    </Card>

    <Card class="py-2">
      <CardContent class="px-0">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Title</TableHead>
              <TableHead>Status</TableHead>
              <TableHead>Published</TableHead>
              <TableHead class="w-[240px] text-right">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <TableRow v-if="!postStore.posts.length">
              <TableCell colspan="4" class="py-8 text-center text-sm text-muted-foreground">
                No posts found for {{ selectedTemplate?.name || 'this template' }}.
              </TableCell>
            </TableRow>
            <TableRow v-for="post in postStore.posts" :key="post.id">
              <TableCell>
                <div class="font-medium">{{ post.title }}</div>
                <div class="line-clamp-1 text-sm text-muted-foreground">{{ post.excerpt }}</div>
              </TableCell>
              <TableCell>
                <Badge variant="outline">{{ post.status }}</Badge>
              </TableCell>
              <TableCell class="text-sm text-muted-foreground">
                {{ formatDate(post.published_at) }}
              </TableCell>
              <TableCell>
                <div class="flex justify-end gap-2">
                  <Button as-child size="sm" variant="outline">
                    <RouterLink
                      :to="{
                        name: 'posts.edit',
                        params: { templateId: selectedTemplateId, postId: post.id },
                      }"
                    >
                      <Edit class="size-4" />
                      Edit
                    </RouterLink>
                  </Button>
                  <Button size="sm" variant="outline" type="button" @click="togglePublish(post)">
                    <Globe2 class="size-4" />
                    {{ post.status === 'published' ? 'Draft' : 'Publish' }}
                  </Button>
                  <Button size="sm" variant="delete" type="button" @click="deletePost(post)">
                    <Trash2 class="size-4" />
                  </Button>
                </div>
              </TableCell>
            </TableRow>
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  </div>
</template>
