<script setup lang="ts">
const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}

const privacyPolicyUrl = String(runtimeSettings['compliance.privacy_policy_url'] || '')
const termsOfServiceUrl = String(runtimeSettings['compliance.terms_of_service_url'] || '')
</script>

<template>
  <div
    v-if="privacyPolicyUrl || termsOfServiceUrl"
    class="flex flex-wrap justify-center gap-4 px-6 text-center text-xs text-muted-foreground"
  >
    <a
      v-if="privacyPolicyUrl"
      :href="privacyPolicyUrl"
      target="_blank"
      rel="noreferrer"
      class="hover:text-foreground"
    >
      Privacy Policy
    </a>
    <a
      v-if="termsOfServiceUrl"
      :href="termsOfServiceUrl"
      target="_blank"
      rel="noreferrer"
      class="hover:text-foreground"
    >
      Terms of Service
    </a>
  </div>
</template>
