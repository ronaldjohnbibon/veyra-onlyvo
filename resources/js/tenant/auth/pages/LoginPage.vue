<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
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
import FieldHelp from '@/shared/components/FieldHelp.vue'
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
const registrationEnabled = runtimeSettings['authentication.allow_tenant_registration'] !== false
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <div :class="cn('flex flex-col gap-6', props.class)">
        <Card>
          <CardHeader>
            <CardTitle>Login to your account</CardTitle>
            <CardDescription> Enter your email below to login to your account </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="authStore.login">
              <FieldGroup>
                <Field v-if="authStore.message">
                  <p class="text-destructive text-sm">{{ authStore.message }}</p>
                </Field>
                <Field>
                  <FieldLabel for="email"> Email </FieldLabel>
                  <FieldHelp description="Enter the email address for your tenant account.">
                    <Input
                      v-model="authStore.loginForm.email"
                      id="email"
                      type="email"
                      placeholder="m@example.com"
                      autocomplete="email"
                    />
                  </FieldHelp>
                  <span v-if="authStore.errors.email" class="text-destructive text-[12px]">
                    {{ authStore.errors.email[0] }}
                  </span>
                </Field>
                <Field>
                  <div class="flex items-center">
                    <FieldLabel for="password"> Password </FieldLabel>
                    <router-link
                      to="/forgot-password"
                      class="ml-auto inline-block text-sm underline-offset-4 hover:underline"
                    >
                      Forgot your password?
                    </router-link>
                  </div>
                  <FieldHelp description="Enter your account password.">
                    <Input
                      v-model="authStore.loginForm.password"
                      id="password"
                      type="password"
                      autocomplete="current-password"
                    />
                  </FieldHelp>
                  <span v-if="authStore.errors.password" class="text-destructive text-[12px]">
                    {{ authStore.errors.password[0] }}
                  </span>
                </Field>
                <Field>
                  <Button size="sm" type="submit" :disabled="authStore.loading">
                    {{ authStore.loading ? 'Logging in...' : 'Login' }}
                  </Button>
                  <FieldDescription v-if="registrationEnabled" class="text-center">
                    Don't have an account?
                    <router-link to="/register"> Sign up </router-link>
                  </FieldDescription>
                </Field>
              </FieldGroup>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
