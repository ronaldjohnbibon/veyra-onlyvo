<script setup lang="ts">
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import { templateService } from '@/modules/templates/api/templates'
import type { TemplateRecord } from '@/types/templates'
import { AxiosError } from 'axios'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const loading = ref(true)
const notFound = ref(false)
const template = ref<TemplateRecord | null>(null)

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
  } catch (error) {
    template.value = null
    notFound.value = error instanceof AxiosError && error.response?.status === 404
  } finally {
    loading.value = false
  }
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
