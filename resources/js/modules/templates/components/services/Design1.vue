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
    <div class="mt-6 grid gap-4 md:grid-cols-3">
      <div v-for="item in items" :key="item" class="rounded border p-4">
        <p class="font-semibold">{{ item }}</p>
      </div>
    </div>
  </section>
</template>
