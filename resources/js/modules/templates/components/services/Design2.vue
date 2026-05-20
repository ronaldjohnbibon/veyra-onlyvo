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
  <section
    class="px-6 py-12"
    style="background: color-mix(in srgb, var(--template-secondary) 8%, transparent)"
  >
    <div class="mx-auto max-w-3xl text-center">
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-2 opacity-70">{{ section.content_json.subtitle }}</p>
    </div>
    <div class="mt-7 grid gap-3">
      <div
        v-for="(item, index) in items"
        :key="item"
        class="flex items-center gap-4 rounded bg-white/80 p-4 shadow-sm"
      >
        <span
          class="flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
          style="background: var(--template-primary)"
        >
          {{ index + 1 }}
        </span>
        <p class="font-semibold">{{ item }}</p>
      </div>
    </div>
  </section>
</template>
