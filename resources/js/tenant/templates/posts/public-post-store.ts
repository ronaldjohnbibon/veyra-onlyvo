import { defineStore } from 'pinia'
import { ref } from 'vue'
import { postService } from '@/tenant/templates/posts/api/posts'
import { isNotFoundError } from '@/shared/api/errors'
import type { PostRecord } from '@/shared/types/posts'

export const usePublicPostStore = defineStore('public-post', () => {
  const post = ref<PostRecord | null>(null)
  const loading = ref(false)
  const notFound = ref(false)

  const loadPost = async (siteSlug: string, postSlug: string): Promise<void> => {
    try {
      loading.value = true
      notFound.value = false
      post.value = (await postService.publicShow(siteSlug, postSlug)).data
    } catch (error) {
      post.value = null
      notFound.value = isNotFoundError(error)
    } finally {
      loading.value = false
    }
  }

  return {
    loadPost,
    loading,
    notFound,
    post,
  }
})
