<script setup lang="ts">
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import { templateService } from '@/modules/templates/api/templates'
import { postService } from '@/modules/posts/api/posts'
import type { PostRecord } from '@/types/posts'
import type { TemplateRecord } from '@/types/templates'
import { AxiosError } from 'axios'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(true)
const notFound = ref(false)
const template = ref<TemplateRecord | null>(null)
const posts = ref<PostRecord[]>([])

const siteSlug = computed(() => {
  const slug = route.params.siteSlug

  return typeof slug === 'string' ? slug : ''
})

const loadSite = async (): Promise<void> => {
  try {
    loading.value = true
    notFound.value = false

    const response = siteSlug.value
      ? await templateService.publicShow(siteSlug.value)
      : await templateService.publicDefault()

    template.value = response.data
    posts.value = template.value?.slug
      ? (await postService.publicIndex(template.value.slug)).data
      : []
  } catch (error) {
    template.value = null
    posts.value = []
    notFound.value = error instanceof AxiosError && error.response?.status === 404
  } finally {
    loading.value = false
  }
}

const formatDate = (value?: string | null): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value))
}

onMounted(loadSite)
watch(siteSlug, loadSite)
</script>

<template>
  <main class="min-h-screen bg-background">
    <TemplatePreview
      v-if="template"
      :template="template"
      :show-controls="false"
      class="[&_.template-preview]:w-full [&_.template-preview]:rounded-none [&_.template-preview]:border-0"
    />

    <section v-if="template && posts.length" class="bg-background px-6 py-12">
      <div class="mx-auto max-w-6xl">
        <div class="mb-6">
          <h2 class="text-2xl font-semibold text-foreground">Latest Posts</h2>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <RouterLink
            v-for="post in posts"
            :key="post.id"
            :to="{
              name: 'public.posts.show',
              params: { siteSlug: template.slug, postSlug: post.slug },
            }"
            class="rounded border bg-card text-card-foreground transition hover:border-primary hover:shadow-md"
          >
            <img
              v-if="post.featured_image"
              :src="post.featured_image"
              :alt="post.title"
              class="aspect-video w-full rounded-t object-cover"
            />
            <div class="space-y-2 p-4">
              <p class="text-xs font-medium text-muted-foreground">
                {{ formatDate(post.published_at) }}
              </p>
              <h3 class="text-lg font-semibold">{{ post.title }}</h3>
              <p class="line-clamp-3 text-sm leading-6 text-muted-foreground">
                {{ post.excerpt }}
              </p>
            </div>
          </RouterLink>
        </div>
      </div>
    </section>

    <section
      v-else-if="!loading && notFound"
      class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 text-center"
    >
      <h1 class="text-2xl font-semibold text-foreground">Site unavailable</h1>
      <p class="mt-3 text-sm text-muted-foreground">
        This site is not published yet or no longer exists.
      </p>
    </section>
  </main>
</template>
