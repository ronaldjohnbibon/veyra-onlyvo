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
      <article v-for="item in items" :key="item" class="rounded border p-5">
        <div
          class="mb-4 flex size-12 items-center justify-center rounded text-lg font-bold text-white"
          style="background: var(--template-primary)"
        >
          {{ item.charAt(0) }}
        </div>
        <h3 class="font-semibold">{{ item.split(' - ')[0] }}</h3>
        <p class="mt-1 text-sm opacity-70">{{ item.split(' - ')[1] || 'Team member' }}</p>
      </article>
    </div>
  </section>
</template>
