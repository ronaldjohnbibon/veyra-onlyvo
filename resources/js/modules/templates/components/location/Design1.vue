<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="grid gap-6 px-6 py-12 md:grid-cols-2 md:items-stretch">
    <div>
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-3 leading-7 opacity-75">{{ section.content_json.body }}</p>
      <div class="mt-4 space-y-2 text-sm opacity-75">
        <p>{{ template.contact_info.address }}</p>
        <p>{{ template.contact_info.phone }}</p>
        <p>{{ template.contact_info.email }}</p>
      </div>
    </div>

    <a
      :href="section.content_json.map_url || '#'"
      class="flex min-h-56 items-center justify-center rounded border p-6 text-center font-semibold"
      style="background: color-mix(in srgb, var(--template-primary) 8%, transparent)"
    >
      Open map
    </a>
  </section>
</template>
