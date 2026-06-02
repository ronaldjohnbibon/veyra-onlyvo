import { defaultTemplateCta } from '@/shared/templates/cta-presets'
import { contentRecord, slugify as baseSlugify } from '@/shared/templates/utils/template-form'
import type {
  TemplateCatalogPayload,
  TemplateContent,
  TemplateFieldOption,
  TemplateFieldSchema,
  TemplateFieldType,
  WebsiteTypePayload,
} from '@/shared/types/templates'

export const fieldTypes: { label: string; value: TemplateFieldType }[] = [
  { label: 'Text', value: 'text' },
  { label: 'Textarea', value: 'textarea' },
  { label: 'Number', value: 'number' },
  { label: 'Image', value: 'image' },
  { label: 'URL', value: 'url' },
  { label: 'Rich Text', value: 'rich_text' },
  { label: 'Boolean', value: 'boolean' },
  { label: 'Select', value: 'select' },
  { label: 'CTA', value: 'cta' },
  { label: 'Repeater/List', value: 'repeater' },
]

export const slugify = (value: string): string => baseSlugify(value, 'template')

export const sanitizeFieldKey = (value: string): string => {
  return value
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '_')
    .replace(/^_+|_+$/g, '')
}

export const fieldKeyFromLabel = (value: string): string => {
  return sanitizeFieldKey(value) || 'field'
}

export const blankWebsiteType = (): WebsiteTypePayload => ({
  name: '',
  slug: '',
  description: '',
  is_active: true,
})

export const blankCatalogItem = (websiteTypeId = ''): TemplateCatalogPayload => ({
  website_type_id: websiteTypeId,
  key: '',
  name: '',
  description: '',
  preview_image: '',
  field_schema: [],
  default_content: {},
  is_active: true,
})

export const blankSchemaField = (): TemplateFieldSchema => ({
  key: '',
  label: '',
  type: 'text',
  required: false,
  placeholder: '',
  default: '',
})

export const defaultValueFor = (field: TemplateFieldSchema): unknown => {
  if (field.type === 'boolean') return false
  if (field.type === 'number') return null
  if (field.type === 'cta') return defaultTemplateCta()
  if (field.type === 'repeater') return []

  return ''
}

export const normalizeInputValue = (field: TemplateFieldSchema, value: unknown): unknown => {
  if (field.type === 'boolean') return Boolean(value)

  if (field.type === 'number') {
    if (value === null || value === undefined || value === '') return null

    const numberValue = Number(value)
    return Number.isFinite(numberValue) ? numberValue : null
  }

  if (field.type === 'repeater') return Array.isArray(value) ? value : []

  if (field.type === 'cta') {
    return value && typeof value === 'object' && !Array.isArray(value)
      ? value
      : defaultTemplateCta()
  }

  return String(value ?? '')
}

export const contentForSchema = (
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

export const schemaForContent = (fields: TemplateFieldSchema[]): TemplateFieldSchema[] => {
  return fields
    .filter((field) => field.key.trim() && field.label.trim())
    .map((field) => ({
      ...field,
      key: field.key.trim(),
      label: field.label.trim(),
      fields: field.fields ? schemaForContent(field.fields) : undefined,
    }))
}

export const normalizeLoadedField = (field: TemplateFieldSchema): TemplateFieldSchema => {
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

export const normalizeLoadedSchema = (
  fields: TemplateFieldSchema[] | null | undefined
): TemplateFieldSchema[] => {
  return (fields ?? []).map(normalizeLoadedField)
}

export const cloneSchemaField = (field: TemplateFieldSchema): TemplateFieldSchema => {
  return normalizeLoadedField(JSON.parse(JSON.stringify(field)) as TemplateFieldSchema)
}

export const fieldSummary = (field: TemplateFieldSchema): string => {
  const parts = [field.key || 'No key', field.type]

  if (field.required) parts.push('required')
  if (field.type === 'select') parts.push(`${field.options?.length ?? 0} options`)
  if (field.type === 'repeater') parts.push(`${field.fields?.length ?? 0} list fields`)

  return parts.join(' / ')
}

export const cleanOptions = (options: TemplateFieldOption[] = []): TemplateFieldOption[] => {
  return options
    .map((option) => ({
      label: String(option.label ?? '').trim(),
      value: String(option.value ?? '').trim(),
    }))
    .filter((option) => option.label && option.value)
}

export const shouldSaveDefault = (field: TemplateFieldSchema): boolean => {
  if (field.type === 'boolean') return field.default === true
  if (field.type === 'number') return field.default !== null && field.default !== undefined
  if (field.type === 'repeater') return Array.isArray(field.default) && field.default.length > 0

  return String(field.default ?? '').trim().length > 0
}

interface BuildSchemaResult {
  fields: TemplateFieldSchema[]
  error: string
}

export const buildSchemaForSave = (
  fields: TemplateFieldSchema[],
  prefix = ''
): BuildSchemaResult => {
  const usedKeys = new Set<string>()
  const savedFields: TemplateFieldSchema[] = []

  for (const [index, field] of fields.entries()) {
    const position = `${prefix}field ${index + 1}`
    const key = field.key.trim()
    const label = field.label.trim()

    if (!label) return { fields: [], error: `Add a label for ${position}.` }

    if (!/^[a-z][a-z0-9_]*$/.test(key)) {
      return {
        fields: [],
        error: `${label} needs a field key using letters, numbers, and underscores.`,
      }
    }

    if (usedKeys.has(key)) {
      return { fields: [], error: `${label} uses a duplicate field key.` }
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
        return { fields: [], error: `${label} needs at least one select option.` }
      }

      savedField.options = options
    }

    if (field.type === 'repeater') {
      const nestedFields = buildSchemaForSave(field.fields ?? [], `${label} `)

      if (nestedFields.error) return nestedFields

      if (!nestedFields.fields.length) {
        return { fields: [], error: `${label} needs at least one list field.` }
      }

      savedField.fields = nestedFields.fields
    }

    savedFields.push(savedField)
  }

  return { fields: savedFields, error: '' }
}
