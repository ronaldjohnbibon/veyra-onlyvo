<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { useAdminSystemSettingStore } from '@/admin/system-settings/system-setting-store'
import type { SystemSettingsPayload, SystemSettingsValues } from '@/shared/types/system-settings'
import {
  AppWindow,
  BarChart3,
  Building2,
  Database,
  FileText,
  Globe2,
  History,
  KeyRound,
  Mail,
  Save,
  Shield,
  Share2,
  ToggleLeft,
  Upload,
  Wrench,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, reactive, ref } from 'vue'

const settingStore = useAdminSystemSettingStore()
const formError = ref('')
const activeSection = ref('general')

interface SettingSection {
  key: keyof SystemSettingsPayload | 'history'
  label: string
  icon: Component
}

const sections: SettingSection[] = [
  { key: 'general', label: 'General', icon: AppWindow },
  { key: 'authentication', label: 'Authentication', icon: KeyRound },
  { key: 'security', label: 'Security', icon: Shield },
  { key: 'tenant_defaults', label: 'Tenant Defaults', icon: Building2 },
  { key: 'feature_flags', label: 'Feature Flags', icon: ToggleLeft },
  { key: 'email', label: 'Email', icon: Mail },
  { key: 'analytics', label: 'Analytics', icon: BarChart3 },
  { key: 'storage', label: 'Storage', icon: Database },
  { key: 'maintenance', label: 'Maintenance', icon: Wrench },
  { key: 'seo', label: 'Public SEO', icon: Globe2 },
  { key: 'compliance', label: 'Compliance', icon: FileText },
  { key: 'social', label: 'Social Links', icon: Share2 },
  { key: 'history', label: 'History', icon: History },
]

const featureFlagFields: Array<{
  key: keyof SystemSettingsPayload['feature_flags']
  label: string
}> = [
  { key: 'enable_templates_module', label: 'Enable Templates Module' },
  { key: 'enable_posts_module', label: 'Enable Posts Module' },
  { key: 'enable_analytics_module', label: 'Enable Analytics Module' },
  { key: 'enable_design_requests_module', label: 'Enable Design Requests Module' },
  { key: 'enable_cta_forms', label: 'Enable CTA Forms' },
  { key: 'enable_tracking_logs', label: 'Enable Tracking Logs' },
]

type ImageSettingKey = 'general.logo' | 'general.favicon' | 'seo.open_graph_image'

const imageUploadErrors = reactive<Record<ImageSettingKey, string>>({
  'general.logo': '',
  'general.favicon': '',
  'seo.open_graph_image': '',
})
const imageUploading = reactive<Record<ImageSettingKey, boolean>>({
  'general.logo': false,
  'general.favicon': false,
  'seo.open_graph_image': false,
})
const imagePreviewFailed = reactive<Record<ImageSettingKey, boolean>>({
  'general.logo': false,
  'general.favicon': false,
  'seo.open_graph_image': false,
})

const form = reactive<SystemSettingsPayload>({
  general: {
    application_name: 'Onlyvo',
    application_description: 'Onlyvo tenant platform',
    logo: '',
    favicon: '',
    support_email: '',
    support_phone: '',
    company_address: '',
  },
  authentication: {
    allow_tenant_registration: true,
    require_email_verification: false,
    default_trial_days: 14,
  },
  security: {
    session_lifetime_minutes: 120,
    login_rate_limit_attempts: 5,
    login_rate_limit_window_minutes: 1,
    require_strong_passwords: false,
    minimum_password_length: 8,
    enable_admin_two_factor: false,
    allowed_admin_ips: '',
  },
  tenant_defaults: {
    default_tenant_timezone: 'UTC',
    default_tenant_status: 'active',
    default_tenant_trial_days: 14,
    default_tenant_template_type: '',
    default_tenant_template_key: '',
  },
  feature_flags: {
    enable_templates_module: true,
    enable_posts_module: true,
    enable_analytics_module: true,
    enable_design_requests_module: true,
    enable_cta_forms: true,
    enable_tracking_logs: true,
  },
  email: {
    mail_driver: 'smtp',
    smtp_host: '',
    smtp_port: 587,
    smtp_username: '',
    smtp_password: '',
    sender_name: 'Onlyvo',
    sender_email: 'hello@example.com',
  },
  analytics: {
    enable_visitor_tracking: true,
    enable_cta_tracking: true,
  },
  storage: {
    maximum_upload_size: 4096,
    allowed_file_types: 'jpg,jpeg,png,webp,gif',
  },
  maintenance: {
    maintenance_mode: false,
    maintenance_message: 'The platform is temporarily unavailable for maintenance.',
    maintenance_start_time: '',
    maintenance_end_time: '',
    allow_admin_bypass: true,
    maintenance_affected_areas: '',
  },
  seo: {
    default_meta_title: 'Onlyvo',
    default_meta_description: 'Onlyvo tenant platform',
    open_graph_image: '',
    allow_search_engine_indexing: true,
    canonical_domain: '',
  },
  compliance: {
    privacy_policy_url: '',
    terms_of_service_url: '',
    cookie_notice_enabled: false,
    data_retention_days: 365,
  },
  social: {
    facebook_url: '',
    instagram_url: '',
    linkedin_url: '',
    twitter_url: '',
    youtube_url: '',
  },
})

const valueFor = <T extends string | number | boolean>(
  values: SystemSettingsValues,
  key: string,
  fallback: T
): T => {
  const value = values[key]

  if (value === null || value === undefined) return fallback

  return value as T
}

const hydrateForm = (): void => {
  const values = settingStore.values

  form.general.application_name = valueFor(values, 'general.application_name', 'Onlyvo')
  form.general.application_description = valueFor(
    values,
    'general.application_description',
    'Onlyvo tenant platform'
  )
  form.general.logo = valueFor(values, 'general.logo', '')
  form.general.favicon = valueFor(values, 'general.favicon', '')
  form.general.support_email = valueFor(values, 'general.support_email', '')
  form.general.support_phone = valueFor(values, 'general.support_phone', '')
  form.general.company_address = valueFor(values, 'general.company_address', '')

  form.authentication.allow_tenant_registration = valueFor(
    values,
    'authentication.allow_tenant_registration',
    true
  )
  form.authentication.require_email_verification = valueFor(
    values,
    'authentication.require_email_verification',
    false
  )
  form.authentication.default_trial_days = Number(
    valueFor(values, 'authentication.default_trial_days', 14)
  )

  form.security.session_lifetime_minutes = Number(
    valueFor(values, 'security.session_lifetime_minutes', 120)
  )
  form.security.login_rate_limit_attempts = Number(
    valueFor(values, 'security.login_rate_limit_attempts', 5)
  )
  form.security.login_rate_limit_window_minutes = Number(
    valueFor(values, 'security.login_rate_limit_window_minutes', 1)
  )
  form.security.require_strong_passwords = valueFor(
    values,
    'security.require_strong_passwords',
    false
  )
  form.security.minimum_password_length = Number(
    valueFor(values, 'security.minimum_password_length', 8)
  )
  form.security.enable_admin_two_factor = valueFor(
    values,
    'security.enable_admin_two_factor',
    false
  )
  form.security.allowed_admin_ips = valueFor(values, 'security.allowed_admin_ips', '')

  form.tenant_defaults.default_tenant_timezone = valueFor(
    values,
    'tenant_defaults.default_tenant_timezone',
    'UTC'
  )
  form.tenant_defaults.default_tenant_status = valueFor(
    values,
    'tenant_defaults.default_tenant_status',
    'active'
  )
  form.tenant_defaults.default_tenant_trial_days = Number(
    valueFor(values, 'tenant_defaults.default_tenant_trial_days', 14)
  )
  form.tenant_defaults.default_tenant_template_type = valueFor(
    values,
    'tenant_defaults.default_tenant_template_type',
    ''
  )
  form.tenant_defaults.default_tenant_template_key = valueFor(
    values,
    'tenant_defaults.default_tenant_template_key',
    ''
  )

  form.feature_flags.enable_templates_module = valueFor(
    values,
    'feature_flags.enable_templates_module',
    true
  )
  form.feature_flags.enable_posts_module = valueFor(
    values,
    'feature_flags.enable_posts_module',
    true
  )
  form.feature_flags.enable_analytics_module = valueFor(
    values,
    'feature_flags.enable_analytics_module',
    true
  )
  form.feature_flags.enable_design_requests_module = valueFor(
    values,
    'feature_flags.enable_design_requests_module',
    true
  )
  form.feature_flags.enable_cta_forms = valueFor(values, 'feature_flags.enable_cta_forms', true)
  form.feature_flags.enable_tracking_logs = valueFor(
    values,
    'feature_flags.enable_tracking_logs',
    true
  )

  form.email.mail_driver = valueFor(values, 'email.mail_driver', 'smtp')
  form.email.smtp_host = valueFor(values, 'email.smtp_host', '')
  form.email.smtp_port = Number(valueFor(values, 'email.smtp_port', 587))
  form.email.smtp_username = valueFor(values, 'email.smtp_username', '')
  form.email.smtp_password = valueFor(values, 'email.smtp_password', '')
  form.email.sender_name = valueFor(values, 'email.sender_name', 'Onlyvo')
  form.email.sender_email = valueFor(values, 'email.sender_email', 'hello@example.com')

  form.analytics.enable_visitor_tracking = valueFor(
    values,
    'analytics.enable_visitor_tracking',
    true
  )
  form.analytics.enable_cta_tracking = valueFor(values, 'analytics.enable_cta_tracking', true)

  form.storage.maximum_upload_size = Number(valueFor(values, 'storage.maximum_upload_size', 4096))
  form.storage.allowed_file_types = valueFor(
    values,
    'storage.allowed_file_types',
    'jpg,jpeg,png,webp,gif'
  )

  form.maintenance.maintenance_mode = valueFor(values, 'maintenance.maintenance_mode', false)
  form.maintenance.maintenance_message = valueFor(
    values,
    'maintenance.maintenance_message',
    'The platform is temporarily unavailable for maintenance.'
  )
  form.maintenance.maintenance_start_time = valueFor(
    values,
    'maintenance.maintenance_start_time',
    ''
  )
  form.maintenance.maintenance_end_time = valueFor(values, 'maintenance.maintenance_end_time', '')
  form.maintenance.allow_admin_bypass = valueFor(values, 'maintenance.allow_admin_bypass', true)
  form.maintenance.maintenance_affected_areas = valueFor(
    values,
    'maintenance.maintenance_affected_areas',
    ''
  )

  form.seo.default_meta_title = valueFor(values, 'seo.default_meta_title', 'Onlyvo')
  form.seo.default_meta_description = valueFor(
    values,
    'seo.default_meta_description',
    'Onlyvo tenant platform'
  )
  form.seo.open_graph_image = valueFor(values, 'seo.open_graph_image', '')
  form.seo.allow_search_engine_indexing = valueFor(values, 'seo.allow_search_engine_indexing', true)
  form.seo.canonical_domain = valueFor(values, 'seo.canonical_domain', '')

  form.compliance.privacy_policy_url = valueFor(values, 'compliance.privacy_policy_url', '')
  form.compliance.terms_of_service_url = valueFor(values, 'compliance.terms_of_service_url', '')
  form.compliance.cookie_notice_enabled = valueFor(
    values,
    'compliance.cookie_notice_enabled',
    false
  )
  form.compliance.data_retention_days = Number(
    valueFor(values, 'compliance.data_retention_days', 365)
  )

  form.social.facebook_url = valueFor(values, 'social.facebook_url', '')
  form.social.instagram_url = valueFor(values, 'social.instagram_url', '')
  form.social.linkedin_url = valueFor(values, 'social.linkedin_url', '')
  form.social.twitter_url = valueFor(values, 'social.twitter_url', '')
  form.social.youtube_url = valueFor(values, 'social.youtube_url', '')
}

const fieldError = (path: string): string | null => {
  return settingStore.errors[`settings.${path}`]?.[0] ?? null
}

const sectionErrorCount = (section: keyof SystemSettingsPayload | 'history'): number => {
  if (section === 'history') return 0

  const prefix = `settings.${section}.`

  return Object.keys(settingStore.errors).filter((key) => key.startsWith(prefix)).length
}

const activeSectionLabel = computed(() => {
  return sections.find((section) => section.key === activeSection.value)?.label ?? 'General'
})

const imageValue = (key: ImageSettingKey): string => {
  if (key === 'general.logo') return form.general.logo
  if (key === 'general.favicon') return form.general.favicon

  return form.seo.open_graph_image
}

const hasImagePreview = (key: ImageSettingKey): boolean => {
  return Boolean(imageValue(key)) && !imagePreviewFailed[key]
}

const setImageValue = (key: ImageSettingKey, value: string): void => {
  imagePreviewFailed[key] = false

  if (key === 'general.logo') {
    form.general.logo = value
    return
  }

  if (key === 'general.favicon') {
    form.general.favicon = value
    return
  }

  form.seo.open_graph_image = value
}

const clearImage = (key: ImageSettingKey): void => {
  imageUploadErrors[key] = ''
  imagePreviewFailed[key] = false
  setImageValue(key, '')
}

const markImagePreviewFailed = (key: ImageSettingKey): void => {
  imagePreviewFailed[key] = true
}

const uploadSettingImage = async (key: ImageSettingKey, event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  try {
    imageUploading[key] = true
    imageUploadErrors[key] = ''
    setImageValue(key, await settingStore.uploadImage(key, file))
  } catch {
    imageUploadErrors[key] =
      settingStore.errors.image?.[0] ||
      settingStore.errors.key?.[0] ||
      'Upload failed. Please choose a valid image.'
  } finally {
    imageUploading[key] = false
    input.value = ''
  }
}

const formatSettingValue = (value: string | number | boolean | null): string => {
  if (value === null || value === '') return 'Empty'
  if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled'

  return String(value)
}

const formatChangedAt = (value: string | null): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(new Date(value))
}

const saveSettings = async (): Promise<void> => {
  try {
    formError.value = ''
    await settingStore.update({
      ...form,
      authentication: {
        ...form.authentication,
        default_trial_days: Number(form.authentication.default_trial_days),
      },
      security: {
        ...form.security,
        session_lifetime_minutes: Number(form.security.session_lifetime_minutes),
        login_rate_limit_attempts: Number(form.security.login_rate_limit_attempts),
        login_rate_limit_window_minutes: Number(form.security.login_rate_limit_window_minutes),
        minimum_password_length: Number(form.security.minimum_password_length),
      },
      tenant_defaults: {
        ...form.tenant_defaults,
        default_tenant_trial_days: Number(form.tenant_defaults.default_tenant_trial_days),
      },
      email: {
        ...form.email,
        smtp_port: Number(form.email.smtp_port || 0),
      },
      storage: {
        ...form.storage,
        maximum_upload_size: Number(form.storage.maximum_upload_size),
      },
      compliance: {
        ...form.compliance,
        data_retention_days: Number(form.compliance.data_retention_days),
      },
    })
    hydrateForm()
  } catch {
    formError.value = 'Please check the highlighted settings and try again.'
  }
}

onMounted(async () => {
  await settingStore.index()
  hydrateForm()
})
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-24">
      <form class="space-y-4" @submit.prevent="saveSettings">
        <div
          class="sticky top-0 z-20 -mx-4 border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80"
        >
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-2xl font-semibold tracking-tight text-foreground">System Settings</h2>
              <p class="mt-1 text-sm text-muted-foreground">{{ activeSectionLabel }} controls</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span
                class="rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  form.maintenance.maintenance_mode
                    ? 'border-amber-300 bg-amber-50 text-amber-800'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-800'
                "
              >
                Maintenance {{ form.maintenance.maintenance_mode ? 'On' : 'Off' }}
              </span>
              <span
                class="rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  form.authentication.allow_tenant_registration
                    ? 'border-sky-200 bg-sky-50 text-sky-800'
                    : 'border-muted bg-muted/50 text-muted-foreground'
                "
              >
                Registration {{ form.authentication.allow_tenant_registration ? 'Open' : 'Closed' }}
              </span>
              <Button variant="update" size="sm" type="submit" :disabled="settingStore.loading">
                <Save class="size-4" />
                {{ settingStore.loading ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[17rem_minmax(0,1fr)]">
          <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded border bg-background p-2">
              <button
                v-for="section in sections"
                :key="section.key"
                type="button"
                class="flex h-11 w-full items-center justify-between rounded px-3 text-left text-sm font-medium transition hover:bg-muted"
                :class="{
                  'bg-primary text-primary-foreground shadow-sm hover:bg-primary':
                    activeSection === section.key,
                  'text-muted-foreground': activeSection !== section.key,
                }"
                @click="activeSection = section.key"
              >
                <span class="flex min-w-0 items-center gap-2">
                  <component :is="section.icon" class="size-4 shrink-0" />
                  <span class="truncate">{{ section.label }}</span>
                </span>
                <span
                  v-if="sectionErrorCount(section.key)"
                  class="ml-2 rounded bg-destructive px-1.5 py-0.5 text-[11px] text-destructive-foreground"
                >
                  {{ sectionErrorCount(section.key) }}
                </span>
              </button>
            </div>
          </aside>

          <main class="rounded border bg-background">
            <div class="border-b px-4 py-3">
              <div class="flex items-center gap-2">
                <component
                  :is="sections.find((section) => section.key === activeSection)?.icon"
                  class="size-4 text-primary"
                />
                <h3 class="text-base font-semibold">{{ activeSectionLabel }}</h3>
              </div>
            </div>

            <FieldSet class="p-4">
              <section v-if="activeSection === 'general'">
                <FieldGroup>
                  <div class="grid gap-4 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]">
                    <div class="space-y-4">
                      <Field>
                        <FieldLabel for="setting-application-name">Application Name</FieldLabel>
                        <Input
                          id="setting-application-name"
                          v-model="form.general.application_name"
                        />
                        <FieldError v-if="fieldError('general.application_name')">
                          {{ fieldError('general.application_name') }}
                        </FieldError>
                      </Field>

                      <Field>
                        <FieldLabel for="setting-application-description">
                          Application Description
                        </FieldLabel>
                        <Textarea
                          id="setting-application-description"
                          v-model="form.general.application_description"
                          class="min-h-28"
                        />
                        <FieldError v-if="fieldError('general.application_description')">
                          {{ fieldError('general.application_description') }}
                        </FieldError>
                      </Field>

                      <Field>
                        <FieldLabel for="setting-company-address">Company Address</FieldLabel>
                        <Textarea
                          id="setting-company-address"
                          v-model="form.general.company_address"
                          class="min-h-24"
                        />
                        <FieldError v-if="fieldError('general.company_address')">
                          {{ fieldError('general.company_address') }}
                        </FieldError>
                      </Field>
                    </div>

                    <div class="space-y-4">
                      <Field>
                        <FieldLabel for="setting-logo">Logo</FieldLabel>
                        <div class="space-y-3 rounded border bg-muted/20 p-3">
                          <img
                            v-if="hasImagePreview('general.logo')"
                            :src="imageValue('general.logo')"
                            alt="Logo preview"
                            class="max-h-28 max-w-full rounded border bg-background object-contain p-2"
                            @error="markImagePreviewFailed('general.logo')"
                          />
                          <div
                            v-else
                            class="flex h-24 items-center justify-center rounded border border-dashed bg-background text-sm text-muted-foreground"
                          >
                            No logo uploaded
                          </div>

                          <div class="flex flex-wrap items-center gap-2">
                            <Button
                              as-child
                              variant="outline_default"
                              size="sm"
                              :disabled="imageUploading['general.logo']"
                            >
                              <label for="setting-logo" class="cursor-pointer">
                                <Upload class="size-4" />
                                {{ imageUploading['general.logo'] ? 'Uploading...' : 'Upload' }}
                              </label>
                            </Button>
                            <Button
                              v-if="imageValue('general.logo')"
                              type="button"
                              variant="cancel"
                              size="sm"
                              @click="clearImage('general.logo')"
                            >
                              <X class="size-4" />
                              Clear
                            </Button>
                          </div>
                          <Input
                            id="setting-logo"
                            type="file"
                            accept="image/*,.ico,.svg"
                            class="sr-only"
                            @change="uploadSettingImage('general.logo', $event)"
                          />
                        </div>
                        <FieldError v-if="imageUploadErrors['general.logo']">
                          {{ imageUploadErrors['general.logo'] }}
                        </FieldError>
                        <FieldError v-if="fieldError('general.logo')">
                          {{ fieldError('general.logo') }}
                        </FieldError>
                      </Field>

                      <Field>
                        <FieldLabel for="setting-favicon">Favicon</FieldLabel>
                        <div class="space-y-3 rounded border bg-muted/20 p-3">
                          <img
                            v-if="hasImagePreview('general.favicon')"
                            :src="imageValue('general.favicon')"
                            alt="Favicon preview"
                            class="size-16 rounded border bg-background object-contain p-2"
                            @error="markImagePreviewFailed('general.favicon')"
                          />
                          <div
                            v-else
                            class="flex h-20 items-center justify-center rounded border border-dashed bg-background text-sm text-muted-foreground"
                          >
                            No favicon uploaded
                          </div>

                          <div class="flex flex-wrap items-center gap-2">
                            <Button
                              as-child
                              variant="outline_default"
                              size="sm"
                              :disabled="imageUploading['general.favicon']"
                            >
                              <label for="setting-favicon" class="cursor-pointer">
                                <Upload class="size-4" />
                                {{ imageUploading['general.favicon'] ? 'Uploading...' : 'Upload' }}
                              </label>
                            </Button>
                            <Button
                              v-if="imageValue('general.favicon')"
                              type="button"
                              variant="cancel"
                              size="sm"
                              @click="clearImage('general.favicon')"
                            >
                              <X class="size-4" />
                              Clear
                            </Button>
                          </div>
                          <Input
                            id="setting-favicon"
                            type="file"
                            accept="image/*,.ico,.svg"
                            class="sr-only"
                            @change="uploadSettingImage('general.favicon', $event)"
                          />
                        </div>
                        <FieldError v-if="imageUploadErrors['general.favicon']">
                          {{ imageUploadErrors['general.favicon'] }}
                        </FieldError>
                        <FieldError v-if="fieldError('general.favicon')">
                          {{ fieldError('general.favicon') }}
                        </FieldError>
                      </Field>

                      <Field>
                        <FieldLabel for="setting-support-email">Support Email</FieldLabel>
                        <Input
                          id="setting-support-email"
                          v-model="form.general.support_email"
                          type="email"
                        />
                        <FieldError v-if="fieldError('general.support_email')">
                          {{ fieldError('general.support_email') }}
                        </FieldError>
                      </Field>

                      <Field>
                        <FieldLabel for="setting-support-phone">Support Phone</FieldLabel>
                        <Input id="setting-support-phone" v-model="form.general.support_phone" />
                        <FieldError v-if="fieldError('general.support_phone')">
                          {{ fieldError('general.support_phone') }}
                        </FieldError>
                      </Field>
                    </div>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'authentication'">
                <FieldGroup>
                  <div class="grid gap-3 xl:grid-cols-2">
                    <label
                      for="setting-allow-registration"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Allow Tenant Registration</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Public tenant signup
                        </span>
                      </span>
                      <Checkbox
                        id="setting-allow-registration"
                        v-model="form.authentication.allow_tenant_registration"
                      />
                    </label>

                    <label
                      for="setting-require-email-verification"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Require Email Verification</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          New tenant owner email check
                        </span>
                      </span>
                      <Checkbox
                        id="setting-require-email-verification"
                        v-model="form.authentication.require_email_verification"
                      />
                    </label>
                  </div>

                  <Field>
                    <FieldLabel for="setting-default-trial-days">Default Trial Days</FieldLabel>
                    <Input
                      id="setting-default-trial-days"
                      :model-value="form.authentication.default_trial_days"
                      type="number"
                      min="0"
                      @update:model-value="
                        form.authentication.default_trial_days = Number($event || 0)
                      "
                    />
                    <FieldError v-if="fieldError('authentication.default_trial_days')">
                      {{ fieldError('authentication.default_trial_days') }}
                    </FieldError>
                  </Field>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'security'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-3">
                    <Field>
                      <FieldLabel for="setting-session-lifetime">
                        Session Lifetime Minutes
                      </FieldLabel>
                      <Input
                        id="setting-session-lifetime"
                        :model-value="form.security.session_lifetime_minutes"
                        type="number"
                        min="5"
                        @update:model-value="
                          form.security.session_lifetime_minutes = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('security.session_lifetime_minutes')">
                        {{ fieldError('security.session_lifetime_minutes') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-login-rate-attempts">
                        Login Rate Limit Attempts
                      </FieldLabel>
                      <Input
                        id="setting-login-rate-attempts"
                        :model-value="form.security.login_rate_limit_attempts"
                        type="number"
                        min="1"
                        @update:model-value="
                          form.security.login_rate_limit_attempts = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('security.login_rate_limit_attempts')">
                        {{ fieldError('security.login_rate_limit_attempts') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-login-rate-window">
                        Login Rate Limit Window Minutes
                      </FieldLabel>
                      <Input
                        id="setting-login-rate-window"
                        :model-value="form.security.login_rate_limit_window_minutes"
                        type="number"
                        min="1"
                        @update:model-value="
                          form.security.login_rate_limit_window_minutes = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('security.login_rate_limit_window_minutes')">
                        {{ fieldError('security.login_rate_limit_window_minutes') }}
                      </FieldError>
                    </Field>
                  </div>

                  <div class="grid gap-3 xl:grid-cols-2">
                    <label
                      for="setting-require-strong-passwords"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Require Strong Passwords</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Upper, lower, number, symbol
                        </span>
                      </span>
                      <Checkbox
                        id="setting-require-strong-passwords"
                        v-model="form.security.require_strong_passwords"
                      />
                    </label>

                    <label
                      for="setting-admin-two-factor"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">
                          Enable Admin Two-Factor Authentication
                        </span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Stored for admin auth policy
                        </span>
                      </span>
                      <Checkbox
                        id="setting-admin-two-factor"
                        v-model="form.security.enable_admin_two_factor"
                      />
                    </label>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <Field>
                      <FieldLabel for="setting-min-password-length">
                        Minimum Password Length
                      </FieldLabel>
                      <Input
                        id="setting-min-password-length"
                        :model-value="form.security.minimum_password_length"
                        type="number"
                        min="8"
                        @update:model-value="
                          form.security.minimum_password_length = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('security.minimum_password_length')">
                        {{ fieldError('security.minimum_password_length') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-allowed-admin-ips">Allowed Admin IPs</FieldLabel>
                      <Textarea
                        id="setting-allowed-admin-ips"
                        v-model="form.security.allowed_admin_ips"
                        class="min-h-24"
                        placeholder="203.0.113.10, 198.51.100.0/24"
                      />
                      <FieldDescription>Comma or line separated</FieldDescription>
                      <FieldError v-if="fieldError('security.allowed_admin_ips')">
                        {{ fieldError('security.allowed_admin_ips') }}
                      </FieldError>
                    </Field>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'tenant_defaults'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-3">
                    <Field>
                      <FieldLabel for="setting-default-tenant-timezone">
                        Default Tenant Timezone
                      </FieldLabel>
                      <Input
                        id="setting-default-tenant-timezone"
                        v-model="form.tenant_defaults.default_tenant_timezone"
                        placeholder="UTC"
                      />
                      <FieldError v-if="fieldError('tenant_defaults.default_tenant_timezone')">
                        {{ fieldError('tenant_defaults.default_tenant_timezone') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-default-tenant-status">
                        Default Tenant Status
                      </FieldLabel>
                      <NativeSelect
                        id="setting-default-tenant-status"
                        v-model="form.tenant_defaults.default_tenant_status"
                      >
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                      </NativeSelect>
                      <FieldError v-if="fieldError('tenant_defaults.default_tenant_status')">
                        {{ fieldError('tenant_defaults.default_tenant_status') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-default-tenant-trial-days">
                        Default Tenant Trial Days
                      </FieldLabel>
                      <Input
                        id="setting-default-tenant-trial-days"
                        :model-value="form.tenant_defaults.default_tenant_trial_days"
                        type="number"
                        min="0"
                        @update:model-value="
                          form.tenant_defaults.default_tenant_trial_days = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('tenant_defaults.default_tenant_trial_days')">
                        {{ fieldError('tenant_defaults.default_tenant_trial_days') }}
                      </FieldError>
                    </Field>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-default-template-type">
                        Default Tenant Template Type
                      </FieldLabel>
                      <Input
                        id="setting-default-template-type"
                        v-model="form.tenant_defaults.default_tenant_template_type"
                        placeholder="business-website"
                      />
                      <FieldError v-if="fieldError('tenant_defaults.default_tenant_template_type')">
                        {{ fieldError('tenant_defaults.default_tenant_template_type') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-default-template-key">
                        Default Tenant Template Key
                      </FieldLabel>
                      <Input
                        id="setting-default-template-key"
                        v-model="form.tenant_defaults.default_tenant_template_key"
                        placeholder="template-1"
                      />
                      <FieldError v-if="fieldError('tenant_defaults.default_tenant_template_key')">
                        {{ fieldError('tenant_defaults.default_tenant_template_key') }}
                      </FieldError>
                    </Field>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'feature_flags'">
                <FieldGroup>
                  <div class="grid gap-3 xl:grid-cols-2">
                    <label
                      v-for="flag in featureFlagFields"
                      :key="flag.key"
                      :for="`setting-${flag.key}`"
                      class="flex min-h-16 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span class="block text-sm font-semibold">{{ flag.label }}</span>
                      <Checkbox
                        :id="`setting-${flag.key}`"
                        v-model="form.feature_flags[flag.key]"
                      />
                    </label>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'email'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-3">
                    <Field>
                      <FieldLabel for="setting-mail-driver">Mail Driver</FieldLabel>
                      <NativeSelect id="setting-mail-driver" v-model="form.email.mail_driver">
                        <option value="smtp">SMTP</option>
                        <option value="sendmail">Sendmail</option>
                        <option value="mailgun">Mailgun</option>
                        <option value="ses">SES</option>
                        <option value="ses-v2">SES v2</option>
                        <option value="postmark">Postmark</option>
                        <option value="log">Log</option>
                        <option value="array">Array</option>
                      </NativeSelect>
                      <FieldError v-if="fieldError('email.mail_driver')">
                        {{ fieldError('email.mail_driver') }}
                      </FieldError>
                    </Field>

                    <Field class="lg:col-span-2">
                      <FieldLabel for="setting-smtp-host">SMTP Host</FieldLabel>
                      <Input id="setting-smtp-host" v-model="form.email.smtp_host" />
                      <FieldError v-if="fieldError('email.smtp_host')">
                        {{ fieldError('email.smtp_host') }}
                      </FieldError>
                    </Field>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-3">
                    <Field>
                      <FieldLabel for="setting-smtp-port">SMTP Port</FieldLabel>
                      <Input
                        id="setting-smtp-port"
                        :model-value="form.email.smtp_port"
                        type="number"
                        min="1"
                        @update:model-value="form.email.smtp_port = Number($event || 0)"
                      />
                      <FieldError v-if="fieldError('email.smtp_port')">
                        {{ fieldError('email.smtp_port') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-smtp-username">SMTP Username</FieldLabel>
                      <Input id="setting-smtp-username" v-model="form.email.smtp_username" />
                      <FieldError v-if="fieldError('email.smtp_username')">
                        {{ fieldError('email.smtp_username') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-smtp-password">SMTP Password</FieldLabel>
                      <Input
                        id="setting-smtp-password"
                        v-model="form.email.smtp_password"
                        type="password"
                      />
                      <FieldError v-if="fieldError('email.smtp_password')">
                        {{ fieldError('email.smtp_password') }}
                      </FieldError>
                    </Field>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-sender-name">Sender Name</FieldLabel>
                      <Input id="setting-sender-name" v-model="form.email.sender_name" />
                      <FieldError v-if="fieldError('email.sender_name')">
                        {{ fieldError('email.sender_name') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-sender-email">Sender Email</FieldLabel>
                      <Input
                        id="setting-sender-email"
                        v-model="form.email.sender_email"
                        type="email"
                      />
                      <FieldError v-if="fieldError('email.sender_email')">
                        {{ fieldError('email.sender_email') }}
                      </FieldError>
                    </Field>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'analytics'">
                <FieldGroup>
                  <div class="grid gap-3 xl:grid-cols-2">
                    <label
                      for="setting-enable-visitor-tracking"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Enable Visitor Tracking</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Public page visits
                        </span>
                      </span>
                      <Checkbox
                        id="setting-enable-visitor-tracking"
                        v-model="form.analytics.enable_visitor_tracking"
                      />
                    </label>

                    <label
                      for="setting-enable-cta-tracking"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Enable CTA Tracking</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Click and view events
                        </span>
                      </span>
                      <Checkbox
                        id="setting-enable-cta-tracking"
                        v-model="form.analytics.enable_cta_tracking"
                      />
                    </label>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'storage'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <Field>
                      <FieldLabel for="setting-maximum-upload-size">Maximum Upload Size</FieldLabel>
                      <Input
                        id="setting-maximum-upload-size"
                        :model-value="form.storage.maximum_upload_size"
                        type="number"
                        min="1"
                        @update:model-value="form.storage.maximum_upload_size = Number($event || 0)"
                      />
                      <FieldDescription>KB</FieldDescription>
                      <FieldError v-if="fieldError('storage.maximum_upload_size')">
                        {{ fieldError('storage.maximum_upload_size') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-allowed-file-types">Allowed File Types</FieldLabel>
                      <Input
                        id="setting-allowed-file-types"
                        v-model="form.storage.allowed_file_types"
                        placeholder="jpg,jpeg,png,webp,gif"
                      />
                      <FieldDescription>Comma-separated extensions</FieldDescription>
                      <FieldError v-if="fieldError('storage.allowed_file_types')">
                        {{ fieldError('storage.allowed_file_types') }}
                      </FieldError>
                    </Field>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'maintenance'">
                <FieldGroup>
                  <label
                    for="setting-maintenance-mode"
                    class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                  >
                    <span>
                      <span class="block text-sm font-semibold">Maintenance Mode</span>
                      <span class="mt-1 block text-xs text-muted-foreground">
                        Non-admin platform access
                      </span>
                    </span>
                    <Checkbox
                      id="setting-maintenance-mode"
                      v-model="form.maintenance.maintenance_mode"
                    />
                  </label>

                  <Field>
                    <FieldLabel for="setting-maintenance-message">Maintenance Message</FieldLabel>
                    <Textarea
                      id="setting-maintenance-message"
                      v-model="form.maintenance.maintenance_message"
                      class="min-h-32"
                    />
                    <FieldError v-if="fieldError('maintenance.maintenance_message')">
                      {{ fieldError('maintenance.maintenance_message') }}
                    </FieldError>
                  </Field>

                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-maintenance-start">
                        Maintenance Start Time
                      </FieldLabel>
                      <Input
                        id="setting-maintenance-start"
                        v-model="form.maintenance.maintenance_start_time"
                        type="datetime-local"
                      />
                      <FieldError v-if="fieldError('maintenance.maintenance_start_time')">
                        {{ fieldError('maintenance.maintenance_start_time') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-maintenance-end">Maintenance End Time</FieldLabel>
                      <Input
                        id="setting-maintenance-end"
                        v-model="form.maintenance.maintenance_end_time"
                        type="datetime-local"
                      />
                      <FieldError v-if="fieldError('maintenance.maintenance_end_time')">
                        {{ fieldError('maintenance.maintenance_end_time') }}
                      </FieldError>
                    </Field>
                  </div>

                  <label
                    for="setting-allow-admin-bypass"
                    class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                  >
                    <span>
                      <span class="block text-sm font-semibold">Allow Admin Bypass</span>
                      <span class="mt-1 block text-xs text-muted-foreground">
                        Admin routes stay available
                      </span>
                    </span>
                    <Checkbox
                      id="setting-allow-admin-bypass"
                      v-model="form.maintenance.allow_admin_bypass"
                    />
                  </label>

                  <Field>
                    <FieldLabel for="setting-maintenance-areas">
                      Maintenance Affected Areas
                    </FieldLabel>
                    <Textarea
                      id="setting-maintenance-areas"
                      v-model="form.maintenance.maintenance_affected_areas"
                      class="min-h-24"
                      placeholder="/, /templates, /posts"
                    />
                    <FieldDescription>Blank applies maintenance to all areas</FieldDescription>
                    <FieldError v-if="fieldError('maintenance.maintenance_affected_areas')">
                      {{ fieldError('maintenance.maintenance_affected_areas') }}
                    </FieldError>
                  </Field>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'seo'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-default-meta-title">Default Meta Title</FieldLabel>
                      <Input
                        id="setting-default-meta-title"
                        v-model="form.seo.default_meta_title"
                      />
                      <FieldError v-if="fieldError('seo.default_meta_title')">
                        {{ fieldError('seo.default_meta_title') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-canonical-domain">Canonical Domain</FieldLabel>
                      <Input
                        id="setting-canonical-domain"
                        v-model="form.seo.canonical_domain"
                        placeholder="https://example.com"
                      />
                      <FieldError v-if="fieldError('seo.canonical_domain')">
                        {{ fieldError('seo.canonical_domain') }}
                      </FieldError>
                    </Field>
                  </div>

                  <Field>
                    <FieldLabel for="setting-default-meta-description">
                      Default Meta Description
                    </FieldLabel>
                    <Textarea
                      id="setting-default-meta-description"
                      v-model="form.seo.default_meta_description"
                      class="min-h-28"
                    />
                    <FieldError v-if="fieldError('seo.default_meta_description')">
                      {{ fieldError('seo.default_meta_description') }}
                    </FieldError>
                  </Field>

                  <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_16rem]">
                    <Field>
                      <FieldLabel for="setting-open-graph-image">Open Graph Image</FieldLabel>
                      <div class="space-y-3 rounded border bg-muted/20 p-3">
                        <img
                          v-if="hasImagePreview('seo.open_graph_image')"
                          :src="imageValue('seo.open_graph_image')"
                          alt="Open Graph preview"
                          class="aspect-video max-h-48 w-full rounded border bg-background object-cover"
                          @error="markImagePreviewFailed('seo.open_graph_image')"
                        />
                        <div
                          v-else
                          class="flex aspect-video max-h-48 items-center justify-center rounded border border-dashed bg-background text-sm text-muted-foreground"
                        >
                          No Open Graph image uploaded
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                          <Button
                            as-child
                            variant="outline_default"
                            size="sm"
                            :disabled="imageUploading['seo.open_graph_image']"
                          >
                            <label for="setting-open-graph-image" class="cursor-pointer">
                              <Upload class="size-4" />
                              {{
                                imageUploading['seo.open_graph_image'] ? 'Uploading...' : 'Upload'
                              }}
                            </label>
                          </Button>
                          <Button
                            v-if="imageValue('seo.open_graph_image')"
                            type="button"
                            variant="cancel"
                            size="sm"
                            @click="clearImage('seo.open_graph_image')"
                          >
                            <X class="size-4" />
                            Clear
                          </Button>
                        </div>
                        <Input
                          id="setting-open-graph-image"
                          type="file"
                          accept="image/*,.ico,.svg"
                          class="sr-only"
                          @change="uploadSettingImage('seo.open_graph_image', $event)"
                        />
                      </div>
                      <FieldError v-if="imageUploadErrors['seo.open_graph_image']">
                        {{ imageUploadErrors['seo.open_graph_image'] }}
                      </FieldError>
                      <FieldError v-if="fieldError('seo.open_graph_image')">
                        {{ fieldError('seo.open_graph_image') }}
                      </FieldError>
                    </Field>

                    <label
                      for="setting-allow-indexing"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span class="block text-sm font-semibold">Allow Search Engine Indexing</span>
                      <Checkbox
                        id="setting-allow-indexing"
                        v-model="form.seo.allow_search_engine_indexing"
                      />
                    </label>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'compliance'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-privacy-policy-url">Privacy Policy URL</FieldLabel>
                      <Input
                        id="setting-privacy-policy-url"
                        v-model="form.compliance.privacy_policy_url"
                      />
                      <FieldError v-if="fieldError('compliance.privacy_policy_url')">
                        {{ fieldError('compliance.privacy_policy_url') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-terms-url">Terms of Service URL</FieldLabel>
                      <Input
                        id="setting-terms-url"
                        v-model="form.compliance.terms_of_service_url"
                      />
                      <FieldError v-if="fieldError('compliance.terms_of_service_url')">
                        {{ fieldError('compliance.terms_of_service_url') }}
                      </FieldError>
                    </Field>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-[16rem_minmax(0,1fr)]">
                    <Field>
                      <FieldLabel for="setting-data-retention-days">Data Retention Days</FieldLabel>
                      <Input
                        id="setting-data-retention-days"
                        :model-value="form.compliance.data_retention_days"
                        type="number"
                        min="1"
                        @update:model-value="
                          form.compliance.data_retention_days = Number($event || 0)
                        "
                      />
                      <FieldError v-if="fieldError('compliance.data_retention_days')">
                        {{ fieldError('compliance.data_retention_days') }}
                      </FieldError>
                    </Field>

                    <label
                      for="setting-cookie-notice"
                      class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                    >
                      <span>
                        <span class="block text-sm font-semibold">Cookie Notice Enabled</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Public cookie banner
                        </span>
                      </span>
                      <Checkbox
                        id="setting-cookie-notice"
                        v-model="form.compliance.cookie_notice_enabled"
                      />
                    </label>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'social'">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-2">
                    <Field>
                      <FieldLabel for="setting-facebook-url">Facebook URL</FieldLabel>
                      <Input id="setting-facebook-url" v-model="form.social.facebook_url" />
                      <FieldError v-if="fieldError('social.facebook_url')">
                        {{ fieldError('social.facebook_url') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-instagram-url">Instagram URL</FieldLabel>
                      <Input id="setting-instagram-url" v-model="form.social.instagram_url" />
                      <FieldError v-if="fieldError('social.instagram_url')">
                        {{ fieldError('social.instagram_url') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-linkedin-url">LinkedIn URL</FieldLabel>
                      <Input id="setting-linkedin-url" v-model="form.social.linkedin_url" />
                      <FieldError v-if="fieldError('social.linkedin_url')">
                        {{ fieldError('social.linkedin_url') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-twitter-url">X/Twitter URL</FieldLabel>
                      <Input id="setting-twitter-url" v-model="form.social.twitter_url" />
                      <FieldError v-if="fieldError('social.twitter_url')">
                        {{ fieldError('social.twitter_url') }}
                      </FieldError>
                    </Field>

                    <Field>
                      <FieldLabel for="setting-youtube-url">YouTube URL</FieldLabel>
                      <Input id="setting-youtube-url" v-model="form.social.youtube_url" />
                      <FieldError v-if="fieldError('social.youtube_url')">
                        {{ fieldError('social.youtube_url') }}
                      </FieldError>
                    </Field>
                  </div>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'history'">
                <div v-if="settingStore.history.length" class="divide-y rounded border">
                  <article
                    v-for="record in settingStore.history"
                    :key="record.id"
                    class="grid gap-3 p-4 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1fr)_minmax(0,1fr)]"
                  >
                    <div>
                      <p class="break-all text-sm font-semibold">{{ record.setting_key }}</p>
                      <p class="mt-1 text-xs text-muted-foreground">
                        {{ record.changed_by.name || record.changed_by.email || 'System' }}
                      </p>
                      <p class="mt-1 text-xs text-muted-foreground">
                        {{ formatChangedAt(record.changed_at) }}
                      </p>
                    </div>
                    <div class="rounded bg-muted/40 p-3">
                      <p class="text-xs font-medium text-muted-foreground">Previous</p>
                      <p class="mt-1 break-words text-sm">
                        {{ formatSettingValue(record.previous_value) }}
                      </p>
                    </div>
                    <div class="rounded bg-muted/40 p-3">
                      <p class="text-xs font-medium text-muted-foreground">New</p>
                      <p class="mt-1 break-words text-sm">
                        {{ formatSettingValue(record.new_value) }}
                      </p>
                    </div>
                  </article>
                </div>

                <div v-else class="rounded border bg-muted/20 p-6 text-sm text-muted-foreground">
                  No settings changes recorded yet.
                </div>
              </section>
            </FieldSet>
          </main>
        </div>

        <p v-if="formError" class="text-sm font-medium text-destructive">
          {{ formError }}
        </p>
      </form>
    </div>
  </div>
</template>
