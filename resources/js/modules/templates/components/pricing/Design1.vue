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
    <div class="text-center">
      <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
      <p class="mt-2 opacity-70">{{ section.content_json.subtitle }}</p>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-3">
      <article v-for="item in items" :key="item" class="rounded border p-5">
        <h3 class="font-semibold">{{ item.split(' - ')[0] }}</h3>
        <p class="mt-3 text-2xl font-bold" style="color: var(--template-primary)">
          {{ item.split(' - ')[1] || 'Custom' }}
        </p>
        <p class="mt-3 text-sm leading-6 opacity-70">A focused plan for common business needs.</p>
      </article>
    </div>
  </section>
</template>
