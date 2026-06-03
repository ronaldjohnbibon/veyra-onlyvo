<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
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

const route = useRoute()
const authStore = useAuthStore()

onMounted(() => {
  authStore.resetPasswordForm.token = String(route.query.token ?? '')
  authStore.resetPasswordForm.email = String(route.query.email ?? '')
})
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <div :class="cn('flex flex-col gap-6', props.class)">
        <Card>
          <CardHeader>
            <CardTitle>Choose a new password</CardTitle>
            <CardDescription> Enter and confirm your new password </CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="authStore.resetPassword">
              <FieldGroup>
                <Field v-if="authStore.message">
                  <p class="text-destructive text-sm">{{ authStore.message }}</p>
                </Field>
                <Field>
                  <FieldLabel for="email"> Email </FieldLabel>

                  <Input
                    v-field-help="'Use the email address connected to the reset link.'"
                    v-model="authStore.resetPasswordForm.email"
                    id="email"
                    type="email"
                    autocomplete="email"
                  />

                  <span v-if="authStore.errors.email" class="text-destructive text-[12px]">
                    {{ authStore.errors.email[0] }}
                  </span>
                </Field>
                <Field>
                  <FieldLabel for="password"> Password </FieldLabel>

                  <Input
                    v-field-help="'Enter the new password you want to use.'"
                    v-model="authStore.resetPasswordForm.password"
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
                    v-model="authStore.resetPasswordForm.password_confirmation"
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
                <Field>
                  <Button size="sm" type="submit" :disabled="authStore.loading">
                    {{ authStore.loading ? 'Resetting...' : 'Reset password' }}
                  </Button>
                  <FieldDescription class="text-center">
                    Back to
                    <router-link to="/login"> sign in </router-link>
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
