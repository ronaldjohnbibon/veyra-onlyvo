import { defineStore } from 'pinia'
import { ref } from 'vue'
import { apiMessageFrom } from '@/shared/api/errors'
import { adminOperationsService } from '@/admin/operations/api/operations'
import type { PlatformOperations } from '@/admin/operations/types'

export const useAdminOperationsStore = defineStore('admin-operations', () => {
  const operations = ref<PlatformOperations | null>(null)
  const error = ref('')
  const loading = ref(false)

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      error.value = ''
      operations.value = (await adminOperationsService.index()).data
    } catch (err) {
      error.value = apiMessageFrom(err, 'Unable to load platform operations.')
    } finally {
      loading.value = false
    }
  }

  return {
    error,
    index,
    loading,
    operations,
  }
})
