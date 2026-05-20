<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <footer
    class="border-t px-6 py-8 text-center"
    style="background: color-mix(in srgb, var(--template-primary) 8%, transparent)"
  >
    <img
      :src="template.logo"
      :alt="`${template.business_name} logo`"
      class="mx-auto size-12 rounded-full object-cover"
    />
    <p class="mt-3 font-semibold">{{ template.business_name }}</p>
    <p class="mx-auto mt-2 max-w-xl text-sm opacity-70">{{ section.content_json.body }}</p>
    <div class="mt-4 flex flex-wrap justify-center gap-3 text-sm opacity-75">
      <span>{{ template.contact_info.email }}</span>
      <span>{{ template.contact_info.phone }}</span>
      <span>{{ template.social_links.website }}</span>
    </div>
  </footer>
</template>
