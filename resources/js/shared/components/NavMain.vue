<script setup lang="ts">
import { iconMap } from '@/shared/utils/iconMap'
import type { SidebarNavItem } from '@/shared/types/sidebar'
import { ChevronRight, Circle } from 'lucide-vue-next'
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/shared/components/ui/collapsible'
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/shared/components/ui/sidebar'
import { useRoute } from 'vue-router'

const props = defineProps<{
  items: SidebarNavItem[]
}>()

const route = useRoute()

const resolveUrl = (url: string): string => {
  if (isExternalUrl(url)) return url
  if (url === '#') return route.path

  const base = route.path.startsWith('/admin') ? '/admin' : ''

  if (url.startsWith('/')) {
    return `${base && !url.startsWith(base) ? base : ''}${url}`.replace(/\/+/g, '/')
  }

  return `${base}/${url}`.replace(/\/+/g, '/')
}

const isExternalUrl = (url: string): boolean => /^(https?:|mailto:|tel:)/i.test(url)

const opensNewTab = (url: string): boolean => /^https?:/i.test(url)

const isRouteActive = (url: string): boolean => {
  if (isExternalUrl(url)) return false

  const resolved = resolveUrl(url)

  return route.path === resolved || route.path.startsWith(`${resolved}/`)
}

const hasActiveChild = (item: SidebarNavItem): boolean => {
  return item.items?.some((subItem) => isRouteActive(subItem.url)) ?? false
}

const iconFor = (icon?: string) => {
  return icon && icon in iconMap ? iconMap[icon as keyof typeof iconMap] : Circle
}
</script>

<template>
  <SidebarGroup>
    <SidebarGroupLabel>Navigation</SidebarGroupLabel>
    <SidebarMenu>
      <template v-for="item in props.items" :key="item.title">
        <Collapsible
          v-if="item.items?.length"
          as-child
          :default-open="item.is_active || hasActiveChild(item)"
          class="group/collapsible"
        >
          <SidebarMenuItem>
            <CollapsibleTrigger as-child>
              <SidebarMenuButton :tooltip="item.title">
                <component :is="iconFor(item.icon)" />
                <span>{{ item.title }}</span>
                <ChevronRight
                  class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                />
              </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                <SidebarMenuSubItem
                  v-for="subItem in item.items"
                  :key="`${item.title}-${subItem.title}`"
                >
                  <SidebarMenuSubButton as-child :is-active="isRouteActive(subItem.url)">
                    <a
                      v-if="isExternalUrl(subItem.url)"
                      :href="subItem.url"
                      :target="opensNewTab(subItem.url) ? '_blank' : undefined"
                      :rel="opensNewTab(subItem.url) ? 'noopener noreferrer' : undefined"
                    >
                      <component :is="iconFor(subItem.icon)" />
                      <span>{{ subItem.title }}</span>
                    </a>
                    <RouterLink v-else :to="resolveUrl(subItem.url)">
                      <component :is="iconFor(subItem.icon)" />
                      <span>{{ subItem.title }}</span>
                    </RouterLink>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>

        <SidebarMenuItem v-else>
          <SidebarMenuButton as-child :tooltip="item.title" :is-active="isRouteActive(item.url)">
            <a
              v-if="isExternalUrl(item.url)"
              :href="item.url"
              :target="opensNewTab(item.url) ? '_blank' : undefined"
              :rel="opensNewTab(item.url) ? 'noopener noreferrer' : undefined"
            >
              <component :is="iconFor(item.icon)" />
              <span>{{ item.title }}</span>
            </a>
            <RouterLink v-else :to="resolveUrl(item.url)">
              <component :is="iconFor(item.icon)" />
              <span>{{ item.title }}</span>
            </RouterLink>
          </SidebarMenuButton>
        </SidebarMenuItem>
      </template>
    </SidebarMenu>
  </SidebarGroup>
</template>
