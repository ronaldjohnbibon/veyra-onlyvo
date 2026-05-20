import type {
  TemplateDesign,
  TemplateFieldConfig,
  TemplateFieldType,
  TemplateSectionContent,
  TemplateSectionType,
} from '@/types/templates'

export type { TemplateFieldConfig, TemplateFieldType }

const fieldTypes: TemplateFieldType[] = ['text', 'textarea', 'url', 'list']

const cloneValue = (value: unknown): string | string[] => {
  return Array.isArray(value) ? [...value].map(String) : String(value ?? '')
}

const cloneContent = (content: TemplateSectionContent = {}): TemplateSectionContent => {
  return Object.entries(content).reduce<TemplateSectionContent>((defaults, [key, value]) => {
    defaults[key] = cloneValue(value)

    return defaults
  }, {})
}

export const designForSection = (
  designs: TemplateDesign[],
  sectionType: TemplateSectionType,
  designKey?: string
): TemplateDesign | undefined => {
  const sectionDesigns = designs.filter((design) => design.section_type === sectionType)

  return sectionDesigns.find((design) => design.design_key === designKey) ?? sectionDesigns[0]
}

export const fieldsForDesign = (
  designs: TemplateDesign[],
  sectionType: TemplateSectionType,
  designKey: string
): TemplateFieldConfig[] => {
  const design = designForSection(designs, sectionType, designKey)

  if (!Array.isArray(design?.fields_json)) return []

  return design.fields_json
    .map((field) => ({
      key: String(field.key ?? ''),
      label: String(field.label ?? field.key ?? ''),
      type: fieldTypes.includes(field.type) ? field.type : 'text',
      defaultValue: field.defaultValue,
      class: field.class,
    }))
    .filter((field) => field.key)
}

export const contentForDesign = (
  designs: TemplateDesign[],
  sectionType: TemplateSectionType,
  designKey: string,
  existing: TemplateSectionContent = {}
): TemplateSectionContent => {
  const design = designForSection(designs, sectionType, designKey)
  const defaults = cloneContent(design?.default_content_json ?? {})
  const fields = fieldsForDesign(designs, sectionType, designKey)

  if (!fields.length) return defaults

  // Prefer saved content, then design defaults, then the field fallback.
  return fields.reduce<TemplateSectionContent>((content, field) => {
    const value = existing[field.key]

    content[field.key] =
      value !== undefined && value !== null
        ? cloneValue(value)
        : (defaults[field.key] ??
          cloneValue(field.defaultValue ?? (field.type === 'list' ? [] : '')))

    return content
  }, {})
}
