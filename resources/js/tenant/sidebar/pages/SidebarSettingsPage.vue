<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Label } from '@/shared/components/ui/label'
import { Textarea } from '@/shared/components/ui/textarea'
import { useSidebarStore } from '@/tenant/sidebar/sidebar-store'
import type { SidebarData } from '@/shared/types/sidebar'
import { computed, onMounted, ref } from 'vue'

const sidebarStore = useSidebarStore()
const editorValue = ref('')
const errorMessage = ref('')
const editorOpen = ref(false)

const sidebar = computed(() => sidebarStore.sidebars[0] ?? null)
const sidebarDataError = computed(() => {
  return Object.entries(sidebarStore.errors).find(([field]) => field.startsWith('data'))?.[1]?.[0]
})

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
    editorOpen.value = false
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
          <h2 class="module-container-title">Sidebar Settings</h2>
          <p class="module-container-description">
            Update the tenant navigation structure stored by the API.
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

        <div class="fixed bottom-6 right-6 flex w-[100px] flex-col gap-2">
          <Button
            variant="update"
            size="sm"
            class="w-full rounded shadow-lg"
            :disabled="sidebarStore.loading || !sidebar"
            @click="editorOpen = true"
          >
            Edit
          </Button>
        </div>

        <Dialog :open="editorOpen" @update:open="editorOpen = $event">
          <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-4xl">
            <DialogHeader>
              <DialogTitle>Edit Sidebar JSON</DialogTitle>
              <DialogDescription>
                Update the tenant navigation JSON stored by the API.
              </DialogDescription>
            </DialogHeader>

            <Textarea
              v-field-help="'Edit the tenant sidebar structure as valid JSON.'"
              v-model="editorValue"
              class="min-h-[60vh] resize-y bg-background p-4 font-mono"
              spellcheck="false"
            />

            <Label v-if="sidebarDataError" class="text-destructive text-xs">
              {{ sidebarDataError }}
            </Label>

            <p v-if="errorMessage" class="text-sm font-medium text-destructive">
              {{ errorMessage }}
            </p>

            <DialogFooter>
              <Button
                variant="update"
                type="button"
                :disabled="sidebarStore.loading || !sidebar"
                @click="saveSidebar"
              >
                {{ sidebarStore.loading ? 'Saving...' : 'Save' }}
              </Button>
            </DialogFooter>
          </DialogScrollContent>
        </Dialog>
      </div>
    </div>
  </div>
</template>
