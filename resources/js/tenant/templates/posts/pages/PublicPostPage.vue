<script setup lang="ts">
import TenantComplianceNotice from '@/tenant/templates/components/TenantComplianceNotice.vue'
import { formatDisplayDate } from '@/shared/utils/date'
import { useAnalyticsStore } from '@/tenant/dashboard/analytics-store'
import {
  applyTenantPublicHead,
  fallbackImage,
} from '@/tenant/templates/composables/useTenantPublicSettings'
import { usePublicPostStore } from '@/tenant/templates/posts/public-post-store'
import { renderMarkdown } from '@/shared/utils/markdown'
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
    applyTenantPublicHead(publicPostStore.post.tenant_settings, {
      title: publicPostStore.post.seo_title || publicPostStore.post.title,
      description: publicPostStore.post.meta_description || publicPostStore.post.excerpt,
      image:
        publicPostStore.post.featured_image || fallbackImage(publicPostStore.post.tenant_settings),
      path: window.location.pathname,
    })
    await analyticsStore.trackPublicVisit(publicPostStore.post.template_id, window.location.href)
  }
}

const postImage = computed(() => {
  return (
    publicPostStore.post?.featured_image || fallbackImage(publicPostStore.post?.tenant_settings)
  )
})

const contentHtml = computed(() => {
  return renderMarkdown(publicPostStore.post?.content ?? '')
})

onMounted(loadPost)
watch([siteSlug, postSlug], loadPost)
</script>

<template>
  <main class="min-h-screen bg-background">
    <article v-if="publicPostStore.post" class="mx-auto max-w-3xl px-6 py-12">
      <RouterLink
        :to="{ name: 'public.sites.show', params: { siteSlug: publicPostStore.post.site_slug } }"
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

      <div v-if="publicPostStore.post.tags?.length" class="mt-4 flex flex-wrap gap-2">
        <span
          v-for="tag in publicPostStore.post.tags"
          :key="tag"
          class="rounded border bg-muted px-2 py-1 text-xs font-medium text-muted-foreground"
        >
          {{ tag }}
        </span>
      </div>

      <img
        v-if="postImage"
        :src="postImage"
        :alt="publicPostStore.post.title"
        class="mt-8 aspect-video w-full rounded object-cover"
      />

      <div
        class="prose prose-neutral mt-8 max-w-none text-base leading-8 text-foreground [&_a]:text-primary [&_a]:underline [&_h1]:text-3xl [&_h2]:text-2xl [&_h3]:text-xl [&_li]:ml-6 [&_p]:mb-5 [&_ul]:list-disc"
        v-html="contentHtml"
      />
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

    <TenantComplianceNotice
      v-if="publicPostStore.post"
      :settings="publicPostStore.post.tenant_settings"
    />
  </main>
</template>
