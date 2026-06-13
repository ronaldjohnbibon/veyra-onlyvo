<script setup lang="ts">
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
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { Textarea } from '@/shared/components/ui/textarea'
import { renderMarkdown } from '@/shared/utils/markdown'
import { usePostStore } from '@/tenant/templates/posts/post-store'
import type { PostPayload, PostRecord, PostStatus } from '@/shared/types/posts'
import { CalendarClock, Eye, FileText, Save, Send } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'

const props = defineProps<{
  open: boolean
  post: PostRecord | null
  templateId: string
}>()

const emit = defineEmits<{
  saved: [post: PostRecord]
  'update:open': [open: boolean]
}>()

type EditorMode = 'write' | 'preview'
type PostFormState = Omit<
  PostPayload,
  'slug' | 'excerpt' | 'featured_image' | 'seo_title' | 'meta_description' | 'published_at'
> & {
  slug: string
  excerpt: string
  featured_image: string
  seo_title: string
  meta_description: string
  published_at: string
}

const postStore = usePostStore()
const formError = ref('')
const editorMode = ref<EditorMode>('write')
const tagInput = ref('')
const slugTouched = ref(false)
const form = ref<PostFormState>({
  title: '',
  slug: '',
  content: '',
  excerpt: '',
  featured_image: '',
  seo_title: '',
  meta_description: '',
  tags: [],
  status: 'draft',
  published_at: '',
})

const isEditing = computed(() => Boolean(props.post?.id))
const dialogTitle = computed(() => (isEditing.value ? 'Edit Post' : 'Create Post'))
const previewHtml = computed(() => renderMarkdown(form.value.content || ''))
const seoTitleLength = computed(() => form.value.seo_title?.length ?? 0)
const metaDescriptionLength = computed(() => form.value.meta_description?.length ?? 0)
const excerptLength = computed(() => form.value.excerpt?.length ?? 0)

const minScheduleDate = computed(() => toDateTimeLocal(new Date().toISOString()))

const slugify = (value: string): string => {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

const toDateTimeLocal = (value?: string | null): string => {
  if (!value) return ''

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) return ''

  const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000)

  return localDate.toISOString().slice(0, 16)
}

const resetForm = (): void => {
  formError.value = ''
  postStore.errors = {}
  editorMode.value = 'write'
  slugTouched.value = Boolean(props.post?.slug)
  tagInput.value = (props.post?.tags ?? []).join(', ')
  form.value = {
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    content: props.post?.content ?? '',
    excerpt: props.post?.excerpt ?? '',
    featured_image: props.post?.featured_image ?? '',
    seo_title: props.post?.seo_title ?? '',
    meta_description: props.post?.meta_description ?? '',
    tags: props.post?.tags ?? [],
    status: props.post?.status ?? 'draft',
    published_at: toDateTimeLocal(props.post?.published_at),
  }
}

const tagsFromInput = (): string[] => {
  return Array.from(
    new Set(
      tagInput.value
        .split(',')
        .map((tag) => tag.trim())
        .filter(Boolean)
        .map((tag) => tag.slice(0, 40))
    )
  ).slice(0, 12)
}

const payloadForStatus = (status: PostStatus): PostPayload => {
  const publishedAt = status === 'scheduled' ? form.value.published_at : null

  return {
    ...form.value,
    slug: form.value.slug || slugify(form.value.title),
    tags: tagsFromInput(),
    status,
    published_at: publishedAt,
  }
}

const savePost = async (status: PostStatus): Promise<void> => {
  if (!props.templateId) return

  if (status === 'scheduled' && !form.value.published_at) {
    formError.value = 'Choose a publish date before scheduling this post.'
    return
  }

  formError.value = ''

  try {
    const payload = payloadForStatus(status)
    const saved = props.post?.id
      ? await postStore.update(props.templateId, props.post.id, payload)
      : await postStore.store(props.templateId, payload)

    emit('saved', saved)
    emit('update:open', false)
  } catch {
    formError.value = 'Please check the form and try again.'
  }
}

const handleSlugInput = (): void => {
  slugTouched.value = true
  form.value.slug = slugify(form.value.slug ?? '')
}

const handleImageUpload = async (event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file || !props.templateId) return

  try {
    form.value.featured_image = await postStore.uploadFeaturedImage(props.templateId, file)
    formError.value = ''
  } catch {
    formError.value = postStore.errors.image?.[0] || 'Featured image upload failed.'
  } finally {
    input.value = ''
  }
}

watch(
  () => [props.open, props.post?.id, props.templateId],
  () => {
    if (props.open) resetForm()
  }
)

watch(
  () => form.value.title,
  (title) => {
    if (!slugTouched.value) {
      form.value.slug = slugify(title)
    }
  }
)
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogScrollContent class="max-w-[calc(100%-2rem)] sm:max-w-5xl">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription>
          Prepare the post content, publishing details, and search metadata.
        </DialogDescription>
      </DialogHeader>

      <form class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]" @submit.prevent>
        <div class="space-y-5">
          <FieldGroup>
            <FieldSet>
              <Field>
                <FieldLabel for="post-title">Title</FieldLabel>
                <Input
                  v-field-help="'Enter the post title shown to readers.'"
                  id="post-title"
                  v-model="form.title"
                />
                <Label v-if="postStore.errors.title" class="text-destructive text-xs">
                  {{ postStore.errors.title[0] }}
                </Label>
              </Field>

              <div class="grid gap-4 md:grid-cols-2">
                <Field>
                  <FieldLabel for="post-slug">Slug</FieldLabel>
                  <Input
                    v-field-help="'Edit the public URL slug for this post.'"
                    id="post-slug"
                    v-model="form.slug"
                    placeholder="post-url-slug"
                    @input="handleSlugInput"
                  />
                  <Label v-if="postStore.errors.slug" class="text-destructive text-xs">
                    {{ postStore.errors.slug[0] }}
                  </Label>
                </Field>

                <Field>
                  <FieldLabel for="post-publish-at">Publish date</FieldLabel>
                  <Input
                    v-field-help="'Choose a future date to schedule this post.'"
                    id="post-publish-at"
                    v-model="form.published_at"
                    type="datetime-local"
                    :min="minScheduleDate"
                  />
                  <Label v-if="postStore.errors.published_at" class="text-destructive text-xs">
                    {{ postStore.errors.published_at[0] }}
                  </Label>
                </Field>
              </div>

              <Field>
                <FieldLabel for="featured-image">Featured image</FieldLabel>
                <Input
                  v-field-help="'Enter an image URL to use as the post feature image.'"
                  id="featured-image"
                  v-model="form.featured_image"
                />
                <Label v-if="postStore.errors.featured_image" class="text-destructive text-xs">
                  {{ postStore.errors.featured_image[0] }}
                </Label>

                <Input
                  v-field-help="'Upload an image file to fill the URL automatically.'"
                  type="file"
                  accept="image/*"
                  class="cursor-pointer text-muted-foreground"
                  :disabled="postStore.loading"
                  @change="handleImageUpload"
                />
              </Field>

              <Field v-if="form.featured_image">
                <img
                  :src="form.featured_image"
                  alt=""
                  class="max-h-72 w-full rounded border object-cover"
                />
              </Field>

              <Field>
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <FieldLabel for="post-content">Content</FieldLabel>
                  <div class="inline-flex rounded border bg-muted p-1">
                    <Button
                      type="button"
                      size="sm"
                      :variant="editorMode === 'write' ? 'default' : 'ghost'"
                      class="h-8"
                      @click="editorMode = 'write'"
                    >
                      <FileText class="size-4" />
                      Write
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      :variant="editorMode === 'preview' ? 'default' : 'ghost'"
                      class="h-8"
                      @click="editorMode = 'preview'"
                    >
                      <Eye class="size-4" />
                      Preview
                    </Button>
                  </div>
                </div>

                <Textarea
                  v-if="editorMode === 'write'"
                  v-field-help="'Write the main post content. Markdown formatting is supported.'"
                  id="post-content"
                  v-model="form.content"
                  class="min-h-96 font-mono text-sm"
                />
                <div
                  v-else
                  class="prose prose-sm min-h-96 max-w-none rounded border bg-background p-4 text-foreground [&_a]:text-primary [&_h1]:text-2xl [&_h2]:text-xl [&_h3]:text-lg [&_li]:ml-5 [&_ul]:list-disc"
                  v-html="previewHtml"
                />

                <Label v-if="postStore.errors.content" class="text-destructive text-xs">
                  {{ postStore.errors.content[0] }}
                </Label>
                <Label v-if="postStore.errors.status" class="text-destructive text-xs">
                  {{ postStore.errors.status[0] }}
                </Label>
              </Field>
            </FieldSet>
          </FieldGroup>
        </div>

        <aside class="space-y-4">
          <div class="rounded border bg-card p-4 text-card-foreground">
            <div class="mb-3 flex items-center justify-between gap-2">
              <h3 class="text-sm font-semibold">Publishing</h3>
              <Badge variant="outline">{{ form.status }}</Badge>
            </div>
            <p class="text-xs leading-5 text-muted-foreground">
              Drafts stay private. Scheduled posts appear publicly when their publish date arrives.
            </p>
          </div>

          <FieldGroup class="rounded border bg-card p-4">
            <FieldSet>
              <Field>
                <div class="flex items-center justify-between gap-2">
                  <FieldLabel for="post-excerpt">Excerpt</FieldLabel>
                  <span class="text-xs text-muted-foreground">{{ excerptLength }}/320</span>
                </div>
                <Textarea
                  v-field-help="'Write the summary shown in post cards and previews.'"
                  id="post-excerpt"
                  v-model="form.excerpt"
                  class="min-h-24"
                />
                <Label v-if="postStore.errors.excerpt" class="text-destructive text-xs">
                  {{ postStore.errors.excerpt[0] }}
                </Label>
              </Field>

              <Field>
                <div class="flex items-center justify-between gap-2">
                  <FieldLabel for="post-seo-title">SEO title</FieldLabel>
                  <span class="text-xs text-muted-foreground">{{ seoTitleLength }}/180</span>
                </div>
                <Input
                  v-field-help="'Enter the browser and search title for this post.'"
                  id="post-seo-title"
                  v-model="form.seo_title"
                />
                <Label v-if="postStore.errors.seo_title" class="text-destructive text-xs">
                  {{ postStore.errors.seo_title[0] }}
                </Label>
              </Field>

              <Field>
                <div class="flex items-center justify-between gap-2">
                  <FieldLabel for="post-meta-description">Meta description</FieldLabel>
                  <span class="text-xs text-muted-foreground">{{ metaDescriptionLength }}/320</span>
                </div>
                <Textarea
                  v-field-help="'Enter the search and social summary for this post.'"
                  id="post-meta-description"
                  v-model="form.meta_description"
                  class="min-h-24"
                />
                <Label v-if="postStore.errors.meta_description" class="text-destructive text-xs">
                  {{ postStore.errors.meta_description[0] }}
                </Label>
              </Field>

              <Field>
                <FieldLabel for="post-tags">Tags</FieldLabel>
                <Input
                  v-field-help="'Separate post tags with commas.'"
                  id="post-tags"
                  v-model="tagInput"
                  placeholder="Launch, News, Updates"
                />
                <div v-if="tagsFromInput().length" class="flex flex-wrap gap-2">
                  <Badge v-for="tag in tagsFromInput()" :key="tag" variant="secondary">
                    {{ tag }}
                  </Badge>
                </div>
                <Label v-if="postStore.errors.tags" class="text-destructive text-xs">
                  {{ postStore.errors.tags[0] }}
                </Label>
              </Field>
            </FieldSet>
          </FieldGroup>

          <p
            v-if="formError"
            class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
          >
            {{ formError }}
          </p>
        </aside>
      </form>

      <DialogFooter class="flex-col gap-2 sm:flex-row sm:justify-between">
        <Button
          variant="update"
          type="button"
          :disabled="postStore.loading"
          @click="savePost('draft')"
        >
          <Save class="size-4" />
          Save Draft
        </Button>
        <div class="flex flex-col gap-2 sm:flex-row">
          <Button
            variant="outline"
            type="button"
            :disabled="postStore.loading"
            @click="savePost('scheduled')"
          >
            <CalendarClock class="size-4" />
            Schedule
          </Button>
          <Button
            variant="create"
            type="button"
            :disabled="postStore.loading"
            @click="savePost('published')"
          >
            <Send class="size-4" />
            Publish Now
          </Button>
        </div>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>
