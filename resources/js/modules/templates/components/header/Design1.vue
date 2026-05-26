<script setup lang="ts">
import type {
  TemplateNavigationItem,
  TemplateRecord,
  TemplateSection,
  TemplateSectionType,
} from '@/types/templates'
import { Menu, X } from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  template: TemplateRecord
  section: TemplateSection
}>()

const mobileOpen = ref(false)
const activeAnchor = ref('')

const sectionAnchorId = (sectionType: TemplateSectionType): string => {
  return `template-section-${sectionType.replaceAll('_', '-')}`
}

const navItems = computed(() => {
  const enabledSections = new Set(
    (props.template.sections ?? [])
      .filter((item) => item.is_enabled && !['header', 'footer'].includes(item.section_type))
      .map((item) => item.section_type)
  )

  return ((props.section.content_json.navigation_items ?? []) as TemplateNavigationItem[])
    .filter((item) => item.label?.trim() && enabledSections.has(item.section_type))
    .map((item) => ({
      id: sectionAnchorId(item.section_type),
      label: item.label.trim(),
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
  <header class="border-b bg-white/95 px-6 py-4">
    <div class="flex items-center justify-between gap-4">
      <div class="flex min-w-0 items-center gap-3">
        <img
          :src="template.logo"
          :alt="`${template.business_name} logo`"
          class="size-10 rounded object-cover"
        />
        <div>
          <p class="font-semibold">{{ template.business_name }}</p>
          <p class="text-xs opacity-70">{{ section.content_json.tagline }}</p>
        </div>
      </div>

      <nav class="hidden flex-wrap items-center gap-3 text-sm font-medium md:flex">
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
        class="rounded border px-3 py-2"
        :style="activeAnchor === item.id ? 'color: var(--template-primary)' : undefined"
        @click="closeMenu(item.id)"
      >
        {{ item.label }}
      </a>
    </nav>
  </header>
</template>
