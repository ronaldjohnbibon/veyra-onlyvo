<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import TimezoneCombobox from '@/shared/components/TimezoneCombobox.vue'
import SystemSettingHistoryTable from '@/admin/system-settings/components/SystemSettingHistoryTable.vue'
import { useAdminSystemSettingStore } from '@/admin/system-settings/system-setting-store'
import type {
  SystemSettingGroup,
  SystemSettingItem,
  SystemSettingValue,
  SystemSettingsPayload,
} from '@/admin/system-settings/types'
import {
  BarChart3,
  Bell,
  Brush,
  CheckCircle2,
  CircleAlert,
  Clock,
  Download,
  ExternalLink,
  History,
  IdCard,
  Info,
  MailCheck,
  Rocket,
  Save,
  Search,
  Send,
  ShieldAlert,
  ShieldCheck,
  Upload,
  Wrench,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

type TaskSectionKey =
  | 'profile'
  | 'launch'
  | 'seo'
  | 'branding'
  | 'notifications'
  | 'compliance'
  | 'analytics'
  | 'history'

interface TaskSection {
  key: TaskSectionKey
  label: string
  title: string
  description: string
  task: string
  icon: Component
  groupKeys: string[]
}

const settingStore = useAdminSystemSettingStore()
const form = reactive<SystemSettingsPayload>({})
const formError = ref('')
const saveNotice = ref('')
const savedSnapshot = ref('')
const activeSection = ref<TaskSectionKey>('profile')
const operationNotice = ref('')
const testEmailRecipient = ref('')
const maintenancePreviewPath = ref('/dashboard')
const imageUploadErrors = reactive<Record<string, string>>({})
const imageUploading = reactive<Record<string, boolean>>({})
const imagePreviewFailed = reactive<Record<string, boolean>>({})
const imageLocalPreviews = reactive<Record<string, string>>({})

const taskSections: TaskSection[] = [
  {
    key: 'profile',
    label: 'Profile',
    title: 'Platform Profile',
    description: 'Set the platform identity and support details shown to admins and tenants.',
    task: 'Keep the platform name, logo, and support contact details current.',
    icon: IdCard,
    groupKeys: ['general'],
  },
  {
    key: 'launch',
    label: 'Launch',
    title: 'Tenant Launch Controls',
    description: 'Control registration, tenant defaults, feature access, uploads, and maintenance.',
    task: 'Use these settings to decide what new tenants can do and what modules are available.',
    icon: Rocket,
    groupKeys: ['authentication', 'tenant_defaults', 'feature_flags', 'storage', 'maintenance'],
  },
  {
    key: 'seo',
    label: 'SEO',
    title: 'SEO and Public Metadata',
    description: 'Set platform-wide search, social preview, and canonical domain defaults.',
    task: 'Make public pages readable to search engines and clean when shared.',
    icon: Search,
    groupKeys: ['seo'],
  },
  {
    key: 'branding',
    label: 'Branding',
    title: 'Social and Brand Presence',
    description: 'Connect public social profiles and brand-facing links.',
    task: 'Keep public social destinations accurate for marketing and footer surfaces.',
    icon: Brush,
    groupKeys: ['social'],
  },
  {
    key: 'notifications',
    label: 'Notifications',
    title: 'Shared Email Delivery',
    description: 'Configure the provider and verified sender used by every Admin and Tenant email.',
    task: 'Keep one verified sender for the platform and route replies through support or tenant Reply-To addresses.',
    icon: Bell,
    groupKeys: ['email'],
  },
  {
    key: 'compliance',
    label: 'Compliance',
    title: 'Access, Security, and Policies',
    description: 'Manage admin access controls, password rules, and public legal policy links.',
    task: 'Review security limits and policy URLs before opening the platform to tenants.',
    icon: ShieldCheck,
    groupKeys: ['security', 'compliance'],
  },
  {
    key: 'analytics',
    label: 'Analytics',
    title: 'Analytics Capture',
    description: 'Control whether visits and CTA events are captured across public tenant sites.',
    task: 'Enable only the tracking signals that admins and tenants need to act on.',
    icon: BarChart3,
    groupKeys: ['analytics'],
  },
  {
    key: 'history',
    label: 'History',
    title: 'Change History',
    description: 'Review recent setting updates and who made them.',
    task: 'Use history to audit platform configuration changes.',
    icon: History,
    groupKeys: [],
  },
]

const fieldCopy: Record<string, string> = {
  'general.application_name':
    'The platform name shown in browser titles, dashboards, and public metadata.',
  'general.application_description':
    'A short platform summary used when public pages need fallback metadata.',
  'general.logo':
    'The main app logo. Use a clear PNG, SVG, or WebP that works on light backgrounds.',
  'general.favicon': 'The browser tab icon. Square images or ICO files work best.',
  'general.support_email':
    'The support inbox shown to tenants and used as Reply-To for Admin emails.',
  'general.support_phone': 'Optional support phone number shown in public or tenant-facing areas.',
  'general.company_address': 'Company address used in public compliance and support surfaces.',
  'general.show_field_descriptions':
    'Shows helpful field descriptions across the app for admins and tenants.',
  'authentication.allow_tenant_registration':
    'Lets new tenants create accounts from the registration page.',
  'authentication.require_email_verification':
    'Requires tenant users to confirm email before logging in.',
  'security.session_lifetime_minutes':
    'How long users can stay signed in before their session expires.',
  'security.login_rate_limit_attempts':
    'How many failed login attempts are allowed before rate limiting.',
  'security.login_rate_limit_window_minutes': 'Time window used to count failed login attempts.',
  'security.require_strong_passwords': 'Requires stronger passwords for account security.',
  'security.minimum_password_length': 'Minimum number of characters accepted for passwords.',
  'security.allowed_admin_ips':
    'Optional allow-list for admin access. Use IPs or CIDR ranges, separated by commas or lines.',
  'tenant_defaults.default_tenant_timezone':
    'Timezone assigned to new tenants until they change it.',
  'tenant_defaults.default_tenant_status': 'Initial status for newly created tenants.',
  'tenant_defaults.default_tenant_trial_days': 'Trial length assigned to new tenants.',
  'tenant_defaults.default_tenant_template_type':
    'Default website type slug used during tenant setup.',
  'tenant_defaults.default_tenant_template_key':
    'Default template key used with the selected website type.',
  'feature_flags.enable_templates_module': 'Shows or hides template builder features for tenants.',
  'feature_flags.enable_posts_module': 'Shows or hides tenant post management.',
  'feature_flags.enable_analytics_module':
    'Shows or hides analytics dashboards and capture features.',
  'feature_flags.enable_design_requests_module':
    'Shows or hides tenant/admin design request workflows.',
  'feature_flags.enable_cta_forms': 'Allows CTA forms to collect public submissions.',
  'feature_flags.enable_tracking_logs': 'Shows or hides raw tracking log tooling.',
  'feature_flags.controlled_rollout_percentage':
    'Optional rollout guardrail for staged feature availability. Use 100 for full availability.',
  'email.mail_driver': 'The shared provider used to send every Admin and Tenant email.',
  'email.smtp_host': 'SMTP server host name, when SMTP delivery is selected.',
  'email.smtp_port': 'SMTP server port. Common values are 587 or 465.',
  'email.smtp_username': 'SMTP username, if your provider requires one.',
  'email.smtp_password': 'SMTP password. This is used only when SMTP delivery is selected.',
  'email.sender_name':
    'Default From display name for Admin emails. Tenant emails may use the tenant business name.',
  'email.sender_email':
    'Verified From address shared by all Admin and Tenant emails. Tenants cannot override it.',
  'analytics.enable_visitor_tracking': 'Records public website visits for tenant analytics.',
  'analytics.enable_cta_tracking':
    'Records CTA views, clicks, and submissions for conversion analytics.',
  'storage.maximum_upload_size': 'Maximum upload size in KB for images and other tenant assets.',
  'storage.allowed_file_types': 'Comma-separated file extensions tenants may upload.',
  'maintenance.maintenance_mode':
    'Temporarily blocks affected platform areas while maintenance is active.',
  'maintenance.maintenance_message': 'Message shown to users while maintenance is active.',
  'maintenance.maintenance_start_time': 'Optional scheduled start time for maintenance messaging.',
  'maintenance.maintenance_end_time': 'Optional scheduled end time for maintenance messaging.',
  'maintenance.allow_admin_bypass':
    'Allows admins to continue using the platform during maintenance.',
  'maintenance.maintenance_affected_areas':
    'Paths affected by maintenance, one per line or separated by commas.',
  'seo.default_meta_title': 'Fallback title used for public platform pages and social previews.',
  'seo.default_meta_description': 'Fallback description used in search and sharing previews.',
  'seo.open_graph_image': 'Default image shown when public pages are shared.',
  'seo.allow_search_engine_indexing': 'Allows search engines to index public platform pages.',
  'seo.canonical_domain': 'Preferred public domain for canonical URLs.',
  'compliance.privacy_policy_url': 'Public link to the platform privacy policy.',
  'compliance.terms_of_service_url': 'Public link to platform terms of service.',
  'compliance.cookie_notice_enabled': 'Shows cookie notice messaging where supported.',
  'social.facebook_url': 'Public Facebook page or profile URL.',
  'social.instagram_url': 'Public Instagram profile URL.',
  'social.linkedin_url': 'Public LinkedIn page or profile URL.',
  'social.twitter_url': 'Public X/Twitter profile URL.',
  'social.youtube_url': 'Public YouTube channel URL.',
}

const activeTaskSection = computed(() => {
  return taskSections.find((section) => section.key === activeSection.value) ?? taskSections[0]
})

const currentSectionGroups = computed<SystemSettingGroup[]>(() => {
  if (activeSection.value === 'history') return []

  return activeTaskSection.value.groupKeys
    .map((key) => settingStore.groups.find((group) => group.key === key))
    .filter((group): group is SystemSettingGroup => Boolean(group))
})

const selectedMailDriver = (): string => String(form.email?.mail_driver || 'smtp')

const visibleSettings = (group: SystemSettingGroup): SystemSettingItem[] => {
  if (group.key !== 'email' || selectedMailDriver() === 'smtp') {
    return group.settings
  }

  return group.settings.filter((setting) => !setting.name.startsWith('smtp_'))
}

const currentSettings = computed<SystemSettingItem[]>(() => {
  return currentSectionGroups.value.flatMap(visibleSettings)
})

const ensureGroup = (group: string): void => {
  form[group] ??= {}
}

const getGroupName = (setting: SystemSettingItem): string => setting.key.split('.')[0] ?? ''

const getSettingValue = (setting: SystemSettingItem): SystemSettingValue => {
  const group = getGroupName(setting)

  return form[group]?.[setting.name] ?? setting.value ?? ''
}

const setSettingValue = (setting: SystemSettingItem, value: SystemSettingValue): void => {
  const group = getGroupName(setting)
  ensureGroup(group)
  form[group][setting.name] = value
}

const payloadForSave = (): SystemSettingsPayload => {
  const payload: SystemSettingsPayload = {}

  for (const group of settingStore.groups) {
    payload[group.key] = {}

    for (const setting of group.settings) {
      const value = getSettingValue(setting)
      payload[group.key][setting.name] =
        setting.type === 'integer' && (value === null || value === '')
          ? null
          : setting.type === 'integer'
            ? Number(value)
            : value
    }
  }

  return payload
}

const syncSavedSnapshot = (): void => {
  savedSnapshot.value = JSON.stringify(payloadForSave())
}

const hydrateForm = (): void => {
  for (const group of settingStore.groups) {
    ensureGroup(group.key)

    for (const setting of group.settings) {
      form[group.key][setting.name] = setting.value
    }
  }
}

const fieldId = (setting: SystemSettingItem): string => {
  return `setting-${setting.key.replace(/[^a-z0-9]+/gi, '-')}`
}

const fieldError = (setting: SystemSettingItem): string | null => {
  return settingStore.errors[`settings.${setting.key}`]?.[0] ?? null
}

const hasFieldError = (setting: SystemSettingItem): boolean => Boolean(fieldError(setting))

const sectionErrorCount = (section: TaskSection): number => {
  if (section.key === 'history') return 0

  return Object.keys(settingStore.errors).filter((key) => {
    return section.groupKeys.some((groupKey) => key.startsWith(`settings.${groupKey}.`))
  }).length
}

const stringValue = (setting: SystemSettingItem): string => {
  const value = getSettingValue(setting)

  return value === null || value === undefined ? '' : String(value)
}

const valueForKey = (key: string): SystemSettingValue => {
  const [group, name] = key.split('.')

  return form[group]?.[name] ?? ''
}

const stringForKey = (key: string): string => {
  const value = valueForKey(key)

  return value === null || value === undefined ? '' : String(value)
}

const booleanForKey = (key: string): boolean => Boolean(valueForKey(key))

const settingDescription = (setting: SystemSettingItem): string => {
  return (
    fieldCopy[setting.key] ??
    setting.description ??
    `Controls how “${setting.label}” is used across the platform.`
  )
}

const settingHelp = (setting: SystemSettingItem): string => settingDescription(setting)

const groupLabel = (group: SystemSettingGroup): string => {
  const labels: Record<string, string> = {
    general: 'Platform identity',
    authentication: 'Registration and access',
    security: 'Admin security',
    tenant_defaults: 'New tenant defaults',
    feature_flags: 'Tenant modules',
    email: 'Email provider',
    analytics: 'Tracking controls',
    storage: 'Uploads',
    maintenance: 'Maintenance window',
    seo: 'Search defaults',
    compliance: 'Policy links',
    social: 'Social links',
  }

  return labels[group.key] ?? group.label
}

const hasImagePreview = (setting: SystemSettingItem): boolean => {
  return Boolean(imagePreviewSource(setting)) && !imagePreviewFailed[setting.key]
}

const imagePreviewSource = (setting: SystemSettingItem): string => {
  return imageLocalPreviews[setting.key] || stringValue(setting)
}

const revokeImagePreview = (key: string): void => {
  if (!imageLocalPreviews[key]) return

  URL.revokeObjectURL(imageLocalPreviews[key])
  delete imageLocalPreviews[key]
}

const clearImage = (setting: SystemSettingItem): void => {
  imageUploadErrors[setting.key] = ''
  imagePreviewFailed[setting.key] = false
  revokeImagePreview(setting.key)
  setSettingValue(setting, '')
}

const markImagePreviewFailed = (setting: SystemSettingItem): void => {
  imagePreviewFailed[setting.key] = true
}

const uploadSettingImage = async (setting: SystemSettingItem, event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  revokeImagePreview(setting.key)
  imageLocalPreviews[setting.key] = URL.createObjectURL(file)

  try {
    imageUploading[setting.key] = true
    imageUploadErrors[setting.key] = ''
    imagePreviewFailed[setting.key] = false
    setSettingValue(setting, await settingStore.uploadImage(setting.key, file))
    revokeImagePreview(setting.key)
  } catch {
    revokeImagePreview(setting.key)
    imageUploadErrors[setting.key] =
      settingStore.errors.image?.[0] ||
      settingStore.errors.key?.[0] ||
      'Upload failed. Please choose a valid image.'
  } finally {
    imageUploading[setting.key] = false
    input.value = ''
  }
}

const hasUnsavedChanges = computed(() => {
  if (!savedSnapshot.value || !settingStore.groups.length) return false

  return JSON.stringify(payloadForSave()) !== savedSnapshot.value
})

const saveState = computed(() => {
  if (settingStore.loading) return { label: 'Saving...', tone: 'info', icon: Clock }
  if (formError.value)
    return { label: 'Review highlighted fields', tone: 'error', icon: CircleAlert }
  if (hasUnsavedChanges.value) return { label: 'Unsaved changes', tone: 'warning', icon: Clock }
  if (saveNotice.value) return { label: saveNotice.value, tone: 'success', icon: CheckCircle2 }

  return { label: 'Saved', tone: 'success', icon: CheckCircle2 }
})

const completionCount = computed(() => {
  return currentSettings.value.filter((setting) => {
    if (setting.type === 'boolean') return true

    return stringValue(setting).trim() !== ''
  }).length
})

const maintenanceMode = computed(() => booleanForKey('maintenance.maintenance_mode'))
const registrationOpen = computed(() => booleanForKey('authentication.allow_tenant_registration'))
const visitorTracking = computed(() => booleanForKey('analytics.enable_visitor_tracking'))
const ctaTracking = computed(() => booleanForKey('analytics.enable_cta_tracking'))
const featureFlags = computed(() => {
  return currentValueFlags('feature_flags').filter((flag) => flag.enabled)
})
const disabledFeatureFlags = computed(() => {
  return currentValueFlags('feature_flags').filter((flag) => !flag.enabled)
})
const criticalWarnings = computed(() => {
  const warnings: { title: string; message: string; tone: 'warning' | 'danger' | 'info' }[] = []

  if (maintenanceMode.value) {
    warnings.push({
      title: 'Maintenance mode is enabled',
      message: 'Run a dry-run preview before saving to confirm which paths will be blocked.',
      tone: 'warning',
    })
  }

  if (maintenanceMode.value && !booleanForKey('maintenance.allow_admin_bypass')) {
    warnings.push({
      title: 'Admin bypass is disabled',
      message: 'Admins may be blocked from affected areas during an active maintenance window.',
      tone: 'danger',
    })
  }

  if (!stringForKey('security.allowed_admin_ips').trim()) {
    warnings.push({
      title: 'No admin IP allow-list',
      message: 'Admin access is not restricted by source IP.',
      tone: 'warning',
    })
  }

  if (disabledFeatureFlags.value.length) {
    warnings.push({
      title: 'Tenant modules disabled',
      message: `${disabledFeatureFlags.value.length} module controls are currently off.`,
      tone: 'info',
    })
  }

  if (!visitorTracking.value || !ctaTracking.value) {
    warnings.push({
      title: 'Analytics capture is partial',
      message: 'Visitor and CTA tracking must both be enabled for complete funnel reporting.',
      tone: 'info',
    })
  }

  return warnings
})

const currentValueFlags = (group: string): { key: string; label: string; enabled: boolean }[] => {
  return (settingStore.groups.find((item) => item.key === group)?.settings ?? [])
    .filter((setting) => setting.type === 'boolean')
    .map((setting) => ({
      key: setting.key,
      label: setting.label.replace(/^Enable /, ''),
      enabled: Boolean(getSettingValue(setting)),
    }))
}

const appNamePreview = computed(() => stringForKey('general.application_name') || 'Onlyvo')
const appDescriptionPreview = computed(() => {
  return stringForKey('general.application_description') || 'Onlyvo tenant platform'
})
const seoTitlePreview = computed(
  () => stringForKey('seo.default_meta_title') || appNamePreview.value
)
const seoDescriptionPreview = computed(() => {
  return stringForKey('seo.default_meta_description') || appDescriptionPreview.value
})
const canonicalPreview = computed(() => stringForKey('seo.canonical_domain') || 'your-platform.com')
const uploadLimitPreview = computed(() => {
  const size = Number(valueForKey('storage.maximum_upload_size') || 0)

  if (!size) return 'Not set'
  if (size >= 1024) return `${Math.round((size / 1024) * 10) / 10} MB`

  return `${size} KB`
})
const rolloutPreview = computed(() => {
  return `${Number(valueForKey('feature_flags.controlled_rollout_percentage') || 0)}%`
})

const downloadBlob = (blob: Blob, filename: string): void => {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')

  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

const exportSettings = async (): Promise<void> => {
  operationNotice.value = ''
  downloadBlob(await settingStore.exportSettings(), 'system-settings-export.json')
  await settingStore.loadHistory()
  operationNotice.value = 'Settings export downloaded'
}

const backupSettings = async (): Promise<void> => {
  operationNotice.value = ''
  downloadBlob(await settingStore.backupSettings(), 'system-settings-backup.json')
  await settingStore.loadHistory()
  operationNotice.value = 'Settings backup downloaded'
}

const testSmtp = async (): Promise<void> => {
  operationNotice.value = ''
  const result = await settingStore.testSmtp()
  operationNotice.value = result.message
}

const sendTestEmail = async (): Promise<void> => {
  operationNotice.value = ''
  await settingStore.testEmail(testEmailRecipient.value)
  operationNotice.value = `Test email sent to ${testEmailRecipient.value}`
}

const previewMaintenance = async (): Promise<void> => {
  operationNotice.value = ''
  await settingStore.previewMaintenance(payloadForSave(), maintenancePreviewPath.value || '/')
  operationNotice.value = 'Maintenance dry-run completed'
}

const restoreHistoryRecord = async (record: { id: string }): Promise<void> => {
  operationNotice.value = ''
  await settingStore.restoreHistory(record.id)
  hydrateForm()
  syncSavedSnapshot()
  operationNotice.value = 'Previous setting version restored'
}

const saveSettings = async (): Promise<void> => {
  try {
    formError.value = ''
    saveNotice.value = ''
    await settingStore.update(payloadForSave())
    hydrateForm()
    syncSavedSnapshot()
    saveNotice.value = 'Changes saved'
  } catch {
    saveNotice.value = ''
    formError.value = 'Please check the highlighted settings and try again.'
  }
}

onMounted(async () => {
  await settingStore.index()
  hydrateForm()
  syncSavedSnapshot()
})

onBeforeUnmount(() => {
  Object.keys(imageLocalPreviews).forEach(revokeImagePreview)
})
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-16">
      <form class="space-y-4" @submit.prevent="saveSettings">
        <div
          class="sticky top-0 z-20 -mx-4 border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80"
        >
          <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <h2 class="text-2xl font-semibold tracking-tight text-foreground">System Settings</h2>
              <p class="mt-1 text-sm text-muted-foreground">
                Admin-friendly controls for launching, protecting, and growing the platform.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  maintenanceMode
                    ? 'border-amber-300 bg-amber-50 text-amber-800'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-800'
                "
              >
                Maintenance {{ maintenanceMode ? 'On' : 'Off' }}
              </span>
              <span
                class="inline-flex items-center gap-1.5 rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  registrationOpen
                    ? 'border-sky-200 bg-sky-50 text-sky-800'
                    : 'border-muted bg-muted/50 text-muted-foreground'
                "
              >
                Registration {{ registrationOpen ? 'Open' : 'Closed' }}
              </span>
              <span
                class="inline-flex items-center gap-1.5 rounded border px-2.5 py-1 text-xs font-medium"
                :class="{
                  'border-emerald-200 bg-emerald-50 text-emerald-800': saveState.tone === 'success',
                  'border-amber-300 bg-amber-50 text-amber-800': saveState.tone === 'warning',
                  'border-sky-200 bg-sky-50 text-sky-800': saveState.tone === 'info',
                  'border-destructive/40 bg-destructive/10 text-destructive':
                    saveState.tone === 'error',
                }"
              >
                <component :is="saveState.icon" class="size-3.5" />
                {{ saveState.label }}
              </span>
              <Button
                variant="navigate"
                size="sm"
                type="button"
                :disabled="settingStore.operationLoading"
                @click="exportSettings"
              >
                <Download class="size-4" />
                Export
              </Button>
              <Button
                variant="outline_default"
                size="sm"
                type="button"
                :disabled="settingStore.operationLoading"
                @click="backupSettings"
              >
                <Download class="size-4" />
                Backup
              </Button>
              <Button variant="update" size="sm" type="submit" :disabled="settingStore.loading">
                <Save class="size-4" />
                {{ settingStore.loading ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </div>
        </div>

        <div
          v-if="formError"
          class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
        >
          {{ formError }}
        </div>
        <div
          v-if="operationNotice"
          class="rounded border border-sky-200 bg-sky-50 p-3 text-sm font-medium text-sky-800"
        >
          {{ operationNotice }}
        </div>

        <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)_24rem]">
          <aside class="lg:sticky lg:top-24 lg:self-start">
            <div class="rounded border bg-background p-2">
              <button
                v-for="section in taskSections"
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
                  v-if="sectionErrorCount(section)"
                  class="ml-2 rounded bg-destructive px-1.5 py-0.5 text-[11px] text-destructive-foreground"
                >
                  {{ sectionErrorCount(section) }}
                </span>
              </button>
            </div>

            <div v-if="activeSection !== 'history'" class="mt-3 rounded border bg-muted/30 p-3">
              <p class="text-xs font-medium text-muted-foreground">Section progress</p>
              <p class="mt-1 text-sm font-semibold">
                {{ completionCount }} of {{ currentSettings.length }} settings filled
              </p>
              <div class="mt-3 h-2 rounded bg-muted">
                <div
                  class="h-2 rounded bg-primary"
                  :style="{
                    width: `${Math.round((completionCount / Math.max(currentSettings.length, 1)) * 100)}%`,
                  }"
                />
              </div>
            </div>
          </aside>

          <main
            class="min-w-0 rounded border bg-background"
            :class="{ 'xl:col-span-2': activeSection === 'history' }"
          >
            <div class="border-b px-4 py-4">
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex gap-3">
                  <div
                    class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary"
                  >
                    <component :is="activeTaskSection.icon" class="size-5" />
                  </div>
                  <div>
                    <h3 class="text-base font-semibold">{{ activeTaskSection.title }}</h3>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                      {{ activeTaskSection.description }}
                    </p>
                  </div>
                </div>
                <Badge v-if="activeSection !== 'history'" variant="outline">
                  {{ currentSettings.length }} settings
                </Badge>
              </div>

              <div class="mt-4 rounded border bg-muted/30 p-3">
                <div class="flex gap-2 text-sm">
                  <Info class="mt-0.5 size-4 shrink-0 text-primary" />
                  <span>{{ activeTaskSection.task }}</span>
                </div>
              </div>

              <div
                v-if="activeSection === 'notifications'"
                class="mt-4 rounded border bg-background p-4"
              >
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <h4 class="flex items-center gap-2 text-sm font-semibold">
                      <MailCheck class="size-4 text-primary" />
                      Email Delivery Tests
                    </h4>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Send a real test through the shared provider before relying on password resets
                      or tenant notifications.
                    </p>
                  </div>
                  <Button
                    v-if="selectedMailDriver() === 'smtp'"
                    type="button"
                    variant="navigate"
                    size="sm"
                    :disabled="settingStore.operationLoading"
                    @click="testSmtp"
                  >
                    <Wrench class="size-4" />
                    SMTP Test
                  </Button>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                  <div class="rounded border bg-muted/20 p-3 text-sm">
                    <p class="font-medium">Shared From address</p>
                    <p class="mt-1 text-muted-foreground">
                      {{ stringForKey('email.sender_name') || 'App name' }}
                      &lt;{{ stringForKey('email.sender_email') || 'Not configured' }}&gt;
                    </p>
                  </div>
                  <div class="rounded border bg-muted/20 p-3 text-sm">
                    <p class="font-medium">Admin Reply-To</p>
                    <p class="mt-1 text-muted-foreground">
                      {{
                        stringForKey('general.support_email') ||
                        'Falls back to the shared From address'
                      }}
                    </p>
                    <button
                      type="button"
                      class="mt-2 text-xs font-medium text-primary hover:underline"
                      @click="activeSection = 'profile'"
                    >
                      Edit support email
                    </button>
                  </div>
                </div>

                <div
                  v-if="selectedMailDriver() === 'resend'"
                  class="mt-3 rounded border border-primary/20 bg-primary/5 p-3 text-sm text-muted-foreground"
                >
                  Resend uses the shared verified sender above. Its API key is configured in the
                  server environment with <code>RESEND_API_KEY</code>, not saved in this page.
                </div>
                <div
                  v-else-if="selectedMailDriver() !== 'smtp'"
                  class="mt-3 rounded border bg-muted/20 p-3 text-sm text-muted-foreground"
                >
                  Provider-specific configuration is read from the server environment. SMTP fields
                  are hidden because they are not used by the selected driver.
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                  <Input
                    v-field-help="
                      'Send a test message to this email address using the current mail settings.'
                    "
                    v-model="testEmailRecipient"
                    type="email"
                    placeholder="admin@example.com"
                  />
                  <Button
                    type="button"
                    variant="publish"
                    :disabled="settingStore.operationLoading || !testEmailRecipient"
                    @click="sendTestEmail"
                  >
                    <Send class="size-4" />
                    Send Test
                  </Button>
                </div>

                <div
                  v-if="settingStore.smtpTestResult || settingStore.emailTestResult"
                  class="mt-4 grid gap-3 md:grid-cols-2"
                >
                  <div v-if="settingStore.smtpTestResult" class="rounded border p-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                      <span class="font-medium">SMTP status</span>
                      <Badge
                        :variant="
                          settingStore.smtpTestResult.status === 'ok'
                            ? 'success'
                            : settingStore.smtpTestResult.status === 'skipped'
                              ? 'outline'
                              : 'destructive'
                        "
                      >
                        {{ settingStore.smtpTestResult.status }}
                      </Badge>
                    </div>
                    <p class="mt-2 text-muted-foreground">
                      {{ settingStore.smtpTestResult.message }}
                    </p>
                  </div>
                  <div v-if="settingStore.emailTestResult" class="rounded border p-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                      <span class="font-medium">Email delivery</span>
                      <Badge variant="success">{{ settingStore.emailTestResult.status }}</Badge>
                    </div>
                    <p class="mt-2 text-muted-foreground">
                      {{ settingStore.emailTestResult.message }}
                    </p>
                  </div>
                </div>
              </div>

              <div v-if="activeSection === 'launch'" class="mt-4 rounded border bg-background p-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <h4 class="flex items-center gap-2 text-sm font-semibold">
                      <ShieldAlert class="size-4 text-amber-600" />
                      Maintenance Mode Dry-Run
                    </h4>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Preview maintenance impact with the unsaved form values before applying them.
                    </p>
                  </div>
                  <Badge :variant="maintenanceMode ? 'warning' : 'outline'">
                    {{ maintenanceMode ? 'Maintenance on' : 'Maintenance off' }}
                  </Badge>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-[minmax(0,1fr)_auto]">
                  <Input
                    v-field-help="
                      'Preview whether this path would be blocked by maintenance settings.'
                    "
                    v-model="maintenancePreviewPath"
                    placeholder="/dashboard"
                  />
                  <Button
                    type="button"
                    variant="restore"
                    :disabled="settingStore.operationLoading"
                    @click="previewMaintenance"
                  >
                    <Wrench class="size-4" />
                    Dry Run
                  </Button>
                </div>

                <div
                  v-if="settingStore.maintenancePreviewResult"
                  class="mt-4 rounded border p-3 text-sm"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <Badge
                      :variant="
                        settingStore.maintenancePreviewResult.result === 'blocked'
                          ? 'warning'
                          : 'success'
                      "
                    >
                      {{ settingStore.maintenancePreviewResult.result }}
                    </Badge>
                    <span class="font-medium">{{
                      settingStore.maintenancePreviewResult.path
                    }}</span>
                  </div>
                  <p class="mt-2 text-muted-foreground">
                    Affected areas: {{ settingStore.maintenancePreviewResult.affected_summary }}
                  </p>
                  <p class="mt-1 text-muted-foreground">
                    Admin bypass:
                    {{
                      settingStore.maintenancePreviewResult.admin_bypass ? 'allowed' : 'disabled'
                    }}
                  </p>
                </div>
              </div>
            </div>

            <FieldSet class="p-4">
              <section v-if="activeSection !== 'history'">
                <FieldGroup>
                  <div class="space-y-5">
                    <section
                      v-for="group in currentSectionGroups"
                      :key="group.key"
                      class="space-y-3"
                    >
                      <div v-if="currentSectionGroups.length > 1" class="flex items-center gap-2">
                        <div class="h-px flex-1 bg-border" />
                        <Badge variant="secondary">{{ groupLabel(group) }}</Badge>
                        <div class="h-px flex-1 bg-border" />
                      </div>

                      <div class="grid gap-4">
                        <template v-for="setting in visibleSettings(group)" :key="setting.key">
                          <label
                            v-if="setting.type === 'boolean'"
                            :for="fieldId(setting)"
                            class="flex cursor-pointer items-start justify-between gap-4 rounded border p-4 transition hover:bg-muted/40"
                            :class="
                              hasFieldError(setting)
                                ? 'border-destructive/60 bg-destructive/5'
                                : 'bg-muted/10'
                            "
                          >
                            <span class="min-w-0">
                              <span class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-semibold">{{ setting.label }}</span>
                                <Badge v-if="setting.is_public" variant="outline">Public</Badge>
                                <Badge v-if="setting.is_sensitive" variant="neutral">Masked</Badge>
                              </span>
                              <span class="mt-1 block text-sm text-muted-foreground">
                                {{ settingDescription(setting) }}
                              </span>
                              <FieldError v-if="fieldError(setting)" class="mt-2">
                                {{ fieldError(setting) }}
                              </FieldError>
                            </span>
                            <Checkbox
                              v-field-help="settingHelp(setting)"
                              :id="fieldId(setting)"
                              :model-value="Boolean(getSettingValue(setting))"
                              class="mt-1"
                              @update:model-value="setSettingValue(setting, Boolean($event))"
                            />
                          </label>

                          <Field
                            v-else-if="setting.type === 'text'"
                            class="rounded border p-4"
                            :class="
                              hasFieldError(setting)
                                ? 'border-destructive/60 bg-destructive/5'
                                : 'bg-muted/10'
                            "
                          >
                            <div class="flex flex-wrap items-center gap-2">
                              <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>
                              <Badge v-if="setting.is_public" variant="outline">Public</Badge>
                              <Badge v-if="setting.is_sensitive" variant="neutral">Masked</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                              {{ settingDescription(setting) }}
                            </p>

                            <Textarea
                              v-field-help="settingHelp(setting)"
                              :id="fieldId(setting)"
                              :model-value="stringValue(setting)"
                              class="min-h-28"
                              @update:model-value="setSettingValue(setting, String($event ?? ''))"
                            />

                            <FieldError v-if="fieldError(setting)">
                              {{ fieldError(setting) }}
                            </FieldError>
                          </Field>

                          <Field
                            v-else-if="setting.type === 'image'"
                            class="rounded border p-4"
                            :class="
                              hasFieldError(setting) || imageUploadErrors[setting.key]
                                ? 'border-destructive/60 bg-destructive/5'
                                : 'bg-muted/10'
                            "
                          >
                            <div class="flex flex-wrap items-center gap-2">
                              <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>
                              <Badge v-if="setting.is_public" variant="outline">Public</Badge>
                              <Badge v-if="setting.is_sensitive" variant="neutral">Masked</Badge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                              {{ settingDescription(setting) }}
                            </p>
                            <div
                              class="mt-2 grid gap-4 rounded border bg-background p-3 md:grid-cols-[18rem_minmax(0,1fr)]"
                            >
                              <img
                                v-if="hasImagePreview(setting)"
                                :src="imagePreviewSource(setting)"
                                :alt="`${setting.label} preview`"
                                class="aspect-video w-full rounded border bg-background object-contain"
                                @error="markImagePreviewFailed(setting)"
                              />
                              <div
                                v-else
                                class="flex aspect-video items-center justify-center rounded border border-dashed bg-background text-sm text-muted-foreground"
                              >
                                No image uploaded
                              </div>

                              <div class="flex flex-col justify-center gap-3">
                                <Input
                                  v-field-help="settingHelp(setting)"
                                  :model-value="stringValue(setting)"
                                  placeholder="/storage/system-settings/logo.png"
                                  @update:model-value="
                                    setSettingValue(setting, String($event ?? ''))
                                  "
                                />

                                <div class="flex flex-wrap items-center gap-2">
                                  <Button
                                    as-child
                                    variant="outline_default"
                                    size="sm"
                                    :disabled="imageUploading[setting.key]"
                                  >
                                    <label
                                      v-field-help="settingHelp(setting)"
                                      :for="fieldId(setting)"
                                      class="cursor-pointer"
                                    >
                                      <Upload class="size-4" />
                                      {{ imageUploading[setting.key] ? 'Uploading...' : 'Upload' }}
                                    </label>
                                  </Button>
                                  <Button
                                    v-if="stringValue(setting)"
                                    type="button"
                                    variant="cancel"
                                    size="sm"
                                    @click="clearImage(setting)"
                                  >
                                    <X class="size-4" />
                                    Clear
                                  </Button>
                                </div>
                                <Input
                                  v-field-help="settingHelp(setting)"
                                  :id="fieldId(setting)"
                                  type="file"
                                  accept="image/*,.ico,.svg"
                                  class="sr-only"
                                  @change="uploadSettingImage(setting, $event)"
                                />
                              </div>
                            </div>
                            <FieldError v-if="imageUploadErrors[setting.key]">
                              {{ imageUploadErrors[setting.key] }}
                            </FieldError>
                            <FieldError v-if="fieldError(setting)">
                              {{ fieldError(setting) }}
                            </FieldError>
                          </Field>

                          <Field v-else>
                            <div
                              class="rounded border p-4"
                              :class="
                                hasFieldError(setting)
                                  ? 'border-destructive/60 bg-destructive/5'
                                  : 'bg-muted/10'
                              "
                            >
                              <div class="mb-2 flex flex-wrap items-center gap-2">
                                <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>
                                <Badge v-if="setting.is_public" variant="outline">Public</Badge>
                                <Badge v-if="setting.is_sensitive" variant="neutral">Masked</Badge>
                              </div>
                              <p class="mb-3 text-sm text-muted-foreground">
                                {{ settingDescription(setting) }}
                              </p>

                              <TimezoneCombobox
                                v-if="setting.key === 'tenant_defaults.default_tenant_timezone'"
                                :id="fieldId(setting)"
                                :model-value="stringValue(setting)"
                                :help="settingHelp(setting)"
                                @update:model-value="setSettingValue(setting, $event)"
                              />
                              <NativeSelect
                                v-field-help="settingHelp(setting)"
                                v-else-if="setting.type === 'select'"
                                :id="fieldId(setting)"
                                class="w-full"
                                :model-value="stringValue(setting)"
                                @update:model-value="setSettingValue(setting, String($event ?? ''))"
                              >
                                <NativeSelectOption
                                  v-for="(label, value) in setting.options ?? {}"
                                  :key="value"
                                  :value="value"
                                >
                                  {{ label }}
                                </NativeSelectOption>
                              </NativeSelect>
                              <Input
                                v-field-help="settingHelp(setting)"
                                v-else
                                :id="fieldId(setting)"
                                :model-value="getSettingValue(setting) as string | number | null"
                                :type="
                                  setting.type === 'integer'
                                    ? 'number'
                                    : setting.type === 'password'
                                      ? 'password'
                                      : setting.type === 'email'
                                        ? 'email'
                                        : setting.type === 'url'
                                          ? 'url'
                                          : setting.type === 'color'
                                            ? 'color'
                                            : 'text'
                                "
                                :min="setting.type === 'integer' ? 0 : undefined"
                                class="w-full"
                                @update:model-value="
                                  setSettingValue(
                                    setting,
                                    setting.type === 'integer'
                                      ? $event === null || $event === ''
                                        ? null
                                        : Number($event)
                                      : String($event ?? '')
                                  )
                                "
                              />

                              <FieldError v-if="fieldError(setting)" class="mt-2">
                                {{ fieldError(setting) }}
                              </FieldError>
                            </div>
                          </Field>
                        </template>
                      </div>
                    </section>
                  </div>
                </FieldGroup>
              </section>

              <section v-else>
                <SystemSettingHistoryTable
                  :records="settingStore.history"
                  :total="settingStore.historyTotal"
                  :params="settingStore.historyParams"
                  :loading="settingStore.loading"
                  @load="settingStore.loadHistory"
                  @restore="restoreHistoryRecord"
                />
              </section>
            </FieldSet>
          </main>

          <aside
            v-if="activeSection !== 'history'"
            class="space-y-4 xl:sticky xl:top-24 xl:self-start"
          >
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Platform Preview</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  A quick view of the public-facing settings admins control.
                </p>
              </div>

              <div class="space-y-4 p-4">
                <div class="rounded border p-4">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="stringForKey('general.logo')"
                      :src="stringForKey('general.logo')"
                      alt="Logo preview"
                      class="size-10 rounded border object-contain"
                    />
                    <div
                      v-else
                      class="flex size-10 items-center justify-center rounded border bg-muted text-sm font-semibold"
                    >
                      {{ appNamePreview.slice(0, 1).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold">{{ appNamePreview }}</p>
                      <p class="truncate text-xs text-muted-foreground">
                        {{ stringForKey('general.support_email') || 'Add support email' }}
                      </p>
                    </div>
                  </div>
                  <p class="mt-3 line-clamp-3 text-sm text-muted-foreground">
                    {{ appDescriptionPreview }}
                  </p>
                </div>

                <div class="rounded border p-4">
                  <p class="text-xs font-medium text-muted-foreground">Search preview</p>
                  <p class="mt-2 line-clamp-1 text-sm text-blue-700">{{ seoTitlePreview }}</p>
                  <p class="line-clamp-1 text-xs text-emerald-700">{{ canonicalPreview }}</p>
                  <p class="mt-1 line-clamp-3 text-sm text-muted-foreground">
                    {{ seoDescriptionPreview }}
                  </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Uploads</p>
                    <p class="mt-1 text-sm font-semibold">{{ uploadLimitPreview }}</p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Mail</p>
                    <p class="mt-1 truncate text-sm font-semibold">
                      {{ stringForKey('email.mail_driver') || 'Not set' }}
                    </p>
                  </div>
                </div>

                <div class="rounded border p-4">
                  <p class="text-xs font-medium text-muted-foreground">Enabled tenant modules</p>
                  <div class="mt-2 flex flex-wrap gap-2">
                    <Badge v-for="flag in featureFlags" :key="flag.key" variant="secondary">
                      {{ flag.label }}
                    </Badge>
                    <span v-if="!featureFlags.length" class="text-sm text-muted-foreground">
                      No modules enabled
                    </span>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <ShieldCheck class="size-4 text-primary" />
                <h3 class="text-sm font-semibold">Readiness</h3>
              </div>
              <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Registration</span>
                  <Badge :variant="registrationOpen ? 'success' : 'outline'">
                    {{ registrationOpen ? 'Open' : 'Closed' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Maintenance</span>
                  <Badge :variant="maintenanceMode ? 'warning' : 'success'">
                    {{ maintenanceMode ? 'On' : 'Off' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Analytics</span>
                  <Badge :variant="visitorTracking && ctaTracking ? 'success' : 'outline'">
                    {{ visitorTracking && ctaTracking ? 'Tracking' : 'Partial' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Rollout</span>
                  <Badge :variant="rolloutPreview === '100%' ? 'success' : 'warning'">
                    {{ rolloutPreview }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Policies</span>
                  <Badge
                    :variant="
                      stringForKey('compliance.privacy_policy_url') &&
                      stringForKey('compliance.terms_of_service_url')
                        ? 'success'
                        : 'outline'
                    "
                  >
                    {{
                      stringForKey('compliance.privacy_policy_url') &&
                      stringForKey('compliance.terms_of_service_url')
                        ? 'Linked'
                        : 'Missing'
                    }}
                  </Badge>
                </div>
              </div>

              <a
                v-if="stringForKey('compliance.privacy_policy_url')"
                :href="stringForKey('compliance.privacy_policy_url')"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary"
              >
                Open privacy policy
                <ExternalLink class="size-3.5" />
              </a>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <ShieldAlert class="size-4 text-amber-600" />
                <h3 class="text-sm font-semibold">Safety Warnings</h3>
              </div>
              <div class="mt-3 space-y-2">
                <div
                  v-for="warning in criticalWarnings"
                  :key="warning.title"
                  class="rounded border p-3 text-sm"
                  :class="{
                    'border-destructive/40 bg-destructive/10 text-destructive':
                      warning.tone === 'danger',
                    'border-amber-300 bg-amber-50 text-amber-900': warning.tone === 'warning',
                    'border-sky-200 bg-sky-50 text-sky-900': warning.tone === 'info',
                  }"
                >
                  <p class="font-medium">{{ warning.title }}</p>
                  <p class="mt-1 text-xs opacity-80">{{ warning.message }}</p>
                </div>
                <div
                  v-if="!criticalWarnings.length"
                  class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
                >
                  No active governance warnings.
                </div>
              </div>
            </section>
          </aside>
        </div>
      </form>
    </div>
  </div>
</template>
