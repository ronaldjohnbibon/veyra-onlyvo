<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="grid gap-8 px-6 py-12 md:grid-cols-2 md:items-center">
    <div>
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-4 leading-7 opacity-75">{{ section.content_json.body }}</p>
      <a
        v-if="section.content_json.cta_label"
        :href="section.content_json.button_link || '#'"
        class="mt-6 inline-flex rounded px-5 py-3 text-sm font-semibold text-white"
        style="background: var(--template-primary)"
      >
        {{ section.content_json.cta_label }}
      </a>
    </div>
    <img
      :src="section.content_json.image || template.logo"
      :alt="section.content_json.title"
      class="h-56 w-full rounded object-cover"
    />
  </section>
</template>
