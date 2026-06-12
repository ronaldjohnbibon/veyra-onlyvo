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
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useTemplateMaintenanceStore } from '@/admin/templates/template-maintenance-store'
import { useConfirmStore } from '@/shared/stores/confirm-store'
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
  TemplateCatalogQaItem,
  TemplateCatalogPayload,
  TemplateFieldOption,
  TemplateFieldSchema,
  TemplateFieldType,
  WebsiteType,
  WebsiteTypePayload,
} from '@/shared/types/templates'
import {
  ArrowDown,
  ArrowUp,
  Check,
  CircleAlert,
  CircleCheck,
  Copy,
  Download,
  FileCode2,
  Pencil,
  Plus,
  RotateCcw,
  Save,
  Trash2,
  Upload,
} from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'

const maintenanceStore = useTemplateMaintenanceStore()
const confirmStore = useConfirmStore()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedCatalogItemId = ref<string | null>(null)
const websiteTypeDialogOpen = ref(false)
const catalogDialogOpen = ref(false)
const schemaExportDialogOpen = ref(false)
const schemaImportDialogOpen = ref(false)
const formError = ref('')
const schemaImportError = ref('')
const schemaImportText = ref('')
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

const selectedUsage = computed(() => selectedCatalogItem.value?.usage_summary)
const selectedValidation = computed(() => selectedCatalogItem.value?.validation)
const selectedQa = computed(() => selectedCatalogItem.value?.qa_checklists)
const selectedVersions = computed(() => selectedCatalogItem.value?.versions ?? [])
const visibleDefaultContent = computed(() => {
  const entries = Object.entries(selectedCatalogItem.value?.default_content ?? {})

  return entries.slice(0, 6).map(([key, value]) => ({
    key,
    value: typeof value === 'object' ? JSON.stringify(value) : String(value ?? ''),
  }))
})

const passedQaCount = (items: TemplateCatalogQaItem[] = []): number => {
  return items.filter((item) => item.passed).length
}

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
    changelog: '',
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

const cloneCatalogItem = async (): Promise<void> => {
  if (!selectedCatalogItem.value) return

  const cloned = await maintenanceStore.cloneCatalogItem(selectedCatalogItem.value)
  selectCatalogItem(cloned)
}

const toggleCatalogPublish = async (): Promise<void> => {
  if (!selectedCatalogItem.value) return

  const action = selectedCatalogItem.value.is_active ? 'unpublish' : 'publish'
  const confirmed = await confirmStore.confirm(`Are you sure you want to ${action} this template?`)

  if (!confirmed) return

  const updated = await maintenanceStore.publishCatalogItem(selectedCatalogItem.value)
  selectCatalogItem(updated)
}

const validateCatalogItem = async (): Promise<void> => {
  if (!selectedCatalogItem.value?.id) return

  await maintenanceStore.validateCatalogItem(selectedCatalogItem.value.id)
  await maintenanceStore.loadCatalogItems(selectedCatalogItem.value.website_type_id)

  const refreshed = maintenanceStore.catalogItems.find(
    (item) => item.id === selectedCatalogItemId.value
  )
  if (refreshed) selectCatalogItem(refreshed)
}

const exportCatalogSchema = async (): Promise<void> => {
  if (!selectedCatalogItem.value?.id) return

  await maintenanceStore.exportCatalogSchema(selectedCatalogItem.value.id)
  schemaExportDialogOpen.value = true
}

const openImportSchemaDialog = (): void => {
  if (!selectedCatalogItem.value) return

  schemaImportText.value = JSON.stringify(
    {
      field_schema: selectedCatalogItem.value.field_schema ?? [],
      default_content: selectedCatalogItem.value.default_content ?? {},
      changelog: 'Imported schema and default content.',
    },
    null,
    2
  )
  schemaImportError.value = ''
  schemaImportDialogOpen.value = true
}

const importCatalogSchema = async (): Promise<void> => {
  if (!selectedCatalogItem.value) return

  try {
    const payload = JSON.parse(schemaImportText.value || '{}') as Pick<
      TemplateCatalogPayload,
      'field_schema' | 'default_content' | 'changelog'
    >

    if (!Array.isArray(payload.field_schema)) {
      schemaImportError.value = 'Import JSON must include a field_schema array.'
      return
    }

    const updated = await maintenanceStore.importCatalogSchema(selectedCatalogItem.value, payload)
    selectCatalogItem(updated)
    schemaImportDialogOpen.value = false
  } catch {
    schemaImportError.value = 'Import JSON is invalid.'
  }
}

const rollbackCatalogItem = async (versionId: string): Promise<void> => {
  if (!selectedCatalogItem.value) return

  const confirmed = await confirmStore.confirm('Rollback this template to the selected version?')

  if (!confirmed) return

  const updated = await maintenanceStore.rollbackCatalogItem(selectedCatalogItem.value, versionId)
  selectCatalogItem(updated)
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

                                <Input
                                  v-field-help="
                                    'Enter the website type name shown to admins and tenants.'
                                  "
                                  id="website-type-name"
                                  v-model="websiteTypeForm.name"
                                />

                                <Label
                                  v-if="maintenanceStore.errors.name"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.name[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="website-type-slug">Slug</FieldLabel>

                                <Input
                                  v-field-help="'Enter the URL folder slug for this website type.'"
                                  id="website-type-slug"
                                  v-model="websiteTypeForm.slug"
                                  @input="typeSlugTouched = true"
                                />

                                <Label
                                  v-if="maintenanceStore.errors.slug"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.slug[0] }}
                                </Label>
                              </Field>

                              <Field orientation="horizontal" class="items-center gap-3">
                                <Checkbox
                                  v-field-help="'Allow tenants to choose templates in this type.'"
                                  id="website-type-active"
                                  v-model="websiteTypeForm.is_active"
                                />
                                <FieldLabel for="website-type-active">Active</FieldLabel>
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

                                <Textarea
                                  v-field-help="'Enter a short note describing this website type.'"
                                  id="website-type-description"
                                  v-model="websiteTypeForm.description"
                                  class="min-h-24"
                                />

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

                                  <Input
                                    v-field-help="'Enter the template name shown in the catalog.'"
                                    id="catalog-name"
                                    v-model="catalogItemForm.name"
                                    :disabled="!selectedWebsiteType"
                                  />

                                  <Label
                                    v-if="maintenanceStore.errors.name"
                                    class="text-destructive text-xs"
                                  >
                                    {{ maintenanceStore.errors.name[0] }}
                                  </Label>
                                </Field>

                                <Field>
                                  <FieldLabel for="catalog-key">Template Key</FieldLabel>

                                  <Input
                                    v-field-help="
                                      'Enter the key that matches the template Vue file name.'
                                    "
                                    id="catalog-key"
                                    v-model="catalogItemForm.key"
                                    :disabled="!selectedWebsiteType"
                                    @input="itemKeyTouched = true"
                                  />

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
                                  v-field-help="'Allow tenants to select this catalog template.'"
                                  id="catalog-active"
                                  v-model="catalogItemForm.is_active"
                                  :disabled="!selectedWebsiteType"
                                />
                                <FieldLabel for="catalog-active">Active</FieldLabel>
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

                                <Textarea
                                  v-field-help="
                                    'Enter a short catalog description for this template.'
                                  "
                                  id="catalog-description"
                                  v-model="catalogItemForm.description"
                                  class="min-h-24"
                                  :disabled="!selectedWebsiteType"
                                />

                                <Label
                                  v-if="maintenanceStore.errors.description"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.description[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="catalog-preview">Preview Image</FieldLabel>

                                <Input
                                  v-field-help="'Enter the image URL shown in template selection.'"
                                  id="catalog-preview"
                                  v-model="catalogItemForm.preview_image"
                                  :disabled="!selectedWebsiteType"
                                />

                                <Label
                                  v-if="maintenanceStore.errors.preview_image"
                                  class="text-destructive text-xs"
                                >
                                  {{ maintenanceStore.errors.preview_image[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="catalog-changelog">Changelog</FieldLabel>

                                <Textarea
                                  v-field-help="'Summarize what changed in this template version.'"
                                  id="catalog-changelog"
                                  v-model="catalogItemForm.changelog"
                                  class="min-h-20"
                                  placeholder="Updated hero defaults and CTA field labels."
                                  :disabled="!selectedWebsiteType"
                                />
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

            <Dialog :open="schemaExportDialogOpen" @update:open="schemaExportDialogOpen = $event">
              <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
                <DialogHeader>
                  <DialogTitle>Export Schema</DialogTitle>
                  <DialogDescription>
                    Copy the current field schema and default content for this catalog template.
                  </DialogDescription>
                </DialogHeader>

                <Textarea
                  :model-value="JSON.stringify(maintenanceStore.schemaExport, null, 2)"
                  class="min-h-96 font-mono text-xs"
                  readonly
                />

                <DialogFooter>
                  <Button type="button" variant="cancel" @click="schemaExportDialogOpen = false">
                    Close
                  </Button>
                </DialogFooter>
              </DialogScrollContent>
            </Dialog>

            <Dialog :open="schemaImportDialogOpen" @update:open="schemaImportDialogOpen = $event">
              <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-3xl">
                <DialogHeader>
                  <DialogTitle>Import Schema</DialogTitle>
                  <DialogDescription>
                    Paste JSON with field_schema and default_content. Importing creates a new
                    version snapshot.
                  </DialogDescription>
                </DialogHeader>

                <Textarea
                  v-model="schemaImportText"
                  class="min-h-96 font-mono text-xs"
                  spellcheck="false"
                />

                <p v-if="schemaImportError" class="text-sm font-medium text-destructive">
                  {{ schemaImportError }}
                </p>

                <DialogFooter>
                  <Button type="button" variant="cancel" @click="schemaImportDialogOpen = false">
                    Cancel
                  </Button>
                  <Button type="button" variant="update" @click="importCatalogSchema">
                    <Upload class="size-4" />
                    Import
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

                          <Input
                            v-field-help="'Enter the label users see above this field.'"
                            id="schema-dialog-label"
                            :model-value="schemaFieldDraft.label"
                            @update:model-value="
                              updateDraftFieldLabel(schemaFieldDraft, String($event))
                            "
                          />
                        </Field>

                        <Field>
                          <FieldLabel for="schema-dialog-key">Field key/name</FieldLabel>

                          <Input
                            v-field-help="
                              'Enter a lowercase key using letters, numbers, and underscores.'
                            "
                            id="schema-dialog-key"
                            :model-value="schemaFieldDraft.key"
                            @update:model-value="
                              updateDraftFieldKey(schemaFieldDraft, String($event))
                            "
                          />
                        </Field>

                        <Field>
                          <FieldLabel for="schema-dialog-type">Field type</FieldLabel>

                          <NativeSelect
                            v-field-help="'Choose the kind of control this field will render.'"
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
                        </Field>

                        <Field
                          v-if="
                            schemaFieldDraft.type !== 'boolean' &&
                            schemaFieldDraft.type !== 'repeater'
                          "
                        >
                          <FieldLabel for="schema-dialog-placeholder">Placeholder</FieldLabel>

                          <Input
                            v-field-help="'Enter example text shown before users type.'"
                            id="schema-dialog-placeholder"
                            v-model="schemaFieldDraft.placeholder"
                          />
                        </Field>

                        <Field
                          v-if="schemaFieldDraft.type === 'boolean'"
                          orientation="horizontal"
                          class="items-center gap-3 self-end"
                        >
                          <Checkbox
                            v-field-help="'Set whether this checkbox starts checked.'"
                            id="schema-dialog-default"
                            :model-value="Boolean(schemaFieldDraft.default)"
                            @update:model-value="
                              updateDraftFieldDefault(schemaFieldDraft, Boolean($event))
                            "
                          />
                          <FieldLabel for="schema-dialog-default">Default checked</FieldLabel>
                        </Field>

                        <Field v-else-if="schemaFieldDraft.type !== 'repeater'">
                          <FieldLabel for="schema-dialog-default">Default value</FieldLabel>

                          <Textarea
                            v-field-help="'Enter the value used when this field is first created.'"
                            v-if="
                              schemaFieldDraft.type === 'textarea' ||
                              schemaFieldDraft.type === 'rich_text'
                            "
                            id="schema-dialog-default"
                            :model-value="String(schemaFieldDraft.default ?? '')"
                            class="min-h-20"
                            @update:model-value="updateDraftFieldDefault(schemaFieldDraft, $event)"
                          />
                          <Input
                            v-field-help="'Enter the value used when this field is first created.'"
                            v-else
                            id="schema-dialog-default"
                            :type="schemaFieldDraft.type === 'number' ? 'number' : 'text'"
                            :model-value="String(schemaFieldDraft.default ?? '')"
                            @update:model-value="updateDraftFieldDefault(schemaFieldDraft, $event)"
                          />
                        </Field>

                        <Field orientation="horizontal" class="items-center gap-3 self-end">
                          <Checkbox
                            v-field-help="'Require users to complete this field before saving.'"
                            id="schema-dialog-required"
                            v-model="schemaFieldDraft.required"
                          />
                          <FieldLabel for="schema-dialog-required">Required</FieldLabel>
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
                        <Input
                          v-field-help="'Enter the option text users see.'"
                          :model-value="String(option.label ?? '')"
                          placeholder="Label"
                          @update:model-value="updateSelectOptionLabel(option, String($event))"
                        />

                        <Input
                          v-field-help="'Enter the saved value for this option.'"
                          :model-value="String(option.value ?? '')"
                          placeholder="Value"
                          @update:model-value="updateSelectOptionValue(option, String($event))"
                        />

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

                            <Input
                              v-field-help="'Enter the label users see for this list item field.'"
                              :id="`schema-dialog-nested-label-${nestedIndex}`"
                              :model-value="nestedField.label"
                              @update:model-value="
                                updateDraftFieldLabel(nestedField, String($event))
                              "
                            />
                          </Field>

                          <Field>
                            <FieldLabel :for="`schema-dialog-nested-key-${nestedIndex}`">
                              Field key/name
                            </FieldLabel>

                            <Input
                              v-field-help="'Enter the saved key for this list item field.'"
                              :id="`schema-dialog-nested-key-${nestedIndex}`"
                              :model-value="nestedField.key"
                              @update:model-value="updateDraftFieldKey(nestedField, String($event))"
                            />
                          </Field>

                          <Field>
                            <FieldLabel :for="`schema-dialog-nested-type-${nestedIndex}`">
                              Field type
                            </FieldLabel>

                            <NativeSelect
                              v-field-help="'Choose the control type for this list item field.'"
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
                          </Field>

                          <Field v-if="nestedField.type !== 'boolean'">
                            <FieldLabel :for="`schema-dialog-nested-placeholder-${nestedIndex}`">
                              Placeholder
                            </FieldLabel>

                            <Input
                              v-field-help="'Enter example text shown before users type.'"
                              :id="`schema-dialog-nested-placeholder-${nestedIndex}`"
                              v-model="nestedField.placeholder"
                            />
                          </Field>

                          <Field
                            v-if="nestedField.type === 'boolean'"
                            orientation="horizontal"
                            class="items-center gap-3 self-end"
                          >
                            <Checkbox
                              v-field-help="'Set whether this checkbox starts checked.'"
                              :id="`schema-dialog-nested-default-${nestedIndex}`"
                              :model-value="Boolean(nestedField.default)"
                              @update:model-value="
                                updateDraftFieldDefault(nestedField, Boolean($event))
                              "
                            />
                            <FieldLabel :for="`schema-dialog-nested-default-${nestedIndex}`">
                              Default checked
                            </FieldLabel>
                          </Field>

                          <Field v-else>
                            <FieldLabel :for="`schema-dialog-nested-default-${nestedIndex}`">
                              Default value
                            </FieldLabel>

                            <Textarea
                              v-field-help="'Enter the starting value for this list item field.'"
                              v-if="
                                nestedField.type === 'textarea' || nestedField.type === 'rich_text'
                              "
                              :id="`schema-dialog-nested-default-${nestedIndex}`"
                              :model-value="String(nestedField.default ?? '')"
                              class="min-h-20"
                              @update:model-value="updateDraftFieldDefault(nestedField, $event)"
                            />
                            <Input
                              v-field-help="'Enter the starting value for this list item field.'"
                              v-else
                              :id="`schema-dialog-nested-default-${nestedIndex}`"
                              :type="nestedField.type === 'number' ? 'number' : 'text'"
                              :model-value="String(nestedField.default ?? '')"
                              @update:model-value="updateDraftFieldDefault(nestedField, $event)"
                            />
                          </Field>

                          <Field orientation="horizontal" class="items-center gap-3 self-end">
                            <Checkbox
                              v-field-help="'Require users to complete this list item field.'"
                              :id="`schema-dialog-nested-required-${nestedIndex}`"
                              v-model="nestedField.required"
                            />
                            <FieldLabel :for="`schema-dialog-nested-required-${nestedIndex}`">
                              Required
                            </FieldLabel>
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
                            <Input
                              v-field-help="'Enter the option text users see.'"
                              :model-value="String(option.label ?? '')"
                              placeholder="Label"
                              @update:model-value="updateSelectOptionLabel(option, String($event))"
                            />

                            <Input
                              v-field-help="'Enter the saved value for this option.'"
                              :model-value="String(option.value ?? '')"
                              placeholder="Value"
                              @update:model-value="updateSelectOptionValue(option, String($event))"
                            />

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
                <div class="flex flex-wrap items-center justify-end gap-2">
                  <Button
                    size="sm"
                    variant="navigate"
                    type="button"
                    :disabled="!selectedCatalogItem"
                    @click="validateCatalogItem"
                  >
                    <CircleCheck class="size-4" />
                    Validate
                  </Button>
                  <Button
                    size="sm"
                    variant="navigate"
                    type="button"
                    :disabled="!selectedCatalogItem"
                    @click="exportCatalogSchema"
                  >
                    <Download class="size-4" />
                    Export
                  </Button>
                  <Button
                    size="sm"
                    variant="navigate"
                    type="button"
                    :disabled="!selectedCatalogItem"
                    @click="openImportSchemaDialog"
                  >
                    <Upload class="size-4" />
                    Import
                  </Button>
                  <Button
                    size="sm"
                    variant="edit"
                    type="button"
                    :disabled="!selectedCatalogItem"
                    @click="cloneCatalogItem"
                  >
                    <Copy class="size-4" />
                    Clone
                  </Button>
                  <Button
                    size="sm"
                    :variant="selectedCatalogItem?.is_active ? 'restore' : 'publish'"
                    type="button"
                    :disabled="!selectedCatalogItem"
                    @click="toggleCatalogPublish"
                  >
                    {{ selectedCatalogItem?.is_active ? 'Unpublish' : 'Publish' }}
                  </Button>
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
                </div>
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
                          <Badge :variant="item.validation?.render.ok ? 'success' : 'warning'">
                            {{ item.validation?.render.ok ? 'Render ready' : 'Needs QA' }}
                          </Badge>
                          <Badge variant="secondary">
                            {{ item.usage_summary?.total ?? 0 }} tenants
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

            <div v-if="selectedCatalogItem" class="grid gap-4 xl:grid-cols-2">
              <Card class="gap-4 py-4">
                <CardHeader class="px-4">
                  <CardTitle class="text-sm">Live Preview</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 px-4">
                  <div class="overflow-hidden rounded border bg-background">
                    <img
                      v-if="selectedCatalogItem.preview_image"
                      :src="selectedCatalogItem.preview_image"
                      :alt="selectedCatalogItem.name"
                      class="h-56 w-full object-cover"
                    />
                    <Empty v-else class="min-h-56 border-0">
                      <EmptyHeader>
                        <EmptyTitle>No preview image</EmptyTitle>
                        <EmptyDescription>
                          Add a preview image to make catalog QA faster.
                        </EmptyDescription>
                      </EmptyHeader>
                    </Empty>
                  </div>

                  <div class="rounded border bg-muted/20 p-3">
                    <div class="flex items-start gap-2">
                      <component
                        :is="selectedValidation?.render.ok ? CircleCheck : CircleAlert"
                        class="mt-0.5 size-4 shrink-0"
                        :class="
                          selectedValidation?.render.ok ? 'text-emerald-600' : 'text-amber-600'
                        "
                      />
                      <div>
                        <p class="text-sm font-semibold">
                          {{
                            selectedValidation?.render.ok
                              ? 'Render Ready'
                              : 'Render Needs Attention'
                          }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                          {{ selectedValidation?.render.message ?? 'Validation has not run yet.' }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div v-if="visibleDefaultContent.length" class="grid gap-2 sm:grid-cols-2">
                    <div
                      v-for="entry in visibleDefaultContent"
                      :key="entry.key"
                      class="rounded border p-3"
                    >
                      <p class="text-xs font-semibold text-muted-foreground">{{ entry.key }}</p>
                      <p class="mt-1 line-clamp-3 text-sm">{{ entry.value }}</p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card class="gap-4 py-4">
                <CardHeader class="px-4">
                  <CardTitle class="text-sm">Tenant Usage Summary</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4 px-4">
                  <div class="grid gap-3 sm:grid-cols-5">
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Total</p>
                      <p class="mt-1 text-lg font-semibold">{{ selectedUsage?.total ?? 0 }}</p>
                    </div>
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Published</p>
                      <p class="mt-1 text-lg font-semibold">{{ selectedUsage?.published ?? 0 }}</p>
                    </div>
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Draft</p>
                      <p class="mt-1 text-lg font-semibold">{{ selectedUsage?.draft ?? 0 }}</p>
                    </div>
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Tenants</p>
                      <p class="mt-1 text-lg font-semibold">{{ selectedUsage?.tenants ?? 0 }}</p>
                    </div>
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Default</p>
                      <p class="mt-1 text-lg font-semibold">
                        {{ selectedUsage?.default_instances ?? 0 }}
                      </p>
                    </div>
                  </div>

                  <Empty v-if="!selectedUsage?.recent.length" class="min-h-28 border">
                    <EmptyHeader>
                      <EmptyTitle>No tenant usage</EmptyTitle>
                      <EmptyDescription>
                        Tenant instances appear after this catalog item is used.
                      </EmptyDescription>
                    </EmptyHeader>
                  </Empty>

                  <div v-else class="space-y-2">
                    <div
                      v-for="template in selectedUsage.recent"
                      :key="template.id"
                      class="flex items-center justify-between gap-3 rounded border p-3"
                    >
                      <div>
                        <p class="text-sm font-semibold">{{ template.business_name }}</p>
                        <p class="text-xs text-muted-foreground">
                          {{ template.tenant_name ?? template.tenant_id }}
                        </p>
                      </div>
                      <Badge :variant="getStatusBadgeVariant(template.status)">
                        {{ getStatusLabel(template.status) }}
                      </Badge>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card class="gap-4 py-4">
                <CardHeader class="px-4">
                  <CardTitle class="text-sm">QA Checklists</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 px-4 lg:grid-cols-2">
                  <div class="space-y-2 rounded border p-3">
                    <div class="flex items-center justify-between gap-3">
                      <p class="text-sm font-semibold">Mobile QA</p>
                      <Badge variant="outline">
                        {{ passedQaCount(selectedQa?.mobile) }}/{{ selectedQa?.mobile.length ?? 0 }}
                      </Badge>
                    </div>
                    <div
                      v-for="item in selectedQa?.mobile ?? []"
                      :key="item.key"
                      class="flex items-center gap-2 text-sm"
                    >
                      <component
                        :is="item.passed ? CircleCheck : CircleAlert"
                        class="size-4"
                        :class="item.passed ? 'text-emerald-600' : 'text-amber-600'"
                      />
                      <span>{{ item.label }}</span>
                    </div>
                  </div>

                  <div class="space-y-2 rounded border p-3">
                    <div class="flex items-center justify-between gap-3">
                      <p class="text-sm font-semibold">Desktop QA</p>
                      <Badge variant="outline">
                        {{ passedQaCount(selectedQa?.desktop) }}/{{
                          selectedQa?.desktop.length ?? 0
                        }}
                      </Badge>
                    </div>
                    <div
                      v-for="item in selectedQa?.desktop ?? []"
                      :key="item.key"
                      class="flex items-center gap-2 text-sm"
                    >
                      <component
                        :is="item.passed ? CircleCheck : CircleAlert"
                        class="size-4"
                        :class="item.passed ? 'text-emerald-600' : 'text-amber-600'"
                      />
                      <span>{{ item.label }}</span>
                    </div>
                  </div>
                </CardContent>
              </Card>

              <Card class="gap-4 py-4">
                <CardHeader class="px-4">
                  <CardTitle class="text-sm">Versions and Changelog</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 px-4">
                  <Empty v-if="!selectedVersions.length" class="min-h-32 border">
                    <EmptyHeader>
                      <EmptyTitle>No versions yet</EmptyTitle>
                      <EmptyDescription>
                        Versions are created when this catalog item is saved.
                      </EmptyDescription>
                    </EmptyHeader>
                  </Empty>

                  <template v-else>
                    <div
                      v-for="version in selectedVersions"
                      :key="version.id"
                      class="rounded border p-3"
                    >
                      <div class="flex items-start justify-between gap-3">
                        <div>
                          <p class="text-sm font-semibold">
                            Version {{ version.version }} &middot; {{ version.action }}
                          </p>
                          <p class="mt-1 text-xs text-muted-foreground">
                            {{ version.changelog || 'No changelog provided.' }}
                          </p>
                          <p class="mt-1 text-xs text-muted-foreground">
                            {{ version.created_by_name || version.created_by_email || 'System' }}
                            &middot;
                            {{ formatDisplayDate(version.created_at) }}
                          </p>
                        </div>
                        <Button
                          size="sm"
                          variant="restore"
                          type="button"
                          @click="rollbackCatalogItem(version.id)"
                        >
                          <RotateCcw class="size-4" />
                          Rollback
                        </Button>
                      </div>
                    </div>
                  </template>
                </CardContent>
              </Card>
            </div>
          </section>
        </main>
      </div>
    </div>
  </div>
</template>
