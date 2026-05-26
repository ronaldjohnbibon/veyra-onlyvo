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
    style="background: color-mix(in srgb, var(--template-primary) 6%, transparent)"
  >
    <div class="mb-6">
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-2 opacity-70">{{ section.content_json.subtitle }}</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
      <blockquote v-for="item in items" :key="item" class="rounded border bg-white/70 p-5">
        <p class="leading-7 opacity-80">"{{ item }}"</p>
        <footer class="mt-4 text-sm font-semibold">{{ template.business_name }} client</footer>
      </blockquote>
    </div>
  </section>
</template>
