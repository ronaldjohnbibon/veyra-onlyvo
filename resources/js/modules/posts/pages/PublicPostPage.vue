<script setup lang="ts">
import { postService } from '@/modules/posts/api/posts'
import type { PostRecord } from '@/types/posts'
import { AxiosError } from 'axios'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(true)
const notFound = ref(false)
const post = ref<PostRecord | null>(null)

const siteSlug = computed(() => String(route.params.siteSlug || ''))
const postSlug = computed(() => String(route.params.postSlug || ''))

const loadPost = async (): Promise<void> => {
  try {
    loading.value = true
    notFound.value = false
    post.value = (await postService.publicShow(siteSlug.value, postSlug.value)).data
  } catch (error) {
    post.value = null
    notFound.value = error instanceof AxiosError && error.response?.status === 404
  } finally {
    loading.value = false
  }
}

const formatDate = (value?: string | null): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, {
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(value))
}

onMounted(loadPost)
watch([siteSlug, postSlug], loadPost)
</script>

<template>
  <main class="min-h-screen bg-background">
    <article v-if="post" class="mx-auto max-w-3xl px-6 py-12">
      <RouterLink
        :to="{ name: 'public.sites.show', params: { siteSlug } }"
        class="text-sm font-medium text-primary hover:underline"
      >
        Back to site
      </RouterLink>

      <p class="mt-8 text-sm text-muted-foreground">{{ formatDate(post.published_at) }}</p>
      <h1 class="mt-3 text-4xl font-semibold tracking-normal text-foreground">{{ post.title }}</h1>

      <img
        v-if="post.featured_image"
        :src="post.featured_image"
        :alt="post.title"
        class="mt-8 aspect-video w-full rounded object-cover"
      />

      <div class="mt-8 whitespace-pre-line text-base leading-8 text-foreground">
        {{ post.content }}
      </div>
    </article>

    <section
      v-else-if="!loading && notFound"
      class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 text-center"
    >
      <h1 class="text-2xl font-semibold text-foreground">Post unavailable</h1>
      <p class="mt-3 text-sm text-muted-foreground">
        This post is not published yet or no longer exists.
      </p>
    </section>
  </main>
</template>
