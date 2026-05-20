<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const stats = computed(() => props.section.content_json.stats?.filter(Boolean) ?? [])
</script>

<template>
  <section class="grid gap-8 px-6 py-12 md:grid-cols-[0.9fr_1.1fr] md:items-center">
    <div class="grid grid-cols-2 gap-3">
      <img
        :src="section.content_json.left_image || template.logo"
        :alt="section.content_json.title"
        class="h-64 w-full rounded object-cover"
      />
      <img
        :src="section.content_json.right_image || template.logo"
        :alt="section.content_json.title"
        class="mt-8 h-64 w-full rounded object-cover"
      />
    </div>
    <div>
      <p class="mb-2 text-sm font-semibold uppercase" style="color: var(--template-primary)">
        {{ section.content_json.subtitle || 'About' }}
      </p>
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-4 leading-7 opacity-75">{{ section.content_json.body }}</p>
      <div v-if="stats.length" class="mt-6 grid gap-3 sm:grid-cols-3">
        <div v-for="stat in stats" :key="stat" class="rounded border p-3">
          <p class="text-sm font-semibold">{{ stat }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
