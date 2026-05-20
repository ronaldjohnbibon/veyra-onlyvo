<script setup lang="ts">
import type { TemplateRecord, TemplateSection } from '@/types/templates'

defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()
</script>

<template>
  <footer class="border-t px-6 py-8">
    <div class="flex flex-wrap justify-between gap-4">
      <div>
        <p class="font-semibold">{{ template.business_name }}</p>
        <p class="text-sm opacity-70">{{ section.content_json.body }}</p>
      </div>
      <div class="text-sm opacity-75">
        <p>{{ template.contact_info.email }}</p>
        <p>{{ template.contact_info.phone }}</p>
        <p>{{ template.contact_info.address }}</p>
      </div>
    </div>
  </footer>
</template>
