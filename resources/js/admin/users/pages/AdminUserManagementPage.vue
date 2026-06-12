<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Checkbox } from '@/shared/components/ui/checkbox'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { formatDisplayDate } from '@/shared/utils/date'
import { useAdminUserStore } from '@/admin/users/admin-user-store'
import { useConfirmStore } from '@/shared/stores/confirm-store'
import type { AdminUser, AdminUserPayload, AdminUserTokenActivity } from '@/admin/users/types'
import { KeyRound, Pencil, Power, PowerOff, ShieldAlert, Trash2, UserPlus } from 'lucide-vue-next'
import { computed, onMounted, reactive, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const userStore = useAdminUserStore()
const confirmStore = useConfirmStore()
const dialogOpen = ref(false)
const detailsOpen = ref(false)
const resetDialogOpen = ref(false)
const editingUser = ref<AdminUser | null>(null)
const selectedUser = ref<AdminUser | null>(null)
const formError = ref('')

const columns = [
  { key: 'name', label: 'Admin User', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'role', label: 'Role' },
  { key: 'security', label: 'Security' },
  { key: 'is_active', label: 'Status', sortable: true },
  { key: 'last_login_at', label: 'Last Login' },
  { key: 'created_at', label: 'Created', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-[330px]', cellClass: 'text-right' },
] as const

const form = reactive<AdminUserPayload>({
  name: '',
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  is_active: true,
  email_verified: true,
  password: '',
  password_confirmation: '',
})

const page = computed(() => userStore.params.page ?? 1)
const pageSize = computed(() => userStore.params.pageSize ?? 15)
const search = computed(() => userStore.params.search ?? '')
const sortKey = computed(() => userStore.params.sort ?? '')
const sortDirection = computed(() => userStore.params.direction ?? '')
const statusFilter = computed({
  get: () => userStore.params.status ?? '',
  set: (value: 'active' | 'inactive' | '') => {
    userStore.index({ status: value, page: 1 })
  },
})

const resetForm = (): void => {
  form.name = ''
  form.first_name = ''
  form.last_name = ''
  form.email = ''
  form.phone = ''
  form.is_active = true
  form.email_verified = true
  form.password = ''
  form.password_confirmation = ''
  formError.value = ''
  userStore.errors = {}
}

const openCreateDialog = (): void => {
  editingUser.value = null
  resetForm()
  dialogOpen.value = true
}

const openEditDialog = (user: AdminUser): void => {
  editingUser.value = user
  form.name = user.name
  form.first_name = user.first_name ?? ''
  form.last_name = user.last_name ?? ''
  form.email = user.email
  form.phone = user.phone ?? ''
  form.is_active = user.is_active
  form.email_verified = user.email_verified
  form.password = ''
  form.password_confirmation = ''
  formError.value = ''
  userStore.errors = {}
  dialogOpen.value = true
}

const openDetails = (user: AdminUser): void => {
  selectedUser.value = user
  detailsOpen.value = true
}

const saveUser = async (): Promise<void> => {
  try {
    formError.value = ''
    await userStore.save({ ...form }, editingUser.value?.id)
    dialogOpen.value = false
  } catch {
    formError.value = 'Please check the admin user fields and try again.'
  }
}

const toggleStatus = async (user: AdminUser): Promise<void> => {
  const action = user.is_active ? 'deactivate' : 'reactivate'
  const confirmed = await confirmStore.confirm(
    `Are you sure you want to ${action} this admin user?`
  )

  if (!confirmed) return

  if (user.is_active) {
    await userStore.deactivate(user.id)
    return
  }

  await userStore.reactivate(user.id)
}

const generatePasswordReset = async (user: AdminUser): Promise<void> => {
  const confirmed = await confirmStore.confirm(
    "Generate a password reset token and revoke this admin user's active sessions?"
  )

  if (!confirmed) return

  selectedUser.value = user
  await userStore.generatePasswordReset(user.id)
  resetDialogOpen.value = true
}

const deleteUser = async (user: AdminUser): Promise<void> => {
  await userStore.destroy(user.id)
}

const updateSort = (key: string, direction: SortDirection): void => {
  userStore.index({
    sort: direction ? key : 'created_at',
    direction: direction || 'desc',
    page: 1,
  })
}

const warningBadgeVariant = (level: string): 'destructive' | 'warning' | 'info' => {
  if (level === 'critical') return 'destructive'
  if (level === 'warning') return 'warning'

  return 'info'
}

const activityDate = (activity: AdminUserTokenActivity): string => {
  return formatDisplayDate(activity.last_used_at ?? activity.created_at, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

onMounted(() => {
  userStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Admin Users</h2>
          <p class="module-container-description">
            Manage platform administrator access, sessions, reset workflow, and security posture.
          </p>
        </div>
      </div>

      <BaseTable
        :columns="columns"
        :data="userStore.users"
        :page="page"
        :page-size="pageSize"
        :total="userStore.total"
        :search="search"
        :loading="userStore.loading"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        with-search
        with-create
        with-details
        with-page-size
        create-label="Create Admin User"
        empty-title="No admin users found"
        empty-text="Create an administrator account to grant platform access."
        search-placeholder="Search admin users..."
        @create="openCreateDialog"
        @details="openDetails"
        @update:page="userStore.index({ page: $event })"
        @update:page-size="userStore.index({ pageSize: $event, page: 1 })"
        @update:search="userStore.index({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect
            v-field-help="'Filter admin users by active access status.'"
            v-model="statusFilter"
            class="h-9 min-w-36"
          >
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </NativeSelect>
        </template>

        <template #empty-icon>
          <UserPlus class="size-5" />
        </template>

        <template #cell-name="{ row }">
          <div>
            <div class="font-medium text-foreground">{{ row.name }}</div>
            <div class="text-xs text-muted-foreground">
              {{
                row.first_name || row.last_name
                  ? `${row.first_name ?? ''} ${row.last_name ?? ''}`.trim()
                  : 'No profile name'
              }}
            </div>
          </div>
        </template>

        <template #cell-role="{ row }">
          <div class="flex flex-wrap gap-1">
            <Badge v-for="role in row.roles" :key="role" variant="outline">{{ role }}</Badge>
          </div>
        </template>

        <template #cell-security="{ row }">
          <div class="flex flex-wrap gap-1">
            <Badge
              v-if="row.security_warnings.length"
              :variant="warningBadgeVariant(row.security_warnings[0].level)"
            >
              {{ row.security_warnings.length }} warnings
            </Badge>
            <Badge v-else variant="success">Clear</Badge>
            <Badge :variant="row.two_factor_enabled ? 'success' : 'warning'">2FA</Badge>
          </div>
        </template>

        <template #cell-is_active="{ row }">
          <Badge :variant="row.is_active ? 'success' : 'destructive'">
            {{ row.is_active ? 'Active' : 'Inactive' }}
          </Badge>
        </template>

        <template #cell-last_login_at="{ row }">
          <span class="text-sm text-muted-foreground">
            {{ formatDisplayDate(row.last_login_at) || 'No activity' }}
          </span>
        </template>

        <template #cell-created_at="{ row }">
          {{ formatDisplayDate(row.created_at) }}
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end gap-2">
            <Button size="xs" variant="edit" type="button" @click.stop="openEditDialog(row)">
              <Pencil class="size-3" />
              Edit
            </Button>
            <Button
              size="xs"
              variant="navigate"
              type="button"
              @click.stop="generatePasswordReset(row)"
            >
              <KeyRound class="size-3" />
              Reset
            </Button>
            <Button
              size="xs"
              :variant="row.is_active ? 'restore' : 'publish'"
              type="button"
              @click.stop="toggleStatus(row)"
            >
              <PowerOff v-if="row.is_active" class="size-3" />
              <Power v-else class="size-3" />
              {{ row.is_active ? 'Deactivate' : 'Reactivate' }}
            </Button>
            <Button size="xs" variant="delete" type="button" @click.stop="deleteUser(row)">
              <Trash2 class="size-3" />
              Delete
            </Button>
          </div>
        </template>
      </BaseTable>

      <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ editingUser ? 'Edit Admin User' : 'Create Admin User' }}</DialogTitle>
            <DialogDescription>
              Manage administrator profile, access status, and password credentials.
            </DialogDescription>
          </DialogHeader>

          <form class="space-y-4" @submit.prevent="saveUser">
            <FieldSet>
              <FieldGroup>
                <Field>
                  <FieldLabel for="admin-user-name">Name</FieldLabel>
                  <Input id="admin-user-name" v-model="form.name" placeholder="Onlyvo Admin" />
                  <FieldError v-if="userStore.errors.name">
                    {{ userStore.errors.name[0] }}
                  </FieldError>
                </Field>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="admin-user-first-name">First Name</FieldLabel>
                    <Input id="admin-user-first-name" v-model="form.first_name" />
                    <FieldError v-if="userStore.errors.first_name">
                      {{ userStore.errors.first_name[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="admin-user-last-name">Last Name</FieldLabel>
                    <Input id="admin-user-last-name" v-model="form.last_name" />
                    <FieldError v-if="userStore.errors.last_name">
                      {{ userStore.errors.last_name[0] }}
                    </FieldError>
                  </Field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="admin-user-email">Email</FieldLabel>
                    <Input
                      id="admin-user-email"
                      v-model="form.email"
                      type="email"
                      placeholder="admin@example.com"
                    />
                    <FieldError v-if="userStore.errors.email">
                      {{ userStore.errors.email[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="admin-user-phone">Phone</FieldLabel>
                    <Input id="admin-user-phone" v-model="form.phone" />
                    <FieldError v-if="userStore.errors.phone">
                      {{ userStore.errors.phone[0] }}
                    </FieldError>
                  </Field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field orientation="horizontal" class="items-center gap-3 rounded border p-3">
                    <Checkbox id="admin-user-active" v-model="form.is_active" />
                    <div>
                      <FieldLabel for="admin-user-active">Active</FieldLabel>
                      <p class="text-xs text-muted-foreground">Allow admin workspace access.</p>
                    </div>
                  </Field>

                  <Field orientation="horizontal" class="items-center gap-3 rounded border p-3">
                    <Checkbox id="admin-user-email-verified" v-model="form.email_verified" />
                    <div>
                      <FieldLabel for="admin-user-email-verified">Email Verified</FieldLabel>
                      <p class="text-xs text-muted-foreground">
                        Mark this admin email as verified.
                      </p>
                    </div>
                  </Field>
                </div>

                <div class="rounded border bg-muted/20 p-3">
                  <p class="text-sm font-semibold text-foreground">Role and Permissions</p>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Admin users currently receive the platform administrator role and the full admin
                    permission set.
                  </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="admin-user-password">
                      {{ editingUser ? 'New Password' : 'Password' }}
                    </FieldLabel>
                    <Input id="admin-user-password" v-model="form.password" type="password" />
                    <FieldError v-if="userStore.errors.password">
                      {{ userStore.errors.password[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="admin-user-password-confirmation">Confirm Password</FieldLabel>
                    <Input
                      id="admin-user-password-confirmation"
                      v-model="form.password_confirmation"
                      type="password"
                    />
                    <FieldError v-if="userStore.errors.password_confirmation">
                      {{ userStore.errors.password_confirmation[0] }}
                    </FieldError>
                  </Field>
                </div>
              </FieldGroup>
            </FieldSet>

            <p v-if="formError" class="text-sm font-medium text-destructive">{{ formError }}</p>

            <DialogFooter>
              <Button type="button" variant="cancel" @click="dialogOpen = false">Cancel</Button>
              <Button type="submit" variant="update" :disabled="userStore.loading">
                {{ userStore.loading ? 'Saving...' : 'Save Admin User' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogScrollContent>
      </Dialog>

      <Dialog :open="detailsOpen" @update:open="detailsOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-4xl">
          <DialogHeader>
            <DialogTitle>{{ selectedUser?.name ?? 'Admin User' }}</DialogTitle>
            <DialogDescription>
              Review access scope, security warnings, login history, and token activity.
            </DialogDescription>
          </DialogHeader>

          <div v-if="selectedUser" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
            <Card class="gap-3 py-4">
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Access Summary</CardTitle>
              </CardHeader>
              <CardContent class="space-y-4 px-4">
                <div class="grid gap-3 sm:grid-cols-2">
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Status</p>
                    <p class="mt-1 text-sm font-semibold">
                      {{ selectedUser.is_active ? 'Active' : 'Inactive' }}
                    </p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Active Sessions</p>
                    <p class="mt-1 text-sm font-semibold">{{ selectedUser.tokens_count ?? 0 }}</p>
                  </div>
                </div>

                <div>
                  <p class="text-xs font-semibold text-muted-foreground">Roles</p>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <Badge v-for="role in selectedUser.roles" :key="role" variant="outline">
                      {{ role }}
                    </Badge>
                  </div>
                </div>

                <div>
                  <p class="text-xs font-semibold text-muted-foreground">Permissions</p>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <Badge
                      v-for="permission in selectedUser.permissions"
                      :key="permission"
                      variant="secondary"
                    >
                      {{ permission }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card class="gap-3 py-4">
              <CardHeader class="px-4">
                <CardTitle class="flex items-center gap-2 text-sm">
                  <ShieldAlert class="size-4" />
                  Security Warnings
                </CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <Empty v-if="!selectedUser.security_warnings.length" class="min-h-40 border">
                  <EmptyHeader>
                    <EmptyTitle>No warnings</EmptyTitle>
                    <EmptyDescription>This admin user has no visible warnings.</EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <div v-else class="space-y-2">
                  <div
                    v-for="warning in selectedUser.security_warnings"
                    :key="warning.type"
                    class="rounded border p-3"
                  >
                    <Badge :variant="warningBadgeVariant(warning.level)">
                      {{ warning.level }}
                    </Badge>
                    <p class="mt-2 text-sm text-foreground">{{ warning.message }}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            <Card class="gap-3 py-4 lg:col-span-2">
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Login History and Access Logs</CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <Empty v-if="!selectedUser.login_history.length" class="min-h-40 border">
                  <EmptyHeader>
                    <EmptyTitle>No token activity</EmptyTitle>
                    <EmptyDescription>
                      Login history appears after this admin user signs in.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <div v-else class="overflow-x-auto rounded border">
                  <table class="w-full text-sm">
                    <thead class="bg-muted/60 text-left text-xs text-muted-foreground">
                      <tr>
                        <th class="p-3 font-medium">Session</th>
                        <th class="p-3 font-medium">Last Used</th>
                        <th class="p-3 font-medium">Created</th>
                        <th class="p-3 font-medium">Expires</th>
                        <th class="p-3 font-medium">Source</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr
                        v-for="activity in selectedUser.login_history"
                        :key="activity.id"
                        class="border-t"
                      >
                        <td class="p-3 font-medium">{{ activity.name }}</td>
                        <td class="p-3">{{ activityDate(activity) || 'Not used yet' }}</td>
                        <td class="p-3">{{ formatDisplayDate(activity.created_at) }}</td>
                        <td class="p-3">{{ formatDisplayDate(activity.expires_at) || 'None' }}</td>
                        <td class="p-3 text-muted-foreground">{{ activity.source }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </CardContent>
            </Card>
          </div>
        </DialogScrollContent>
      </Dialog>

      <Dialog :open="resetDialogOpen" @update:open="resetDialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-2xl">
          <DialogHeader>
            <DialogTitle>Password Reset Token</DialogTitle>
            <DialogDescription>
              This token is shown once. Active sessions for this admin user were revoked.
            </DialogDescription>
          </DialogHeader>

          <div v-if="userStore.reset" class="space-y-4">
            <div class="rounded border bg-muted/20 p-3">
              <p class="text-xs font-semibold text-muted-foreground">Admin Email</p>
              <p class="mt-1 text-sm">{{ userStore.reset.email }}</p>
            </div>

            <div class="rounded border bg-muted/20 p-3">
              <p class="text-xs font-semibold text-muted-foreground">Reset Token</p>
              <p class="mt-2 break-all font-mono text-xs">{{ userStore.reset.reset_token }}</p>
            </div>

            <p class="text-sm text-muted-foreground">
              Expires {{ formatDisplayDate(userStore.reset.expires_at) }}. Full email delivery and
              admin reset-screen consumption are still product follow-ups.
            </p>
          </div>

          <DialogFooter>
            <Button type="button" variant="cancel" @click="resetDialogOpen = false">Close</Button>
          </DialogFooter>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
