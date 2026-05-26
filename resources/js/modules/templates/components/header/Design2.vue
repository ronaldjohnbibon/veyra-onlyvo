<script setup lang="ts">
import type { TemplateRecord, TemplateSection, TemplateSectionType } from '@/types/templates'
import { Menu, X } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const mobileOpen = ref(false)
const activeAnchor = ref('')

const labels: Partial<Record<TemplateSectionType, string>> = {
  cta: 'Contact',
  faq: 'FAQ',
  mission_vision: 'Mission',
  social_links: 'Social',
}

const sectionAnchorId = (sectionType: TemplateSectionType): string => {
  return `template-section-${sectionType.replaceAll('_', '-')}`
}

const sectionLabel = (section: TemplateSection): string => {
  const title = section.content_json.title

  return (
    (typeof title === 'string' && title.trim()) ||
    labels[section.section_type] ||
    section.section_type.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
  )
}

const navItems = computed(() => {
  return [...props.template.sections]
    .filter((item) => item.is_enabled && !['header', 'footer'].includes(item.section_type))
    .sort((a, b) => a.sort_order - b.sort_order)
    .map((item) => ({
      id: sectionAnchorId(item.section_type),
      label: sectionLabel(item),
    }))
})

const syncActiveAnchor = (): void => {
  activeAnchor.value = window.location.hash.replace('#', '')
}

const closeMenu = (id: string): void => {
  activeAnchor.value = id
  mobileOpen.value = false
}

onMounted(() => {
  syncActiveAnchor()
  window.addEventListener('hashchange', syncActiveAnchor)
})

onBeforeUnmount(() => {
  window.removeEventListener('hashchange', syncActiveAnchor)
})
</script>

<template>
  <header
    class="border-b px-6 py-5"
    style="background: color-mix(in srgb, var(--template-primary) 8%, transparent)"
  >
    <div class="flex items-center justify-between gap-4">
      <nav class="hidden flex-1 flex-wrap gap-3 text-sm font-medium md:flex">
        <a
          v-for="item in navItems"
          :key="item.id"
          :href="`#${item.id}`"
          class="rounded px-2 py-1 opacity-75 hover:opacity-100"
          :class="{ 'opacity-100': activeAnchor === item.id }"
          :style="activeAnchor === item.id ? 'color: var(--template-primary)' : undefined"
          @click="closeMenu(item.id)"
        >
          {{ item.label }}
        </a>
      </nav>

      <div class="flex min-w-0 flex-1 flex-col items-center gap-2 text-center">
        <img
          :src="template.logo"
          :alt="`${template.business_name} logo`"
          class="size-12 rounded-full object-cover"
        />
        <div>
          <p class="font-semibold">{{ template.business_name }}</p>
          <p class="text-xs opacity-70">{{ section.content_json.tagline }}</p>
        </div>
      </div>

      <div class="hidden flex-1 justify-end md:flex">
        <a
          v-if="section.content_json.cta_label"
          :href="section.content_json.button_link || '#template-section-contact'"
          class="rounded px-4 py-2 text-xs font-semibold text-white"
          style="background: var(--template-primary)"
        >
          {{ section.content_json.cta_label }}
        </a>
      </div>

      <button
        class="rounded border p-2 md:hidden"
        type="button"
        aria-label="Toggle navigation"
        :aria-expanded="mobileOpen"
        @click="mobileOpen = !mobileOpen"
      >
        <X v-if="mobileOpen" class="size-4" />
        <Menu v-else class="size-4" />
      </button>
    </div>

    <nav v-if="mobileOpen" class="mt-4 grid gap-2 text-sm font-medium md:hidden">
      <a
        v-for="item in navItems"
        :key="item.id"
        :href="`#${item.id}`"
        class="rounded border bg-white/60 px-3 py-2"
        :style="activeAnchor === item.id ? 'color: var(--template-primary)' : undefined"
        @click="closeMenu(item.id)"
      >
        {{ item.label }}
      </a>
      <a
        v-if="section.content_json.cta_label"
        :href="section.content_json.button_link || '#template-section-contact'"
        class="rounded px-4 py-2 text-center text-xs font-semibold text-white"
        style="background: var(--template-primary)"
        @click="mobileOpen = false"
      >
        {{ section.content_json.cta_label }}
      </a>
    </nav>
  </header>
</template>
