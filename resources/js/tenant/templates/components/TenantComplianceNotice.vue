<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import {
  settingBoolean,
  settingString,
  type TenantPublicSettings,
} from '@/tenant/templates/composables/useTenantPublicSettings'
import { computed, ref } from 'vue'

const props = defineProps<{
  settings?: TenantPublicSettings
}>()

const accepted = ref(localStorage.getItem('tenant_cookie_notice_accepted') === 'true')
const privacyUrl = computed(() => settingString(props.settings, 'compliance.privacy_policy_url'))
const termsUrl = computed(() => settingString(props.settings, 'compliance.terms_of_service_url'))
const footerText = computed(() => settingString(props.settings, 'website.footer_text'))
const cookieEnabled = computed(() =>
  settingBoolean(props.settings, 'compliance.cookie_notice_enabled', false)
)
const cookieText = computed(
  () =>
    settingString(props.settings, 'compliance.cookie_notice_text') ||
    'This site uses cookies and analytics to improve the visitor experience.'
)

const acceptCookies = (): void => {
  localStorage.setItem('tenant_cookie_notice_accepted', 'true')
  accepted.value = true
}
</script>

<template>
  <footer
    v-if="footerText || privacyUrl || termsUrl"
    class="border-t bg-background px-6 py-5 text-sm text-muted-foreground"
  >
    <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4">
      <p v-if="footerText">{{ footerText }}</p>
      <div class="flex flex-wrap items-center gap-4">
        <a v-if="privacyUrl" :href="privacyUrl" class="hover:text-foreground">Privacy Policy</a>
        <a v-if="termsUrl" :href="termsUrl" class="hover:text-foreground">Terms of Service</a>
      </div>
    </div>
  </footer>

  <div
    v-if="cookieEnabled && !accepted"
    class="fixed inset-x-4 bottom-4 z-50 mx-auto flex max-w-3xl flex-col gap-3 rounded border bg-background p-4 text-sm shadow-lg sm:flex-row sm:items-center sm:justify-between"
  >
    <p class="leading-6 text-muted-foreground">{{ cookieText }}</p>
    <Button type="button" size="sm" variant="update" class="shrink-0" @click="acceptCookies">
      Accept
    </Button>
  </div>
</template>
