<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section
    class="px-6 py-12"
    style="background: color-mix(in srgb, var(--template-primary) 6%, transparent)"
  >
    <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
    <div class="mt-6 grid gap-4 md:grid-cols-2">
      <article class="rounded border bg-white/70 p-5">
        <p class="text-sm font-semibold uppercase" style="color: var(--template-primary)">
          Mission
        </p>
        <p class="mt-3 leading-7 opacity-80">{{ section.content_json.body }}</p>
      </article>
      <article class="rounded border bg-white/70 p-5">
        <p class="text-sm font-semibold uppercase" style="color: var(--template-primary)">Vision</p>
        <p class="mt-3 leading-7 opacity-80">{{ section.content_json.subtitle }}</p>
      </article>
    </div>
  </section>
</template>
