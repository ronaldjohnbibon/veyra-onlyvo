<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="px-6 py-12">
    <div
      class="rounded p-8 text-center text-white"
      style="
        background: linear-gradient(135deg, var(--template-primary), var(--template-secondary));
      "
    >
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mx-auto mt-3 max-w-2xl opacity-85">{{ section.content_json.body }}</p>
      <a
        v-if="section.content_json.cta_label"
        :href="section.content_json.button_link || '#'"
        class="mt-6 inline-flex rounded bg-white px-5 py-3 text-sm font-semibold"
        style="color: var(--template-secondary)"
      >
        {{ section.content_json.cta_label }}
      </a>
    </div>
  </section>
</template>
