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
    <div class="mt-6 grid gap-4 md:grid-cols-2">
      <figure v-for="item in items" :key="item" class="overflow-hidden rounded border">
        <div
          class="h-32"
          style="
            background: linear-gradient(135deg, var(--template-primary), var(--template-secondary));
          "
        />
        <figcaption class="p-4 font-semibold">{{ item }}</figcaption>
      </figure>
    </div>
  </section>
</template>
