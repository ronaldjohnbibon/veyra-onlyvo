<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Field, FieldError, FieldLabel } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { useAccountStore } from '@/tenant/account/account-store'
import {
  BadgeCheck,
  Building2,
  CreditCard,
  KeyRound,
  Mail,
  Save,
  Shield,
  User,
  UserPlus,
  Users,
} from 'lucide-vue-next'
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const accountStore = useAccountStore()
const route = useRoute()

const workspace = computed(() => accountStore.account?.workspace ?? null)
const user = computed(() => accountStore.account?.user ?? null)
const ownerMember = computed(() => workspace.value?.members.find((member) => member.is_owner) ?? null)
const isTeamManagement = computed(() => route.name === 'tenant.team-management')
const pageTitle = computed(() => (isTeamManagement.value ? 'Team Management' : 'Account/Profile'))
const pageDescription = computed(() =>
  isTeamManagement.value
    ? 'Review workspace ownership, team members, and access capabilities.'
    : 'Manage your sign-in profile, password, and workspace access.'
)
const profileComplete = computed(() => {
  return Boolean(
    accountStore.profileForm.name &&
      accountStore.profileForm.email &&
      accountStore.profileForm.first_name &&
      accountStore.profileForm.last_name
  )
})

const fieldError = (field: string): string | null => accountStore.errors[field]?.[0] ?? null

const updateProfile = async (): Promise<void> => {
  await accountStore.updateProfile()
}

const updatePassword = async (): Promise<void> => {
  await accountStore.updatePassword()
}

onMounted(accountStore.show)
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-16">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">{{ pageTitle }}</h2>
          <p class="module-container-description">
            {{ pageDescription }}
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <main class="space-y-4">
          <section class="rounded border bg-background">
            <div class="flex flex-col gap-3 border-b p-4 md:flex-row md:items-start md:justify-between">
              <div class="flex gap-3">
                <div class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary">
                  <User class="size-5" />
                </div>
                <div>
                  <h3 class="text-base font-semibold">Profile</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Keep your tenant account identity and contact details current.
                  </p>
                </div>
              </div>
              <Badge :variant="profileComplete ? 'success' : 'outline'">
                {{ profileComplete ? 'Complete' : 'Needs details' }}
              </Badge>
            </div>

            <form class="grid gap-4 p-4 md:grid-cols-2" @submit.prevent="updateProfile">
              <Field class="md:col-span-2">
                <FieldLabel for="account-name">Display name</FieldLabel>
                <Input
                  id="account-name"
                  v-model="accountStore.profileForm.name"
                  v-field-help="'This is the name shown in the tenant workspace.'"
                  autocomplete="name"
                />
                <FieldError v-if="fieldError('name')">{{ fieldError('name') }}</FieldError>
              </Field>

              <Field>
                <FieldLabel for="account-first-name">First name</FieldLabel>
                <Input
                  id="account-first-name"
                  v-model="accountStore.profileForm.first_name"
                  v-field-help="'Used to personalize workspace activity and support interactions.'"
                  autocomplete="given-name"
                />
                <FieldError v-if="fieldError('first_name')">{{ fieldError('first_name') }}</FieldError>
              </Field>

              <Field>
                <FieldLabel for="account-last-name">Last name</FieldLabel>
                <Input
                  id="account-last-name"
                  v-model="accountStore.profileForm.last_name"
                  v-field-help="'Used with your first name for a complete account profile.'"
                  autocomplete="family-name"
                />
                <FieldError v-if="fieldError('last_name')">{{ fieldError('last_name') }}</FieldError>
              </Field>

              <Field>
                <FieldLabel for="account-email">Email</FieldLabel>
                <Input
                  id="account-email"
                  v-model="accountStore.profileForm.email"
                  v-field-help="'This email is used for tenant sign-in and account contact.'"
                  type="email"
                  autocomplete="email"
                />
                <FieldError v-if="fieldError('email')">{{ fieldError('email') }}</FieldError>
              </Field>

              <Field>
                <FieldLabel for="account-phone">Phone</FieldLabel>
                <Input
                  id="account-phone"
                  v-model="accountStore.profileForm.phone"
                  v-field-help="'Optional phone number for account contact and support.'"
                  autocomplete="tel"
                />
                <FieldError v-if="fieldError('phone')">{{ fieldError('phone') }}</FieldError>
              </Field>

              <div class="md:col-span-2">
                <Button type="submit" variant="update" :disabled="accountStore.loading">
                  <Save class="size-4" />
                  {{ accountStore.loading ? 'Saving...' : 'Save Profile' }}
                </Button>
              </div>
            </form>
          </section>

          <section class="rounded border bg-background">
            <div class="flex gap-3 border-b p-4">
              <div class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary">
                <KeyRound class="size-5" />
              </div>
              <div>
                <h3 class="text-base font-semibold">Password</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  Change your password using your current password for confirmation.
                </p>
              </div>
            </div>

            <form class="grid gap-4 p-4 md:grid-cols-2" @submit.prevent="updatePassword">
              <Field class="md:col-span-2">
                <FieldLabel for="current-password">Current password</FieldLabel>
                <Input
                  id="current-password"
                  v-model="accountStore.passwordForm.current_password"
                  v-field-help="'Enter your current password before choosing a new one.'"
                  type="password"
                  autocomplete="current-password"
                />
                <FieldError v-if="fieldError('current_password')">
                  {{ fieldError('current_password') }}
                </FieldError>
              </Field>

              <Field>
                <FieldLabel for="new-password">New password</FieldLabel>
                <Input
                  id="new-password"
                  v-model="accountStore.passwordForm.password"
                  v-field-help="'Use a password that meets the platform password policy.'"
                  type="password"
                  autocomplete="new-password"
                />
                <FieldError v-if="fieldError('password')">{{ fieldError('password') }}</FieldError>
              </Field>

              <Field>
                <FieldLabel for="confirm-password">Confirm password</FieldLabel>
                <Input
                  id="confirm-password"
                  v-model="accountStore.passwordForm.password_confirmation"
                  v-field-help="'Enter the same new password again.'"
                  type="password"
                  autocomplete="new-password"
                />
                <FieldError v-if="fieldError('password_confirmation')">
                  {{ fieldError('password_confirmation') }}
                </FieldError>
              </Field>

              <div class="md:col-span-2">
                <Button type="submit" variant="update" :disabled="accountStore.loading">
                  <KeyRound class="size-4" />
                  {{ accountStore.loading ? 'Updating...' : 'Update Password' }}
                </Button>
              </div>
            </form>
          </section>

          <section class="rounded border bg-background">
            <div class="flex gap-3 border-b p-4">
              <div class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary">
                <Users class="size-5" />
              </div>
              <div>
                <h3 class="text-base font-semibold">Team</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  View the people currently attached to this tenant workspace.
                </p>
              </div>
            </div>

            <div class="p-4">
              <div v-if="!workspace?.members.length" class="rounded border bg-muted/30 p-5 text-sm text-muted-foreground">
                No workspace members were found.
              </div>

              <div v-else class="space-y-2">
                <div
                  v-for="member in workspace.members"
                  :key="member.id"
                  class="flex flex-col gap-3 rounded border p-3 md:flex-row md:items-center md:justify-between"
                >
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <p class="truncate text-sm font-semibold">{{ member.name }}</p>
                      <Badge v-if="member.is_owner" variant="success">Owner</Badge>
                      <Badge v-else variant="outline">{{ member.role }}</Badge>
                      <Badge :variant="member.is_active ? 'success' : 'outline'">
                        {{ member.is_active ? 'Active' : 'Inactive' }}
                      </Badge>
                    </div>
                    <p class="mt-1 truncate text-sm text-muted-foreground">{{ member.email }}</p>
                  </div>
                  <p class="text-xs text-muted-foreground">
                    {{ member.permissions.length ? member.permissions.join(', ') : 'No custom permissions' }}
                  </p>
                </div>
              </div>
            </div>
          </section>
        </main>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
          <Card>
            <CardHeader>
              <CardTitle class="text-base">Workspace</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded border bg-primary/5 text-primary">
                  <Building2 class="size-5" />
                </div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-semibold">
                    {{ workspace?.tenant?.name || 'Workspace' }}
                  </p>
                  <p class="truncate text-xs text-muted-foreground">
                    {{ workspace?.tenant?.subdomain || 'No subdomain' }}
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <div class="rounded border p-3">
                  <p class="text-xs text-muted-foreground">Status</p>
                  <p class="mt-1 text-sm font-semibold">
                    {{ workspace?.tenant?.status || 'Unknown' }}
                  </p>
                </div>
                <div class="rounded border p-3">
                  <p class="text-xs text-muted-foreground">Members</p>
                  <p class="mt-1 text-sm font-semibold">{{ workspace?.member_count ?? 0 }}</p>
                </div>
              </div>

              <div class="rounded border p-3">
                <p class="text-xs text-muted-foreground">Owner</p>
                <p class="mt-1 text-sm font-semibold">{{ ownerMember?.name || 'Not assigned' }}</p>
                <p class="mt-1 text-xs text-muted-foreground">{{ ownerMember?.email }}</p>
              </div>

              <div class="rounded border p-3">
                <p class="flex items-center gap-2 text-sm font-semibold">
                  <BadgeCheck class="size-4 text-primary" />
                  Your access
                </p>
                <p class="mt-2 text-sm text-muted-foreground">
                  {{ workspace?.current_user_owner ? 'You are the workspace owner.' : 'You are a workspace member.' }}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle class="text-base">What Exists Today</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-start gap-3 rounded border p-3">
                <User class="mt-0.5 size-4 text-primary" />
                <div>
                  <p class="text-sm font-semibold">Profile editing</p>
                  <p class="text-sm text-muted-foreground">Available now.</p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded border p-3">
                <KeyRound class="mt-0.5 size-4 text-primary" />
                <div>
                  <p class="text-sm font-semibold">Password change</p>
                  <p class="text-sm text-muted-foreground">Available now.</p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded border p-3">
                <UserPlus class="mt-0.5 size-4 text-muted-foreground" />
                <div>
                  <p class="text-sm font-semibold">Team invites</p>
                  <p class="text-sm text-muted-foreground">
                    Not implemented yet; no invite model or route exists.
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded border p-3">
                <Shield class="mt-0.5 size-4 text-muted-foreground" />
                <div>
                  <p class="text-sm font-semibold">Roles and permissions</p>
                  <p class="text-sm text-muted-foreground">
                    {{ workspace?.permissions_summary || 'No role system is configured yet.' }}
                  </p>
                </div>
              </div>
              <div class="flex items-start gap-3 rounded border p-3">
                <CreditCard class="mt-0.5 size-4 text-muted-foreground" />
                <div>
                  <p class="text-sm font-semibold">Billing and plan</p>
                  <p class="text-sm text-muted-foreground">
                    Not added because this app has no billing/payment feature to extend.
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <p v-if="accountStore.message" class="rounded border bg-muted/30 p-3 text-sm text-muted-foreground">
            {{ accountStore.message }}
          </p>
          <p v-if="user?.email" class="flex items-center gap-2 text-xs text-muted-foreground">
            <Mail class="size-3.5" />
            Signed in as {{ user.email }}
          </p>
        </aside>
      </div>
    </div>
  </div>
</template>
