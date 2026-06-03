<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { ChevronsUpDown } from 'lucide-vue-next'
import { iconMap } from '@/shared/utils/iconMap'
import type { SidebarTeam } from '@/shared/types/sidebar'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuTrigger,
} from '@/shared/components/ui/dropdown-menu'
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/shared/components/ui/sidebar'

const props = defineProps<{
  teams?: SidebarTeam[]
}>()

const { isMobile } = useSidebar()
const activeIndex = ref(0)
const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}
const applicationName = computed(() =>
  String(runtimeSettings['general.application_name'] || 'Onlyvo')
)

const teams = computed(() =>
  props.teams?.length
    ? props.teams
    : [
        {
          name: applicationName.value,
          logo: 'Sparkles',
          plan: 'Workspace',
        },
      ]
)

const activeTeam = computed(() => teams.value[activeIndex.value] ?? teams.value[0])

watchEffect(() => {
  if (activeIndex.value >= teams.value.length) {
    activeIndex.value = 0
  }
})

const iconFor = (icon?: string) => {
  return icon && icon in iconMap ? iconMap[icon as keyof typeof iconMap] : iconMap.Sparkles
}
</script>

<template>
  <SidebarMenu>
    <SidebarMenuItem>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <SidebarMenuButton
            size="lg"
            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
          >
            <div
              class="flex aspect-square size-8 items-center justify-center rounded bg-teal-600 text-white"
            >
              <component :is="iconFor(activeTeam.logo)" class="size-4" />
            </div>
            <div class="grid flex-1 text-left text-sm leading-tight">
              <span class="truncate font-medium">{{ activeTeam.name }}</span>
              <span class="truncate text-xs">{{ activeTeam.plan }}</span>
            </div>
            <ChevronsUpDown class="ml-auto size-4" />
          </SidebarMenuButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded"
          align="start"
          :side="isMobile ? 'bottom' : 'right'"
          :side-offset="4"
        >
          <DropdownMenuLabel class="text-xs text-muted-foreground">Workspaces</DropdownMenuLabel>
          <DropdownMenuItem
            v-for="(team, index) in teams"
            :key="team.name"
            class="gap-2 p-2"
            @click="activeIndex = index"
          >
            <div class="flex size-6 items-center justify-center rounded border">
              <component :is="iconFor(team.logo)" class="size-3.5 shrink-0" />
            </div>
            {{ team.name }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </SidebarMenuItem>
  </SidebarMenu>
</template>
