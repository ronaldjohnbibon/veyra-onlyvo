import axios, { type AxiosError, type AxiosResponse, type InternalAxiosRequestConfig } from 'axios'
import { useConfirmStore } from '@/shared/stores/confirm-store'
import { useToastStore } from '@/shared/stores/toast-store'

interface ApiResponse {
  message?: string
  data?: unknown
}

const notifySuccess = (message?: string): void => {
  try {
    const toast = useToastStore()
    toast.addAlert('success', 'Success', message || 'Operation completed successfully.')
  } catch {
    // Keep toast rendering issues from breaking the HTTP response flow.
  }
}

const notifyError = (message?: string): void => {
  try {
    const toast = useToastStore()
    toast.addAlert('error', 'Error', message || 'Something went wrong')
  } catch {
    // Keep toast rendering issues from hiding the original request error.
  }
}

const resolveBaseURL = (): string => {
  return import.meta.env.VITE_API_URL || '/api'
}

const resolveToken = (url: string): string | null => {
  if (url.startsWith('public/')) return null

  const tokenKey = url.startsWith('admin/') ? 'admin_token' : 'tenant_token'

  return localStorage.getItem(tokenKey)
}

const normalizeUrl = (url: string): string => {
  const normalized = url.replace(/^\/+/, '')

  if (
    !normalized.startsWith('admin/') &&
    !normalized.startsWith('app/') &&
    !normalized.startsWith('public/')
  ) {
    return `app/${normalized}`
  }

  return normalized
}

const isSilentRequest = (
  config?: InternalAxiosRequestConfig | AxiosResponse<ApiResponse>['config']
): boolean => {
  return config?.headers?.['X-Silent-Request'] === 'true'
}

const http = axios.create({
  baseURL: resolveBaseURL(),
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

http.interceptors.request.use(
  async (config: InternalAxiosRequestConfig): Promise<InternalAxiosRequestConfig> => {
    const normalizedUrl =
      config.url && !config.url.startsWith('http') ? normalizeUrl(config.url) : null
    const token = normalizedUrl ? resolveToken(normalizedUrl) : null

    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }

    if (normalizedUrl) {
      config.url = normalizedUrl
    }

    // All delete requests share the same confirmation flow.
    if (config.method?.toLowerCase() === 'delete') {
      const confirmStore = useConfirmStore()
      const confirmed = await confirmStore.confirm('Are you sure you want to delete this item?')

      if (!confirmed) {
        return Promise.reject(new axios.CanceledError('User cancelled delete'))
      }
    }

    return config
  },
  (error: AxiosError): Promise<never> => Promise.reject(error)
)

http.interceptors.response.use(
  (response: AxiosResponse<ApiResponse>): AxiosResponse<ApiResponse> => {
    if (response.config.method?.toLowerCase() !== 'get' && !isSilentRequest(response.config)) {
      notifySuccess(response.data?.message)
    }

    return response
  },
  (error: unknown): Promise<never> => {
    const silent = axios.isAxiosError<ApiResponse>(error) && isSilentRequest(error.config)

    if (axios.isAxiosError<ApiResponse>(error) && error.code !== 'ERR_CANCELED' && !silent) {
      notifyError(error.response?.data?.message || error.message)
    } else if (!axios.isCancel(error) && !silent) {
      notifyError()
    }

    return Promise.reject(error)
  }
)

export default http
