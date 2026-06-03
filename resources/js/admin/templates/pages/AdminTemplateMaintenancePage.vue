<script setup lang="ts">
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/shared/components/ui/accordion'
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
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import FieldHelp from '@/shared/components/FieldHelp.vue'
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useTemplateMaintenanceStore } from '@/admin/templates/template-maintenance-store'
import {
  blankCatalogItem,
  blankSchemaField,
  blankWebsiteType,
  buildSchemaForSave,
  cloneSchemaField,
  contentForSchema,
  defaultValueFor,
  fieldKeyFromLabel,
  fieldSummary,
  fieldTypes,
  normalizeInputValue,
  normalizeLoadedField,
  normalizeLoadedSchema,
  sanitizeFieldKey,
  schemaForContent,
  slugify,
} from '@/admin/templates/utils/template-schema'
import DynamicTemplateFields from '@/shared/templates/components/DynamicTemplateFields.vue'
import { contentRecord } from '@/shared/templates/utils/template-form'
import type {
  TemplateContent,
  TemplateCatalogItem,
  TemplateCatalogPayload,
  TemplateFieldOption,
  TemplateFieldSchema,
  TemplateFieldType,
  WebsiteType,
  WebsiteTypePayload,
} from '@/shared/types/templates'
import { ArrowDown, ArrowUp, Check, FileCode2, Pencil, Plus, Save, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'

const maintenanceStore = useTemplateMaintenanceStore()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedCatalogItemId = ref<string | null>(null)
const websiteTypeDialogOpen = ref(false)
const catalogDialogOpen = ref(false)
const formError = ref('')
const typeSlugTouched = ref(false)
const itemKeyTouched = ref(false)
const schemaFields = ref<TemplateFieldSchema[]>([])
const defaultContent = ref<TemplateContent>({})
const openSchemaFieldSections = ref<string[]>([])
// Tracks which details card should show the active edit border.
const activeFormSection = ref<'websiteType' | 'template'>('websiteType')

const nestedFieldTypes = computed(() =>
  fieldTypes.filter((type) => !['cta', 'repeater'].includes(type.value))
)

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

// Show content setup only after the catalog entry exists.
const canConfigureTemplateContent = computed(() => Boolean(selectedCatalogItemId.value))

const contentSchema = computed<TemplateFieldSchema[]>(() => {
  return schemaForContent(schemaFields.value)
})

const renderPath = computed(() => {
  if (!selectedWebsiteType.value?.slug || !catalogItemForm.value.key) return ''

  // The catalog key must match a real Vue file in this folder.
  return `resources/js/tenant/templates/templates/${selectedWebsiteType.value.slug}/${catalogItemForm.value.key}.vue`
})

interface SchemaFieldDialogTarget {
  fields: TemplateFieldSchema[]
  index: number | null
  parentField: TemplateFieldSchema | null
}

const schemaFieldDialogOpen = ref(false)
const schemaFieldDialogTarget = ref<SchemaFieldDialogTarget | null>(null)
const schemaFieldDraft = ref<TemplateFieldSchema | null>(null)
const schemaFieldError = ref('')

const schemaFieldDialogTitle = computed(() => {
  if (!schemaFieldDialogTarget.value) return 'Field'

  const action = schemaFieldDialogTarget.value.index === null ? 'Add' : 'Edit'
  const scope = schemaFieldDialogTarget.value.parentField ? 'List Field' : 'Field'

  return `${action} ${scope}`
})

const schemaFieldTypeOptions = computed(() => {
  return schemaFieldDialogTarget.value?.parentField ? nestedFieldTypes.value : fieldTypes
})

const openSchemaFieldDialog = (
  fields: TemplateFieldSchema[],
  index: number | null = null,
  parentField: TemplateFieldSchema | null = null
): void => {
  // Edit schema fields in a dialog so adding fields does not extend the page.
  schemaFieldDialogTarget.value = { fields, index, parentField }
  schemaFieldDraft.value = index === null ? blankSchemaField() : cloneSchemaField(fields[index])
  schemaFieldError.value = ''
  schemaFieldDialogOpen.value = true
}

const updateDraftFieldLabel = (field: TemplateFieldSchema, value: string): void => {
  field.label = value

  if (!field.key.trim()) {
    field.key = fieldKeyFromLabel(value)
  }
}

const updateDraftFieldKey = (field: TemplateFieldSchema, value: string): void => {
  field.key = sanitizeFieldKey(value)
}

const updateDraftFieldType = (field: TemplateFieldSchema, type: TemplateFieldType): void => {
  field.type = type
  field.default = defaultValueFor(field)

  if (type === 'select') {
    field.options = field.options?.length ? field.options : [{ label: 'Option', value: 'option' }]
  } else {
    delete field.options
  }

  if (type === 'repeater') {
    field.fields = field.fields?.length ? field.fields : []
  } else {
    delete field.fields
  }
}

const updateDraftFieldDefault = (field: TemplateFieldSchema, value: unknown): void => {
  field.default = normalizeInputValue(field, value)
}

const addDraftSelectOption = (field: TemplateFieldSchema): void => {
  field.options = [...(field.options ?? []), { label: 'Option', value: 'option' }]
}

const removeDraftSelectOption = (field: TemplateFieldSchema, index: number): void => {
  field.options = (field.options ?? []).filter((_, optionIndex) => optionIndex !== index)
}

const addDraftListField = (field: TemplateFieldSchema): void => {
  field.fields = [...(field.fields ?? []), blankSchemaField()]
}

const removeDraftListField = (field: TemplateFieldSchema, index: number): void => {
  field.fields = (field.fields ?? []).filter((_, fieldIndex) => fieldIndex !== index)
}

const moveDraftListField = (field: TemplateFieldSchema, index: number, direction: -1 | 1): void => {
  const fields = [...(field.fields ?? [])]
  const targetIndex = index + direction

  if (targetIndex < 0 || targetIndex >= fields.length) return

  const [movedField] = fields.splice(index, 1)
  fields.splice(targetIndex, 0, movedField)
  field.fields = fields
}

const saveSchemaFieldDialog = (): void => {
  const target = schemaFieldDialogTarget.value
  const draft = schemaFieldDraft.value

  if (!target || !draft) return

  const savedField = normalizeLoadedField({ ...draft, key: sanitizeFieldKey(draft.key) })
  savedField.label = savedField.label.trim()

  if (!savedField.label) {
    schemaFieldError.value = 'Field label is required.'
    return
  }

  if (!savedField.key) {
    savedField.key = fieldKeyFromLabel(savedField.label)
  }

  if (!/^[a-z][a-z0-9_]*$/.test(savedField.key)) {
    schemaFieldError.value =
      'Field key must start with a letter and use lowercase letters, numbers, or underscores.'
    return
  }

  const oldField = target.index === null ? null : target.fields[target.index]

  if (target.index === null) {
    target.fields.push(savedField)
  } else {
    target.fields.splice(target.index, 1, savedField)
  }

  if (oldField?.key && oldField.key !== savedField.key) {
    if (target.parentField) {
      remapRepeaterRows(target.parentField, oldField.key, savedField.key)
    } else if (Object.prototype.hasOwnProperty.call(defaultContent.value, oldField.key)) {
      const nextContent = {
        ...defaultContent.value,
        [savedField.key]: defaultContent.value[oldField.key],
      }
      delete nextContent[oldField.key]
      defaultContent.value = nextContent
    }
  }

  syncDefaultContent()
  schemaFieldDialogOpen.value = false
}

const schemaFieldSectionValue = (index: number): string => {
  return `schema-field-${index}`
}

const syncDefaultContent = (): void => {
  // Keep default content aligned with the current schema keys.
  defaultContent.value = contentForSchema(schemaFields.value, defaultContent.value)
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

const updateSelectOptionLabel = (option: TemplateFieldOption, value: string): void => {
  option.label = value

  if (!String(option.value ?? '').trim()) {
    option.value = fieldKeyFromLabel(value)
  }
}

const updateSelectOptionValue = (option: TemplateFieldOption, value: string): void => {
  option.value = value
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
  catalogItemForm.value = blankCatalogItem(selectedWebsiteTypeId.value ?? '')
  schemaFields.value = []
  openSchemaFieldSections.value = []
  defaultContent.value = {}
  formError.value = ''
  maintenanceStore.errors = {}

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
  openSchemaFieldSections.value = []
  defaultContent.value = contentForSchema(schemaFields.value, item.default_content ?? {})
  formError.value = ''
  maintenanceStore.errors = {}
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
  maintenanceStore.errors = {}
}

const newCatalogItem = (): void => {
  selectedCatalogItemId.value = null
  activeFormSection.value = 'template'
  itemKeyTouched.value = false
  catalogItemForm.value = blankCatalogItem(selectedWebsiteTypeId.value ?? '')
  schemaFields.value = []
  openSchemaFieldSections.value = []
  defaultContent.value = {}
  formError.value = ''
  maintenanceStore.errors = {}
}

const createWebsiteType = (): void => {
  // Open a clean website type form in the dialog.
  newWebsiteType()
  websiteTypeDialogOpen.value = true
}

const editWebsiteType = (): void => {
  if (!selectedWebsiteType.value) return

  activeFormSection.value = 'websiteType'
  formError.value = ''
  websiteTypeDialogOpen.value = true
}

const createCatalogItem = (): void => {
  if (!selectedWebsiteType.value) return

  // Start a new catalog item for the selected website type.
  newCatalogItem()
  catalogDialogOpen.value = true
}

const editCatalogItem = (item: TemplateCatalogItem): void => {
  selectCatalogItem(item)
  catalogDialogOpen.value = true
}

const saveWebsiteType = async (): Promise<void> => {
  activeFormSection.value = 'websiteType'

  websiteTypeForm.value.slug = slugify(websiteTypeForm.value.slug || websiteTypeForm.value.name)

  try {
    const saved = await maintenanceStore.saveWebsiteType(
      { ...websiteTypeForm.value },
      selectedWebsiteTypeId.value
    )

    await selectWebsiteType(saved)
    websiteTypeDialogOpen.value = false
  } catch {
    formError.value = 'Please check the website type fields and try again.'
  }
}

const deleteWebsiteType = async (): Promise<void> => {
  if (!selectedWebsiteTypeId.value) return

  await maintenanceStore.deleteWebsiteType(selectedWebsiteTypeId.value)
  newWebsiteType()
  websiteTypeDialogOpen.value = false
}

const saveCatalogItem = async (): Promise<void> => {
  activeFormSection.value = 'template'

  if (!selectedWebsiteTypeId.value) {
    formError.value = 'Select a website type before saving a template.'
    return
  }

  catalogItemForm.value.website_type_id = selectedWebsiteTypeId.value
  catalogItemForm.value.key = slugify(catalogItemForm.value.key || catalogItemForm.value.name)

  const fieldSchema = buildSchemaForSave(schemaFields.value)

  if (fieldSchema.error) {
    formError.value = fieldSchema.error
    return
  }

  try {
    const saved = await maintenanceStore.saveCatalogItem(
      {
        ...catalogItemForm.value,
        field_schema: fieldSchema.fields,
        default_content: contentForSchema(fieldSchema.fields, defaultContent.value),
      },
      selectedCatalogItemId.value
    )

    selectCatalogItem(saved)
    catalogDialogOpen.value = false
  } catch {
    formError.value = 'Please check the template fields and try again.'
  }
}

const deleteCatalogItem = async (): Promise<void> => {
  if (!selectedCatalogItemId.value) return

  await maintenanceStore.deleteCatalogItem(selectedCatalogItemId.value, selectedWebsiteTypeId.value)
  newCatalogItem()
  catalogDialogOpen.value = false
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
            <div class="flex items-center gap-2">
              <Button
                size="sm"
                variant="edit"
                type="button"
                :disabled="!selectedWebsiteType"
                @click="editWebsiteType"
              >
                <Save class="size-4" />
                Edit
              </Button>
              <Button size="sm" variant="create" type="button" @click="createWebsiteType">
                <Plus class="size-4" />
                New
              </Button>
            </div>
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
                    <Badge
                      :variant="
                        getStatusBadgeVariant(websiteType.is_active ? 'active' : 'inactive')
                      "
                    >
                      {{ getStatusLabel(websiteType.is_active ? 'active' : 'inactive') }}
                    </Badge>
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

        <main class="space-y-4">
          <section class="contents">
            <Dialog :open="websiteTypeDialogOpen" @update:open="websiteTypeDialogOpen = $event">
              <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
                <DialogHeader>
                  <DialogTitle>
                    {{ selectedWebsiteTypeId ? 'Edit Website Type' : 'Create Website Type' }}
                  </DialogTitle>
                  <DialogDescription>
                    Manage the website type name, slug, description, and active state.
                  </DialogDescription>
                </DialogHeader>

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
                        <Accordion type="multiple" :default-value="[]" class="space-y-2">
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
                                <FieldHelp
                                  description="Enter the website type name shown to admins and tenants."
                                >
                                  <Input id="website-type-name" v-model="websiteTypeForm.name" />
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.name"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.name[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="website-type-slug">Slug</FieldLabel>
                                <FieldHelp
                                  description="Enter the URL folder slug for this website type."
                                >
                                  <Input
                                    id="website-type-slug"
                                    v-model="websiteTypeForm.slug"
                                    @input="typeSlugTouched = true"
                                  />
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.slug"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.slug[0] }}
                                </Label>
                              </Field>

                              <Field orientation="horizontal" class="items-center gap-3">
                                <Checkbox
                                  id="website-type-active"
                                  v-model="websiteTypeForm.is_active"
                                />
                                <FieldHelp
                                  description="Allow tenants to choose templates in this type."
                                >
                                  <FieldLabel for="website-type-active">Active</FieldLabel>
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.is_active"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.is_active[0] }}
                                </Label>
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
                                <FieldHelp
                                  description="Enter a short note describing this website type."
                                >
                                  <Textarea
                                    id="website-type-description"
                                    v-model="websiteTypeForm.description"
                                    class="min-h-24"
                                  />
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.description"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.description[0] }}
                                </Label>
                              </Field>
                            </AccordionContent>
                          </AccordionItem>
                        </Accordion>
                      </FieldSet>
                    </FieldGroup>
                  </CardContent>
                </Card>

                <p
                  v-if="formError && activeFormSection === 'websiteType'"
                  class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
                >
                  {{ formError }}
                </p>

                <DialogFooter>
                  <Button
                    variant="delete"
                    type="button"
                    :disabled="maintenanceStore.loading || !selectedWebsiteType"
                    @click="deleteWebsiteType"
                  >
                    <Trash2 class="size-4" />
                    Delete
                  </Button>
                  <Button
                    variant="update"
                    type="button"
                    :disabled="maintenanceStore.loading"
                    @click="saveWebsiteType"
                  >
                    <Save class="size-4" />
                    Save
                  </Button>
                </DialogFooter>
              </DialogScrollContent>
            </Dialog>

            <Dialog :open="catalogDialogOpen" @update:open="catalogDialogOpen = $event">
              <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-5xl xl:max-w-6xl">
                <DialogHeader>
                  <DialogTitle>
                    {{ selectedCatalogItemId ? 'Edit Template' : 'Create Template' }}
                  </DialogTitle>
                  <DialogDescription>
                    {{
                      selectedCatalogItemId
                        ? 'Configure the catalog template metadata, field schema, and default content.'
                        : 'Create the catalog entry first. Field schema and default content can be configured after the Vue template file exists.'
                    }}
                  </DialogDescription>
                </DialogHeader>

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
                        <Accordion type="multiple" :default-value="[]" class="space-y-2">
                          <AccordionItem
                            value="template-basics"
                            class="rounded border bg-background px-3 last:border-b"
                          >
                            <AccordionTrigger class="py-3 hover:no-underline">
                              <span>
                                <span class="block text-sm font-semibold">Template Basics</span>
                                <span class="mt-1 block text-xs text-muted-foreground">
                                  Name, catalog key, and active state.
                                </span>
                              </span>
                            </AccordionTrigger>
                            <AccordionContent class="space-y-3 pb-3">
                              <div class="grid gap-3 md:grid-cols-2">
                                <Field>
                                  <FieldLabel for="catalog-name">Name</FieldLabel>
                                  <FieldHelp
                                    description="Enter the template name shown in the catalog."
                                  >
                                    <Input
                                      id="catalog-name"
                                      v-model="catalogItemForm.name"
                                      :disabled="!selectedWebsiteType"
                                    />
                                  </FieldHelp>
                                  <Label
                                    v-if="maintenanceStore.errors.name"
                                    class="text-destructive text-xs"
                                  >
                                    {{ maintenanceStore.errors.name[0] }}
                                  </Label>
                                </Field>

                                <Field>
                                  <FieldLabel for="catalog-key">Template Key</FieldLabel>
                                  <FieldHelp
                                    description="Enter the key that matches the template Vue file name."
                                  >
                                    <Input
                                      id="catalog-key"
                                      v-model="catalogItemForm.key"
                                      :disabled="!selectedWebsiteType"
                                      @input="itemKeyTouched = true"
                                    />
                                  </FieldHelp>
                                  <Label
                                    v-if="maintenanceStore.errors.key"
                                    class="text-destructive text-xs"
                                  >
                                    {{ maintenanceStore.errors.key[0] }}
                                  </Label>
                                </Field>
                              </div>

                              <Field orientation="horizontal" class="items-center gap-3">
                                <Checkbox
                                  id="catalog-active"
                                  v-model="catalogItemForm.is_active"
                                  :disabled="!selectedWebsiteType"
                                />
                                <FieldHelp
                                  description="Allow tenants to select this catalog template."
                                >
                                  <FieldLabel for="catalog-active">Active</FieldLabel>
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.is_active"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.is_active[0] }}
                                </Label>
                              </Field>
                            </AccordionContent>
                          </AccordionItem>

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
                                <FieldHelp
                                  description="Enter a short catalog description for this template."
                                >
                                  <Textarea
                                    id="catalog-description"
                                    v-model="catalogItemForm.description"
                                    class="min-h-24"
                                    :disabled="!selectedWebsiteType"
                                  />
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.description"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.description[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="catalog-preview">Preview Image</FieldLabel>
                                <FieldHelp
                                  description="Enter the image URL shown in template selection."
                                >
                                  <Input
                                    id="catalog-preview"
                                    v-model="catalogItemForm.preview_image"
                                    :disabled="!selectedWebsiteType"
                                  />
                                </FieldHelp>
                                <Label
                                  v-if="maintenanceStore.errors.preview_image"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.preview_image[0] }}
                                </Label>
                              </Field>
                            </AccordionContent>
                          </AccordionItem>

                          <AccordionItem
                            v-if="canConfigureTemplateContent"
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
                                  variant="create"
                                  type="button"
                                  :disabled="!selectedWebsiteType"
                                  @click="openSchemaFieldDialog(schemaFields)"
                                >
                                  <Plus class="size-4" />
                                  Field
                                </Button>
                              </div>

                              <Empty
                                v-if="!schemaFields.length"
                                class="min-h-[160px] bg-background"
                              >
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
                                      <span
                                        class="block text-xs font-semibold text-muted-foreground"
                                      >
                                        Field {{ fieldIndex + 1 }}
                                      </span>
                                      <span class="mt-1 block text-sm font-semibold">
                                        {{ field.label || 'Untitled field' }}
                                      </span>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1 py-2">
                                      <Button
                                        size="sm"
                                        variant="edit"
                                        type="button"
                                        @click.stop="
                                          openSchemaFieldDialog(schemaFields, fieldIndex)
                                        "
                                      >
                                        <Pencil class="size-4" />
                                        Edit
                                      </Button>
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
                                    <div class="rounded border bg-muted/20 p-3">
                                      <p class="text-xs font-semibold text-muted-foreground">
                                        {{ fieldSummary(field) }}
                                      </p>
                                      <p
                                        v-if="field.placeholder"
                                        class="mt-2 text-xs text-muted-foreground"
                                      >
                                        Placeholder: {{ field.placeholder }}
                                      </p>
                                    </div>
                                  </AccordionContent>
                                </AccordionItem>
                              </Accordion>
                              <Label
                                v-if="maintenanceStore.errors.field_schema"
                                class="text-destructive text-xs"
                              >
                                {{ maintenanceStore.errors.field_schema[0] }}
                              </Label>
                            </AccordionContent>
                          </AccordionItem>

                          <AccordionItem
                            v-if="canConfigureTemplateContent"
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
                              <Empty
                                v-if="!contentSchema.length"
                                class="min-h-[160px] bg-background"
                              >
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
                              <Label
                                v-if="maintenanceStore.errors.default_content"
                                class="text-destructive text-xs"
                              >
                                {{ maintenanceStore.errors.default_content[0] }}
                              </Label>
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
                  v-if="formError && activeFormSection === 'template'"
                  class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
                >
                  {{ formError }}
                </p>

                <DialogFooter>
                  <Button
                    variant="delete"
                    type="button"
                    :disabled="maintenanceStore.loading || !selectedCatalogItem"
                    @click="deleteCatalogItem"
                  >
                    <Trash2 class="size-4" />
                    Delete
                  </Button>
                  <Button
                    variant="create"
                    type="button"
                    :disabled="maintenanceStore.loading || !selectedWebsiteType"
                    @click="saveCatalogItem"
                  >
                    <Save class="size-4" />
                    Save
                  </Button>
                </DialogFooter>
              </DialogScrollContent>
            </Dialog>

            <Dialog :open="schemaFieldDialogOpen" @update:open="schemaFieldDialogOpen = $event">
              <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
                <DialogHeader>
                  <DialogTitle>{{ schemaFieldDialogTitle }}</DialogTitle>
                  <DialogDescription>
                    Add or update the field settings without expanding the template form.
                  </DialogDescription>
                </DialogHeader>

                <Accordion
                  v-if="schemaFieldDraft"
                  type="multiple"
                  :default-value="[]"
                  class="space-y-2"
                >
                  <AccordionItem
                    value="field-basics"
                    class="rounded border bg-background px-3 last:border-b"
                  >
                    <AccordionTrigger class="py-3 hover:no-underline">
                      <span>
                        <span class="block text-sm font-semibold">Field Basics</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Label, key, type, default value, and required state.
                        </span>
                      </span>
                    </AccordionTrigger>
                    <AccordionContent class="pb-3">
                      <div class="grid gap-3 md:grid-cols-2">
                        <Field>
                          <FieldLabel for="schema-dialog-label">Field label</FieldLabel>
                          <FieldHelp description="Enter the label users see above this field.">
                            <Input
                              id="schema-dialog-label"
                              :model-value="schemaFieldDraft.label"
                              @update:model-value="
                                updateDraftFieldLabel(schemaFieldDraft, String($event))
                              "
                            />
                          </FieldHelp>
                        </Field>

                        <Field>
                          <FieldLabel for="schema-dialog-key">Field key/name</FieldLabel>
                          <FieldHelp
                            description="Enter a lowercase key using letters, numbers, and underscores."
                          >
                            <Input
                              id="schema-dialog-key"
                              :model-value="schemaFieldDraft.key"
                              @update:model-value="
                                updateDraftFieldKey(schemaFieldDraft, String($event))
                              "
                            />
                          </FieldHelp>
                        </Field>

                        <Field>
                          <FieldLabel for="schema-dialog-type">Field type</FieldLabel>
                          <FieldHelp
                            description="Choose the kind of control this field will render."
                          >
                            <NativeSelect
                              id="schema-dialog-type"
                              class="w-full"
                              :model-value="schemaFieldDraft.type"
                              @update:model-value="
                                updateDraftFieldType(
                                  schemaFieldDraft,
                                  String($event) as TemplateFieldType
                                )
                              "
                            >
                              <NativeSelectOption
                                v-for="fieldType in schemaFieldTypeOptions"
                                :key="fieldType.value"
                                :value="fieldType.value"
                              >
                                {{ fieldType.label }}
                              </NativeSelectOption>
                            </NativeSelect>
                          </FieldHelp>
                        </Field>

                        <Field
                          v-if="
                            schemaFieldDraft.type !== 'boolean' &&
                            schemaFieldDraft.type !== 'repeater'
                          "
                        >
                          <FieldLabel for="schema-dialog-placeholder">Placeholder</FieldLabel>
                          <FieldHelp description="Enter example text shown before users type.">
                            <Input
                              id="schema-dialog-placeholder"
                              v-model="schemaFieldDraft.placeholder"
                            />
                          </FieldHelp>
                        </Field>

                        <Field
                          v-if="schemaFieldDraft.type === 'boolean'"
                          orientation="horizontal"
                          class="items-center gap-3 self-end"
                        >
                          <Checkbox
                            id="schema-dialog-default"
                            :model-value="Boolean(schemaFieldDraft.default)"
                            @update:model-value="
                              updateDraftFieldDefault(schemaFieldDraft, Boolean($event))
                            "
                          />
                          <FieldHelp description="Set whether this checkbox starts checked.">
                            <FieldLabel for="schema-dialog-default">Default checked</FieldLabel>
                          </FieldHelp>
                        </Field>

                        <Field v-else-if="schemaFieldDraft.type !== 'repeater'">
                          <FieldLabel for="schema-dialog-default">Default value</FieldLabel>
                          <FieldHelp
                            description="Enter the value used when this field is first created."
                          >
                            <Textarea
                              v-if="
                                schemaFieldDraft.type === 'textarea' ||
                                schemaFieldDraft.type === 'rich_text'
                              "
                              id="schema-dialog-default"
                              :model-value="String(schemaFieldDraft.default ?? '')"
                              class="min-h-20"
                              @update:model-value="
                                updateDraftFieldDefault(schemaFieldDraft, $event)
                              "
                            />
                            <Input
                              v-else
                              id="schema-dialog-default"
                              :type="schemaFieldDraft.type === 'number' ? 'number' : 'text'"
                              :model-value="String(schemaFieldDraft.default ?? '')"
                              @update:model-value="
                                updateDraftFieldDefault(schemaFieldDraft, $event)
                              "
                            />
                          </FieldHelp>
                        </Field>

                        <Field orientation="horizontal" class="items-center gap-3 self-end">
                          <Checkbox
                            id="schema-dialog-required"
                            v-model="schemaFieldDraft.required"
                          />
                          <FieldHelp
                            description="Require users to complete this field before saving."
                          >
                            <FieldLabel for="schema-dialog-required">Required</FieldLabel>
                          </FieldHelp>
                        </Field>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <AccordionItem
                    v-if="schemaFieldDraft.type === 'select'"
                    value="field-select-options"
                    class="rounded border bg-background px-3 last:border-b"
                  >
                    <AccordionTrigger class="py-3 hover:no-underline">
                      <span>
                        <span class="block text-sm font-semibold">Select Options</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Choices users can pick from this field.
                        </span>
                      </span>
                    </AccordionTrigger>
                    <AccordionContent class="space-y-2 pb-3">
                      <div class="flex items-center justify-end gap-3">
                        <Button
                          size="sm"
                          variant="create"
                          type="button"
                          @click="addDraftSelectOption(schemaFieldDraft)"
                        >
                          <Plus class="size-4" />
                          Option
                        </Button>
                      </div>

                      <div
                        v-for="(option, optionIndex) in schemaFieldDraft.options ?? []"
                        :key="optionIndex"
                        class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"
                      >
                        <FieldHelp description="Enter the option text users see.">
                          <Input
                            :model-value="String(option.label ?? '')"
                            placeholder="Label"
                            @update:model-value="updateSelectOptionLabel(option, String($event))"
                          />
                        </FieldHelp>
                        <FieldHelp description="Enter the saved value for this option.">
                          <Input
                            :model-value="String(option.value ?? '')"
                            placeholder="Value"
                            @update:model-value="updateSelectOptionValue(option, String($event))"
                          />
                        </FieldHelp>
                        <Button
                          size="sm"
                          variant="ghost"
                          type="button"
                          @click="removeDraftSelectOption(schemaFieldDraft, optionIndex)"
                        >
                          <Trash2 class="size-4" />
                        </Button>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <AccordionItem
                    v-if="schemaFieldDraft.type === 'repeater'"
                    value="field-list-fields"
                    class="rounded border bg-background px-3 last:border-b"
                  >
                    <AccordionTrigger class="py-3 hover:no-underline">
                      <span>
                        <span class="block text-sm font-semibold">List Fields</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                          Define the fields each list item should contain.
                        </span>
                      </span>
                    </AccordionTrigger>
                    <AccordionContent class="space-y-3 pb-3">
                      <div class="flex items-center justify-end gap-3">
                        <Button
                          size="sm"
                          variant="create"
                          type="button"
                          @click="addDraftListField(schemaFieldDraft)"
                        >
                          <Plus class="size-4" />
                          Field
                        </Button>
                      </div>

                      <Empty
                        v-if="!schemaFieldDraft.fields?.length"
                        class="min-h-[120px] bg-background"
                      >
                        <EmptyHeader>
                          <EmptyTitle>No list fields</EmptyTitle>
                          <EmptyDescription>Add fields for each list item.</EmptyDescription>
                        </EmptyHeader>
                      </Empty>

                      <div
                        v-for="(nestedField, nestedIndex) in schemaFieldDraft.fields ?? []"
                        :key="nestedIndex"
                        class="space-y-3 rounded border bg-background p-3"
                      >
                        <div class="flex items-center justify-between gap-3">
                          <p class="text-sm font-semibold">
                            {{ nestedField.label || `List field ${nestedIndex + 1}` }}
                          </p>
                          <div class="flex items-center gap-1">
                            <Button
                              size="sm"
                              variant="ghost"
                              type="button"
                              :disabled="nestedIndex === 0"
                              @click="moveDraftListField(schemaFieldDraft, nestedIndex, -1)"
                            >
                              <ArrowUp class="size-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              type="button"
                              :disabled="nestedIndex === (schemaFieldDraft.fields ?? []).length - 1"
                              @click="moveDraftListField(schemaFieldDraft, nestedIndex, 1)"
                            >
                              <ArrowDown class="size-4" />
                            </Button>
                            <Button
                              size="sm"
                              variant="ghost"
                              type="button"
                              @click="removeDraftListField(schemaFieldDraft, nestedIndex)"
                            >
                              <Trash2 class="size-4" />
                            </Button>
                          </div>
                        </div>

                        <div class="grid gap-3 md:grid-cols-2">
                          <Field>
                            <FieldLabel :for="`schema-dialog-nested-label-${nestedIndex}`">
                              Field label
                            </FieldLabel>
                            <FieldHelp
                              description="Enter the label users see for this list item field."
                            >
                              <Input
                                :id="`schema-dialog-nested-label-${nestedIndex}`"
                                :model-value="nestedField.label"
                                @update:model-value="
                                  updateDraftFieldLabel(nestedField, String($event))
                                "
                              />
                            </FieldHelp>
                          </Field>

                          <Field>
                            <FieldLabel :for="`schema-dialog-nested-key-${nestedIndex}`">
                              Field key/name
                            </FieldLabel>
                            <FieldHelp description="Enter the saved key for this list item field.">
                              <Input
                                :id="`schema-dialog-nested-key-${nestedIndex}`"
                                :model-value="nestedField.key"
                                @update:model-value="
                                  updateDraftFieldKey(nestedField, String($event))
                                "
                              />
                            </FieldHelp>
                          </Field>

                          <Field>
                            <FieldLabel :for="`schema-dialog-nested-type-${nestedIndex}`">
                              Field type
                            </FieldLabel>
                            <FieldHelp
                              description="Choose the control type for this list item field."
                            >
                              <NativeSelect
                                :id="`schema-dialog-nested-type-${nestedIndex}`"
                                class="w-full"
                                :model-value="nestedField.type"
                                @update:model-value="
                                  updateDraftFieldType(
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
                            </FieldHelp>
                          </Field>

                          <Field v-if="nestedField.type !== 'boolean'">
                            <FieldLabel :for="`schema-dialog-nested-placeholder-${nestedIndex}`">
                              Placeholder
                            </FieldLabel>
                            <FieldHelp description="Enter example text shown before users type.">
                              <Input
                                :id="`schema-dialog-nested-placeholder-${nestedIndex}`"
                                v-model="nestedField.placeholder"
                              />
                            </FieldHelp>
                          </Field>

                          <Field
                            v-if="nestedField.type === 'boolean'"
                            orientation="horizontal"
                            class="items-center gap-3 self-end"
                          >
                            <Checkbox
                              :id="`schema-dialog-nested-default-${nestedIndex}`"
                              :model-value="Boolean(nestedField.default)"
                              @update:model-value="
                                updateDraftFieldDefault(nestedField, Boolean($event))
                              "
                            />
                            <FieldHelp description="Set whether this checkbox starts checked.">
                              <FieldLabel :for="`schema-dialog-nested-default-${nestedIndex}`">
                                Default checked
                              </FieldLabel>
                            </FieldHelp>
                          </Field>

                          <Field v-else>
                            <FieldLabel :for="`schema-dialog-nested-default-${nestedIndex}`">
                              Default value
                            </FieldLabel>
                            <FieldHelp
                              description="Enter the starting value for this list item field."
                            >
                              <Textarea
                                v-if="
                                  nestedField.type === 'textarea' ||
                                  nestedField.type === 'rich_text'
                                "
                                :id="`schema-dialog-nested-default-${nestedIndex}`"
                                :model-value="String(nestedField.default ?? '')"
                                class="min-h-20"
                                @update:model-value="updateDraftFieldDefault(nestedField, $event)"
                              />
                              <Input
                                v-else
                                :id="`schema-dialog-nested-default-${nestedIndex}`"
                                :type="nestedField.type === 'number' ? 'number' : 'text'"
                                :model-value="String(nestedField.default ?? '')"
                                @update:model-value="updateDraftFieldDefault(nestedField, $event)"
                              />
                            </FieldHelp>
                          </Field>

                          <Field orientation="horizontal" class="items-center gap-3 self-end">
                            <Checkbox
                              :id="`schema-dialog-nested-required-${nestedIndex}`"
                              v-model="nestedField.required"
                            />
                            <FieldHelp
                              description="Require users to complete this list item field."
                            >
                              <FieldLabel :for="`schema-dialog-nested-required-${nestedIndex}`">
                                Required
                              </FieldLabel>
                            </FieldHelp>
                          </Field>
                        </div>

                        <div
                          v-if="nestedField.type === 'select'"
                          class="space-y-2 rounded border bg-muted/20 p-3"
                        >
                          <div class="flex items-center justify-between gap-3">
                            <p class="text-xs font-semibold text-muted-foreground">
                              Select options
                            </p>
                            <Button
                              size="sm"
                              variant="create"
                              type="button"
                              @click="addDraftSelectOption(nestedField)"
                            >
                              <Plus class="size-4" />
                              Option
                            </Button>
                          </div>

                          <div
                            v-for="(option, optionIndex) in nestedField.options ?? []"
                            :key="optionIndex"
                            class="grid gap-2 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"
                          >
                            <FieldHelp description="Enter the option text users see.">
                              <Input
                                :model-value="String(option.label ?? '')"
                                placeholder="Label"
                                @update:model-value="
                                  updateSelectOptionLabel(option, String($event))
                                "
                              />
                            </FieldHelp>
                            <FieldHelp description="Enter the saved value for this option.">
                              <Input
                                :model-value="String(option.value ?? '')"
                                placeholder="Value"
                                @update:model-value="
                                  updateSelectOptionValue(option, String($event))
                                "
                              />
                            </FieldHelp>
                            <Button
                              size="sm"
                              variant="ghost"
                              type="button"
                              @click="removeDraftSelectOption(nestedField, optionIndex)"
                            >
                              <Trash2 class="size-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    </AccordionContent>
                  </AccordionItem>

                  <p v-if="schemaFieldError" class="text-sm font-medium text-destructive">
                    {{ schemaFieldError }}
                  </p>
                </Accordion>

                <DialogFooter>
                  <Button variant="cancel" type="button" @click="schemaFieldDialogOpen = false">
                    Cancel
                  </Button>
                  <Button variant="create" type="button" @click="saveSchemaFieldDialog">
                    Save
                  </Button>
                </DialogFooter>
              </DialogScrollContent>
            </Dialog>
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
                  variant="create"
                  type="button"
                  :disabled="!selectedWebsiteType"
                  @click="createCatalogItem"
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
                    @click="editCatalogItem(item)"
                  >
                    <span class="flex items-start justify-between gap-3">
                      <span>
                        <span class="block text-sm font-semibold">{{ item.name }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">{{ item.key }}</span>
                        <span class="mt-2 flex flex-wrap gap-2">
                          <Badge variant="outline">{{ item.website_type_slug }}</Badge>
                          <Badge
                            :variant="getStatusBadgeVariant(item.is_active ? 'active' : 'inactive')"
                          >
                            {{ getStatusLabel(item.is_active ? 'active' : 'inactive') }}
                          </Badge>
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
    </div>
  </div>
</template>
