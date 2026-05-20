<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <header
    class="flex flex-wrap items-center justify-center gap-4 border-b px-6 py-5 text-center sm:justify-between sm:text-left"
    style="background: color-mix(in srgb, var(--template-primary) 8%, transparent)"
  >
    <nav class="hidden flex-wrap gap-3 text-sm font-medium sm:flex">
      <span v-for="item in ['About', 'Services']" :key="item">{{ item }}</span>
    </nav>
    <div class="flex min-w-0 flex-col items-center gap-2">
      <img
        :src="template.logo"
        :alt="`${template.business_name} logo`"
        class="size-12 rounded-full object-cover"
      />
      <div>
        <p class="font-semibold">{{ template.business_name }}</p>
        <p class="text-xs opacity-70">{{ section.content_json.tagline }}</p>
      </div>
    </div>
    <button
      class="rounded px-4 py-2 text-xs font-semibold text-white"
      style="background: var(--template-primary)"
    >
      {{ section.content_json.cta_label || 'Contact us' }}
    </button>
  </header>
</template>
