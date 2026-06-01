<script setup lang="ts">
import { formatDisplayDate } from '@/lib/date'
import { useAnalyticsStore } from '@/modules/analytics/analytics-store'
import { usePublicPostStore } from '@/modules/posts/public-post-store'
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const analyticsStore = useAnalyticsStore()
const publicPostStore = usePublicPostStore()

const siteSlug = computed(() => String(route.params.siteSlug || ''))
const postSlug = computed(() => String(route.params.postSlug || ''))

const loadPost = async (): Promise<void> => {
  await publicPostStore.loadPost(siteSlug.value, postSlug.value)

  if (publicPostStore.post) {
    await analyticsStore.trackPublicVisit(publicPostStore.post.template_id, window.location.href)
  }
}

onMounted(loadPost)
watch([siteSlug, postSlug], loadPost)
</script>

<template>
  <main class="min-h-screen bg-background">
    <article v-if="publicPostStore.post" class="mx-auto max-w-3xl px-6 py-12">
      <RouterLink
        :to="{ name: 'public.sites.show', params: { siteSlug } }"
        class="text-sm font-medium text-primary hover:underline"
      >
        Back to site
      </RouterLink>

      <p class="mt-8 text-sm text-muted-foreground">
        {{
          formatDisplayDate(publicPostStore.post.published_at, {
            month: 'long',
            day: 'numeric',
            year: 'numeric',
          })
        }}
      </p>
      <h1 class="mt-3 text-4xl font-semibold tracking-normal text-foreground">
        {{ publicPostStore.post.title }}
      </h1>

      <img
        v-if="publicPostStore.post.featured_image"
        :src="publicPostStore.post.featured_image"
        :alt="publicPostStore.post.title"
        class="mt-8 aspect-video w-full rounded object-cover"
      />

      <div class="mt-8 whitespace-pre-line text-base leading-8 text-foreground">
        {{ publicPostStore.post.content }}
      </div>
    </article>

    <section
      v-else-if="!publicPostStore.loading && publicPostStore.notFound"
      class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 text-center"
    >
      <h1 class="text-2xl font-semibold text-foreground">Post unavailable</h1>
      <p class="mt-3 text-sm text-muted-foreground">
        This post is not published yet or no longer exists.
      </p>
    </section>
  </main>
</template>
