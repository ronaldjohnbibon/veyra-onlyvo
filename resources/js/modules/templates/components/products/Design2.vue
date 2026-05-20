<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const items = computed(() => props.section.content_json.items?.filter(Boolean) ?? [])
</script>

<template>
  <section class="px-6 py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="text-sm font-semibold" style="color: var(--template-primary)">
        {{ template.business_name }}
      </p>
    </div>
    <div class="mt-6 divide-y rounded border">
      <div v-for="item in items" :key="item" class="flex items-center justify-between gap-4 p-4">
        <p class="font-semibold">{{ item }}</p>
        <span class="text-sm opacity-60">Available</span>
      </div>
    </div>
  </section>
</template>
