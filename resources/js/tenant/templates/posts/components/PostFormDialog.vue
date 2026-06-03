<script setup lang="ts">
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
import { usePostStore } from '@/tenant/templates/posts/post-store'
import type { PostPayload, PostRecord, PostStatus } from '@/shared/types/posts'
import { Save, Send } from 'lucide-vue-next'
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

const postStore = usePostStore()
const formError = ref('')
const form = ref<PostPayload>({
  title: '',
  content: '',
  featured_image: '',
  status: 'draft',
})
const maxFeaturedImageSize = 4 * 1024 * 1024

const isEditing = computed(() => Boolean(props.post?.id))

const dialogTitle = computed(() => (isEditing.value ? 'Edit Post' : 'Create Post'))

const resetForm = (): void => {
  // Seed the dialog from the selected post or a clean draft.
  formError.value = ''
  postStore.errors = {}
  form.value = {
    title: props.post?.title ?? '',
    content: props.post?.content ?? '',
    featured_image: props.post?.featured_image ?? '',
    status: props.post?.status ?? 'draft',
  }
}

const savePost = async (status: PostStatus): Promise<void> => {
  if (!props.templateId) return

  // Reuse the existing store flow for create and update submissions.
  form.value.status = status
  formError.value = ''

  try {
    const saved = props.post?.id
      ? await postStore.update(props.templateId, props.post.id, form.value)
      : await postStore.store(props.templateId, form.value)

    emit('saved', saved)
    emit('update:open', false)
  } catch {
    formError.value = 'Please check the form and try again.'
  }
}

const handleImageUpload = async (event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file || !props.templateId) return

  if (file.size > maxFeaturedImageSize) {
    formError.value = 'Featured image must be 4 MB or smaller.'
    input.value = ''
    return
  }

  try {
    form.value.featured_image = await postStore.uploadFeaturedImage(props.templateId, file)
    formError.value = ''
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
</script>

<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogScrollContent class="max-w-[calc(100%-2rem)] sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ dialogTitle }}</DialogTitle>
        <DialogDescription>
          Write the post details, then save it as a draft or publish it.
        </DialogDescription>
      </DialogHeader>

      <form class="space-y-5" @submit.prevent>
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

            <Field>
              <FieldLabel for="featured-image">Featured Image</FieldLabel>

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
              <FieldLabel for="post-content">Content</FieldLabel>

              <Textarea
                v-field-help="'Write the main post content shown on the public site.'"
                id="post-content"
                v-model="form.content"
                class="min-h-72"
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

        <p
          v-if="formError"
          class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
        >
          {{ formError }}
        </p>
      </form>

      <DialogFooter>
        <Button
          variant="update"
          type="button"
          :disabled="postStore.loading"
          @click="savePost('draft')"
        >
          <Save class="size-4" />
          Draft
        </Button>
        <Button
          variant="create"
          type="button"
          :disabled="postStore.loading"
          @click="savePost('published')"
        >
          <Send class="size-4" />
          Publish
        </Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>
