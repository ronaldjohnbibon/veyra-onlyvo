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
  <section class="px-6 py-10">
    <h2 class="text-center text-2xl font-bold">{{ section.content_json.title }}</h2>
    <div class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4">
      <div
        v-for="item in items"
        :key="item"
        class="rounded border px-4 py-5 text-center font-semibold"
      >
        {{ item }}
      </div>
    </div>
  </section>
</template>
