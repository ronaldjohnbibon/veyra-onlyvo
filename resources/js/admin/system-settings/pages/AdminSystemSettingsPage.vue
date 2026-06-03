<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { useAdminSystemSettingStore } from '@/admin/system-settings/system-setting-store'
import type {
  SystemSettingGroup,
  SystemSettingItem,
  SystemSettingValue,
  SystemSettingsPayload,
} from '@/shared/types/system-settings'
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
  Share2,
  Shield,
  SlidersHorizontal,
  ToggleLeft,
  Upload,
  Wrench,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, reactive, ref } from 'vue'

const settingStore = useAdminSystemSettingStore()
const form = reactive<SystemSettingsPayload>({})
const formError = ref('')
const activeSection = ref('general')
const imageUploadErrors = reactive<Record<string, string>>({})
const imageUploading = reactive<Record<string, boolean>>({})
const imagePreviewFailed = reactive<Record<string, boolean>>({})

const iconMap: Record<string, Component> = {
  general: AppWindow,
  authentication: KeyRound,
  security: Shield,
  tenant_defaults: Building2,
  feature_flags: ToggleLeft,
  email: Mail,
  analytics: BarChart3,
  storage: Database,
  maintenance: Wrench,
  seo: Globe2,
  compliance: FileText,
  social: Share2,
  history: History,
}

const sections = computed(() => [
  ...settingStore.groups.map((group) => ({
    key: group.key,
    label: group.label,
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

const settingHelp = (setting: SystemSettingItem): string => {
  return setting.description ?? 'Enter the value for this setting.'
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
    activeSection.value = settingStore.groups[0]?.key ?? 'general'
  }
}

const fieldId = (setting: SystemSettingItem): string => {
  return `setting-${setting.key.replace(/[^a-z0-9]+/gi, '-')}`
}

const fieldError = (setting: SystemSettingItem): string | null => {
  return settingStore.errors[`settings.${setting.key}`]?.[0] ?? null
}

const sectionErrorCount = (section: string): number => {
  if (section === 'history') return 0

  const prefix = `settings.${section}.`

  return Object.keys(settingStore.errors).filter((key) => key.startsWith(prefix)).length
}

const formatSettingValue = (value: SystemSettingValue): string => {
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

const stringValue = (setting: SystemSettingItem): string => {
  const value = getSettingValue(setting)

  return value === null || value === undefined ? '' : String(value)
}

const hasImagePreview = (setting: SystemSettingItem): boolean => {
  return Boolean(stringValue(setting)) && !imagePreviewFailed[setting.key]
}

const clearImage = (setting: SystemSettingItem): void => {
  imageUploadErrors[setting.key] = ''
  imagePreviewFailed[setting.key] = false
  setSettingValue(setting, '')
}

const markImagePreviewFailed = (setting: SystemSettingItem): void => {
  imagePreviewFailed[setting.key] = true
}

const uploadSettingImage = async (setting: SystemSettingItem, event: Event): Promise<void> => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  try {
    imageUploading[setting.key] = true
    imageUploadErrors[setting.key] = ''
    imagePreviewFailed[setting.key] = false
    setSettingValue(setting, await settingStore.uploadImage(setting.key, file))
  } catch {
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
    await settingStore.update(payloadForSave())
    hydrateForm()
  } catch {
    formError.value = 'Please check the highlighted settings and try again.'
  }
}

const maintenanceMode = computed(() => Boolean(form.maintenance?.maintenance_mode))
const registrationOpen = computed(() => Boolean(form.authentication?.allow_tenant_registration))

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
                  maintenanceMode
                    ? 'border-amber-300 bg-amber-50 text-amber-800'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-800'
                "
              >
                Maintenance {{ maintenanceMode ? 'On' : 'Off' }}
              </span>
              <span
                class="rounded border px-2.5 py-1 text-xs font-medium"
                :class="
                  registrationOpen
                    ? 'border-sky-200 bg-sky-50 text-sky-800'
                    : 'border-muted bg-muted/50 text-muted-foreground'
                "
              >
                Registration {{ registrationOpen ? 'Open' : 'Closed' }}
              </span>
              <Button variant="update" size="sm" type="submit" :disabled="settingStore.loading">
                <Save class="size-4" />
                {{ settingStore.loading ? 'Saving...' : 'Save Changes' }}
              </Button>
            </div>
          </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
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
              <section v-if="currentGroup">
                <FieldGroup>
                  <div class="grid gap-4 lg:grid-cols-2">
                    <template v-for="setting in currentGroup.settings" :key="setting.key">
                      <label
                        v-if="setting.type === 'boolean'"
                        :for="fieldId(setting)"
                        class="flex min-h-20 cursor-pointer items-center justify-between gap-4 rounded border bg-muted/20 p-4"
                      >
                        <span class="block text-sm font-semibold">{{ setting.label }}</span>
                        <Checkbox
                          v-field-help="settingHelp(setting)"
                          :id="fieldId(setting)"
                          :model-value="Boolean(getSettingValue(setting))"
                          @update:model-value="setSettingValue(setting, Boolean($event))"
                        />
                      </label>

                      <Field v-else-if="setting.type === 'text'" class="lg:col-span-2">
                        <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>

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

                      <Field v-else-if="setting.type === 'image'" class="lg:col-span-2">
                        <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>
                        <div
                          class="grid gap-4 rounded border bg-muted/20 p-3 md:grid-cols-[18rem_minmax(0,1fr)]"
                        >
                          <img
                            v-if="hasImagePreview(setting)"
                            :src="stringValue(setting)"
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
                        <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>

                        <NativeSelect
                          v-field-help="settingHelp(setting)"
                          v-if="setting.type === 'select'"
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
                                ? Number($event || 0)
                                : String($event ?? '')
                            )
                          "
                        />

                        <FieldError v-if="fieldError(setting)">
                          {{ fieldError(setting) }}
                        </FieldError>
                      </Field>
                    </template>
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
