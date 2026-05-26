<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <section class="px-6 py-12">
    <div class="grid gap-6 rounded border p-6 md:grid-cols-[1fr_0.9fr] md:items-center">
      <div>
        <h2 class="text-3xl font-bold">{{ section.content_json.title }}</h2>
        <p class="mt-3 leading-7 opacity-75">{{ section.content_json.body }}</p>
      </div>
      <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent>
        <input
          :placeholder="section.content_json.email_placeholder || 'Email address'"
          class="min-h-11 flex-1 rounded border bg-white px-4 text-sm"
          type="email"
        />
        <button
          class="rounded px-5 py-3 text-sm font-semibold text-white"
          style="background: var(--template-primary)"
        >
          {{ section.content_json.cta_label || 'Subscribe' }}
        </button>
      </form>
    </div>
  </section>
</template>
