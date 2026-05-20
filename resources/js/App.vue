<script setup lang="ts">
import BaseLoading from '@/components/BaseLoading.vue'
import BaseToast from '@/components/BaseToast.vue'
import GlobalConfirmDialog from '@/components/GlobalConfirmDialog.vue'
import { useAdminAuthStore } from '@/modules/admin/admin-auth-store'
import { useAuthStore } from '@/modules/auth/auth-store'
import { useToastStore } from '@/store/toast-store'
import { computed, onMounted, watchEffect } from 'vue'
import { useRouter } from 'vue-router'

const toastStore = useToastStore()
const tenantAuthStore = useAuthStore()
const adminAuthStore = useAdminAuthStore()
const { currentRoute } = useRouter()

const defaultLayout = 'empty'

const layout = computed(
  () => `${(currentRoute.value.meta.layout as string) || defaultLayout}-layout`
)

const defaultTitle = 'Onlyvo'
const defaultDescription = 'Onlyvo tenant platform'

onMounted(async () => {
  await Promise.all([tenantAuthStore.init(), adminAuthStore.init()])
})

watchEffect(() => {
  const meta = currentRoute.value.meta
  document.title = (meta.title as string) || defaultTitle

  let description = document.querySelector<HTMLMetaElement>('meta[name="description"]')

  if (!description) {
    description = document.createElement('meta')
    description.name = 'description'
    document.head.appendChild(description)
  }

  description.content = (meta.description as string) || defaultDescription
})
</script>

<template>
  <main class="relative min-h-screen bg-background text-foreground">
    <BaseLoading />
    <GlobalConfirmDialog />
    <BaseToast
      v-for="alert in toastStore.alerts"
      :key="alert.id"
      :type="alert.type"
      :title="alert.title"
      :message="alert.message"
    />

    <component :is="layout">
      <RouterView />
    </component>
  </main>
</template>
