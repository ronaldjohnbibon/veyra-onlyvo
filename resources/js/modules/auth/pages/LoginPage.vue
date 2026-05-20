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
                  <Input
                    v-model="authStore.loginForm.email"
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
                  <div class="flex items-center">
                    <FieldLabel for="password"> Password </FieldLabel>
                    <router-link
                      to="/forgot-password"
                      class="ml-auto inline-block text-sm underline-offset-4 hover:underline"
                    >
                      Forgot your password?
                    </router-link>
                  </div>
                  <Input
                    v-model="authStore.loginForm.password"
                    id="password"
                    type="password"
                    autocomplete="current-password"
                    required
                  />
                  <span v-if="authStore.errors.password" class="text-destructive text-[12px]">
                    {{ authStore.errors.password[0] }}
                  </span>
                </Field>
                <Field>
                  <Button size="sm" type="submit" :disabled="authStore.loading">
                    {{ authStore.loading ? 'Logging in...' : 'Login' }}
                  </Button>
                  <Button variant="outline" size="sm" type="button"> Login with Google </Button>
                  <FieldDescription class="text-center">
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
