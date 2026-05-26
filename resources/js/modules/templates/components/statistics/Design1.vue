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
  <section
    class="px-6 py-12"
    style="background: color-mix(in srgb, var(--template-secondary) 8%, transparent)"
  >
    <h2 class="text-center text-3xl font-bold">{{ section.content_json.title }}</h2>
    <div class="mt-8 grid gap-4 md:grid-cols-3">
      <div v-for="stat in stats" :key="stat" class="rounded bg-white/70 p-6 text-center">
        <p class="text-3xl font-bold" style="color: var(--template-primary)">
          {{ stat.split(' ')[0] }}
        </p>
        <p class="mt-2 text-sm font-medium opacity-75">{{ stat.split(' ').slice(1).join(' ') }}</p>
      </div>
    </div>
  </section>
</template>
