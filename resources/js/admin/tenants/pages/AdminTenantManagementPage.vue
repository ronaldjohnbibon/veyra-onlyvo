<script setup lang="ts">
import BaseTable from '@/shared/components/BaseTable.vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useAdminSystemSettingStore } from '@/admin/system-settings/system-setting-store'
import { useAdminTenantStore } from '@/admin/tenants/tenant-store'
import { useConfirmStore } from '@/shared/stores/confirm-store'
import type { TenantPayload, TenantRecord, TenantStatus } from '@/shared/types/tenants'
import { Pencil, Power, PowerOff, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, reactive, ref } from 'vue'

type SortDirection = 'asc' | 'desc' | ''

const tenantStore = useAdminTenantStore()
const systemSettingStore = useAdminSystemSettingStore()
const confirmStore = useConfirmStore()
const dialogOpen = ref(false)
const editingTenant = ref<TenantRecord | null>(null)
const settingsText = ref('{}')
const formError = ref('')

const columns = [
  { key: 'name', label: 'Tenant', sortable: true },
  { key: 'subdomain', label: 'Subdomain', sortable: true },
  { key: 'timezone', label: 'Timezone', sortable: true },
  { key: 'status', label: 'Status', sortable: true },
  { key: 'usage', label: 'Usage' },
  { key: 'created_at', label: 'Created', sortable: true },
  { key: 'actions', label: '', headerClass: 'w-[190px]', cellClass: 'text-right' },
] as const

const form = reactive<TenantPayload>({
  name: '',
  subdomain: '',
  timezone: 'UTC',
  status: 'active',
  settings: {},
  owner_name: '',
  owner_first_name: '',
  owner_last_name: '',
  owner_email: '',
  owner_phone: '',
  owner_password: '',
  owner_password_confirmation: '',
})

const page = computed(() => tenantStore.params.page ?? 1)
const pageSize = computed(() => tenantStore.params.pageSize ?? 15)
const search = computed(() => tenantStore.params.search ?? '')
const sortKey = computed(() => tenantStore.params.sort ?? '')
const sortDirection = computed(() => tenantStore.params.direction ?? '')
const statusFilter = computed({
  get: () => tenantStore.params.status ?? '',
  set: (value: TenantStatus | '') => {
    tenantStore.index({ status: value, page: 1 })
  },
})
const defaultTenantTimezone = computed(() =>
  String(systemSettingStore.values['tenant_defaults.default_tenant_timezone'] || 'UTC')
)
const defaultTenantStatus = computed(
  () =>
    String(
      systemSettingStore.values['tenant_defaults.default_tenant_status'] || 'active'
    ) as TenantStatus
)

const resetForm = (): void => {
  form.name = ''
  form.subdomain = ''
  form.timezone = defaultTenantTimezone.value
  form.status = defaultTenantStatus.value
  form.settings = {}
  form.owner_name = ''
  form.owner_first_name = ''
  form.owner_last_name = ''
  form.owner_email = ''
  form.owner_phone = ''
  form.owner_password = ''
  form.owner_password_confirmation = ''
  settingsText.value = '{}'
  formError.value = ''
  tenantStore.errors = {}
}

const openCreateDialog = (): void => {
  editingTenant.value = null
  resetForm()
  dialogOpen.value = true
}

const openEditDialog = (tenant: TenantRecord): void => {
  editingTenant.value = tenant
  form.name = tenant.name
  form.subdomain = tenant.subdomain
  form.timezone = tenant.timezone
  form.status = tenant.status
  form.settings = tenant.settings ?? {}
  form.owner_name = tenant.owner?.name ?? ''
  form.owner_first_name = tenant.owner?.first_name ?? ''
  form.owner_last_name = tenant.owner?.last_name ?? ''
  form.owner_email = tenant.owner?.email ?? ''
  form.owner_phone = tenant.owner?.phone ?? ''
  form.owner_password = ''
  form.owner_password_confirmation = ''
  settingsText.value = JSON.stringify(form.settings, null, 2)
  formError.value = ''
  tenantStore.errors = {}
  dialogOpen.value = true
}

const saveTenant = async (): Promise<void> => {
  try {
    formError.value = ''
    // Settings are stored as JSON so admins can keep tenant-specific metadata.
    form.settings = JSON.parse(settingsText.value || '{}')
    await tenantStore.save({ ...form }, editingTenant.value?.id)
    dialogOpen.value = false
  } catch (error) {
    formError.value =
      error instanceof SyntaxError ? 'Settings JSON is invalid.' : 'Unable to save tenant.'
  }
}

const toggleTenantStatus = async (tenant: TenantRecord): Promise<void> => {
  const nextAction = tenant.status === 'active' ? 'deactivate' : 'reactivate'
  const confirmed = await confirmStore.confirm(
    `Are you sure you want to ${nextAction} this tenant?`
  )

  if (!confirmed) return

  if (tenant.status === 'active') {
    await tenantStore.deactivate(tenant.id)
    return
  }

  await tenantStore.reactivate(tenant.id)
}

const deleteTenant = async (tenant: TenantRecord): Promise<void> => {
  await tenantStore.destroy(tenant.id)
}

const updateSort = (key: string, direction: SortDirection): void => {
  tenantStore.index({
    sort: direction ? key : 'created_at',
    direction: direction || 'desc',
    page: 1,
  })
}

onMounted(async () => {
  await Promise.all([tenantStore.index(), systemSettingStore.index()])
  resetForm()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Tenant Management</h2>
          <p class="module-container-description">
            Create, update, deactivate, reactivate, and delete platform tenants.
          </p>
        </div>
      </div>

      <BaseTable
        :columns="columns"
        :data="tenantStore.tenants"
        :page="page"
        :page-size="pageSize"
        :total="tenantStore.total"
        :search="search"
        :loading="tenantStore.loading"
        :sort-key="sortKey"
        :sort-direction="sortDirection"
        with-search
        with-create
        with-page-size
        create-label="Create Tenant"
        empty-text="No tenants found."
        search-placeholder="Search tenants..."
        @create="openCreateDialog"
        @update:page="tenantStore.index({ page: $event })"
        @update:page-size="tenantStore.index({ pageSize: $event, page: 1 })"
        @update:search="tenantStore.index({ search: $event, page: 1 })"
        @update:sort="updateSort"
      >
        <template #filters>
          <NativeSelect v-model="statusFilter" class="h-9 min-w-36">
            <option value="">All statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </NativeSelect>
        </template>

        <template #cell-name="{ row }">
          <div class="font-medium text-foreground">{{ row.name }}</div>
        </template>

        <template #cell-subdomain="{ row }">
          <span class="font-mono text-xs">{{ row.subdomain }}</span>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="getStatusBadgeVariant(row.status)">
            {{ getStatusLabel(row.status) }}
          </Badge>
        </template>

        <template #cell-usage="{ row }">
          <span class="text-sm text-muted-foreground">
            {{ row.users_count ?? 0 }} users, {{ row.templates_count ?? 0 }} templates
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
              :variant="row.status === 'active' ? 'restore' : 'publish'"
              type="button"
              @click.stop="toggleTenantStatus(row)"
            >
              <PowerOff v-if="row.status === 'active'" class="size-3" />
              <Power v-else class="size-3" />
              {{ row.status === 'active' ? 'Deactivate' : 'Reactivate' }}
            </Button>
            <Button size="xs" variant="delete" type="button" @click.stop="deleteTenant(row)">
              <Trash2 class="size-3" />
              Delete
            </Button>
          </div>
        </template>
      </BaseTable>

      <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
        <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-2xl">
          <DialogHeader>
            <DialogTitle>{{ editingTenant ? 'Update Tenant' : 'Create Tenant' }}</DialogTitle>
            <DialogDescription>
              Manage tenant identity, subdomain, timezone, status, and settings.
            </DialogDescription>
          </DialogHeader>

          <form class="space-y-4" @submit.prevent="saveTenant">
            <FieldSet>
              <FieldGroup>
                <Field>
                  <FieldLabel for="tenant-name">Name</FieldLabel>
                  <Input id="tenant-name" v-model="form.name" placeholder="Onlyvo Demo" />
                  <FieldError v-if="tenantStore.errors.name">
                    {{ tenantStore.errors.name[0] }}
                  </FieldError>
                </Field>

                <Field>
                  <FieldLabel for="tenant-subdomain">Subdomain</FieldLabel>
                  <Input id="tenant-subdomain" v-model="form.subdomain" placeholder="onlyvo" />
                  <FieldError v-if="tenantStore.errors.subdomain">
                    {{ tenantStore.errors.subdomain[0] }}
                  </FieldError>
                </Field>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="tenant-timezone">Timezone</FieldLabel>
                    <Input id="tenant-timezone" v-model="form.timezone" placeholder="UTC" />
                    <FieldError v-if="tenantStore.errors.timezone">
                      {{ tenantStore.errors.timezone[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="tenant-status">Status</FieldLabel>
                    <NativeSelect id="tenant-status" v-model="form.status" class="w-full">
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </NativeSelect>
                    <FieldError v-if="tenantStore.errors.status">
                      {{ tenantStore.errors.status[0] }}
                    </FieldError>
                  </Field>
                </div>

                <Field>
                  <FieldLabel for="tenant-settings">Settings JSON</FieldLabel>
                  <Textarea
                    id="tenant-settings"
                    v-model="settingsText"
                    class="min-h-36 font-mono text-xs"
                    spellcheck="false"
                  />
                  <FieldError v-if="tenantStore.errors.settings">
                    {{ tenantStore.errors.settings[0] }}
                  </FieldError>
                </Field>

                <div class="border-t pt-4">
                  <h3 class="text-sm font-semibold text-foreground">Tenant Owner</h3>
                  <p class="text-sm text-muted-foreground">
                    This user account is linked to tenant login access.
                  </p>
                </div>

                <Field>
                  <FieldLabel for="owner-name">Owner Name</FieldLabel>
                  <Input id="owner-name" v-model="form.owner_name" placeholder="Onlyvo Tenant" />
                  <FieldError v-if="tenantStore.errors.owner_name">
                    {{ tenantStore.errors.owner_name[0] }}
                  </FieldError>
                </Field>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="owner-first-name">First Name</FieldLabel>
                    <Input id="owner-first-name" v-model="form.owner_first_name" />
                    <FieldError v-if="tenantStore.errors.owner_first_name">
                      {{ tenantStore.errors.owner_first_name[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="owner-last-name">Last Name</FieldLabel>
                    <Input id="owner-last-name" v-model="form.owner_last_name" />
                    <FieldError v-if="tenantStore.errors.owner_last_name">
                      {{ tenantStore.errors.owner_last_name[0] }}
                    </FieldError>
                  </Field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="owner-email">Email</FieldLabel>
                    <Input
                      id="owner-email"
                      v-model="form.owner_email"
                      type="email"
                      placeholder="tenant@example.com"
                    />
                    <FieldError v-if="tenantStore.errors.owner_email">
                      {{ tenantStore.errors.owner_email[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="owner-phone">Phone</FieldLabel>
                    <Input id="owner-phone" v-model="form.owner_phone" placeholder="09123456789" />
                    <FieldError v-if="tenantStore.errors.owner_phone">
                      {{ tenantStore.errors.owner_phone[0] }}
                    </FieldError>
                  </Field>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                  <Field>
                    <FieldLabel for="owner-password">
                      {{ editingTenant ? 'New Password' : 'Password' }}
                    </FieldLabel>
                    <Input id="owner-password" v-model="form.owner_password" type="password" />
                    <FieldError v-if="tenantStore.errors.owner_password">
                      {{ tenantStore.errors.owner_password[0] }}
                    </FieldError>
                  </Field>

                  <Field>
                    <FieldLabel for="owner-password-confirmation">Confirm Password</FieldLabel>
                    <Input
                      id="owner-password-confirmation"
                      v-model="form.owner_password_confirmation"
                      type="password"
                    />
                    <FieldError v-if="tenantStore.errors.owner_password_confirmation">
                      {{ tenantStore.errors.owner_password_confirmation[0] }}
                    </FieldError>
                  </Field>
                </div>
              </FieldGroup>
            </FieldSet>

            <p v-if="formError" class="text-sm font-medium text-destructive">
              {{ formError }}
            </p>

            <DialogFooter>
              <Button type="button" variant="cancel" @click="dialogOpen = false">Cancel</Button>
              <Button type="submit" variant="update" :disabled="tenantStore.loading">
                {{ tenantStore.loading ? 'Saving...' : 'Save Tenant' }}
              </Button>
            </DialogFooter>
          </form>
        </DialogScrollContent>
      </Dialog>
    </div>
  </div>
</template>
