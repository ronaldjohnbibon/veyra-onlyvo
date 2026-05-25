import http from '@/shared/api/http'
import type { PostParams, PostPayload, PostRecord } from '@/types/posts'

interface PostCollectionResponse {
  data: PostRecord[]
  pagination?: {
    total: number
  }
}

interface PostResponse {
  data: PostRecord
}

export const postService = {
  index(templateId: string, params: PostParams = {}) {
    return http
      .get<PostCollectionResponse>(`templates/${templateId}/posts`, { params })
      .then((response) => response.data)
  },

  show(templateId: string, postId: string) {
    return http
      .get<PostResponse>(`templates/${templateId}/posts/${postId}`)
      .then((response) => response.data)
  },

  store(templateId: string, payload: PostPayload) {
    return http
      .post<PostResponse>(`templates/${templateId}/posts`, payload)
      .then((response) => response.data)
  },

  update(templateId: string, postId: string, payload: PostPayload) {
    return http
      .put<PostResponse>(`templates/${templateId}/posts/${postId}`, payload)
      .then((response) => response.data)
  },

  destroy(templateId: string, postId: string) {
    return http.delete(`templates/${templateId}/posts/${postId}`).then((response) => response.data)
  },

  publish(templateId: string, postId: string) {
    return http
      .post<PostResponse>(`templates/${templateId}/posts/${postId}/publish`)
      .then((response) => response.data)
  },

  unpublish(templateId: string, postId: string) {
    return http
      .post<PostResponse>(`templates/${templateId}/posts/${postId}/unpublish`)
      .then((response) => response.data)
  },

  publicIndex(siteSlug: string) {
    return http
      .get<PostCollectionResponse>(`public/sites/${siteSlug}/posts`)
      .then((response) => response.data)
  },

  publicShow(siteSlug: string, postSlug: string) {
    return http
      .get<PostResponse>(`public/sites/${siteSlug}/posts/${postSlug}`)
      .then((response) => response.data)
  },
}
