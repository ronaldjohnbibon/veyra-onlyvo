<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <header class="flex flex-wrap items-center justify-between gap-4 border-b px-6 py-4">
    <div class="flex min-w-0 items-center gap-3">
      <img
        :src="template.logo"
        :alt="`${template.business_name} logo`"
        class="size-10 rounded object-cover"
      />
      <div>
        <p class="font-semibold">{{ template.business_name }}</p>
        <p class="text-xs opacity-70">{{ section.content_json.tagline }}</p>
      </div>
    </div>
    <nav class="flex flex-wrap gap-3 text-sm font-medium">
      <span v-for="item in ['About', 'Services', 'FAQ']" :key="item">{{ item }}</span>
    </nav>
  </header>
</template>
