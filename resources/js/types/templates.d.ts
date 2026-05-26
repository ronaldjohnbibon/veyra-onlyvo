export type TemplateStatus = 'draft' | 'published'

export type TemplateSectionType =
  | 'header'
  | 'hero'
  | 'about'
  | 'services'
  | 'products'
  | 'portfolio'
  | 'cta'
  | 'testimonials'
  | 'team'
  | 'pricing'
  | 'booking'
  | 'clients'
  | 'statistics'
  | 'process'
  | 'gallery'
  | 'newsletter'
  | 'location'
  | 'social_links'
  | 'features'
  | 'mission_vision'
  | 'faq'
  | 'contact'
  | 'footer'

export interface TemplateContactInfo {
  email: string
  phone: string
  address: string
}

export interface TemplateSocialLinks {
  website: string
  linkedin: string
  instagram: string
  facebook: string
}

export interface TemplateNavigationItem {
  label: string
  section_type: TemplateSectionType
}

export interface TemplateSectionContent extends Record<string, any> {
  title?: string
  subtitle?: string
  body?: string
  image?: string
  tagline?: string
  cta_label?: string
  button_link?: string
  navigation_items?: TemplateNavigationItem[]
  email_placeholder?: string
  map_url?: string
  schedule?: string
  items?: string[]
  stats?: string[]
}

export interface TemplateSection {
  id?: string
  section_type: TemplateSectionType
  design_key: string
  sort_order: number
  is_enabled: boolean
  content_json: TemplateSectionContent
}

export interface WebsiteType {
  id: string
  name: string
  slug: string
  description?: string | null
  sort_order: number
  is_active: boolean
  designs_count: number
  templates_count: number
}

export interface TemplatePreset {
  id?: string
  website_type_id?: string
  key: string
  name: string
  description?: string | null
  preview_image?: string | null
  is_active?: boolean
  sort_order?: number
  sections?: TemplateSection[]
}

export interface TemplateRecord {
  id: string
  tenant_id?: string
  website_type_id: string
  website_type?: WebsiteType
  name: string
  slug: string
  template_key: string | null
  business_name: string
  logo: string
  contact_info: TemplateContactInfo
  social_links: TemplateSocialLinks
  font_family: string
  primary_color: string
  secondary_color: string
  background_color: string
  text_color: string
  status: TemplateStatus
  is_default: boolean
  sections?: TemplateSection[]
  created_at?: string
  updated_at?: string
}

export interface TemplatePayload {
  name: string
  slug: string
  website_type_id: string
  template_key: string | null
  business_name: string
  logo: string
  contact_info: TemplateContactInfo
  social_links: TemplateSocialLinks
  font_family: string
  primary_color: string
  secondary_color: string
  background_color: string
  text_color: string
  status: TemplateStatus
  is_default: boolean
}

export interface TemplateParams {
  direction?: 'asc' | 'desc'
  search?: string
  website_type_id?: string
  page?: number
  pageSize?: number
  sort?: string
}
