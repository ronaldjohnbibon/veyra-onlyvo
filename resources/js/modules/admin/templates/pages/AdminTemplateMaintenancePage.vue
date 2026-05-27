<script setup lang="ts">
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldDescription, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Textarea } from '@/components/ui/textarea'
import { useTemplateMaintenanceStore } from '@/modules/admin/templates/template-maintenance-store'
import DynamicTemplateFields from '@/modules/templates/components/DynamicTemplateFields.vue'
import type {
  TemplateContent,
  TemplateCatalogItem,
  TemplateCatalogPayload,
  TemplateFieldOption,
  TemplateFieldSchema,
  TemplateFieldType,
  WebsiteType,
  WebsiteTypePayload,
} from '@/types/templates'
import { ArrowDown, ArrowUp, Check, FileCode2, Plus, Save, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'

const maintenanceStore = useTemplateMaintenanceStore()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedCatalogItemId = ref<string | null>(null)
const formError = ref('')
const typeSlugTouched = ref(false)
const itemKeyTouched = ref(false)
const schemaFields = ref<TemplateFieldSchema[]>([])
const defaultContent = ref<TemplateContent>({})
const openSchemaFieldSections = ref<string[]>([])
// Tracks which details card should show the active edit border.
const activeFormSection = ref<'websiteType' | 'template'>('websiteType')

const fieldTypes: { label: string; value: TemplateFieldType }[] = [
  { label: 'Text', value: 'text' },
  { label: 'Textarea', value: 'textarea' },
  { label: 'Number', value: 'number' },
  { label: 'Image', value: 'image' },
  { label: 'URL', value: 'url' },
  { label: 'Rich Text', value: 'rich_text' },
  { label: 'Boolean', value: 'boolean' },
  { label: 'Select', value: 'select' },
  { label: 'Repeater/List', value: 'repeater' },
]

const nestedFieldTypes = computed(() => fieldTypes.filter((type) => type.value !== 'repeater'))

const slugify = (value: string): string => {
  return (
    value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'template'
  )
}

const fieldKeyFromLabel = (value: string): string => {
  return sanitizeFieldKey(value) || 'field'
}

const sanitizeFieldKey = (value: string): string => {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

const blankWebsiteType = (): WebsiteTypePayload => ({
  name: '',
  slug: '',
  description: '',
  is_active: true,
})

const blankCatalogItem = (): TemplateCatalogPayload => ({
  website_type_id: selectedWebsiteTypeId.value ?? '',
  key: '',
  name: '',
  description: '',
  preview_image: '',
  field_schema: [],
  default_content: {},
  is_active: true,
})

const websiteTypeForm = ref<WebsiteTypePayload>(blankWebsiteType())
const catalogItemForm = ref<TemplateCatalogPayload>(blankCatalogItem())

const selectedWebsiteType = computed<WebsiteType | null>(() => {
  return (
    maintenanceStore.websiteTypes.find((type) => type.id === selectedWebsiteTypeId.value) ?? null
  )
})

const selectedCatalogItem = computed<TemplateCatalogItem | null>(() => {
  return (
    maintenanceStore.catalogItems.find((item) => item.id === selectedCatalogItemId.value) ?? null
  )
})

const contentSchema = computed<TemplateFieldSchema[]>(() => {
  return schemaForContent(schemaFields.value)
})

const renderPath = computed(() => {
  if (!selectedWebsiteType.value?.slug || !catalogItemForm.value.key) return ''

  // The catalog key must match a real Vue file in this folder.
  return `resources/js/modules/templates/templates/${selectedWebsiteType.value.slug}/${catalogItemForm.value.key}.vue`
})

const blankSchemaField = (): TemplateFieldSchema => ({
  key: '',
  label: '',
  type: 'text',
  required: false,
  placeholder: '',
  default: '',
})

const defaultValueFor = (field: TemplateFieldSchema): unknown => {
  if (field.type === 'boolean') return false
  if (field.type === 'number') return null
  if (field.type === 'repeater') return []

  return ''
}

const normalizeInputValue = (field: TemplateFieldSchema, value: unknown): unknown => {
  if (field.type === 'boolean') return Boolean(value)

  if (field.type === 'number') {
    if (value === null || value === undefined || value === '') return null

    const numberValue = Number(value)
    return Number.isFinite(numberValue) ? numberValue : null
  }

  if (field.type === 'repeater') {
    return Array.isArray(value) ? value : []
  }

  return String(value ?? '')
}

const contentRecord = (value: unknown): TemplateContent => {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as TemplateContent)
    : {}
}

const contentForSchema = (
  fields: TemplateFieldSchema[],
  content: TemplateContent = {}
): TemplateContent => {
  return fields.reduce<TemplateContent>((nextContent, field) => {
    const key = field.key.trim()

    if (!key) return nextContent

    const existing = Object.prototype.hasOwnProperty.call(content, key)
      ? content[key]
      : field.default

    if (field.type === 'repeater') {
      const rows = Array.isArray(existing) ? existing : []
      nextContent[key] = rows.map((row) => contentForSchema(field.fields ?? [], contentRecord(row)))

      return nextContent
    }

    nextContent[key] = normalizeInputValue(field, existing ?? defaultValueFor(field))

    return nextContent
  }, {})
}

const schemaForContent = (fields: TemplateFieldSchema[]): TemplateFieldSchema[] => {
  return fields
    .filter((field) => field.key.trim() && field.label.trim())
    .map((field) => ({
      ...field,
      key: field.key.trim(),
      label: field.label.trim(),
      fields: field.fields ? schemaForContent(field.fields) : undefined,
    }))
}

const normalizeLoadedField = (field: TemplateFieldSchema): TemplateFieldSchema => {
  const normalized: TemplateFieldSchema = {
    ...blankSchemaField(),
    ...field,
    key: field.key ?? '',
    label: field.label ?? '',
    type: field.type ?? 'text',
    required: Boolean(field.required),
    placeholder: field.placeholder ?? '',
  }

  if (normalized.type === 'select') {
    normalized.options = normalized.options?.length
      ? normalized.options
      : [{ label: 'Option', value: 'option' }]
  }

  if (normalized.type === 'repeater') {
    normalized.fields = (normalized.fields ?? []).map(normalizeLoadedField)
  }

  normalized.default = normalizeInputValue(
    normalized,
    normalized.default ?? defaultValueFor(normalized)
  )

  return normalized
}

const normalizeLoadedSchema = (
  fields: TemplateFieldSchema[] | null | undefined
): TemplateFieldSchema[] => {
  return (fields ?? []).map(normalizeLoadedField)
}

const schemaFieldSectionValue = (index: number): string => {
  return `schema-field-${index}`
}

const syncDefaultContent = (): void => {
  // Keep default content aligned with the current schema keys.
  defaultContent.value = contentForSchema(schemaFields.value, defaultContent.value)
}

const addSchemaField = (fields: TemplateFieldSchema[]): void => {
  fields.push(blankSchemaField())

  if (fields === schemaFields.value) {
    openSchemaFieldSections.value = [schemaFieldSectionValue(fields.length - 1)]
  }

  syncDefaultContent()
}

const removeSchemaField = (
  fields: TemplateFieldSchema[],
  index: number,
  parentField: TemplateFieldSchema | null = null
): void => {
  const [removed] = fields.splice(index, 1)

  if (removed?.key && !parentField) {
    const nextContent = { ...defaultContent.value }
    delete nextContent[removed.key]
    defaultContent.value = nextContent
  }

  syncDefaultContent()
}

const moveSchemaField = (fields: TemplateFieldSchema[], index: number, direction: -1 | 1): void => {
  const targetIndex = index + direction

  if (targetIndex < 0 || targetIndex >= fields.length) return

  const [field] = fields.splice(index, 1)
  fields.splice(targetIndex, 0, field)
}

const updateFieldLabel = (field: TemplateFieldSchema, value: string): void => {
  field.label = value

  if (!field.key.trim()) {
    updateFieldKey(field, fieldKeyFromLabel(value))
  }
}

const remapRepeaterRows = (
  parentField: TemplateFieldSchema,
  oldKey: string,
  newKey: string
): void => {
  const rows = defaultContent.value[parentField.key]

  if (!Array.isArray(rows)) return

  defaultContent.value[parentField.key] = rows.map((row) => {
    const nextRow = { ...contentRecord(row) }

    if (Object.prototype.hasOwnProperty.call(nextRow, oldKey)) {
      nextRow[newKey] = nextRow[oldKey]
      delete nextRow[oldKey]
    }

    return nextRow
  })
}

const updateFieldKey = (
  field: TemplateFieldSchema,
  value: string,
  parentField: TemplateFieldSchema | null = null
): void => {
  const oldKey = field.key
  field.key = sanitizeFieldKey(value)

  if (oldKey && field.key && oldKey !== field.key) {
    if (parentField) {
      remapRepeaterRows(parentField, oldKey, field.key)
    } else if (Object.prototype.hasOwnProperty.call(defaultContent.value, oldKey)) {
      const nextContent = { ...defaultContent.value, [field.key]: defaultContent.value[oldKey] }
      delete nextContent[oldKey]
      defaultContent.value = nextContent
    }
  }

  syncDefaultContent()
}

const updateFieldType = (field: TemplateFieldSchema, type: TemplateFieldType): void => {
  field.type = type
  field.default = defaultValueFor(field)

  if (type === 'select') {
    field.options = field.options?.length ? field.options : [{ label: 'Option', value: 'option' }]
  } else {
    delete field.options
  }

  if (type === 'repeater') {
    field.fields = field.fields?.length ? field.fields : [blankSchemaField()]
  } else {
    delete field.fields
  }

  syncDefaultContent()
}

const addSelectOption = (field: TemplateFieldSchema): void => {
  field.options = [...(field.options ?? []), { label: 'Option', value: 'option' }]
}

const updateSelectOptionLabel = (option: TemplateFieldOption, value: string): void => {
  option.label = value

  if (!String(option.value ?? '').trim()) {
    option.value = fieldKeyFromLabel(value)
  }
}

const updateSelectOptionValue = (option: TemplateFieldOption, value: string): void => {
  option.value = value
}

const removeSelectOption = (field: TemplateFieldSchema, index: number): void => {
  field.options = (field.options ?? []).filter((_, optionIndex) => optionIndex !== index)
  syncDefaultContent()
}

const updateFieldDefault = (field: TemplateFieldSchema, value: unknown): void => {
  field.default = normalizeInputValue(field, value)
  syncDefaultContent()
}

const cleanOptions = (options: TemplateFieldOption[] = []): TemplateFieldOption[] => {
  return options
    .map((option) => ({
      label: String(option.label ?? '').trim(),
      value: String(option.value ?? '').trim(),
    }))
    .filter((option) => option.label && option.value)
}

const shouldSaveDefault = (field: TemplateFieldSchema): boolean => {
  if (field.type === 'boolean') return field.default === true
  if (field.type === 'number') return field.default !== null && field.default !== undefined
  if (field.type === 'repeater') return Array.isArray(field.default) && field.default.length > 0

  return String(field.default ?? '').trim().length > 0
}

const buildSchemaForSave = (
  fields: TemplateFieldSchema[],
  prefix = ''
): TemplateFieldSchema[] | null => {
  const usedKeys = new Set<string>()
  const savedFields: TemplateFieldSchema[] = []

  for (const [index, field] of fields.entries()) {
    const position = `${prefix}field ${index + 1}`
    const key = field.key.trim()
    const label = field.label.trim()

    if (!label) {
      formError.value = `Add a label for ${position}.`
      return null
    }

    if (!/^[a-z][a-z0-9_]*$/.test(key)) {
      formError.value = `${label} needs a field key using letters, numbers, and underscores.`
      return null
    }

    if (usedKeys.has(key)) {
      formError.value = `${label} uses a duplicate field key.`
      return null
    }

    usedKeys.add(key)

    const savedField: TemplateFieldSchema = {
      key,
      label,
      type: field.type,
    }

    if (field.required) savedField.required = true
    if (field.placeholder?.trim()) savedField.placeholder = field.placeholder.trim()
    if (shouldSaveDefault(field)) savedField.default = normalizeInputValue(field, field.default)

    if (field.type === 'select') {
      const options = cleanOptions(field.options)

      if (!options.length) {
        formError.value = `${label} needs at least one select option.`
        return null
      }

      savedField.options = options
    }

    if (field.type === 'repeater') {
      const nestedFields = buildSchemaForSave(field.fields ?? [], `${label} `)

      if (nestedFields === null) return null

      if (!nestedFields.length) {
        formError.value = `${label} needs at least one list field.`
        return null
      }

      savedField.fields = nestedFields
    }

    savedFields.push(savedField)
  }

  return savedFields
}

const selectWebsiteType = async (websiteType: WebsiteType): Promise<void> => {
  selectedWebsiteTypeId.value = websiteType.id
  selectedCatalogItemId.value = null
  activeFormSection.value = 'websiteType'
  typeSlugTouched.value = true
  itemKeyTouched.value = false
  websiteTypeForm.value = {
    name: websiteType.name,
    slug: websiteType.slug,
    description: websiteType.description ?? '',
    is_active: websiteType.is_active,
  }
  catalogItemForm.value = blankCatalogItem()
  schemaFields.value = []
  openSchemaFieldSections.value = []
  defaultContent.value = {}
  formError.value = ''

  await maintenanceStore.loadCatalogItems(websiteType.id)
}

const selectCatalogItem = (item: TemplateCatalogItem): void => {
  selectedCatalogItemId.value = item.id ?? null
  activeFormSection.value = 'template'
  itemKeyTouched.value = true
  catalogItemForm.value = {
    website_type_id: item.website_type_id ?? selectedWebsiteTypeId.value ?? '',
    key: item.key,
    name: item.name,
    description: item.description ?? '',
    preview_image: item.preview_image ?? '',
    field_schema: item.field_schema ?? [],
    default_content: item.default_content ?? {},
    is_active: item.is_active ?? true,
  }
  schemaFields.value = normalizeLoadedSchema(item.field_schema)
  openSchemaFieldSections.value = schemaFields.value.length ? [schemaFieldSectionValue(0)] : []
  defaultContent.value = contentForSchema(schemaFields.value, item.default_content ?? {})
  formError.value = ''
}

const newWebsiteType = (): void => {
  selectedWebsiteTypeId.value = null
  selectedCatalogItemId.value = null
  activeFormSection.value = 'websiteType'
  typeSlugTouched.value = false
  itemKeyTouched.value = false
  websiteTypeForm.value = blankWebsiteType()
  catalogItemForm.value = blankCatalogItem()
  schemaFields.value = []
  openSchemaFieldSections.value = []
  defaultContent.value = {}
  maintenanceStore.catalogItems = []
  formError.value = ''
}

const newCatalogItem = (): void => {
  selectedCatalogItemId.value = null
  activeFormSection.value = 'template'
  itemKeyTouched.value = false
  catalogItemForm.value = blankCatalogItem()
  schemaFields.value = []
  openSchemaFieldSections.value = []
  defaultContent.value = {}
  formError.value = ''
}

const saveWebsiteType = async (): Promise<void> => {
  activeFormSection.value = 'websiteType'

  if (!websiteTypeForm.value.name.trim()) {
    formError.value = 'Website type name is required.'
    return
  }

  websiteTypeForm.value.slug = slugify(websiteTypeForm.value.slug || websiteTypeForm.value.name)

  const saved = await maintenanceStore.saveWebsiteType(
    { ...websiteTypeForm.value },
    selectedWebsiteTypeId.value
  )

  await selectWebsiteType(saved)
}

const deleteWebsiteType = async (): Promise<void> => {
  if (!selectedWebsiteTypeId.value) return

  await maintenanceStore.deleteWebsiteType(selectedWebsiteTypeId.value)
  newWebsiteType()
}

const saveCatalogItem = async (): Promise<void> => {
  activeFormSection.value = 'template'

  if (!selectedWebsiteTypeId.value) {
    formError.value = 'Select a website type before saving a template.'
    return
  }

  if (!catalogItemForm.value.name.trim()) {
    formError.value = 'Template name is required.'
    return
  }

  catalogItemForm.value.website_type_id = selectedWebsiteTypeId.value
  catalogItemForm.value.key = slugify(catalogItemForm.value.key || catalogItemForm.value.name)

  const fieldSchema = buildSchemaForSave(schemaFields.value)
  if (fieldSchema === null) return

  const saved = await maintenanceStore.saveCatalogItem(
    {
      ...catalogItemForm.value,
      field_schema: fieldSchema,
      default_content: contentForSchema(fieldSchema, defaultContent.value),
    },
    selectedCatalogItemId.value
  )

  selectCatalogItem(saved)
}

const deleteCatalogItem = async (): Promise<void> => {
  if (!selectedCatalogItemId.value) return

  await maintenanceStore.deleteCatalogItem(selectedCatalogItemId.value, selectedWebsiteTypeId.value)
  newCatalogItem()
}

onMounted(async () => {
  await maintenanceStore.loadWebsiteTypes()

  if (maintenanceStore.websiteTypes[0]) {
    await selectWebsiteType(maintenanceStore.websiteTypes[0])
  }
})

watch(
  () => websiteTypeForm.value.name,
  (name) => {
    if (!typeSlugTouched.value) {
      websiteTypeForm.value.slug = slugify(name)
    }
  }
)

watch(
  () => catalogItemForm.value.name,
  (name) => {
    if (!itemKeyTouched.value) {
      catalogItemForm.value.key = slugify(name)
    }
  }
)

watch(
  schemaFields,
  () => {
    syncDefaultContent()
  },
  { deep: true }
)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Template Maintenance</h2>
          <p class="module-container-description">
            Manage website types and complete templates available to tenants.
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-3">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">Website Types</h3>
            <Button size="sm" variant="outline" type="button" @click="newWebsiteType">
              <Plus class="size-4" />
              New
            </Button>
          </div>

          <div class="space-y-2">
            <button
              v-for="websiteType in maintenanceStore.websiteTypes"
              :key="websiteType.id"
              type="button"
              class="w-full rounded border bg-background p-3 text-left transition hover:border-primary hover:shadow-sm"
              :class="{
                'border-primary ring-2 ring-primary/20': selectedWebsiteTypeId === websiteType.id,
              }"
              @click="selectWebsiteType(websiteType)"
            >
              <span class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-semibold">{{ websiteType.name }}</span>
                  <span class="mt-1 block text-xs text-muted-foreground">{{
                    websiteType.slug
                  }}</span>
                  <span class="mt-2 flex flex-wrap gap-2">
                    <Badge variant="outline">
                      {{ websiteType.available_templates_count }} templates
                    </Badge>
                    <Badge v-if="!websiteType.is_active" variant="outline">Inactive</Badge>
                  </span>
                </span>
                <Check
                  v-if="selectedWebsiteTypeId === websiteType.id"
                  class="size-4 shrink-0 text-primary"
                />
              </span>
            </button>
          </div>
        </aside>

        <main class="grid gap-4 2xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
          <section class="space-y-4">
            <Card
              class="gap-4 py-4 transition"
              :class="{
                'border-primary ring-2 ring-primary/20': activeFormSection === 'websiteType',
              }"
              @focusin="activeFormSection = 'websiteType'"
            >
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Website Type Details</CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <FieldGroup>
                  <FieldSet>
                    <Accordion
                      type="multiple"
                      :default-value="['website-type-basics']"
                      class="space-y-2"
                    >
                      <AccordionItem
                        value="website-type-basics"
                        class="rounded border bg-background px-3 last:border-b"
                      >
                        <AccordionTrigger class="py-3 hover:no-underline">
                          <span>
                            <span class="block text-sm font-semibold">Basic Type Settings</span>
                            <span class="mt-1 block text-xs text-muted-foreground">
                              Name, URL slug, and active state.
                            </span>
                          </span>
                        </AccordionTrigger>
                        <AccordionContent class="space-y-3 pb-3">
                          <Field>
                            <FieldLabel for="website-type-name">Name</FieldLabel>
                            <Input id="website-type-name" v-model="websiteTypeForm.name" />
                          </Field>

                          <Field>
                            <FieldLabel for="website-type-slug">Slug</FieldLabel>
                            <Input
                              id="website-type-slug"
                              v-model="websiteTypeForm.slug"
                              @input="typeSlugTouched = true"
                            />
                          </Field>

                          <Field orientation="horizontal" class="items-center gap-3">
                            <Checkbox
                              id="website-type-active"
                              v-model="websiteTypeForm.is_active"
                            />
                            <FieldLabel for="website-type-active">Active</FieldLabel>
                          </Field>
                        </AccordionContent>
                      </AccordionItem>

                      <AccordionItem
                        value="website-type-description"
                        class="rounded border bg-background px-3 last:border-b"
                      >
                        <AccordionTrigger class="py-3 hover:no-underline">
                          <span>
                            <span class="block text-sm font-semibold">Type Description</span>
                            <span class="mt-1 block text-xs text-muted-foreground">
                              Optional notes shown with this website type.
                            </span>
                          </span>
                        </AccordionTrigger>
                        <AccordionContent class="space-y-3 pb-3">
                          <Field>
                            <FieldLabel for="website-type-description">Description</FieldLabel>
                            <Textarea
                              id="website-type-description"
                              v-model="websiteTypeForm.description"
                              class="min-h-24"
                            />
                          </Field>
                        </AccordionContent>
                      </AccordionItem>
                    </Accordion>
                  </FieldSet>
                </FieldGroup>
              </CardContent>
            </Card>

            <Card
              class="gap-4 py-4 transition"
              :class="{
                'border-primary ring-2 ring-primary/20': activeFormSection === 'template',
              }"
              @focusin="activeFormSection = 'template'"
            >
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Template Details</CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <FieldGroup>
                  <FieldSet>
                    <div class="space-y-3 rounded border bg-muted/20 p-3">
                      <div>
                        <h4 class="text-sm font-semibold">Template Basics</h4>
                        <p class="mt-1 text-xs text-muted-foreground">
                          The fields used most often when creating or finding a template.
                        </p>
                      </div>

                      <div class="grid gap-3 md:grid-cols-2">
                        <Field>
                          <FieldLabel for="catalog-name">Name</FieldLabel>
                          <Input
                            id="catalog-name"
                            v-model="catalogItemForm.name"
                            :disabled="!selectedWebsiteType"
                          />
                        </Field>

                        <Field>
                          <FieldLabel for="catalog-key">Template Key</FieldLabel>
                          <Input
                            id="catalog-key"
                            v-model="catalogItemForm.key"
                            :disabled="!selectedWebsiteType"
                            @input="itemKeyTouched = true"
                          />
                        </Field>
                      </div>

                      <Field orientation="horizontal" class="items-center gap-3">
                        <Checkbox
                          id="catalog-active"
                          v-model="catalogItemForm.is_active"
                          :disabled="!selectedWebsiteType"
                        />
                        <FieldLabel for="catalog-active">Active</FieldLabel>
                      </Field>
                    </div>

                    <Accordion
                      type="multiple"
                      :default-value="['template-field-schema']"
                      class="space-y-2"
                    >
                      <AccordionItem
                        value="template-metadata"
                        class="rounded border bg-background px-3 last:border-b"
                      >
                        <AccordionTrigger class="py-3 hover:no-underline">
                          <span>
                            <span class="block text-sm font-semibold">Template Metadata</span>
                            <span class="mt-1 block text-xs text-muted-foreground">
                              Description and preview image settings.
                            </span>
                          </span>
                        </AccordionTrigger>
                        <AccordionContent class="space-y-3 pb-3">
                          <Field>
                            <FieldLabel for="catalog-description">Description</FieldLabel>
                            <Textarea
                              id="catalog-description"
                              v-model="catalogItemForm.description"
                              class="min-h-24"
                              :disabled="!selectedWebsiteType"
                            />
                          </Field>

                          <Field>
                            <FieldLabel for="catalog-preview">Preview Image</FieldLabel>
                            <Input
                              id="catalog-preview"
                              v-model="catalogItemForm.preview_image"
                              :disabled="!selectedWebsiteType"
                            />
                          </Field>
                        </AccordionContent>
                      </AccordionItem>

                      <AccordionItem
                        value="template-field-schema"
                        class="rounded border bg-background px-3 last:border-b"
                      >
                        <AccordionTrigger class="py-3 hover:no-underline">
                          <span>
                            <span class="block text-sm font-semibold">Field Schema</span>
                            <span class="mt-1 block text-xs text-muted-foreground">
                              Add the content fields users will complete for this template.
                            </span>
                          </span>
                        </AccordionTrigger>
                        <AccordionContent class="space-y-3 pb-3">
                          <div class="flex items-start justify-end gap-3">
                            <Button
                              size="sm"
                              variant="outline"
                              type="button"
                              :disabled="!selectedWebsiteType"
                              @click="addSchemaField(schemaFields)"
                            >
                              <Plus class="size-4" />
                              Field
                            </Button>
                          </div>

                          <Empty v-if="!schemaFields.length" class="min-h-[160px] bg-background">
                            <EmptyHeader>
                              <EmptyTitle>No fields yet</EmptyTitle>
                              <EmptyDescription
                                >Add a field to build this template form.</EmptyDescription
                              >
                            </EmptyHeader>
                          </Empty>

                          <Accordion
                            v-else
                            v-model="openSchemaFieldSections"
                            type="multiple"
                            class="space-y-2"
                          >
                            <AccordionItem
                              v-for="(field, fieldIndex) in schemaFields"
                              :key="fieldIndex"
                              :value="schemaFieldSectionValue(fieldIndex)"
                              class="rounded border bg-background px-3 last:border-b"
                            >
                              <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1 py-3">
                                  <span class="block text-xs font-semibold text-muted-foreground">
                                    Field {{ fieldIndex + 1 }}
                                  </span>
                                  <span class="mt-1 block text-sm font-semibold">
                                    {{ field.label || 'Untitled field' }}
                                  </span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1 py-2">
                                  <Button
                                    size="sm"
                                    variant="ghost"
                                    type="button"
                                    :disabled="fieldIndex === 0"
                                    @click.stop="moveSchemaField(schemaFields, fieldIndex, -1)"
                                  >
                                    <ArrowUp class="size-4" />
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="ghost"
                                    type="button"
                                    :disabled="fieldIndex === schemaFields.length - 1"
                                    @click.stop="moveSchemaField(schemaFields, fieldIndex, 1)"
                                  >
                                    <ArrowDown class="size-4" />
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="ghost"
                                    type="button"
                                    @click.stop="removeSchemaField(schemaFields, fieldIndex)"
                                  >
                                    <Trash2 class="size-4" />
                                  </Button>
                                </div>
                                <AccordionTrigger
                                  class="flex-none px-0 py-3 hover:no-underline"
                                  :aria-label="`Toggle field ${fieldIndex + 1}`"
                                >
                                  <span class="sr-only">Toggle field {{ fieldIndex + 1 }}</span>
                                </AccordionTrigger>
                              </div>

                              <AccordionContent class="space-y-3 pb-3">
                                <div class="grid gap-3 md:grid-cols-2">
                                  <Field>
                                    <FieldLabel :for="`schema-label-${fieldIndex}`"
                                      >Field label</FieldLabel
                                    >
                                    <Input
                                      :id="`schema-label-${fieldIndex}`"
                                      :model-value="field.label"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="updateFieldLabel(field, String($event))"
                                    />
                                  </Field>

                                  <Field>
                                    <FieldLabel :for="`schema-key-${fieldIndex}`"
                                      >Field key/name</FieldLabel
                                    >
                                    <Input
                                      :id="`schema-key-${fieldIndex}`"
                                      :model-value="field.key"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="updateFieldKey(field, String($event))"
                                    />
                                    <FieldDescription
                                      >Use lowercase letters, numbers, and
                                      underscores.</FieldDescription
                                    >
                                  </Field>

                                  <Field>
                                    <FieldLabel :for="`schema-type-${fieldIndex}`"
                                      >Field type</FieldLabel
                                    >
                                    <NativeSelect
                                      :id="`schema-type-${fieldIndex}`"
                                      class="w-full"
                                      :model-value="field.type"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="
                                        updateFieldType(field, String($event) as TemplateFieldType)
                                      "
                                    >
                                      <NativeSelectOption
                                        v-for="fieldType in fieldTypes"
                                        :key="fieldType.value"
                                        :value="fieldType.value"
                                      >
                                        {{ fieldType.label }}
                                      </NativeSelectOption>
                                    </NativeSelect>
                                  </Field>

                                  <Field
                                    v-if="field.type !== 'boolean' && field.type !== 'repeater'"
                                  >
                                    <FieldLabel :for="`schema-placeholder-${fieldIndex}`">
                                      Placeholder
                                    </FieldLabel>
                                    <Input
                                      :id="`schema-placeholder-${fieldIndex}`"
                                      v-model="field.placeholder"
                                      :disabled="!selectedWebsiteType"
                                    />
                                  </Field>

                                  <Field
                                    v-if="field.type === 'boolean'"
                                    orientation="horizontal"
                                    class="items-center gap-3 self-end"
                                  >
                                    <Checkbox
                                      :id="`schema-default-${fieldIndex}`"
                                      :model-value="Boolean(field.default)"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="
                                        updateFieldDefault(field, Boolean($event))
                                      "
                                    />
                                    <FieldLabel :for="`schema-default-${fieldIndex}`">
                                      Default checked
                                    </FieldLabel>
                                  </Field>

                                  <Field v-else-if="field.type !== 'repeater'">
                                    <FieldLabel :for="`schema-default-${fieldIndex}`">
                                      Default value
                                    </FieldLabel>
                                    <Textarea
                                      v-if="field.type === 'textarea' || field.type === 'rich_text'"
                                      :id="`schema-default-${fieldIndex}`"
                                      :model-value="String(field.default ?? '')"
                                      class="min-h-20"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="updateFieldDefault(field, $event)"
                                    />
                                    <Input
                                      v-else
                                      :id="`schema-default-${fieldIndex}`"
                                      :type="field.type === 'number' ? 'number' : 'text'"
                                      :model-value="String(field.default ?? '')"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="updateFieldDefault(field, $event)"
                                    />
                                  </Field>

                                  <Field
                                    orientation="horizontal"
                                    class="items-center gap-3 self-end"
                                  >
                                    <Checkbox
                                      :id="`schema-required-${fieldIndex}`"
                                      :model-value="Boolean(field.required)"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="field.required = Boolean($event)"
                                    />
                                    <FieldLabel :for="`schema-required-${fieldIndex}`">
                                      Required
                                    </FieldLabel>
                                  </Field>
                                </div>

                                <div
                                  v-if="field.type === 'select'"
                                  class="mt-3 space-y-2 rounded border bg-muted/20 p-3"
                                >
                                  <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold text-muted-foreground">
                                      Select options
                                    </p>
                                    <Button
                                      size="sm"
                                      variant="outline"
                                      type="button"
                                      @click="addSelectOption(field)"
                                    >
                                      <Plus class="size-4" />
                                      Option
                                    </Button>
                                  </div>

                                  <div
                                    v-for="(option, optionIndex) in field.options ?? []"
                                    :key="optionIndex"
                                    class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"
                                  >
                                    <Input
                                      :model-value="String(option.label ?? '')"
                                      placeholder="Label"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="
                                        updateSelectOptionLabel(option, String($event))
                                      "
                                    />
                                    <Input
                                      :model-value="String(option.value ?? '')"
                                      placeholder="Value"
                                      :disabled="!selectedWebsiteType"
                                      @update:model-value="
                                        updateSelectOptionValue(option, String($event))
                                      "
                                    />
                                    <Button
                                      size="sm"
                                      variant="ghost"
                                      type="button"
                                      @click="removeSelectOption(field, optionIndex)"
                                    >
                                      <Trash2 class="size-4" />
                                    </Button>
                                  </div>
                                </div>

                                <div
                                  v-if="field.type === 'repeater'"
                                  class="mt-3 space-y-3 rounded border bg-muted/20 p-3"
                                >
                                  <div class="flex items-center justify-between gap-3">
                                    <div>
                                      <p class="text-xs font-semibold text-muted-foreground">
                                        List fields
                                      </p>
                                      <p class="mt-1 text-xs text-muted-foreground">
                                        Define the fields each list item should contain.
                                      </p>
                                    </div>
                                    <Button
                                      size="sm"
                                      variant="outline"
                                      type="button"
                                      @click="addSchemaField(field.fields ?? (field.fields = []))"
                                    >
                                      <Plus class="size-4" />
                                      Field
                                    </Button>
                                  </div>

                                  <div
                                    v-for="(nestedField, nestedIndex) in field.fields ?? []"
                                    :key="nestedIndex"
                                    class="rounded border bg-background p-3"
                                  >
                                    <div class="mb-3 flex items-center justify-between gap-3">
                                      <p class="text-sm font-semibold">
                                        {{ nestedField.label || `List field ${nestedIndex + 1}` }}
                                      </p>
                                      <div class="flex items-center gap-1">
                                        <Button
                                          size="sm"
                                          variant="ghost"
                                          type="button"
                                          :disabled="nestedIndex === 0"
                                          @click="
                                            moveSchemaField(field.fields ?? [], nestedIndex, -1)
                                          "
                                        >
                                          <ArrowUp class="size-4" />
                                        </Button>
                                        <Button
                                          size="sm"
                                          variant="ghost"
                                          type="button"
                                          :disabled="
                                            nestedIndex === (field.fields ?? []).length - 1
                                          "
                                          @click="
                                            moveSchemaField(field.fields ?? [], nestedIndex, 1)
                                          "
                                        >
                                          <ArrowDown class="size-4" />
                                        </Button>
                                        <Button
                                          size="sm"
                                          variant="ghost"
                                          type="button"
                                          @click="
                                            removeSchemaField(
                                              field.fields ?? [],
                                              nestedIndex,
                                              field
                                            )
                                          "
                                        >
                                          <Trash2 class="size-4" />
                                        </Button>
                                      </div>
                                    </div>

                                    <div class="grid gap-3 md:grid-cols-2">
                                      <Field>
                                        <FieldLabel
                                          :for="`schema-nested-label-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Field label
                                        </FieldLabel>
                                        <Input
                                          :id="`schema-nested-label-${fieldIndex}-${nestedIndex}`"
                                          :model-value="nestedField.label"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldLabel(nestedField, String($event))
                                          "
                                        />
                                      </Field>

                                      <Field>
                                        <FieldLabel
                                          :for="`schema-nested-key-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Field key/name
                                        </FieldLabel>
                                        <Input
                                          :id="`schema-nested-key-${fieldIndex}-${nestedIndex}`"
                                          :model-value="nestedField.key"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldKey(nestedField, String($event), field)
                                          "
                                        />
                                      </Field>

                                      <Field>
                                        <FieldLabel
                                          :for="`schema-nested-type-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Field type
                                        </FieldLabel>
                                        <NativeSelect
                                          :id="`schema-nested-type-${fieldIndex}-${nestedIndex}`"
                                          class="w-full"
                                          :model-value="nestedField.type"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldType(
                                              nestedField,
                                              String($event) as TemplateFieldType
                                            )
                                          "
                                        >
                                          <NativeSelectOption
                                            v-for="fieldType in nestedFieldTypes"
                                            :key="fieldType.value"
                                            :value="fieldType.value"
                                          >
                                            {{ fieldType.label }}
                                          </NativeSelectOption>
                                        </NativeSelect>
                                      </Field>

                                      <Field
                                        v-if="
                                          nestedField.type !== 'boolean' &&
                                          nestedField.type !== 'repeater'
                                        "
                                      >
                                        <FieldLabel
                                          :for="`schema-nested-placeholder-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Placeholder
                                        </FieldLabel>
                                        <Input
                                          :id="`schema-nested-placeholder-${fieldIndex}-${nestedIndex}`"
                                          v-model="nestedField.placeholder"
                                          :disabled="!selectedWebsiteType"
                                        />
                                      </Field>

                                      <Field
                                        v-if="nestedField.type === 'boolean'"
                                        orientation="horizontal"
                                        class="items-center gap-3 self-end"
                                      >
                                        <Checkbox
                                          :id="`schema-nested-default-${fieldIndex}-${nestedIndex}`"
                                          :model-value="Boolean(nestedField.default)"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldDefault(nestedField, Boolean($event))
                                          "
                                        />
                                        <FieldLabel
                                          :for="`schema-nested-default-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Default checked
                                        </FieldLabel>
                                      </Field>

                                      <Field v-else>
                                        <FieldLabel
                                          :for="`schema-nested-default-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Default value
                                        </FieldLabel>
                                        <Textarea
                                          v-if="
                                            nestedField.type === 'textarea' ||
                                            nestedField.type === 'rich_text'
                                          "
                                          :id="`schema-nested-default-${fieldIndex}-${nestedIndex}`"
                                          :model-value="String(nestedField.default ?? '')"
                                          class="min-h-20"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldDefault(nestedField, $event)
                                          "
                                        />
                                        <Input
                                          v-else
                                          :id="`schema-nested-default-${fieldIndex}-${nestedIndex}`"
                                          :type="nestedField.type === 'number' ? 'number' : 'text'"
                                          :model-value="String(nestedField.default ?? '')"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            updateFieldDefault(nestedField, $event)
                                          "
                                        />
                                      </Field>

                                      <Field
                                        orientation="horizontal"
                                        class="items-center gap-3 self-end"
                                      >
                                        <Checkbox
                                          :id="`schema-nested-required-${fieldIndex}-${nestedIndex}`"
                                          :model-value="Boolean(nestedField.required)"
                                          :disabled="!selectedWebsiteType"
                                          @update:model-value="
                                            nestedField.required = Boolean($event)
                                          "
                                        />
                                        <FieldLabel
                                          :for="`schema-nested-required-${fieldIndex}-${nestedIndex}`"
                                        >
                                          Required
                                        </FieldLabel>
                                      </Field>
                                    </div>
                                  </div>
                                </div>
                              </AccordionContent>
                            </AccordionItem>
                          </Accordion>
                        </AccordionContent>
                      </AccordionItem>

                      <AccordionItem
                        value="template-default-content"
                        class="rounded border bg-background px-3 last:border-b"
                      >
                        <AccordionTrigger class="py-3 hover:no-underline">
                          <span>
                            <span class="block text-sm font-semibold">Default Content</span>
                            <span class="mt-1 mb-1 block text-xs text-muted-foreground">
                              Starter values generated from the current field schema.
                            </span>
                          </span>
                        </AccordionTrigger>
                        <AccordionContent class="space-y-3 pb-3">
                          <Empty v-if="!contentSchema.length" class="min-h-[160px] bg-background">
                            <EmptyHeader>
                              <EmptyTitle>No content fields</EmptyTitle>
                              <EmptyDescription>
                                Add schema fields before entering default content.
                              </EmptyDescription>
                            </EmptyHeader>
                          </Empty>

                          <DynamicTemplateFields
                            v-else
                            :schema="contentSchema"
                            :model-value="defaultContent"
                            @update:model-value="defaultContent = $event"
                          />
                        </AccordionContent>
                      </AccordionItem>
                    </Accordion>
                  </FieldSet>
                </FieldGroup>

                <div
                  v-if="renderPath"
                  class="mt-4 rounded border bg-muted/30 p-3 text-xs text-muted-foreground"
                >
                  <div class="flex items-start gap-2">
                    <FileCode2 class="mt-0.5 size-4 shrink-0" />
                    <span>{{ renderPath }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <p
              v-if="formError"
              class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
            >
              {{ formError }}
            </p>
          </section>

          <section class="space-y-4">
            <Card class="gap-4 py-4">
              <CardHeader class="flex-row items-center justify-between px-4">
                <CardTitle class="text-sm">
                  Templates
                  <span v-if="selectedWebsiteType" class="text-muted-foreground">
                    for {{ selectedWebsiteType.name }}
                  </span>
                </CardTitle>
                <Button
                  size="sm"
                  variant="outline"
                  type="button"
                  :disabled="!selectedWebsiteType"
                  @click="newCatalogItem"
                >
                  <Plus class="size-4" />
                  New
                </Button>
              </CardHeader>

              <CardContent class="px-4">
                <Empty v-if="!selectedWebsiteType" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>Select a website type</EmptyTitle>
                    <EmptyDescription
                      >Templates appear after choosing a website type.</EmptyDescription
                    >
                  </EmptyHeader>
                </Empty>

                <Empty v-else-if="!maintenanceStore.catalogItems.length" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>No templates yet</EmptyTitle>
                    <EmptyDescription>Add a template catalog entry for this type.</EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <div v-else class="grid gap-3 lg:grid-cols-2">
                  <button
                    v-for="item in maintenanceStore.catalogItems"
                    :key="item.id"
                    type="button"
                    class="rounded border bg-background p-3 text-left transition hover:border-primary hover:shadow-sm"
                    :class="{
                      'border-primary ring-2 ring-primary/20': selectedCatalogItemId === item.id,
                    }"
                    @click="selectCatalogItem(item)"
                  >
                    <span class="flex items-start justify-between gap-3">
                      <span>
                        <span class="block text-sm font-semibold">{{ item.name }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">{{ item.key }}</span>
                        <span class="mt-2 flex flex-wrap gap-2">
                          <Badge variant="outline">{{ item.website_type_slug }}</Badge>
                          <Badge v-if="!item.is_active" variant="outline">Inactive</Badge>
                        </span>
                      </span>
                      <Check
                        v-if="selectedCatalogItemId === item.id"
                        class="size-4 shrink-0 text-primary"
                      />
                    </span>
                  </button>
                </div>
              </CardContent>
            </Card>
          </section>
        </main>
      </div>

      <div class="fixed bottom-6 right-6 z-20 flex w-[120px] flex-col gap-2 draggable">
        <Button
          variant="update"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading"
          @click="saveWebsiteType"
        >
          <Save class="size-3" />
          Type
        </Button>
        <Button
          variant="create"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedWebsiteType"
          @click="saveCatalogItem"
        >
          <Save class="size-3" />
          Template
        </Button>
        <Button
          variant="delete"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedCatalogItem"
          @click="deleteCatalogItem"
        >
          <Trash2 class="size-3" />
          Template
        </Button>
        <Button
          variant="delete"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedWebsiteType"
          @click="deleteWebsiteType"
        >
          <Trash2 class="size-3" />
          Type
        </Button>
      </div>
    </div>
  </div>
</template>
