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
    <div class="mb-6 max-w-2xl">
      <p class="text-sm font-semibold uppercase" style="color: var(--template-primary)">Work</p>
      <h2 class="mt-2 text-3xl font-bold">{{ section.content_json.title }}</h2>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
      <figure v-for="item in items" :key="item" class="rounded border p-4">
        <div
          class="mb-4 h-24 rounded"
          style="
            background: linear-gradient(135deg, var(--template-secondary), var(--template-primary));
          "
        />
        <figcaption class="font-semibold">{{ item }}</figcaption>
        <p class="mt-1 text-sm opacity-60">{{ template.business_name }}</p>
      </figure>
    </div>
  </section>
</template>
