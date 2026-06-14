import { defineStore } from 'pinia'
import { reactive, ref } from 'vue'
import { apiMessageFrom, validationErrorsFrom } from '@/shared/api/errors'
import { useAuthStore } from '@/tenant/auth/auth-store'
import { accountService } from '@/tenant/account/api/account'
import type {
  AccountPasswordPayload,
  AccountPayload,
  AccountProfilePayload,
} from '@/tenant/account/types'

export const useAccountStore = defineStore('tenant-account', () => {
  const authStore = useAuthStore()
  const account = ref<AccountPayload | null>(null)
  const loading = ref(false)
  const loadingAction = ref<'show' | 'profile' | 'password' | null>(null)
  const errors = ref<Record<string, string[]>>({})
  const message = ref('')
  const messageType = ref<'success' | 'error' | ''>('')

  const profileForm = reactive<AccountProfilePayload>({
    name: '',
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
  })

  const passwordForm = reactive<AccountPasswordPayload>({
    current_password: '',
    password: '',
    password_confirmation: '',
  })

  const hydrateProfile = (): void => {
    const user = account.value?.user ?? authStore.user

    profileForm.name = user?.name ?? ''
    profileForm.first_name = user?.first_name ?? ''
    profileForm.last_name = user?.last_name ?? ''
    profileForm.email = user?.email ?? ''
    profileForm.phone = user?.phone ?? ''
  }

  const clearPassword = (): void => {
    passwordForm.current_password = ''
    passwordForm.password = ''
    passwordForm.password_confirmation = ''
  }

  const setAccount = (payload: AccountPayload): void => {
    account.value = payload
    authStore.user = payload.user
    hydrateProfile()
  }

  const show = async (): Promise<void> => {
    try {
      loading.value = true
      loadingAction.value = 'show'
      errors.value = {}
      message.value = ''
      messageType.value = ''
      setAccount((await accountService.show()).data)
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to load account settings.')
      messageType.value = 'error'
    } finally {
      loading.value = false
      loadingAction.value = null
    }
  }

  const updateProfile = async (): Promise<void> => {
    try {
      loading.value = true
      loadingAction.value = 'profile'
      errors.value = {}
      message.value = ''
      messageType.value = ''
      setAccount((await accountService.updateProfile(profileForm)).data)
      message.value = 'Profile updated.'
      messageType.value = 'success'
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to update profile.')
      messageType.value = 'error'
      throw err
    } finally {
      loading.value = false
      loadingAction.value = null
    }
  }

  const updatePassword = async (): Promise<void> => {
    try {
      loading.value = true
      loadingAction.value = 'password'
      errors.value = {}
      message.value = ''
      messageType.value = ''
      setAccount((await accountService.updatePassword(passwordForm)).data)
      clearPassword()
      message.value = 'Password updated.'
      messageType.value = 'success'
    } catch (err) {
      errors.value = validationErrorsFrom(err)
      message.value = apiMessageFrom(err, 'Unable to update password.')
      messageType.value = 'error'
      throw err
    } finally {
      loading.value = false
      loadingAction.value = null
    }
  }

  return {
    account,
    clearPassword,
    errors,
    hydrateProfile,
    loading,
    loadingAction,
    message,
    messageType,
    passwordForm,
    profileForm,
    show,
    updatePassword,
    updateProfile,
  }
})
