<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { getWebsiteTemplateComponent } from '@/modules/templates/components/registry'
import type { TemplateRecord } from '@/types/templates'
import { Maximize2, Monitor, Smartphone, Tablet } from 'lucide-vue-next'
import { computed, ref, type CSSProperties } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  showControls?: boolean
}>()

const previewSizes = [
  { key: 'mobile', label: 'Mobile', width: '390px', icon: Smartphone },
  { key: 'tablet', label: 'Tablet', width: '768px', icon: Tablet },
  { key: 'desktop', label: 'Desktop', width: '1024px', icon: Monitor },
  { key: 'full', label: 'Full width', width: '100%', icon: Maximize2 },
] as const

type PreviewSizeKey = (typeof previewSizes)[number]['key']

const selectedPreviewSize = ref<PreviewSizeKey>('full')

const templateComponent = computed(() => {
  return getWebsiteTemplateComponent(props.template.website_type?.slug, props.template.template_key)
})

const selectedPreview = computed(() => {
  return previewSizes.find((size) => size.key === selectedPreviewSize.value) ?? previewSizes[3]
})

const previewStyle = computed<CSSProperties>(() => ({
  '--template-primary': props.template.primary_color,
  '--template-secondary': props.template.secondary_color,
  '--template-bg': props.template.background_color,
  '--template-text': props.template.text_color,
  backgroundColor: props.template.background_color,
  color: props.template.text_color,
  fontFamily: `${props.template.font_family}, Inter, sans-serif`,
}))

const previewFrameStyle = computed<CSSProperties>(() => ({
  width: selectedPreview.value.width,
}))
</script>

<template>
  <div class="space-y-3">
    <div
      v-if="props.showControls !== false"
      class="flex flex-wrap items-center justify-between gap-2"
    >
      <p class="text-xs font-semibold text-muted-foreground">Preview size</p>

      <div class="flex flex-wrap items-center gap-1 rounded border bg-background p-1">
        <Button
          v-for="size in previewSizes"
          :key="size.key"
          size="xs"
          :variant="selectedPreviewSize === size.key ? 'secondary' : 'ghost'"
          type="button"
          :aria-pressed="selectedPreviewSize === size.key"
          :title="size.label"
          @click="selectedPreviewSize = size.key"
        >
          <component :is="size.icon" class="size-3.5" />
          {{ size.label }}
        </Button>
      </div>
    </div>

    <div class="overflow-auto pb-2">
      <article
        class="template-preview mx-auto min-h-full overflow-hidden rounded border bg-white transition-[width] duration-200"
        :style="[previewStyle, previewFrameStyle]"
      >
        <component :is="templateComponent" v-if="templateComponent" :template="props.template" />
        <Empty v-else class="min-h-[320px] border-0">
          <EmptyHeader>
            <EmptyTitle>No template selected</EmptyTitle>
            <EmptyDescription>
              Select a website type and complete template to build a preview.
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      </article>
    </div>
  </div>
</template>

<style scoped>
.template-preview {
  --template-primary: #14b8a6;
  --template-secondary: #0f766e;
  --template-bg: #ffffff;
  --template-text: #111827;
}
</style>
