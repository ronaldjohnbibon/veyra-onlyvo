<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { useConfirmStore } from '@/shared/stores/confirm-store'
import { useToastStore } from '@/shared/stores/toast-store'
import { Textarea } from '@/shared/components/ui/textarea'
import TimezoneCombobox from '@/shared/components/TimezoneCombobox.vue'
import SystemSettingHistoryTable from '@/tenant/system-settings/components/SystemSettingHistoryTable.vue'
import { useTenantSystemSettingStore } from '@/tenant/system-settings/system-setting-store'
import type {
  SystemSettingGroup,
  SystemSettingItem,
  SystemSettingValue,
  SystemSettingsPayload,
} from '@/tenant/system-settings/types'
import {
  BarChart3,
  Bell,
  Brush,
  CheckCircle2,
  CircleAlert,
  Clock,
  ExternalLink,
  FileText,
  Globe2,
  History,
  IdCard,
  Info,
  RotateCcw,
  Save,
  Search,
  ShieldCheck,
  SlidersHorizontal,
  Upload,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'

const settingStore = useTenantSystemSettingStore()
const confirmStore = useConfirmStore()
const toastStore = useToastStore()
const form = reactive<SystemSettingsPayload>({})
const formError = ref('')
const saveNotice = ref('')
const savedSnapshot = ref('')
const activeSection = ref('profile')
const imageUploadErrors = reactive<Record<string, string>>({})
const imageUploading = reactive<Record<string, boolean>>({})
const imagePreviewFailed = reactive<Record<string, boolean>>({})
const imageLocalPreviews = reactive<Record<string, string>>({})

const iconMap: Record<string, Component> = {
  profile: IdCard,
  website: Globe2,
  seo: Search,
  branding: Brush,
  analytics: BarChart3,
  notifications: Bell,
  compliance: FileText,
  history: History,
}

const sectionMeta: Record<
  string,
  {
    label: string
    title: string
    description: string
    task: string
  }
> = {
  profile: {
    label: 'Profile',
    title: 'Business Profile',
    description: 'Tell visitors who you are and where they can reach you.',
    task: 'Complete your public business name, logo, and contact details.',
  },
  website: {
    label: 'Launch',
    title: 'Launch Controls',
    description: 'Control whether the public site is live and how visitors take action.',
    task: 'Confirm the site status, homepage slug, CTA, forms, and footer copy.',
  },
  seo: {
    label: 'SEO',
    title: 'Search and Sharing',
    description: 'Set the default title, description, and sharing image for public pages.',
    task: 'Write clear search copy and confirm indexing rules.',
  },
  branding: {
    label: 'Branding',
    title: 'Brand Style',
    description: 'Choose the colors, typography, and fallback media used across templates.',
    task: 'Match the site style to your brand before launch.',
  },
  notifications: {
    label: 'Notifications',
    title: 'Notification Routing',
    description: 'Choose where lead and design request emails should go.',
    task: 'Send follow-up alerts to the right inboxes.',
  },
  compliance: {
    label: 'Compliance',
    title: 'Legal and Consent',
    description: 'Connect required policy pages and cookie notice text.',
    task: 'Make privacy, terms, and cookie messaging easy to find.',
  },
  analytics: {
    label: 'Analytics',
    title: 'Tracking and Retention',
    description: 'Control visitor, CTA, and data retention settings.',
    task: 'Keep growth data useful without retaining it longer than needed.',
  },
  history: {
    label: 'History',
    title: 'Change History',
    description: 'Review recent setting updates and who made them.',
    task: 'Audit recent changes to public site settings.',
  },
}

const fieldCopy: Record<string, string> = {
  'profile.business_name':
    'Shown as your public business name and used as a fallback in page titles.',
  'profile.description': 'A plain-language summary visitors and search engines can understand.',
  'profile.logo':
    'Used in templates that support a logo. A transparent PNG or SVG usually works best.',
  'profile.favicon': 'The small icon shown in browser tabs. Square images work best.',
  'profile.timezone': 'Used for reports, analytics windows, and date-based activity.',
  'profile.contact_email':
    'The public email visitors can use if templates display contact details.',
  'profile.contact_phone': 'Optional public phone number for templates that show phone contact.',
  'profile.contact_address': 'Optional public address for templates that show location details.',
  'website.site_status':
    'Draft keeps the public site unavailable; Live makes published content reachable.',
  'website.homepage_slug':
    'Leave blank to use the default published template, or enter a custom slug.',
  'website.primary_cta_label': 'The default button text templates can use for primary actions.',
  'website.primary_cta_url': 'Where the default call-to-action button should send visitors.',
  'website.contact_form_enabled':
    'Turn this on when visitors should be able to submit contact forms.',
  'website.footer_text': 'Small footer or copyright text shown on public pages where supported.',
  'seo.default_meta_title':
    'Keep this specific and readable; it can appear in browser tabs and search results.',
  'seo.default_meta_description': 'A short summary for search previews and social cards.',
  'seo.open_graph_image':
    'The image used when pages are shared and no page-specific image is available.',
  'seo.allow_search_engine_indexing':
    'Turn off while preparing the site, then turn on when ready to be found.',
  'seo.canonical_domain':
    'Your preferred public domain, such as example.com or https://example.com.',
  'branding.primary_color': 'Main brand color used for important actions and accents.',
  'branding.accent_color': 'Secondary brand color used for highlights and supporting accents.',
  'branding.font_family': 'Default type style templates can use for public pages.',
  'branding.button_radius': 'Default corner shape for buttons in templates that support it.',
  'branding.fallback_image':
    'Used when a template needs an image but no content-specific image exists.',
  'analytics.enable_visitor_tracking':
    'Records visits so the dashboard can show traffic and growth signals.',
  'analytics.enable_cta_tracking':
    'Records CTA views, clicks, and submissions for conversion insights.',
  'analytics.retention_days': 'How long tenant analytics records should be kept.',
  'notifications.cta_submission_email': 'Inbox for new form submission and lead alerts.',
  'notifications.design_request_email':
    'Inbox for design request updates and collaboration alerts.',
  'notifications.reply_to_email':
    'Address used when recipients reply to tenant notification emails.',
  'notifications.weekly_analytics_summary':
    'Send a weekly performance summary when scheduled email jobs are enabled.',
  'compliance.privacy_policy_url': 'Link to the privacy policy visitors should be able to access.',
  'compliance.terms_of_service_url':
    'Link to terms or service rules visitors should be able to access.',
  'compliance.cookie_notice_enabled': 'Shows cookie messaging where public templates support it.',
  'compliance.cookie_notice_text': 'Short, readable notice shown when cookie messaging is enabled.',
}

const sections = computed(() => [
  ...settingStore.groups.map((group) => ({
    key: group.key,
    label: sectionMeta[group.key]?.label ?? group.label,
    icon: iconMap[group.key] ?? SlidersHorizontal,
  })),
  { key: 'history', label: 'History', icon: History },
])

const currentGroup = computed<SystemSettingGroup | null>(() => {
  return settingStore.groups.find((group) => group.key === activeSection.value) ?? null
})

const activeSectionLabel = computed(() => {
  return sections.value.find((section) => section.key === activeSection.value)?.label ?? 'Settings'
})

const currentSectionMeta = computed(() => {
  return (
    sectionMeta[activeSection.value] ?? {
      label: activeSectionLabel.value,
      title: activeSectionLabel.value,
      description: 'Review and update this group of tenant settings.',
      task: 'Keep these settings accurate before launch.',
    }
  )
})

const settingHelp = (setting: SystemSettingItem): string => {
  return settingDescription(setting)
}

const settingDescription = (setting: SystemSettingItem): string => {
  return fieldCopy[setting.key] ?? setting.description ?? 'Enter the value for this setting.'
}

const getGroupName = (setting: SystemSettingItem): string => setting.key.split('.')[0] ?? ''

const ensureGroup = (group: string): void => {
  form[group] ??= {}
}

const getSettingValue = (setting: SystemSettingItem): SystemSettingValue => {
  const group = getGroupName(setting)

  return form[group]?.[setting.name] ?? setting.value ?? ''
}

const setSettingValue = (setting: SystemSettingItem, value: SystemSettingValue): void => {
  const group = getGroupName(setting)
  ensureGroup(group)
  form[group][setting.name] = value
}

const hydrateForm = (): void => {
  for (const group of settingStore.groups) {
    ensureGroup(group.key)

    for (const setting of group.settings) {
      form[group.key][setting.name] = setting.value
    }
  }

  if (!settingStore.groups.some((group) => group.key === activeSection.value)) {
    activeSection.value = settingStore.groups[0]?.key ?? 'profile'
  }
}

const syncSavedSnapshot = (): void => {
  savedSnapshot.value = JSON.stringify(payloadForSave())
}

const fieldId = (setting: SystemSettingItem): string => {
  return `tenant-setting-${setting.key.replace(/[^a-z0-9]+/gi, '-')}`
}

const fieldError = (setting: SystemSettingItem): string | null => {
  return settingStore.errors[`settings.${setting.key}`]?.[0] ?? null
}

const hasFieldError = (setting: SystemSettingItem): boolean => Boolean(fieldError(setting))

const sectionErrorCount = (section: string): number => {
  if (section === 'history') return 0

  const prefix = `settings.${section}.`

  return Object.keys(settingStore.errors).filter((key) => key.startsWith(prefix)).length
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

const completionCount = (group: SystemSettingGroup): number => {
  return group.settings.filter((setting) => {
    if (setting.type === 'boolean') return true

    return stringValue(setting).trim() !== ''
  }).length
}

const sectionCompletion = computed(() => {
  if (!currentGroup.value) return null

  const total = currentGroup.value.settings.length
  const completed = completionCount(currentGroup.value)

  return { completed, total }
})

const titlePreview = computed(() => {
  return (
    stringForKey('seo.default_meta_title') ||
    stringForKey('profile.business_name') ||
    'Your site title'
  )
})

const descriptionPreview = computed(() => {
  return (
    stringForKey('seo.default_meta_description') ||
    stringForKey('profile.description') ||
    'Add a short description so visitors know what your site offers.'
  )
})

const publicDomainPreview = computed(() => {
  return (
    stringForKey('seo.canonical_domain') || stringForKey('website.homepage_slug') || 'your-site'
  )
})

const primaryColor = computed(() => stringForKey('branding.primary_color') || '#2563eb')
const accentColor = computed(() => stringForKey('branding.accent_color') || '#10b981')

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

const payloadForSave = (): SystemSettingsPayload => {
  const payload: SystemSettingsPayload = {}

  for (const group of settingStore.groups) {
    const usesUnchangedTemplateDefaults =
      group.key === 'branding' &&
      settingStore.brandingUsesTemplateDefaults &&
      group.settings.every((setting) => getSettingValue(setting) === setting.value)

    if (usesUnchangedTemplateDefaults) continue

    payload[group.key] = {}

    for (const setting of group.settings) {
      const value = getSettingValue(setting)
      payload[group.key][setting.name] = setting.type === 'integer' ? Number(value || 0) : value
    }
  }

  return payload
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

const resetBranding = async (): Promise<void> => {
  const confirmed = await confirmStore.confirm(
    'Use the original branding from each template? Your tenant colors, typography, button style, and fallback image overrides will be removed.'
  )

  if (!confirmed) return

  try {
    formError.value = ''
    saveNotice.value = ''
    await settingStore.resetBranding()
    hydrateForm()
    syncSavedSnapshot()
    saveNotice.value = 'Template defaults restored'
    toastStore.addAlert(
      'success',
      'Template branding restored',
      'Templates now use their original default design settings.'
    )
  } catch {
    formError.value = 'Unable to restore the template branding defaults.'
  }
}

const siteStatus = computed(() => String(form.website?.site_status || 'live'))
const visitorTracking = computed(() => Boolean(form.analytics?.enable_visitor_tracking))

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
                Tenant-friendly launch, brand, SEO, and growth controls.
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  siteStatus === 'live'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-800'
                    : 'border-amber-300 bg-amber-50 text-amber-800'
                "
              >
                Site {{ siteStatus === 'live' ? 'Live' : 'Draft' }}
              </span>
              <span
                class="rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  visitorTracking
                    ? 'border-sky-200 bg-sky-50 text-sky-800'
                    : 'border-muted bg-muted/50 text-muted-foreground'
                "
              >
                Tracking {{ visitorTracking ? 'On' : 'Off' }}
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

        <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)_24rem]">
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

            <div
              v-if="currentGroup && sectionCompletion"
              class="mt-3 rounded border bg-muted/30 p-3"
            >
              <p class="text-xs font-medium text-muted-foreground">Section progress</p>
              <p class="mt-1 text-sm font-semibold">
                {{ sectionCompletion.completed }} of {{ sectionCompletion.total }} settings filled
              </p>
              <div class="mt-3 h-2 rounded bg-muted">
                <div
                  class="h-2 rounded bg-primary"
                  :style="{
                    width: `${Math.round((sectionCompletion.completed / Math.max(sectionCompletion.total, 1)) * 100)}%`,
                  }"
                />
              </div>
            </div>
          </aside>

          <main class="rounded border bg-background">
            <div class="border-b px-4 py-4">
              <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                <div class="flex gap-3">
                  <div
                    class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary"
                  >
                    <component
                      :is="sections.find((section) => section.key === activeSection)?.icon"
                      class="size-5"
                    />
                  </div>
                  <div>
                    <h3 class="text-base font-semibold">{{ currentSectionMeta.title }}</h3>
                    <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                      {{ currentSectionMeta.description }}
                    </p>
                  </div>
                </div>
                <div v-if="currentGroup" class="flex flex-wrap items-center gap-2">
                  <Badge v-if="activeSection === 'branding'" variant="outline">
                    {{
                      settingStore.brandingUsesTemplateDefaults
                        ? 'Template defaults'
                        : 'Custom branding'
                    }}
                  </Badge>
                  <Badge variant="outline">{{ currentGroup.settings.length }} settings</Badge>
                  <Button
                    v-if="activeSection === 'branding'"
                    variant="outline_default"
                    size="sm"
                    type="button"
                    :disabled="settingStore.loading || settingStore.brandingUsesTemplateDefaults"
                    @click="resetBranding"
                  >
                    <RotateCcw class="size-4" />
                    Use Template Defaults
                  </Button>
                </div>
              </div>

              <div class="mt-4 rounded border bg-muted/30 p-3">
                <div class="flex gap-2 text-sm">
                  <Info class="mt-0.5 size-4 shrink-0 text-primary" />
                  <span>{{ currentSectionMeta.task }}</span>
                </div>
              </div>
            </div>

            <FieldSet class="p-4">
              <section v-if="currentGroup">
                <FieldGroup>
                  <div class="grid gap-4">
                    <template v-for="setting in currentGroup.settings" :key="setting.key">
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
                        </div>
                        <p class="text-sm text-muted-foreground">
                          {{ settingDescription(setting) }}
                        </p>

                        <Textarea
                          v-field-help="settingHelp(setting)"
                          :id="fieldId(setting)"
                          :model-value="stringValue(setting)"
                          class="min-h-28 w-full"
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
                              placeholder="/storage/tenant-system-settings/logo.png"
                              class="h-9 w-full"
                              @update:model-value="setSettingValue(setting, String($event ?? ''))"
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
                              accept="image/*"
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
                          </div>
                          <p class="mb-3 text-sm text-muted-foreground">
                            {{ settingDescription(setting) }}
                          </p>

                          <TimezoneCombobox
                            v-if="setting.key === 'profile.timezone'"
                            :id="fieldId(setting)"
                            :model-value="stringValue(setting)"
                            :help="settingHelp(setting)"
                            @update:model-value="setSettingValue(setting, $event)"
                          />
                          <NativeSelect
                            v-field-help="settingHelp(setting)"
                            v-else-if="setting.type === 'select'"
                            :id="fieldId(setting)"
                            class="min-w-48"
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
                          <div v-else-if="setting.type === 'color'" class="flex w-full gap-2">
                            <Input
                              v-field-help="settingHelp(setting)"
                              :id="fieldId(setting)"
                              :model-value="stringValue(setting)"
                              type="color"
                              class="h-10 w-16 shrink-0 cursor-pointer p-1"
                              @update:model-value="setSettingValue(setting, String($event ?? ''))"
                            />
                            <Input
                              v-field-help="settingHelp(setting)"
                              :model-value="stringValue(setting)"
                              class="h-10 w-full font-mono"
                              @update:model-value="setSettingValue(setting, String($event ?? ''))"
                            />
                          </div>
                          <Input
                            v-field-help="settingHelp(setting)"
                            v-else
                            :id="fieldId(setting)"
                            :model-value="stringValue(setting)"
                            :type="
                              setting.type === 'integer'
                                ? 'number'
                                : setting.type === 'email'
                                  ? 'email'
                                  : setting.type === 'url'
                                    ? 'url'
                                    : 'text'
                            "
                            :min="setting.type === 'integer' ? 0 : undefined"
                            class="h-9 w-full"
                            @update:model-value="
                              setSettingValue(
                                setting,
                                setting.type === 'integer'
                                  ? Number($event || 0)
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
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'history'">
                <SystemSettingHistoryTable
                  :records="settingStore.history"
                  :total="settingStore.historyTotal"
                  :params="settingStore.historyParams"
                  :loading="settingStore.loading"
                  :action-options="['created', 'updated']"
                  @load="settingStore.loadHistory"
                />
              </section>
            </FieldSet>
          </main>

          <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Live Preview</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  A quick view of how key settings read to visitors.
                </p>
              </div>

              <div class="space-y-4 p-4">
                <div class="rounded border p-4">
                  <div class="flex items-center gap-3">
                    <img
                      v-if="stringForKey('profile.logo')"
                      :src="stringForKey('profile.logo')"
                      alt="Logo preview"
                      class="size-10 rounded border object-contain"
                    />
                    <div
                      v-else
                      class="flex size-10 items-center justify-center rounded border bg-muted text-sm font-semibold"
                    >
                      {{
                        (stringForKey('profile.business_name') || 'Site').slice(0, 1).toUpperCase()
                      }}
                    </div>
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold">
                        {{ stringForKey('profile.business_name') || 'Your business name' }}
                      </p>
                      <p class="truncate text-xs text-muted-foreground">
                        {{ stringForKey('profile.contact_email') || 'Add a public contact email' }}
                      </p>
                    </div>
                  </div>

                  <p class="mt-3 line-clamp-3 text-sm text-muted-foreground">
                    {{
                      stringForKey('profile.description') ||
                      'Add a short business description for public pages.'
                    }}
                  </p>
                </div>

                <div class="rounded border p-4">
                  <p class="text-xs font-medium text-muted-foreground">Search preview</p>
                  <p class="mt-2 line-clamp-1 text-sm text-blue-700">{{ titlePreview }}</p>
                  <p class="line-clamp-1 text-xs text-emerald-700">{{ publicDomainPreview }}</p>
                  <p class="mt-1 line-clamp-3 text-sm text-muted-foreground">
                    {{ descriptionPreview }}
                  </p>
                </div>

                <div class="rounded border p-4">
                  <p class="text-xs font-medium text-muted-foreground">Brand sample</p>
                  <div class="mt-3 flex items-center gap-3">
                    <div
                      class="size-10 rounded border"
                      :style="{ backgroundColor: primaryColor }"
                    />
                    <div class="size-10 rounded border" :style="{ backgroundColor: accentColor }" />
                    <Button
                      type="button"
                      size="sm"
                      :style="{ backgroundColor: primaryColor, color: '#ffffff' }"
                    >
                      {{ stringForKey('website.primary_cta_label') || 'Primary CTA' }}
                    </Button>
                  </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Forms</p>
                    <p class="mt-1 text-sm font-semibold">
                      {{ booleanForKey('website.contact_form_enabled') ? 'Enabled' : 'Disabled' }}
                    </p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Indexing</p>
                    <p class="mt-1 text-sm font-semibold">
                      {{
                        booleanForKey('seo.allow_search_engine_indexing') ? 'Allowed' : 'Blocked'
                      }}
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <ShieldCheck class="size-4 text-primary" />
                <h3 class="text-sm font-semibold">Launch Readiness</h3>
              </div>
              <div class="mt-3 space-y-2 text-sm">
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Site status</span>
                  <Badge :variant="siteStatus === 'live' ? 'success' : 'outline'">
                    {{ siteStatus === 'live' ? 'Live' : 'Draft' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3">
                  <span class="text-muted-foreground">Tracking</span>
                  <Badge :variant="visitorTracking ? 'success' : 'outline'">
                    {{ visitorTracking ? 'On' : 'Off' }}
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
                v-if="stringForKey('website.primary_cta_url')"
                :href="stringForKey('website.primary_cta_url')"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-primary"
              >
                Test primary CTA
                <ExternalLink class="size-3.5" />
              </a>
            </section>
          </aside>
        </div>
      </form>
    </div>
  </div>
</template>
