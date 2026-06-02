<script setup lang="ts">
import TemplatePreview from '@/tenant/templates/components/TemplatePreview.vue'
import { useTemplateStore } from '@/tenant/templates/template-store'
import { Button } from '@/shared/components/ui/button'
import { ArrowLeft, FileText } from 'lucide-vue-next'
import { onMounted } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

const route = useRoute()
const templateStore = useTemplateStore()
const templateId = String(route.params.id)

onMounted(async () => {
  await templateStore.show(templateId)
})
</script>

<template>
  <main class="min-h-screen bg-muted/30 p-4">
    <div class="mx-auto mb-4 flex max-w-6xl justify-between gap-3">
      <Button as-child variant="navigate">
        <RouterLink to="/templates">
          <ArrowLeft class="size-4" />
          Back
        </RouterLink>
      </Button>
      <Button as-child variant="navigate">
        <RouterLink :to="{ name: 'posts.index', query: { templateId } }">
          <FileText class="size-4" />
          Posts
        </RouterLink>
      </Button>
    </div>
    <div class="mx-auto max-w-6xl">
      <TemplatePreview v-if="templateStore.template" :template="templateStore.template" />
      <div v-else class="rounded border bg-card p-6 text-sm text-muted-foreground">
        Template not found.
      </div>
    </div>
  </main>
</template>
