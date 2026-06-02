<script setup lang="ts">
import type { SidebarProps } from '@/shared/components/ui/sidebar'
import type { SidebarRecord } from '@/shared/types/sidebar'
import { computed, onMounted } from 'vue'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { useSidebarStore } from '@/tenant/sidebar/sidebar-store'
import NavMain from '@/shared/components/NavMain.vue'
import NavUser from '@/tenant/sidebar/components/NavUser.vue'
import TeamSwitcher from '@/shared/components/TeamSwitcher.vue'
import { Skeleton } from '@/shared/components/ui/skeleton'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/shared/components/ui/sidebar'

const props = withDefaults(defineProps<SidebarProps>(), {
  collapsible: 'icon',
})

const sidebarStore = useSidebarStore()
const authStore = useAuthStore()

const data = computed<SidebarRecord | null>(() => sidebarStore.sidebars[0] ?? null)

const currentUser = computed(() => {
  if (!authStore.user) return null

  const fullName = `${authStore.user.first_name ?? ''} ${authStore.user.last_name ?? ''}`.trim()

  return {
    name: fullName || authStore.user.name || authStore.user.email,
    email: authStore.user.email,
    avatar: '',
  }
})

onMounted(() => {
  sidebarStore.index()
})
</script>

<template>
  <Sidebar v-bind="props">
    <template v-if="data">
      <SidebarHeader>
        <TeamSwitcher :teams="data.data.teams" />
      </SidebarHeader>
      <SidebarContent>
        <NavMain :items="data.data.main_nav" />
      </SidebarContent>
    </template>

    <template v-else>
      <SidebarContent>
        <div class="flex flex-col gap-6 p-4">
          <div class="flex flex-col gap-2">
            <Skeleton class="h-4 w-20" />
            <Skeleton class="h-10 w-full" />
          </div>
          <div class="flex flex-col gap-2">
            <Skeleton class="h-4 w-24" />
            <Skeleton class="h-10 w-full" />
          </div>
          <div class="flex flex-col gap-2">
            <Skeleton class="h-4 w-16" />
            <Skeleton class="h-10 w-full" />
          </div>
          <Skeleton class="h-10 w-32" />
        </div>
      </SidebarContent>
    </template>

    <SidebarFooter v-if="currentUser">
      <NavUser :user="currentUser" />
    </SidebarFooter>

    <SidebarRail />
  </Sidebar>
</template>
