<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import { useAdminSidebarStore } from '@/modules/admin/sidebar/sidebar-store'
import type { SidebarData } from '@/types/sidebar'
import { computed, onMounted, ref } from 'vue'

const sidebarStore = useAdminSidebarStore()
const editorValue = ref('')
const errorMessage = ref('')

const sidebar = computed(() => sidebarStore.sidebars[0] ?? null)

const loadSidebar = async () => {
  await sidebarStore.index()
  editorValue.value = sidebar.value ? JSON.stringify(sidebar.value.data, null, 2) : ''
}

const saveSidebar = async () => {
  if (!sidebar.value) return

  try {
    errorMessage.value = ''
    const parsed = JSON.parse(editorValue.value) as SidebarData

    await sidebarStore.update(sidebar.value.id, {
      name: sidebar.value.name,
      description: sidebar.value.description,
      data: parsed,
    })

    editorValue.value = sidebar.value
      ? JSON.stringify(sidebar.value.data, null, 2)
      : editorValue.value
  } catch (error) {
    errorMessage.value =
      error instanceof SyntaxError ? 'Sidebar JSON is invalid.' : 'Unable to save sidebar.'
  }
}

onMounted(loadSidebar)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Admin Sidebar Settings</h2>
          <p class="module-container-description">
            Update the platform admin navigation structure stored by the API.
          </p>
        </div>
      </div>

      <div class="space-y-4">
        <div
          v-if="sidebar"
          class="grid gap-2 rounded border bg-background p-3 text-sm text-muted-foreground sm:grid-cols-3"
        >
          <div>Name: {{ sidebar.name }}</div>
          <div>Groups: {{ sidebar.stats?.nav_groups ?? 0 }}</div>
          <div>Links: {{ sidebar.stats?.links ?? 0 }}</div>
        </div>

        <Textarea
          v-model="editorValue"
          class="min-h-[calc(100vh-18rem)] resize-y bg-background p-4 font-mono"
          spellcheck="false"
        />

        <p v-if="errorMessage" class="text-sm font-medium text-destructive">{{ errorMessage }}</p>

        <div class="fixed bottom-6 right-6 flex w-[100px] flex-col gap-2">
          <Button
            variant="update"
            size="sm"
            class="w-full rounded shadow-lg"
            :disabled="sidebarStore.loading || !sidebar"
            @click="saveSidebar"
          >
            {{ sidebarStore.loading ? 'Saving...' : 'Save' }}
          </Button>
        </div>
      </div>
    </div>
  </div>
</template>
