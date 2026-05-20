<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section
    class="px-6 py-12 text-center"
    style="background: color-mix(in srgb, var(--template-secondary) 10%, transparent)"
  >
    <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
    <p class="mx-auto mt-3 max-w-2xl leading-7 opacity-75">{{ section.content_json.body }}</p>
    <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm font-semibold">
      <a
        class="rounded px-4 py-2 text-white"
        :href="`mailto:${template.contact_info.email}`"
        style="background: var(--template-primary)"
      >
        {{ template.contact_info.email }}
      </a>
      <a class="rounded border px-4 py-2" :href="`tel:${template.contact_info.phone}`">
        {{ template.contact_info.phone }}
      </a>
    </div>
  </section>
</template>
