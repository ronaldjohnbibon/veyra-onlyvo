<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Field, FieldDescription, FieldGroup, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { useAuthStore } from '../auth-store'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const authStore = useAuthStore()
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
                  <p class="text-sm">{{ authStore.message }}</p>
                </Field>
                <Field>
                  <FieldLabel for="email"> Email </FieldLabel>
                  <Input
                    v-model="authStore.forgotPasswordForm.email"
                    id="email"
                    type="email"
                    placeholder="m@example.com"
                    autocomplete="email"
                    required
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
      </div>
    </div>
  </div>
</template>
