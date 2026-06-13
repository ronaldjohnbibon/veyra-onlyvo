import { getTemplateCatalogItem } from '@/shared/templates/template-catalog'
import type {
  TemplateCatalogItem,
  TemplateContent,
  TemplatePayload,
  TemplateRecord,
  TemplateStatus,
  WebsiteType,
} from '@/shared/types/templates'

export const slugify = (value: string, fallback = 'site'): string => {
  return (
    value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || fallback
  )
}

export const contentRecord = (value: unknown): TemplateContent => {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as TemplateContent)
    : {}
}

export const mergeTemplateContent = (
  catalogTemplate: TemplateCatalogItem,
  existingContent: TemplateContent = {}
): TemplateContent => {
  return {
    ...(catalogTemplate.default_content ?? {}),
    ...existingContent,
  }
}

export const stringContent = (content: TemplateContent, keys: string[], fallback = ''): string => {
  for (const key of keys) {
    const value = content[key]

    if (typeof value === 'string' && value.trim()) return value
  }

  return fallback
}

const setExistingContentValue = (
  content: TemplateContent,
  keys: string[],
  value: string
): TemplateContent => {
  for (const key of keys) {
    if (Object.prototype.hasOwnProperty.call(content, key)) {
      content[key] = value
    }
  }

  return content
}

export const contentWithTemplateDetails = (form: TemplatePayload): TemplateContent => {
  const content = { ...contentRecord(form.content) }

  setExistingContentValue(content, ['business_name', 'display_name'], form.business_name)
  setExistingContentValue(content, ['contact_email', 'email'], form.contact_info.email)
  setExistingContentValue(content, ['contact_phone', 'phone'], form.contact_info.phone)
  setExistingContentValue(
    content,
    ['contact_address', 'contact_location', 'location', 'address'],
    form.contact_info.address
  )

  return content
}

export const createBlankTemplate = (websiteType?: WebsiteType | null): TemplatePayload => {
  const templateName = websiteType?.name ?? 'Website'

  return {
    website_type_id: websiteType?.id ?? '',
    name: templateName,
    slug: slugify(templateName),
    template_key: '',
    business_name: 'Onlyvo Studio',
    logo: 'https://dummyimage.com/120x120/14b8a6/ffffff.png&text=OV',
    contact_info: {
      email: 'hello@example.com',
      phone: '+1 555 0100',
      address: '123 Market Street',
    },
    social_links: {
      website: 'https://example.com',
      linkedin: '',
      instagram: '',
      facebook: '',
    },
    content: {},
    font_family: 'Inter',
    primary_color: '#14b8a6',
    secondary_color: '#0f766e',
    background_color: '#ffffff',
    text_color: '#111827',
    status: 'draft',
    is_default: false,
  }
}

export const applyCatalogStyleDefaults = (
  payload: TemplatePayload,
  websiteType: WebsiteType | null,
  catalogTemplate: TemplateCatalogItem
): TemplatePayload => {
  if (websiteType?.slug !== 'landing-page' || catalogTemplate.key !== 'template-1') {
    return payload
  }

  return {
    ...payload,
    font_family: 'Arial',
    primary_color: '#3377aa',
    secondary_color: '#336699',
    background_color: '#ffffff',
    text_color: '#707070',
  }
}

export const payloadForSave = (form: TemplatePayload, status: TemplateStatus): TemplatePayload => {
  const content = contentWithTemplateDetails(form)

  return {
    ...form,
    business_name: form.business_name || form.name,
    contact_info: {
      email: form.contact_info.email || 'hello@example.com',
      phone: form.contact_info.phone || '+1 555 0100',
      address: form.contact_info.address,
    },
    social_links: {
      ...form.social_links,
      website: stringContent(
        content,
        ['website_url', 'portfolio_url', 'reservation_link', 'chat_url'],
        form.social_links.website
      ),
    },
    content,
    status,
  }
}

export const templateToPayload = (
  template: TemplateRecord,
  catalogTemplates: TemplateCatalogItem[]
): TemplatePayload => {
  return {
    website_type_id: template.website_type_id,
    name: template.name,
    slug: template.slug,
    template_key: template.template_key,
    business_name: template.business_name,
    logo: template.logo,
    contact_info: {
      email: template.contact_info?.email ?? '',
      phone: template.contact_info?.phone ?? '',
      address: template.contact_info?.address ?? '',
    },
    social_links: {
      website: template.social_links?.website ?? '',
      linkedin: template.social_links?.linkedin ?? '',
      instagram: template.social_links?.instagram ?? '',
      facebook: template.social_links?.facebook ?? '',
    },
    content: mergeTemplateContent(
      getTemplateCatalogItem(template.template_key, catalogTemplates),
      contentRecord(template.content)
    ),
    font_family: template.font_family,
    primary_color: template.primary_color,
    secondary_color: template.secondary_color,
    background_color: template.background_color,
    text_color: template.text_color,
    status: template.status,
    is_default: template.is_default,
  }
}
