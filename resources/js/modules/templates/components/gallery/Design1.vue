<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const images = computed(() => props.section.content_json.items?.filter(Boolean) ?? [])
</script>

<template>
  <section class="px-6 py-12">
    <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
    <div class="mt-6 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
      <img
        v-for="image in images"
        :key="image"
        :src="image"
        :alt="section.content_json.title"
        class="aspect-[4/3] w-full rounded object-cover"
      />
    </div>
  </section>
</template>
