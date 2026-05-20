<script setup lang="ts">
import type { SidebarProps } from '@/components/ui/sidebar'
import type { SidebarRecord } from '@/types/sidebar'
import { computed, onMounted } from 'vue'
import { useAdminAuthStore } from '@/modules/admin/admin-auth-store'
import { useAdminSidebarStore } from '@/modules/admin/sidebar/sidebar-store'
import AdminNavUser from '@/components/AdminNavUser.vue'
import NavMain from '@/components/NavMain.vue'
import TeamSwitcher from '@/components/TeamSwitcher.vue'
import { Skeleton } from '@/components/ui/skeleton'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarRail,
} from '@/components/ui/sidebar'

const props = withDefaults(defineProps<SidebarProps>(), {
  collapsible: 'icon',
})

const sidebarStore = useAdminSidebarStore()
const authStore = useAdminAuthStore()

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

      <SidebarFooter v-if="currentUser">
        <AdminNavUser :user="currentUser" />
      </SidebarFooter>

      <SidebarRail />
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
  </Sidebar>
</template>
