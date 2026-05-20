<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="grid gap-6 px-6 py-12 md:grid-cols-[1fr_0.9fr] md:items-start">
    <div>
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-3 leading-7 opacity-75">{{ section.content_json.body }}</p>
    </div>
    <div class="rounded border p-5">
      <p class="font-semibold">{{ template.business_name }}</p>
      <div class="mt-4 space-y-2 text-sm opacity-75">
        <p>{{ template.contact_info.email }}</p>
        <p>{{ template.contact_info.phone }}</p>
        <p>{{ template.contact_info.address }}</p>
      </div>
    </div>
  </section>
</template>
