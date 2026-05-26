<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section id="booking" class="grid gap-6 px-6 py-12 md:grid-cols-[1fr_0.9fr] md:items-center">
    <div>
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-3 leading-7 opacity-75">{{ section.content_json.body }}</p>
      <p class="mt-4 text-sm font-semibold" style="color: var(--template-primary)">
        {{ section.content_json.schedule }}
      </p>
    </div>

    <div class="rounded border p-5">
      <p class="font-semibold">{{ template.business_name }}</p>
      <div class="mt-4 space-y-2 text-sm opacity-75">
        <p>{{ template.contact_info.email }}</p>
        <p>{{ template.contact_info.phone }}</p>
      </div>
      <button
        class="mt-5 w-full rounded px-5 py-3 text-sm font-semibold text-white"
        style="background: var(--template-primary)"
      >
        {{ section.content_json.cta_label || 'Book now' }}
      </button>
    </div>
  </section>
</template>
