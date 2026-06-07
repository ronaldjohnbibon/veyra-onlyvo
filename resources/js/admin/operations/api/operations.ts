import http from '@/shared/api/http'
import type { PlatformOperations } from '@/admin/operations/types'

interface PlatformOperationsResponse {
  data: PlatformOperations
}

export const adminOperationsService = {
  index() {
    return http
      .get<PlatformOperationsResponse>('admin/operations')
      .then((response) => response.data)
  },
}
