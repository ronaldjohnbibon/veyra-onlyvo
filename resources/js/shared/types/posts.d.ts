export type PostStatus = 'draft' | 'published' | 'scheduled'

export interface PostRecord {
  id: string
  template_id: string
  site_slug?: string | null
  site_name?: string | null
  title: string
  slug: string
  content: string
  excerpt: string
  featured_image?: string | null
  seo_title?: string | null
  meta_description?: string | null
  tags?: string[]
  status: PostStatus
  tenant_settings?: Record<string, string | number | boolean | null>
  published_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface PostPayload {
  title: string
  slug?: string | null
  content: string
  excerpt?: string | null
  featured_image?: string | null
  seo_title?: string | null
  meta_description?: string | null
  tags?: string[]
  status: PostStatus
  published_at?: string | null
}

export interface PostParams {
  direction?: 'asc' | 'desc'
  search?: string
  status?: PostStatus | ''
  page?: number
  pageSize?: number
  sort?: string
}
