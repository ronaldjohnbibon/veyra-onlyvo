<script setup lang="ts">
import AppSidebar from '@/tenant/sidebar/components/AppSidebar.vue'
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbList,
  BreadcrumbPage,
} from '@/shared/components/ui/breadcrumb'
import { Button } from '@/shared/components/ui/button'
import { Separator } from '@/shared/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/shared/components/ui/sidebar'
import { Moon, Sun } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const isDark = ref(false)

const pageTitle = computed(() => {
  return (route.meta.title as string) || 'Workspace'
})

const setTheme = (mode: 'dark' | 'light') => {
  document.documentElement.classList.toggle('dark', mode === 'dark')
  localStorage.setItem('theme', mode)
  isDark.value = mode === 'dark'
}

const toggleTheme = () => {
  setTheme(isDark.value ? 'light' : 'dark')
}

onMounted(() => {
  const stored = localStorage.getItem('theme')

  if (stored === 'dark' || stored === 'light') {
    setTheme(stored)
    return
  }

  const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches
  setTheme(prefersDark ? 'dark' : 'light')
})
</script>

<template>
  <SidebarProvider>
    <AppSidebar class="h-full" />

    <SidebarInset class="relative">
      <header class="flex h-16 shrink-0 items-center justify-between gap-2 border-b px-3">
        <div class="flex items-center gap-2">
          <SidebarTrigger />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem>
                <BreadcrumbPage>{{ pageTitle }}</BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </div>
        <Button variant="secondary" size="sm" @click="toggleTheme" aria-label="Toggle theme">
          <Sun v-if="isDark" class="size-4" />
          <Moon v-else class="size-4" />
        </Button>
      </header>

      <slot />
    </SidebarInset>
  </SidebarProvider>
</template>
