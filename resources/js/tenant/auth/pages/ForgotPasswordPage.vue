<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { onMounted } from 'vue'
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
import AuthLegalLinks from '@/tenant/auth/components/AuthLegalLinks.vue'
import { useAuthStore } from '../auth-store'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const authStore = useAuthStore()

onMounted(() => {
  authStore.clearFeedback()
})
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <div :class="cn('flex flex-col gap-6', props.class)">
        <Card>
          <CardHeader>
            <CardTitle>Reset your password</CardTitle>
            <CardDescription> Enter your email and we'll send you a reset link </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="authStore.forgotPassword">
              <FieldGroup>
                <Field v-if="authStore.message">
                  <p
                    class="text-sm"
                    :class="{ 'text-destructive': authStore.messageType === 'error' }"
                  >
                    {{ authStore.message }}
                  </p>
                </Field>
                <Field>
                  <FieldLabel for="email"> Email </FieldLabel>

                  <Input
                    v-field-help="'Enter the account email that should receive the reset link.'"
                    v-model="authStore.forgotPasswordForm.email"
                    id="email"
                    type="email"
                    placeholder="m@example.com"
                    autocomplete="email"
                  />

                  <span v-if="authStore.errors.email" class="text-destructive text-[12px]">
                    {{ authStore.errors.email[0] }}
                  </span>
                </Field>
                <Field>
                  <Button size="sm" type="submit" :disabled="authStore.loading">
                    {{ authStore.loading ? 'Sending...' : 'Send reset link' }}
                  </Button>
                  <FieldDescription class="text-center">
                    Remembered it?
                    <router-link to="/login"> Sign in </router-link>
                  </FieldDescription>
                </Field>
              </FieldGroup>
            </form>
          </CardContent>
        </Card>
        <AuthLegalLinks />
      </div>
    </div>
  </div>
</template>
