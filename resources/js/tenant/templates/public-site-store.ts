import { defineStore } from 'pinia'
import { ref } from 'vue'
import { postService } from '@/tenant/templates/posts/api/posts'
import { templateService } from '@/tenant/templates/api/templates'
import { isNotFoundError } from '@/shared/api/errors'
import type { PostRecord } from '@/shared/types/posts'
import type { TemplateRecord } from '@/shared/types/templates'

export const usePublicSiteStore = defineStore('public-site', () => {
  const template = ref<TemplateRecord | null>(null)
  const posts = ref<PostRecord[]>([])
  const loading = ref(false)
  const notFound = ref(false)
  const errorMessage = ref('')

  const loadSite = async (siteSlug: string): Promise<void> => {
    try {
      loading.value = true
      notFound.value = false
      errorMessage.value = ''

      const response = siteSlug
        ? await templateService.publicShow(siteSlug)
        : await templateService.publicDefault()

      template.value = response.data
      posts.value = []

      if (template.value?.slug) {
        try {
          posts.value = (await postService.publicIndex(template.value.slug)).data
        } catch {
          posts.value = []
        }
      }
    } catch (error) {
      template.value = null
      posts.value = []
      notFound.value = isNotFoundError(error)
      errorMessage.value = notFound.value
        ? 'This site is not published yet or no longer exists.'
        : 'This site is temporarily unavailable.'
    } finally {
      loading.value = false
    }
  }

  return {
    loadSite,
    errorMessage,
    loading,
    notFound,
    posts,
    template,
  }
})
