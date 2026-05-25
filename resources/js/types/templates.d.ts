export type TemplateStatus = 'draft' | 'published'

export type TemplateFieldType = 'text' | 'textarea' | 'url' | 'list'

export type TemplateSectionType =
  | 'header'
  | 'hero'
  | 'about'
  | 'services'
  | 'products'
  | 'portfolio'
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

export interface TemplateFieldConfig {
  key: string
  label: string
  type: TemplateFieldType
  defaultValue?: string | string[]
  class?: string
}

export interface TemplateSectionContent extends Record<string, any> {
  title?: string
  subtitle?: string
  body?: string
  image?: string
  left_image?: string
  right_image?: string
  tagline?: string
  cta_label?: string
  button_link?: string
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

export interface TemplateDesign {
  id: string
  section_type: TemplateSectionType
  section_label: string
  name: string
  design_key: string
  preview_image: string
  fields_json: TemplateFieldConfig[]
  default_content_json: TemplateSectionContent
  default_enabled: boolean
  default_sort_order: number
  is_active: boolean
}

export interface TemplateDesignPayload {
  section_type: string
  section_label: string
  name: string
  design_key: string
  preview_image: string
  fields_json: TemplateFieldConfig[]
  default_content_json: TemplateSectionContent
  default_enabled: boolean
  default_sort_order: number
  is_active: boolean
}

export interface TemplateRecord {
  id: string
  tenant_id?: string
  name: string
  slug: string
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
  sections: TemplateSection[]
  created_at?: string
  updated_at?: string
}

export interface TemplatePayload {
  name: string
  slug: string
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
  sections: TemplateSection[]
}

export interface TemplateParams {
  direction?: 'asc' | 'desc'
  search?: string
  page?: number
  pageSize?: number
  sort?: string
}

export type TemplateDesignParams = TemplateParams
