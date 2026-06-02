<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
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
</script>

<template>
  <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
    <div class="flex w-full max-w-sm flex-col gap-6">
      <a href="#" class="flex items-center gap-2 self-center font-medium">
        <div
          class="bg-primary text-primary-foreground flex size-6 items-center justify-center rounded"
        >
          <GalleryVerticalEnd class="size-4" />
        </div>
        Acme Inc.
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
                    v-model="authStore.registerForm.name"
                    id="company_name"
                    type="text"
                    autocomplete="organization"
                  />
                  <span v-if="authStore.errors.name" class="text-destructive text-[12px]">
                    {{ authStore.errors.name[0] }}
                  </span>
                </Field>
                <Field>
                  <Field class="grid grid-cols-2 gap-4">
                    <Field>
                      <FieldLabel for="password"> Password </FieldLabel>
                      <Input
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
                  <FieldDescription> Must be at least 8 characters long. </FieldDescription>
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
          By clicking continue, you agree to our <a href="#">Terms of Service</a> and
          <a href="#">Privacy Policy</a>.
        </FieldDescription>
      </div>
    </div>
  </div>
</template>
