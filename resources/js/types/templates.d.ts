export type TemplateStatus = 'draft' | 'published'

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

export interface WebsiteType {
  id: string
  name: string
  slug: string
  description?: string | null
  is_active: boolean
  available_templates_count: number
  templates_count: number
}

export interface TemplateCatalogItem {
  id?: string
  website_type_id?: string
  website_type_slug?: string
  website_type?: WebsiteType
  key: string
  name: string
  description?: string | null
  preview_image?: string | null
  is_active?: boolean
  created_at?: string
  updated_at?: string
}

export interface WebsiteTypePayload {
  name: string
  slug: string
  description: string
  is_active: boolean
}

export interface TemplateCatalogPayload {
  website_type_id: string
  key: string
  name: string
  description: string
  preview_image: string
  is_active: boolean
}

export interface TemplateRecord {
  id: string
  tenant_id?: string
  website_type_id: string
  website_type?: WebsiteType
  name: string
  slug: string
  template_key: string
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
  created_at?: string
  updated_at?: string
}

export interface TemplatePayload {
  name: string
  slug: string
  website_type_id: string
  template_key: string
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
