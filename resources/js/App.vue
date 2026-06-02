<script setup lang="ts">
import BaseLoading from '@/shared/components/BaseLoading.vue'
import BaseToast from '@/shared/components/BaseToast.vue'
import GlobalConfirmDialog from '@/shared/components/GlobalConfirmDialog.vue'
import { useAdminAuthStore } from '@/admin/auth/auth-store'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { useToastStore } from '@/shared/stores/toast-store'
import { computed, onMounted, ref, watchEffect } from 'vue'
import { useRouter } from 'vue-router'

const toastStore = useToastStore()
const tenantAuthStore = useAuthStore()
const adminAuthStore = useAdminAuthStore()
const { currentRoute } = useRouter()
const showCookieNotice = ref(false)

const defaultLayout = 'empty'

const layout = computed(
  () => `${(currentRoute.value.meta.layout as string) || defaultLayout}-layout`
)

const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}
const defaultTitle = String(
  runtimeSettings['seo.default_meta_title'] ||
    runtimeSettings['general.application_name'] ||
    'Onlyvo'
)
const defaultDescription = String(
  runtimeSettings['seo.default_meta_description'] ||
    runtimeSettings['general.application_description'] ||
    'Onlyvo tenant platform'
)
const maintenanceMode = Boolean(runtimeSettings['maintenance.maintenance_mode'])
const maintenanceMessage = String(
  runtimeSettings['maintenance.maintenance_message'] ||
    'The platform is temporarily unavailable for maintenance.'
)
const maintenanceStart = String(runtimeSettings['maintenance.maintenance_start_time'] || '')
const maintenanceEnd = String(runtimeSettings['maintenance.maintenance_end_time'] || '')
const allowAdminBypass = runtimeSettings['maintenance.allow_admin_bypass'] !== false
const affectedAreas = String(runtimeSettings['maintenance.maintenance_affected_areas'] || '')
  .split(/[\s,]+/)
  .map((area) => area.trim())
  .filter(Boolean)
const cookieNoticeEnabled = Boolean(runtimeSettings['compliance.cookie_notice_enabled'])
const privacyPolicyUrl = String(runtimeSettings['compliance.privacy_policy_url'] || '')
const termsOfServiceUrl = String(runtimeSettings['compliance.terms_of_service_url'] || '')

const maintenanceWindowActive = computed(() => {
  if (!maintenanceMode) return false

  const now = Date.now()
  const startsAt = maintenanceStart ? Date.parse(maintenanceStart) : null
  const endsAt = maintenanceEnd ? Date.parse(maintenanceEnd) : null

  return (!startsAt || now >= startsAt) && (!endsAt || now <= endsAt)
})
const affectedByMaintenance = computed(() => {
  if (!affectedAreas.length) return true

  return affectedAreas.some((area) => currentRoute.value.path.startsWith(area))
})
const showMaintenance = computed(() => {
  const adminBypassed = currentRoute.value.path.startsWith('/admin') && allowAdminBypass

  return maintenanceWindowActive.value && affectedByMaintenance.value && !adminBypassed
})

const acceptCookieNotice = (): void => {
  localStorage.setItem('cookie_notice_accepted', 'true')
  showCookieNotice.value = false
}

onMounted(async () => {
  await Promise.all([tenantAuthStore.init(), adminAuthStore.init()])
  showCookieNotice.value =
    cookieNoticeEnabled && localStorage.getItem('cookie_notice_accepted') !== 'true'
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

    <section
      v-if="showMaintenance"
      class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-6 text-center"
    >
      <h1 class="text-2xl font-semibold text-foreground">Maintenance Mode</h1>
      <p class="mt-3 text-sm leading-6 text-muted-foreground">
        {{ maintenanceMessage }}
      </p>
    </section>

    <component v-else :is="layout">
      <RouterView />
    </component>

    <div
      v-if="showCookieNotice"
      class="fixed inset-x-4 bottom-4 z-50 rounded border bg-background p-4 shadow-lg md:left-auto md:max-w-md"
    >
      <p class="text-sm leading-6 text-foreground">
        We use cookies to keep the platform reliable and understand usage.
      </p>
      <div class="mt-3 flex flex-wrap items-center gap-3">
        <a
          v-if="privacyPolicyUrl"
          :href="privacyPolicyUrl"
          class="text-sm font-medium text-primary"
          target="_blank"
          rel="noreferrer"
        >
          Privacy
        </a>
        <a
          v-if="termsOfServiceUrl"
          :href="termsOfServiceUrl"
          class="text-sm font-medium text-primary"
          target="_blank"
          rel="noreferrer"
        >
          Terms
        </a>
        <button class="ml-auto text-sm font-semibold text-primary" @click="acceptCookieNotice">
          Accept
        </button>
      </div>
    </div>
  </main>
</template>
