<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Field, FieldError, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
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
  FileText,
  Globe2,
  History,
  IdCard,
  Save,
  Search,
  SlidersHorizontal,
  Upload,
  X,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, reactive, ref } from 'vue'

const settingStore = useTenantSystemSettingStore()
const form = reactive<SystemSettingsPayload>({})
const formError = ref('')
const activeSection = ref('profile')
const imageUploadErrors = reactive<Record<string, string>>({})
const imageUploading = reactive<Record<string, boolean>>({})
const imagePreviewFailed = reactive<Record<string, boolean>>({})

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
    activeSection.value = settingStore.groups[0]?.key ?? 'profile'
  }
}

const fieldId = (setting: SystemSettingItem): string => {
  return `tenant-setting-${setting.key.replace(/[^a-z0-9]+/gi, '-')}`
}

const fieldError = (setting: SystemSettingItem): string | null => {
  return settingStore.errors[`settings.${setting.key}`]?.[0] ?? null
}

const sectionErrorCount = (section: string): number => {
  if (section === 'history') return 0

  const prefix = `settings.${section}.`

  return Object.keys(settingStore.errors).filter((key) => key.startsWith(prefix)).length
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

const siteStatus = computed(() => String(form.website?.site_status || 'live'))
const visitorTracking = computed(() => Boolean(form.analytics?.enable_visitor_tracking))

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
                  <div class="grid gap-4 lg:grid-cols-1">
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

                      <Field v-else-if="setting.type === 'text'" class="lg:col-span-1">
                        <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>

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

                      <Field v-else-if="setting.type === 'image'" class="lg:col-span-1">
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
                        <FieldLabel :for="fieldId(setting)">{{ setting.label }}</FieldLabel>

                        <NativeSelect
                          v-field-help="settingHelp(setting)"
                          v-if="setting.type === 'select'"
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
                          :model-value="getSettingValue(setting) as string | number | null"
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

                        <FieldError v-if="fieldError(setting)">
                          {{ fieldError(setting) }}
                        </FieldError>
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
        </div>

        <p v-if="formError" class="text-sm font-medium text-destructive">
          {{ formError }}
        </p>
      </form>
    </div>
  </div>
</template>
