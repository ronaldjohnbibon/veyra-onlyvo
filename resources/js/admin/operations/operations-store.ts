import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminOperationsService } from '@/admin/operations/api/operations'
import type { PlatformOperations } from '@/admin/operations/types'

export const useAdminOperationsStore = defineStore('admin-operations', () => {
  const operations = ref<PlatformOperations | null>(null)
  const loading = ref(false)

  const index = async (): Promise<void> => {
    try {
      loading.value = true
      operations.value = (await adminOperationsService.index()).data
    } finally {
      loading.value = false
    }
  }

  return {
    index,
    loading,
    operations,
  }
})
