export type PostStatus = 'draft' | 'published'

export interface PostRecord {
  id: string
  template_id: string
  title: string
  slug: string
  content: string
  excerpt: string
  featured_image?: string | null
  status: PostStatus
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
  search?: string
  status?: PostStatus | ''
  page?: number
  pageSize?: number
}
