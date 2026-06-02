import { defineStore } from 'pinia'
import { ref } from 'vue'
import { postService } from './api/posts'
import { validationErrorsFrom } from '@/shared/api/errors'
import type { PostParams, PostPayload, PostRecord } from '@/shared/types/posts'

export const usePostStore = defineStore('tenant-posts', () => {
  const posts = ref<PostRecord[]>([])
  const post = ref<PostRecord | null>(null)
  const loading = ref(false)
  const errors = ref<Record<string, string[]>>({})
  const total = ref(0)
  const params = ref<PostParams>({
    page: 1,
    pageSize: 15,
    search: '',
    status: '',
  })

  const index = async (templateId: string, newParams: Partial<PostParams> = {}): Promise<void> => {
    try {
      loading.value = true
      params.value = { ...params.value, ...newParams }

      const data = await postService.index(templateId, params.value)
      posts.value = data.data ?? []
      total.value = data.pagination?.total ?? posts.value.length
    } finally {
      loading.value = false
    }
  }

  const show = async (templateId: string, postId: string): Promise<void> => {
    try {
      loading.value = true
      post.value = (await postService.show(templateId, postId)).data
    } finally {
      loading.value = false
    }
  }

  const store = async (templateId: string, payload: PostPayload): Promise<PostRecord> => {
    try {
      loading.value = true
      errors.value = {}
      post.value = (await postService.store(templateId, payload)).data
      await index(templateId)
      return post.value
    } catch (err) {
      // Keep Laravel validation errors keyed by field for the dialog.
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const update = async (
    templateId: string,
    postId: string,
    payload: PostPayload
  ): Promise<PostRecord> => {
    try {
      loading.value = true
      errors.value = {}
      post.value = (await postService.update(templateId, postId, payload)).data
      await index(templateId)
      return post.value
    } catch (err) {
      // Keep Laravel validation errors keyed by field for the dialog.
      errors.value = validationErrorsFrom(err)
      throw err
    } finally {
      loading.value = false
    }
  }

  const uploadFeaturedImage = async (templateId: string, image: File): Promise<string> => {
    try {
      loading.value = true
      return (await postService.uploadFeaturedImage(templateId, image)).data.url
    } finally {
      loading.value = false
    }
  }

  const destroy = async (templateId: string, postId: string): Promise<void> => {
    try {
      loading.value = true
      await postService.destroy(templateId, postId)
      if (post.value?.id === postId) post.value = null
      await index(templateId)
    } finally {
      loading.value = false
    }
  }

  const publish = async (templateId: string, postId: string): Promise<void> => {
    try {
      loading.value = true
      post.value = (await postService.publish(templateId, postId)).data
      await index(templateId)
    } finally {
      loading.value = false
    }
  }

  const unpublish = async (templateId: string, postId: string): Promise<void> => {
    try {
      loading.value = true
      post.value = (await postService.unpublish(templateId, postId)).data
      await index(templateId)
    } finally {
      loading.value = false
    }
  }

  return {
    destroy,
    errors,
    index,
    loading,
    params,
    post,
    posts,
    publish,
    show,
    store,
    total,
    unpublish,
    update,
    uploadFeaturedImage,
  }
})
