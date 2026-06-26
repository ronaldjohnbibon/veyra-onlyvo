import type { TemplateContactInfo, TemplateRecord } from '@/shared/types/templates'

export type TenantPublicSettings = Record<string, string | number | boolean | null>

export const settingString = (
  settings: TenantPublicSettings | undefined,
  key: string,
  fallback = ''
): string => {
  const value = settings?.[key]

  return value === null || value === undefined ? fallback : String(value)
}

export const settingBoolean = (
  settings: TenantPublicSettings | undefined,
  key: string,
  fallback = false
): boolean => {
  const value = settings?.[key]

  return value === null || value === undefined ? fallback : Boolean(value)
}

export const effectiveTemplate = (template: TemplateRecord): TemplateRecord => {
  const settings = template.tenant_settings
  const footerText = settingString(settings, 'website.footer_text')
  const content = {
    ...template.content,
    ...(footerText ? { footer_text: footerText } : {}),
  }
  const contactInfo: TemplateContactInfo = {
    email: settingString(settings, 'profile.contact_email', template.contact_info.email),
    phone: settingString(settings, 'profile.contact_phone', template.contact_info.phone),
    address: settingString(settings, 'profile.contact_address', template.contact_info.address),
  }

  return {
    ...template,
    business_name: settingString(settings, 'profile.business_name', template.business_name),
    contact_info: contactInfo,
    content,
    font_family: publicFontFamily(
      settingString(settings, 'branding.font_family', template.font_family)
    ),
    primary_color: settingString(settings, 'branding.primary_color', template.primary_color),
    secondary_color: settingString(settings, 'branding.accent_color', template.secondary_color),
  }
}

export const buttonRadius = (settings: TenantPublicSettings | undefined): string => {
  const radius = settingString(settings, 'branding.button_radius', 'medium')

  return (
    {
      none: '0px',
      small: '4px',
      medium: '8px',
      large: '14px',
      pill: '9999px',
    }[radius] ?? '8px'
  )
}

export const fallbackImage = (settings: TenantPublicSettings | undefined): string => {
  return settingString(settings, 'branding.fallback_image') || settingString(settings, 'seo.open_graph_image')
}

export const applyTenantPublicHead = (
  settings: TenantPublicSettings | undefined,
  options: {
    title?: string | null
    description?: string | null
    image?: string | null
    indexable?: boolean
    path?: string
  } = {}
): void => {
  const profileName = settingString(settings, 'profile.business_name', 'Onlyvo')
  const title = options.title || settingString(settings, 'seo.default_meta_title', profileName)
  const description =
    options.description ||
    settingString(
      settings,
      'seo.default_meta_description',
      settingString(settings, 'profile.description')
    )
  const image = options.image || fallbackImage(settings)

  document.title = title
  setMeta('name', 'description', description)
  setMeta(
    'name',
    'robots',
    (options.indexable ?? settingBoolean(settings, 'seo.allow_search_engine_indexing', true))
      ? 'index,follow'
      : 'noindex,nofollow'
  )
  setMeta('property', 'og:title', title)
  setMeta('property', 'og:description', description)
  if (image) {
    setMeta('property', 'og:image', absoluteUrl(image))
  } else {
    removeMeta('property', 'og:image')
  }

  const favicon = settingString(settings, 'profile.favicon')
  if (favicon) setLink('icon', absoluteUrl(favicon))

  const canonicalDomain = canonicalBaseUrl(settingString(settings, 'seo.canonical_domain'))
  if (canonicalDomain) {
    const path = options.path || window.location.pathname
    setLink('canonical', `${canonicalDomain}${path.startsWith('/') ? path : `/${path}`}`)
  } else {
    removeLink('canonical')
  }
}

const publicFontFamily = (font: string): string => {
  return (
    {
      system: 'Inter',
      inter: 'Inter',
      serif: 'Georgia',
      mono: 'ui-monospace',
    }[font] ?? font
  )
}

const setMeta = (attribute: 'name' | 'property', key: string, content: string): void => {
  let element = document.head.querySelector<HTMLMetaElement>(`meta[${attribute}="${key}"]`)

  if (!element) {
    element = document.createElement('meta')
    element.setAttribute(attribute, key)
    document.head.appendChild(element)
  }

  element.content = content
}

const setLink = (rel: string, href: string): void => {
  let element = document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)

  if (!element) {
    element = document.createElement('link')
    element.rel = rel
    document.head.appendChild(element)
  }

  element.href = href
}

const removeMeta = (attribute: 'name' | 'property', key: string): void => {
  document.head.querySelector<HTMLMetaElement>(`meta[${attribute}="${key}"]`)?.remove()
}

const removeLink = (rel: string): void => {
  document.head.querySelector<HTMLLinkElement>(`link[rel="${rel}"]`)?.remove()
}

const absoluteUrl = (value: string): string => {
  if (!value) return ''
  if (/^https?:\/\//i.test(value)) return value

  return `${window.location.origin}${value.startsWith('/') ? value : `/${value}`}`
}

const canonicalBaseUrl = (value: string): string => {
  const trimmed = value.trim().replace(/\/+$/, '')

  if (!trimmed) return ''
  if (/^https?:\/\//i.test(trimmed)) return trimmed

  return `https://${trimmed}`
}
