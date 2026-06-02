<script setup lang="ts">
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/shared/components/ui/accordion'
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/shared/components/ui/dialog'
import {
  Field,
  FieldDescription,
  FieldGroup,
  FieldLabel,
  FieldSet,
} from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import {
  defaultTemplateCta,
  templateCtaFieldTypes,
  templateCtaPresets,
  templateCtaTypes,
} from '@/shared/templates/cta-presets'
import type {
  TemplateContent,
  TemplateCtaConfig,
  TemplateCtaField,
  TemplateCtaFieldType,
  TemplateCtaType,
  TemplateFieldSchema,
  TemplateFieldOption,
} from '@/shared/types/templates'
import { Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

defineOptions({
  name: 'DynamicTemplateFields',
})

const props = defineProps<{
  schema: TemplateFieldSchema[]
  modelValue: TemplateContent
}>()

const emit = defineEmits<{
  'update:modelValue': [value: TemplateContent]
}>()

const repeaterDialogOpen = ref(false)
const activeRepeaterField = ref<TemplateFieldSchema | null>(null)
const activeRepeaterIndex = ref<number | null>(null)
const repeaterDraft = ref<TemplateContent>({})

const repeaterDialogTitle = computed(() => {
  const label = activeRepeaterField.value?.label ?? 'Item'

  return activeRepeaterIndex.value === null ? `Add ${label}` : `Edit ${label}`
})

type TemplateFieldSection = {
  value: string
  title: string
  description: string
  fields: TemplateFieldSchema[]
}

const fieldSectionMeta: Record<
  string,
  Pick<TemplateFieldSection, 'value' | 'title' | 'description'>
> = {
  content: {
    value: 'content-fields',
    title: 'Content Fields',
    description: 'Core text, links, choices, and simple values.',
  },
  media: {
    value: 'media-fields',
    title: 'Media Fields',
    description: 'Images and visual assets used by the template.',
  },
  lists: {
    value: 'list-fields',
    title: 'List Fields',
    description: 'Repeatable content groups such as services, menus, or projects.',
  },
  cta: {
    value: 'cta-fields',
    title: 'CTA Fields',
    description: 'Call-to-action setup and form fields.',
  },
}

const fieldSectionKey = (field: TemplateFieldSchema): string => {
  if (field.type === 'image') return 'media'
  if (field.type === 'repeater') return 'lists'
  if (field.type === 'cta') return 'cta'

  return 'content'
}

const fieldSections = computed<TemplateFieldSection[]>(() => {
  // Group fields by editing purpose so large template forms stay navigable.
  const grouped = props.schema.reduce<Record<string, TemplateFieldSchema[]>>((sections, field) => {
    const key = fieldSectionKey(field)
    sections[key] = [...(sections[key] ?? []), field]

    return sections
  }, {})

  return ['content', 'media', 'lists', 'cta']
    .filter((key) => grouped[key]?.length)
    .map((key) => ({
      ...fieldSectionMeta[key],
      fields: grouped[key],
    }))
})

const fieldId = (field: TemplateFieldSchema): string => `template-content-${field.key}`

const asRecord = (value: unknown): TemplateContent => {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as TemplateContent)
    : {}
}

const valueFor = (field: TemplateFieldSchema): unknown => {
  return props.modelValue?.[field.key] ?? field.default ?? defaultValueFor(field)
}

const defaultValueFor = (field: TemplateFieldSchema): unknown => {
  if (field.type === 'boolean') return false
  if (field.type === 'repeater') return []
  if (field.type === 'number') return null
  if (field.type === 'cta') return defaultTemplateCta()

  return ''
}

const defaultRowFor = (fields: TemplateFieldSchema[] = []): TemplateContent => {
  return fields.reduce<TemplateContent>((row, field) => {
    row[field.key] = field.default ?? defaultValueFor(field)

    return row
  }, {})
}

const updateField = (field: TemplateFieldSchema, value: unknown): void => {
  // Emit a new object so nested edits update parent previews.
  emit('update:modelValue', {
    ...(props.modelValue ?? {}),
    [field.key]: value,
  })
}

const updateNumberField = (field: TemplateFieldSchema, event: Event): void => {
  const value = (event.target as HTMLInputElement).value

  updateField(field, value === '' ? null : Number(value))
}

const updateImageField = (field: TemplateFieldSchema, event: Event): void => {
  const file = (event.target as HTMLInputElement).files?.[0]

  if (!file) return

  const reader = new FileReader()
  reader.onload = () => updateField(field, String(reader.result))
  reader.readAsDataURL(file)
}

const repeaterRows = (field: TemplateFieldSchema): TemplateContent[] => {
  const value = valueFor(field)

  return Array.isArray(value) ? value.map((row) => asRecord(row)) : []
}

const openRepeaterDialog = (field: TemplateFieldSchema, rowIndex: number | null = null): void => {
  // Keep new and existing repeater rows editable in a dialog instead of expanding the page.
  activeRepeaterField.value = field
  activeRepeaterIndex.value = rowIndex
  repeaterDraft.value =
    rowIndex === null ? defaultRowFor(field.fields) : { ...repeaterRows(field)[rowIndex] }
  repeaterDialogOpen.value = true
}

const saveRepeaterDialog = (): void => {
  if (!activeRepeaterField.value) return

  const rows = [...repeaterRows(activeRepeaterField.value)]

  if (activeRepeaterIndex.value === null) {
    rows.push(repeaterDraft.value)
  } else {
    rows[activeRepeaterIndex.value] = repeaterDraft.value
  }

  updateField(activeRepeaterField.value, rows)
  repeaterDialogOpen.value = false
}

const removeRepeaterRow = (field: TemplateFieldSchema, rowIndex: number): void => {
  updateField(
    field,
    repeaterRows(field).filter((_, index) => index !== rowIndex)
  )
}

const ctaConfigFor = (field: TemplateFieldSchema): TemplateCtaConfig => {
  const value = asRecord(valueFor(field))
  const type = templateCtaTypes.includes(value.type as TemplateCtaType)
    ? (value.type as TemplateCtaType)
    : 'contact_message'
  const fallback = defaultTemplateCta(type)

  return {
    ...fallback,
    ...value,
    type,
    title: stringValue(value.title, fallback.title),
    description: stringValue(value.description, fallback.description),
    submit_label: stringValue(value.submit_label, fallback.submit_label),
    success_message: stringValue(value.success_message, fallback.success_message),
    fields: ctaFieldsFor(value.fields, fallback.fields),
  }
}

const stringValue = (value: unknown, fallback = ''): string => {
  return typeof value === 'string' ? value : fallback
}

const ctaFieldsFor = (value: unknown, fallback: TemplateCtaField[] = []): TemplateCtaField[] => {
  return Array.isArray(value) ? value.map((row) => normalizeCtaField(row)) : fallback
}

const normalizeCtaField = (value: unknown): TemplateCtaField => {
  const row = asRecord(value)
  const type = templateCtaFieldTypes.includes(row.type as TemplateCtaFieldType)
    ? (row.type as TemplateCtaFieldType)
    : 'text'

  return {
    key: stringValue(row.key, 'field'),
    label: stringValue(row.label, 'Field'),
    type,
    required: Boolean(row.required),
    placeholder: stringValue(row.placeholder),
    options: ctaFieldOptions(row.options),
  }
}

const ctaFieldOptions = (value: unknown): TemplateFieldOption[] => {
  return Array.isArray(value)
    ? value
        .map((option) => asRecord(option))
        .map((option) => ({
          label: stringValue(option.label, String(option.value ?? 'Option')),
          value: stringValue(option.value, String(option.label ?? 'option')),
        }))
    : []
}

const ctaFieldOptionsText = (field: TemplateCtaField): string => {
  return (field.options ?? []).map((option) => `${option.label}:${option.value}`).join('\n')
}

const parseCtaFieldOptions = (value: string): TemplateFieldOption[] => {
  return value
    .split(/\r?\n|,/)
    .map((option) => option.trim())
    .filter(Boolean)
    .map((option) => {
      const [label, rawValue] = option.split(':')

      return {
        label: label.trim(),
        value: (rawValue ?? label).trim(),
      }
    })
}

const slugKey = (value: string): string => {
  return (
    value
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '') || 'field'
  )
}

const updateCta = (field: TemplateFieldSchema, value: Partial<TemplateCtaConfig>): void => {
  updateField(field, {
    ...ctaConfigFor(field),
    ...value,
  })
}

const loadCtaPreset = (field: TemplateFieldSchema, type?: TemplateCtaType): void => {
  const current = ctaConfigFor(field)
  const nextType = type ?? current.type
  const next = defaultTemplateCta(nextType)

  updateField(field, {
    ...next,
    redirect_url: current.redirect_url,
    recipient_email: current.recipient_email,
    downloadable_file: current.downloadable_file,
  })
}

const loadCtaPresetFromValue = (field: TemplateFieldSchema, value: unknown): void => {
  const type = templateCtaTypes.includes(value as TemplateCtaType)
    ? (value as TemplateCtaType)
    : 'contact_message'

  loadCtaPreset(field, type)
}

const addCtaField = (field: TemplateFieldSchema): void => {
  const config = ctaConfigFor(field)

  updateCta(field, {
    fields: [
      ...config.fields,
      {
        key: `field_${config.fields.length + 1}`,
        label: 'New Field',
        type: 'text',
        required: false,
        placeholder: '',
      },
    ],
  })
}

const updateCtaCustomField = (
  field: TemplateFieldSchema,
  index: number,
  key: keyof TemplateCtaField,
  value: unknown
): void => {
  const config = ctaConfigFor(field)
  const fields = [...config.fields]
  const current = { ...fields[index] }

  if (key === 'label' && current.key.startsWith('field_')) {
    current.key = slugKey(String(value))
  }

  fields[index] = { ...current, [key]: value }
  updateCta(field, { fields })
}

const removeCtaCustomField = (field: TemplateFieldSchema, index: number): void => {
  const config = ctaConfigFor(field)

  updateCta(field, {
    fields: config.fields.filter((_, fieldIndex) => fieldIndex !== index),
  })
}
</script>

<template>
  <FieldGroup>
    <FieldSet>
      <Accordion type="multiple" :default-value="[]" class="space-y-2">
        <AccordionItem
          v-for="section in fieldSections"
          :key="section.value"
          :value="section.value"
          class="rounded border bg-background px-3 last:border-b"
        >
          <AccordionTrigger class="py-3 hover:no-underline">
            <span>
              <span class="block text-sm font-semibold">{{ section.title }}</span>
              <span class="mt-1 block text-xs text-muted-foreground">
                {{ section.description }}
              </span>
            </span>
          </AccordionTrigger>
          <AccordionContent class="pb-3">
            <div class="grid gap-4 md:grid-cols-2">
              <template v-for="field in section.fields" :key="field.key">
                <Field
                  v-if="field.type === 'boolean'"
                  orientation="horizontal"
                  class="items-center gap-3 self-end"
                >
                  <Checkbox
                    :id="fieldId(field)"
                    :model-value="Boolean(valueFor(field))"
                    @update:model-value="updateField(field, Boolean($event))"
                  />
                  <div>
                    <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
                    <FieldDescription v-if="field.description">
                      {{ field.description }}
                    </FieldDescription>
                  </div>
                </Field>

                <Field
                  v-else-if="field.type === 'textarea' || field.type === 'rich_text'"
                  class="md:col-span-2"
                >
                  <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
                  <Textarea
                    :id="fieldId(field)"
                    :model-value="String(valueFor(field) ?? '')"
                    :placeholder="field.placeholder"
                    class="min-h-28"
                    :required="field.required"
                    @update:model-value="updateField(field, $event)"
                  />
                  <FieldDescription v-if="field.description">{{
                    field.description
                  }}</FieldDescription>
                </Field>

                <Field v-else-if="field.type === 'select'">
                  <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
                  <NativeSelect
                    :id="fieldId(field)"
                    class="w-full"
                    :model-value="String(valueFor(field) ?? '')"
                    :required="field.required"
                    @update:model-value="updateField(field, $event)"
                  >
                    <NativeSelectOption value="">Select...</NativeSelectOption>
                    <NativeSelectOption
                      v-for="option in field.options ?? []"
                      :key="String(option.value)"
                      :value="String(option.value)"
                    >
                      {{ option.label }}
                    </NativeSelectOption>
                  </NativeSelect>
                  <FieldDescription v-if="field.description">{{
                    field.description
                  }}</FieldDescription>
                </Field>

                <Field v-else-if="field.type === 'cta'" class="md:col-span-2">
                  <div class="space-y-5 rounded border bg-background p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                      <div>
                        <FieldLabel>{{ field.label }}</FieldLabel>
                        <FieldDescription v-if="field.description">
                          {{ field.description }}
                        </FieldDescription>
                      </div>
                      <Button
                        size="sm"
                        variant="restore"
                        type="button"
                        @click="loadCtaPreset(field)"
                      >
                        Load preset defaults
                      </Button>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-type`">CTA Type</FieldLabel>
                        <NativeSelect
                          :id="`${fieldId(field)}-type`"
                          class="w-full"
                          :model-value="ctaConfigFor(field).type"
                          @update:model-value="loadCtaPresetFromValue(field, $event)"
                        >
                          <NativeSelectOption
                            v-for="type in templateCtaTypes"
                            :key="type"
                            :value="type"
                          >
                            {{ templateCtaPresets[type].label }}
                          </NativeSelectOption>
                        </NativeSelect>
                      </Field>

                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-title`">Title</FieldLabel>
                        <Input
                          :id="`${fieldId(field)}-title`"
                          :model-value="ctaConfigFor(field).title"
                          @update:model-value="updateCta(field, { title: String($event) })"
                        />
                      </Field>

                      <Field class="md:col-span-2">
                        <FieldLabel :for="`${fieldId(field)}-description`">Description</FieldLabel>
                        <Textarea
                          :id="`${fieldId(field)}-description`"
                          :model-value="ctaConfigFor(field).description"
                          class="min-h-20"
                          @update:model-value="updateCta(field, { description: String($event) })"
                        />
                      </Field>

                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-submit-label`"
                          >Submit Label</FieldLabel
                        >
                        <Input
                          :id="`${fieldId(field)}-submit-label`"
                          :model-value="ctaConfigFor(field).submit_label"
                          @update:model-value="updateCta(field, { submit_label: String($event) })"
                        />
                      </Field>

                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-success-message`"
                          >Success Message</FieldLabel
                        >
                        <Input
                          :id="`${fieldId(field)}-success-message`"
                          :model-value="ctaConfigFor(field).success_message"
                          @update:model-value="
                            updateCta(field, { success_message: String($event) })
                          "
                        />
                      </Field>

                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-recipient-email`"
                          >Recipient Email</FieldLabel
                        >
                        <Input
                          :id="`${fieldId(field)}-recipient-email`"
                          type="email"
                          :model-value="ctaConfigFor(field).recipient_email ?? ''"
                          @update:model-value="
                            updateCta(field, { recipient_email: String($event) })
                          "
                        />
                      </Field>

                      <Field>
                        <FieldLabel :for="`${fieldId(field)}-redirect-url`"
                          >Redirect URL</FieldLabel
                        >
                        <Input
                          :id="`${fieldId(field)}-redirect-url`"
                          type="url"
                          :model-value="ctaConfigFor(field).redirect_url ?? ''"
                          @update:model-value="updateCta(field, { redirect_url: String($event) })"
                        />
                      </Field>

                      <Field
                        v-if="ctaConfigFor(field).type === 'file_download'"
                        class="md:col-span-2"
                      >
                        <FieldLabel :for="`${fieldId(field)}-downloadable-file`">
                          Downloadable File
                        </FieldLabel>
                        <Input
                          :id="`${fieldId(field)}-downloadable-file`"
                          type="url"
                          :model-value="ctaConfigFor(field).downloadable_file ?? ''"
                          @update:model-value="
                            updateCta(field, { downloadable_file: String($event) })
                          "
                        />
                      </Field>
                    </div>

                    <div class="space-y-3">
                      <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                          <p class="text-sm font-medium text-foreground">Fields</p>
                          <p class="text-xs text-muted-foreground">
                            Preset fields are editable when this CTA is a custom form.
                          </p>
                        </div>
                        <Button
                          v-if="ctaConfigFor(field).type === 'custom_form'"
                          size="sm"
                          variant="create"
                          type="button"
                          @click="addCtaField(field)"
                        >
                          <Plus class="size-4" />
                          Add field
                        </Button>
                      </div>

                      <div
                        v-if="ctaConfigFor(field).type !== 'custom_form'"
                        class="grid gap-2 md:grid-cols-2"
                      >
                        <div
                          v-for="ctaField in ctaConfigFor(field).fields"
                          :key="ctaField.key"
                          class="rounded border p-3 text-sm"
                        >
                          <span class="font-medium">{{ ctaField.label }}</span>
                          <span class="ml-2 text-xs text-muted-foreground">
                            {{ ctaField.type }}{{ ctaField.required ? ', required' : '' }}
                          </span>
                        </div>
                      </div>

                      <div v-else class="space-y-3">
                        <div
                          v-for="(ctaField, ctaFieldIndex) in ctaConfigFor(field).fields"
                          :key="`${ctaField.key}-${ctaFieldIndex}`"
                          class="grid gap-3 rounded border p-3 md:grid-cols-2"
                        >
                          <Field>
                            <FieldLabel>Label</FieldLabel>
                            <Input
                              :model-value="ctaField.label"
                              @update:model-value="
                                updateCtaCustomField(field, ctaFieldIndex, 'label', String($event))
                              "
                            />
                          </Field>
                          <Field>
                            <FieldLabel>Key</FieldLabel>
                            <Input
                              :model-value="ctaField.key"
                              @update:model-value="
                                updateCtaCustomField(
                                  field,
                                  ctaFieldIndex,
                                  'key',
                                  slugKey(String($event))
                                )
                              "
                            />
                          </Field>
                          <Field>
                            <FieldLabel>Type</FieldLabel>
                            <NativeSelect
                              class="w-full"
                              :model-value="ctaField.type"
                              @update:model-value="
                                updateCtaCustomField(field, ctaFieldIndex, 'type', $event)
                              "
                            >
                              <NativeSelectOption
                                v-for="type in templateCtaFieldTypes"
                                :key="type"
                                :value="type"
                              >
                                {{ type }}
                              </NativeSelectOption>
                            </NativeSelect>
                          </Field>
                          <Field orientation="horizontal" class="items-center gap-3 self-end">
                            <Checkbox
                              :model-value="Boolean(ctaField.required)"
                              @update:model-value="
                                updateCtaCustomField(
                                  field,
                                  ctaFieldIndex,
                                  'required',
                                  Boolean($event)
                                )
                              "
                            />
                            <FieldLabel>Required</FieldLabel>
                          </Field>
                          <Field>
                            <FieldLabel>Placeholder</FieldLabel>
                            <Input
                              :model-value="ctaField.placeholder ?? ''"
                              @update:model-value="
                                updateCtaCustomField(
                                  field,
                                  ctaFieldIndex,
                                  'placeholder',
                                  String($event)
                                )
                              "
                            />
                          </Field>
                          <Field v-if="ctaField.type === 'select'">
                            <FieldLabel>Options</FieldLabel>
                            <Textarea
                              :model-value="ctaFieldOptionsText(ctaField)"
                              placeholder="Label:value"
                              class="min-h-20"
                              @update:model-value="
                                updateCtaCustomField(
                                  field,
                                  ctaFieldIndex,
                                  'options',
                                  parseCtaFieldOptions(String($event))
                                )
                              "
                            />
                          </Field>
                          <div class="md:col-span-2">
                            <Button
                              size="sm"
                              variant="ghost"
                              type="button"
                              @click="removeCtaCustomField(field, ctaFieldIndex)"
                            >
                              <Trash2 class="size-4" />
                              Remove field
                            </Button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </Field>

                <Field v-else-if="field.type === 'repeater'" class="md:col-span-2">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <FieldLabel>{{ field.label }}</FieldLabel>
                      <FieldDescription v-if="field.description">{{
                        field.description
                      }}</FieldDescription>
                    </div>
                    <Button
                      size="sm"
                      variant="create"
                      type="button"
                      @click="openRepeaterDialog(field)"
                    >
                      <Plus class="size-4" />
                      Add
                    </Button>
                  </div>

                  <div class="mt-3 space-y-2">
                    <div
                      v-for="(row, rowIndex) in repeaterRows(field)"
                      :key="rowIndex"
                      class="flex items-center justify-between gap-3 rounded border bg-background p-3"
                    >
                      <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ field.label }} {{ rowIndex + 1 }}</p>
                        <p class="mt-1 truncate text-xs text-muted-foreground">
                          Click edit to manage this item.
                        </p>
                      </div>
                      <div class="flex shrink-0 items-center gap-1">
                        <Button
                          size="sm"
                          variant="edit"
                          type="button"
                          @click="openRepeaterDialog(field, rowIndex)"
                        >
                          Edit
                        </Button>
                        <Button
                          size="sm"
                          variant="ghost"
                          type="button"
                          @click="removeRepeaterRow(field, rowIndex)"
                        >
                          <Trash2 class="size-4" />
                        </Button>
                      </div>
                    </div>
                  </div>
                </Field>

                <Field v-else>
                  <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
                  <Input
                    v-if="field.type === 'number'"
                    :id="fieldId(field)"
                    type="number"
                    :model-value="String(valueFor(field) ?? '')"
                    :placeholder="field.placeholder"
                    :min="field.min"
                    :max="field.max"
                    :step="field.step"
                    :required="field.required"
                    @input="updateNumberField(field, $event)"
                  />
                  <div v-else-if="field.type === 'image'" class="space-y-2">
                    <Input
                      :id="fieldId(field)"
                      type="url"
                      :model-value="String(valueFor(field) ?? '')"
                      :placeholder="field.placeholder"
                      :required="field.required"
                      @update:model-value="updateField(field, $event)"
                    />
                    <Input
                      type="file"
                      accept="image/*"
                      class="cursor-pointer text-muted-foreground"
                      @change="updateImageField(field, $event)"
                    />
                  </div>
                  <Input
                    v-else
                    :id="fieldId(field)"
                    :type="
                      field.type === 'url' || field.type === 'email' || field.type === 'color'
                        ? field.type
                        : field.type === 'phone'
                          ? 'tel'
                          : 'text'
                    "
                    :model-value="String(valueFor(field) ?? '')"
                    :placeholder="field.placeholder"
                    :required="field.required"
                    @update:model-value="updateField(field, $event)"
                  />
                  <FieldDescription v-if="field.description">{{
                    field.description
                  }}</FieldDescription>
                </Field>
              </template>
            </div>
          </AccordionContent>
        </AccordionItem>
      </Accordion>
    </FieldSet>
  </FieldGroup>

  <Dialog :open="repeaterDialogOpen" @update:open="repeaterDialogOpen = $event">
    <DialogScrollContent class="max-w-[calc(100%-2rem)] sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ repeaterDialogTitle }}</DialogTitle>
        <DialogDescription>Manage the fields for this list item.</DialogDescription>
      </DialogHeader>

      <DynamicTemplateFields
        v-if="activeRepeaterField"
        :schema="activeRepeaterField.fields ?? []"
        :model-value="repeaterDraft"
        @update:model-value="repeaterDraft = $event"
      />

      <DialogFooter>
        <Button variant="cancel" type="button" @click="repeaterDialogOpen = false"> Cancel </Button>
        <Button variant="create" type="button" @click="saveRepeaterDialog">Save</Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>
