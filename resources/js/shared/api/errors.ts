import axios from 'axios'

interface ApiErrorResponse {
  errors?: Record<string, string[]>
  message?: string
}

export const validationErrorsFrom = (error: unknown): Record<string, string[]> => {
  if (!axios.isAxiosError<ApiErrorResponse>(error)) return {}

  return error.response?.data?.errors ?? {}
}

export const isNotFoundError = (error: unknown): boolean => {
  return axios.isAxiosError(error) && error.response?.status === 404
}

export const apiMessageFrom = (error: unknown, fallback: string): string => {
  if (!axios.isAxiosError<ApiErrorResponse>(error)) return fallback

  return error.response?.data?.message ?? fallback
}
