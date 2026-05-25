<script setup lang="ts">
import { usePostStore } from '@/modules/posts/post-store'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import type { PostPayload, PostStatus } from '@/types/posts'
import { ArrowLeft, Save, Send } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const postStore = usePostStore()
const templateId = computed(() => String(route.params.templateId))
const postId = computed(() => (route.params.postId ? String(route.params.postId) : ''))
const formError = ref('')
const form = ref<PostPayload>({
  title: '',
  content: '',
  featured_image: '',
  status: 'draft',
})
const maxFeaturedImageSize = 4 * 1024 * 1024

const isEditing = computed(() => Boolean(postId.value))

const validate = (): boolean => {
  if (!form.value.title.trim() || !form.value.content.trim()) {
    formError.value = 'Title and content are required.'
    return false
  }

  formError.value = ''
  return true
}

const savePost = async (status: PostStatus): Promise<void> => {
  if (!validate()) return

  form.value.status = status

  const saved = isEditing.value
    ? await postStore.update(templateId.value, postId.value, form.value)
    : await postStore.store(templateId.value, form.value)

  await router.push({
    name: 'posts.edit',
    params: { templateId: templateId.value, postId: saved.id },
  })
}

const handleImageUpload = async (event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  if (file.size > maxFeaturedImageSize) {
    formError.value = 'Featured image must be 4 MB or smaller.'
    input.value = ''
    return
  }

  try {
    form.value.featured_image = await postStore.uploadFeaturedImage(templateId.value, file)
    formError.value = ''
  } finally {
    input.value = ''
  }
}

onMounted(async () => {
  if (!postId.value) return

  await postStore.show(templateId.value, postId.value)

  if (postStore.post) {
    form.value = {
      title: postStore.post.title,
      content: postStore.post.content,
      featured_image: postStore.post.featured_image ?? '',
      status: postStore.post.status,
    }
  }
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">{{ isEditing ? 'Edit Post' : 'Create Post' }}</h2>
          <p class="module-container-description">
            Write a post for this website template and publish it when ready.
          </p>
        </div>
        <Button as-child variant="outline">
          <RouterLink to="/posts">
            <ArrowLeft class="size-4" />
            Back
          </RouterLink>
        </Button>
      </div>

      <div class="space-y-4">
        <Card class="gap-4 py-4">
          <CardHeader class="px-4">
            <CardTitle class="text-sm">Post Details</CardTitle>
          </CardHeader>
          <CardContent class="px-4">
            <form class="space-y-5" @submit.prevent>
              <FieldGroup>
                <FieldSet>
                  <Field>
                    <FieldLabel for="post-title">Title</FieldLabel>
                    <Input id="post-title" v-model="form.title" required />
                  </Field>

                  <Field>
                    <FieldLabel for="featured-image">Featured Image</FieldLabel>
                    <Input id="featured-image" v-model="form.featured_image" />
                    <Input
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
                      id="post-content"
                      v-model="form.content"
                      class="min-h-[320px]"
                      required
                    />
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
          </CardContent>
        </Card>
      </div>
    </div>

    <div class="fixed bottom-6 right-6 z-20 flex w-[100px] flex-col gap-2 draggable">
      <Button
        variant="update"
        class="w-full rounded shadow h-8 px-3 text-xs"
        type="button"
        :disabled="postStore.loading"
        @click="savePost('draft')"
      >
        <Save class="size-3" />
        Draft
      </Button>
      <Button
        variant="create"
        class="w-full rounded shadow h-8 px-3 text-xs"
        type="button"
        :disabled="postStore.loading"
        @click="savePost('published')"
      >
        <Send class="size-3" />
        Publish
      </Button>
    </div>
  </div>
</template>
