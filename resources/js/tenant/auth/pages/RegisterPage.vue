<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue'
import { GalleryVerticalEnd } from 'lucide-vue-next'
import { cn } from '@/shared/utils/utils'
import { Button } from '@/shared/components/ui/button'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/shared/components/ui/card'
import { Field, FieldDescription, FieldGroup, FieldLabel } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { useAuthStore } from '../auth-store'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const authStore = useAuthStore()
const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}
const applicationName = String(runtimeSettings['general.application_name'] || 'Onlyvo')
const logoUrl = String(runtimeSettings['general.logo'] || '')
const privacyPolicyUrl = String(runtimeSettings['compliance.privacy_policy_url'] || '')
const termsOfServiceUrl = String(runtimeSettings['compliance.terms_of_service_url'] || '')
const companyNameError = computed(
  () => authStore.errors.name?.[0] ?? authStore.errors.subdomain?.[0] ?? ''
)
</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
    <div class="flex w-full max-w-sm flex-col gap-6">
      <a href="#" class="flex items-center gap-2 self-center font-medium">
        <div
          v-if="!logoUrl"
          class="bg-primary text-primary-foreground flex size-6 items-center justify-center rounded"
        >
          <GalleryVerticalEnd class="size-4" />
        </div>
        <img v-else :src="logoUrl" :alt="applicationName" class="size-6 rounded object-contain" />
        {{ applicationName }}
      </a>
      <div :class="cn('flex flex-col', props.class)">
        <Card>
          <CardHeader class="text-center">
            <CardTitle class="text-xl"> Create your account </CardTitle>
            <CardDescription> Enter your email below to create your account </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="authStore.register">
              <FieldGroup>
                <Field v-if="authStore.message">
                  <p class="text-destructive text-sm">{{ authStore.message }}</p>
                </Field>
                <Field>
                  <FieldLabel for="email"> Email </FieldLabel>

                  <Input
                    v-field-help="'Enter the email address you will use to sign in.'"
                    v-model="authStore.registerForm.email"
                    id="email"
                    type="email"
                    placeholder="user@example.com"
                    autocomplete="email"
                  />

                  <span v-if="authStore.errors.email" class="text-destructive text-[12px]">
                    {{ authStore.errors.email[0] }}
                  </span>
                </Field>
                <Field>
                  <FieldLabel for="phone"> Contact Number </FieldLabel>

                  <Input
                    v-field-help="'Enter a phone number for account contact and support.'"
                    v-model="authStore.registerForm.phone"
                    id="phone"
                    type="tel"
                    inputmode="numeric"
                    placeholder="09xxxxxxxxx"
                    maxlength="11"
                    autocomplete="tel"
                  />

                  <span v-if="authStore.errors.phone" class="text-destructive text-[12px]">
                    {{ authStore.errors.phone[0] }}
                  </span>
                </Field>
                <Field>
                  <FieldLabel for="company_name"> Company Name </FieldLabel>

                  <Input
                    v-field-help="'Enter the company or tenant name for this account.'"
                    v-model="authStore.registerForm.name"
                    id="company_name"
                    type="text"
                    autocomplete="organization"
                  />

                  <span v-if="companyNameError" class="text-destructive text-[12px]">
                    {{ companyNameError }}
                  </span>
                </Field>
                <Field>
                  <Field class="grid grid-cols-2 gap-4">
                    <Field>
                      <FieldLabel for="password"> Password </FieldLabel>

                      <Input
                        v-field-help="'Create a password that meets the platform policy.'"
                        v-model="authStore.registerForm.password"
                        id="password"
                        type="password"
                        autocomplete="new-password"
                      />

                      <span v-if="authStore.errors.password" class="text-destructive text-[12px]">
                        {{ authStore.errors.password[0] }}
                      </span>
                    </Field>
                    <Field>
                      <FieldLabel for="confirm-password"> Confirm Password </FieldLabel>

                      <Input
                        v-field-help="'Enter the same password again to confirm it.'"
                        v-model="authStore.registerForm.password_confirmation"
                        id="confirm-password"
                        type="password"
                        autocomplete="new-password"
                      />

                      <span
                        v-if="authStore.errors.password_confirmation"
                        class="text-destructive text-[12px]"
                      >
                        {{ authStore.errors.password_confirmation[0] }}
                      </span>
                    </Field>
                  </Field>
                </Field>
                <Field>
                  <Button type="submit" size="sm" :disabled="authStore.loading">
                    {{ authStore.loading ? 'Creating account...' : 'Create Account' }}
                  </Button>

                  <FieldDescription class="text-center">
                    Already have an account? <router-link to="/login">Sign in</router-link>
                  </FieldDescription>
                </Field>
              </FieldGroup>
            </form>
          </CardContent>
        </Card>
        <FieldDescription class="px-6 text-center">
          By clicking continue, you agree to our
          <a v-if="termsOfServiceUrl" :href="termsOfServiceUrl" target="_blank" rel="noreferrer">
            Terms of Service
          </a>
          <span v-else>Terms of Service</span>
          and
          <a v-if="privacyPolicyUrl" :href="privacyPolicyUrl" target="_blank" rel="noreferrer">
            Privacy Policy
          </a>
          <span v-else>Privacy Policy</span>.
        </FieldDescription>
      </div>
    </div>
  </div>
</template>
