<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="grid gap-8 px-6 py-12 md:grid-cols-[1.1fr_0.9fr] md:items-center">
    <div>
      <p
        class="mb-3 text-sm font-semibold uppercase tracking-wide"
        style="color: var(--template-primary)"
      >
        {{ template.business_name }}
      </p>
      <h1 class="text-4xl font-bold leading-tight">{{ section.content_json.title }}</h1>
      <p class="mt-4 max-w-2xl text-lg opacity-75">{{ section.content_json.subtitle }}</p>
      <button
        class="mt-6 rounded px-5 py-3 text-sm font-semibold text-white"
        style="background: var(--template-primary)"
      >
        {{ section.content_json.cta_label || 'Get started' }}
      </button>
    </div>
    <img
      :src="section.content_json.image || template.logo"
      :alt="section.content_json.title"
      class="h-64 w-full rounded object-cover"
    />
  </section>
</template>
