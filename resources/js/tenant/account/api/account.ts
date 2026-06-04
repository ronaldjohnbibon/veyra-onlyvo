import http from '@/shared/api/http'
import type {
  AccountPasswordPayload,
  AccountPayload,
  AccountProfilePayload,
} from '@/tenant/account/types'

interface AccountResponse {
  data: AccountPayload
}

export const accountService = {
  show() {
    return http.get<AccountResponse>('account').then((response) => response.data)
  },

  updateProfile(payload: AccountProfilePayload) {
    return http.put<AccountResponse>('account/profile', payload).then((response) => response.data)
  },

  updatePassword(payload: AccountPasswordPayload) {
    return http.put<AccountResponse>('account/password', payload).then((response) => response.data)
  },
}
