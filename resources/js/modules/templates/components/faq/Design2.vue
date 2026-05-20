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
    class="grid gap-6 px-6 py-12 md:grid-cols-[0.75fr_1.25fr]"
    style="background: color-mix(in srgb, var(--template-primary) 7%, transparent)"
  >
    <div>
      <p class="text-sm font-semibold uppercase" style="color: var(--template-primary)">
        Questions
      </p>
      <h2 class="mt-2 text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-3 text-sm opacity-70">{{ template.contact_info.email }}</p>
    </div>
    <div class="grid gap-3">
      <details v-for="item in items" :key="item" class="rounded bg-white/80 p-4 shadow-sm">
        <summary class="cursor-pointer font-semibold">{{ item }}</summary>
        <p class="mt-2 opacity-70">{{ section.content_json.body }}</p>
      </details>
    </div>
  </section>
</template>
