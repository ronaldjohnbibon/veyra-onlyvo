<script setup lang="ts">
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import { useTemplateStore } from '@/modules/templates/template-store'
import { Button } from '@/components/ui/button'
import type { TemplatePayload, TemplateRecord, TemplateStatus } from '@/types/templates'
import { ArrowLeft, Save, Send, Trash2 } from 'lucide-vue-next'
import { onMounted } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const templateStore = useTemplateStore()
const templateId = String(route.params.id)

// Build an update payload from the loaded template and requested status.
const payloadFromTemplate = (
  template: TemplateRecord,
  status: TemplateStatus
): TemplatePayload => ({
  name: template.name,
  business_name: template.business_name,
  logo: template.logo,
  contact_info: template.contact_info,
  social_links: template.social_links,
  font_family: template.font_family,
  primary_color: template.primary_color,
  secondary_color: template.secondary_color,
  background_color: template.background_color,
  text_color: template.text_color,
  status,
  sections: template.sections,
})

const saveTemplate = async (status: TemplateStatus): Promise<void> => {
  if (!templateStore.template) return

  await templateStore.update(templateId, payloadFromTemplate(templateStore.template, status))
}

const deleteTemplate = async (): Promise<void> => {
  if (!templateStore.template) return

  await templateStore.destroy(templateId)
  await router.push({ name: 'templates.index' })
}

onMounted(async () => {
  await templateStore.show(templateId)
})
</script>

<template>
  <main class="min-h-screen bg-muted/30 p-4">
    <div class="mx-auto mb-4 flex max-w-6xl justify-between gap-3">
      <Button as-child variant="outline">
        <RouterLink to="/templates">
          <ArrowLeft class="size-4" />
          Back
        </RouterLink>
      </Button>
    </div>
    <div class="mx-auto max-w-6xl">
      <TemplatePreview v-if="templateStore.template" :template="templateStore.template" />
      <div v-else class="rounded border bg-card p-6 text-sm text-muted-foreground">
        Template not found.
      </div>
    </div>

    <div class="fixed bottom-6 right-6 z-20 flex w-[100px] flex-col gap-2 draggable">
      <Button
        variant="update"
        class="w-full rounded shadow h-8 px-3 text-xs"
        type="button"
        :disabled="!templateStore.template || templateStore.loading"
        @click="saveTemplate('draft')"
      >
        <Save class="size-3" />
        Draft
      </Button>
      <Button
        variant="create"
        class="w-full rounded shadow h-8 px-3 text-xs"
        type="button"
        :disabled="!templateStore.template || templateStore.loading"
        @click="saveTemplate('published')"
      >
        <Send class="size-3" />
        Publish
      </Button>
      <Button
        variant="delete"
        class="w-full rounded shadow h-8 px-3 text-xs"
        type="button"
        :disabled="!templateStore.template || templateStore.loading"
        @click="deleteTemplate"
      >
        <Trash2 class="size-3" />
        Delete
      </Button>
    </div>
  </main>
</template>
