export type PostStatus = 'draft' | 'published'

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
  status: PostStatus
  tenant_settings?: Record<string, string | number | boolean | null>
  published_at?: string | null
  created_at?: string
  updated_at?: string
}

export interface PostPayload {
  title: string
  content: string
  featured_image?: string | null
  status: PostStatus
}

export interface PostParams {
  direction?: 'asc' | 'desc'
  search?: string
  status?: PostStatus | ''
  page?: number
  pageSize?: number
  sort?: string
}
