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
    <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
    <p class="mt-2 opacity-70">{{ section.content_json.subtitle }}</p>

    <div class="mt-6 grid gap-4 md:grid-cols-4">
      <article v-for="(item, index) in items" :key="item" class="rounded border p-5">
        <span class="text-sm font-bold" style="color: var(--template-primary)">
          {{ String(index + 1).padStart(2, '0') }}
        </span>
        <h3 class="mt-3 font-semibold">{{ item }}</h3>
      </article>
    </div>
  </section>
</template>
