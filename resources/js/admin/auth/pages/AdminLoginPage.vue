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
import { Field, FieldGroup, FieldLabel } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { useAdminAuthStore } from '@/admin/auth/auth-store'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const adminAuthStore = useAdminAuthStore()
</script>

<template>
  <div class="flex min-h-svh w-full items-center justify-center p-6 md:p-10">
    <div class="w-full max-w-sm">
      <div :class="cn('flex flex-col gap-6', props.class)">
        <Card>
          <CardHeader>
            <CardTitle>Admin login</CardTitle>
            <CardDescription>Enter your admin credentials to continue</CardDescription>
          </CardHeader>
          <CardContent>
            <form @submit.prevent="adminAuthStore.login">
              <FieldGroup>
                <Field v-if="adminAuthStore.message">
                  <p class="text-destructive text-sm">{{ adminAuthStore.message }}</p>
                </Field>
                <Field>
                  <FieldLabel for="admin-email">Email</FieldLabel>
                  <Input
                    v-model="adminAuthStore.loginForm.email"
                    id="admin-email"
                    type="email"
                    placeholder="admin@example.com"
                    autocomplete="email"
                  />
                  <span v-if="adminAuthStore.errors.email" class="text-destructive text-[12px]">
                    {{ adminAuthStore.errors.email[0] }}
                  </span>
                </Field>
                <Field>
                  <FieldLabel for="admin-password">Password</FieldLabel>
                  <Input
                    v-model="adminAuthStore.loginForm.password"
                    id="admin-password"
                    type="password"
                    autocomplete="current-password"
                  />
                  <span v-if="adminAuthStore.errors.password" class="text-destructive text-[12px]">
                    {{ adminAuthStore.errors.password[0] }}
                  </span>
                </Field>
                <Field>
                  <Button size="sm" type="submit" :disabled="adminAuthStore.loading">
                    {{ adminAuthStore.loading ? 'Logging in...' : 'Login' }}
                  </Button>
                </Field>
              </FieldGroup>
            </form>
          </CardContent>
        </Card>
      </div>
    </div>
  </div>
</template>
