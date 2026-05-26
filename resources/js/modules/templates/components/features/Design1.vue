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
    <div class="mb-6">
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-2 opacity-70">{{ section.content_json.subtitle }}</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
      <article v-for="item in items" :key="item" class="rounded border p-5">
        <div class="mb-4 size-2 rounded-full" style="background: var(--template-primary)" />
        <h3 class="font-semibold">{{ item }}</h3>
        <p class="mt-2 text-sm leading-6 opacity-70">A reason customers can trust this business.</p>
      </article>
    </div>
  </section>
</template>
