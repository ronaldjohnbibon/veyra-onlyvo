export type TemplateStatus = 'draft' | 'published'

export type TemplateFieldType =
  | 'text'
  | 'textarea'
  | 'url'
  | 'email'
  | 'phone'
  | 'image'
  | 'rich_text'
  | 'color'
  | 'number'
  | 'boolean'
  | 'select'
  | 'cta'
  | 'repeater'

export type TemplateCtaType =
  | 'contact_message'
  | 'quote_request'
  | 'booking'
  | 'consultation'
  | 'support'
  | 'registration'
  | 'newsletter'
  | 'event_registration'
  | 'job_application'
  | 'feedback'
  | 'product_inquiry'
  | 'service_request'
  | 'demo_request'
  | 'lead_capture'
  | 'file_download'
  | 'donation'
  | 'callback_request'
  | 'custom_form'

export type TemplateCtaFieldType =
  | 'text'
  | 'email'
  | 'phone'
  | 'textarea'
  | 'number'
  | 'date'
  | 'time'
  | 'select'
  | 'checkbox'
  | 'file'
  | 'url'

export interface TemplateCtaField {
  key: string
  label: string
  type: TemplateCtaFieldType
  required?: boolean
  placeholder?: string
  options?: TemplateFieldOption[]
}

export interface TemplateCtaConfig {
  type: TemplateCtaType
  label?: string
  title: string
  description: string
  submit_label: string
  success_message: string
  fields: TemplateCtaField[]
  redirect_url?: string
  recipient_email?: string
  downloadable_file?: string
}

export interface TemplateFieldOption {
  label: string
  value: string | number | boolean
}

export interface TemplateFieldSchema {
  key: string
  label: string
  type: TemplateFieldType
  description?: string
  placeholder?: string
  required?: boolean
  default?: unknown
  min?: number
  max?: number
  step?: number
  options?: TemplateFieldOption[]
  fields?: TemplateFieldSchema[]
}

export type TemplateContent = Record<string, unknown>

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
  field_schema?: TemplateFieldSchema[]
  default_content?: TemplateContent
  is_active?: boolean
  usage_summary?: TemplateCatalogUsageSummary
  validation?: TemplateCatalogValidation
  qa_checklists?: TemplateCatalogQaChecklists
  versions?: TemplateCatalogVersion[]
  created_at?: string
  updated_at?: string
}

export interface TemplateCatalogUsageSummary {
  total: number
  published: number
  draft: number
  tenants: number
  default_instances: number
  recent: {
    id: string
    name: string
    business_name: string
    status: string
    tenant_id: string
    tenant_name?: string | null
    updated_at?: string | null
  }[]
}

export interface TemplateCatalogValidation {
  template_file: {
    path: string
    exists: boolean
  }
  registry_key: string
  dependencies: {
    missing: string[]
    ok: boolean
  }
  render: {
    ok: boolean
    message: string
  }
}

export interface TemplateCatalogQaItem {
  key: string
  label: string
  passed: boolean
}

export interface TemplateCatalogQaChecklists {
  mobile: TemplateCatalogQaItem[]
  desktop: TemplateCatalogQaItem[]
}

export interface TemplateCatalogVersion {
  id: string
  version: number
  action: string
  changelog?: string | null
  created_by_name?: string | null
  created_by_email?: string | null
  created_at?: string | null
  field_schema_count: number
  is_active: boolean
}

export interface TemplateCatalogExport {
  website_type_slug?: string | null
  template_key: string
  name: string
  description?: string | null
  field_schema: TemplateFieldSchema[]
  default_content: TemplateContent
  exported_at: string
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
  field_schema?: TemplateFieldSchema[] | null
  default_content?: TemplateContent | null
  is_active: boolean
  changelog?: string
}

export interface TemplateRecord {
  id: string
  tenant_id?: string
  website_type_id: string
  website_type?: WebsiteType
  name: string
  slug: string
  public_url?: string
  template_key: string
  business_name: string
  contact_info: TemplateContactInfo
  social_links: TemplateSocialLinks
  content: TemplateContent
  font_family: string
  primary_color: string
  secondary_color: string
  background_color: string
  text_color: string
  status: TemplateStatus
  is_default: boolean
  tenant_settings?: Record<string, string | number | boolean | null>
  created_at?: string
  updated_at?: string
}

export interface TemplatePayload {
  name: string
  slug: string
  website_type_id: string
  template_key: string
  business_name: string
  contact_info: TemplateContactInfo
  social_links: TemplateSocialLinks
  content: TemplateContent
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
