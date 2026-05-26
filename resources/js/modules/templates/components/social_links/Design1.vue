<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const items = computed(() => props.section.content_json.items?.filter(Boolean) ?? [])

const linkFor = (label: string): string => {
  const key = label.toLowerCase().replace(/\s+/g, '_')

  if (key === 'website') return props.template.social_links.website || '#'
  if (key === 'linkedin') return props.template.social_links.linkedin || '#'
  if (key === 'instagram') return props.template.social_links.instagram || '#'
  if (key === 'facebook') return props.template.social_links.facebook || '#'

  return '#'
}
</script>

<template>
  <section class="px-6 py-10">
    <div
      class="flex flex-col gap-4 rounded border p-6 md:flex-row md:items-center md:justify-between"
    >
      <h2 class="text-2xl font-bold">{{ section.content_json.title }}</h2>
      <div class="flex flex-wrap gap-3">
        <a
          v-for="item in items"
          :key="item"
          :href="linkFor(item)"
          class="rounded border px-4 py-2 text-sm font-semibold hover:border-primary"
        >
          {{ item }}
        </a>
      </div>
    </div>
  </section>
</template>
