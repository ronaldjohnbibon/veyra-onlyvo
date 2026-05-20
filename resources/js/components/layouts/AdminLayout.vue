<script setup lang="ts">
import AdminSidebar from '@/components/AdminSidebar.vue'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
</script>

<template>
  <SidebarProvider>
    <AdminSidebar class="h-full" />

    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center justify-between gap-2 border-b px-3">
        <div class="flex items-center gap-2">
          <SidebarTrigger />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <div class="text-sm font-medium uppercase tracking-wide text-muted-foreground">
            Platform Admin
          </div>
        </div>
      </header>

      <slot />
    </SidebarInset>
  </SidebarProvider>
</template>
