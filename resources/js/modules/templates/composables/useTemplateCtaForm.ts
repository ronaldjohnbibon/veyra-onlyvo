import { useTemplateStore } from '@/modules/templates/template-store'
import type { TemplateCtaConfig, TemplateCtaField } from '@/types/templates'
import { computed, ref, type Ref } from 'vue'

export const inputTypeFor = (field: TemplateCtaField): string => {
  if (field.type === 'phone') return 'tel'
  if (['email', 'number', 'date', 'time', 'url', 'file'].includes(field.type)) return field.type

  return 'text'
}

export const useTemplateCtaForm = (
  templateId: Ref<string>,
  cta: Ref<TemplateCtaConfig>,
  emitSubmitted: (payload: Record<string, unknown>) => void
) => {
  const templateStore = useTemplateStore()
  const loading = ref(false)
  const success = ref(false)
  const error = ref('')
  const payload = ref<Record<string, unknown>>({})
  const fields = computed(() => cta.value.fields ?? [])

  const updateFileField = (field: TemplateCtaField, event: Event): void => {
    const file = (event.target as HTMLInputElement).files?.[0]

    payload.value = {
      ...payload.value,
      [field.key]: file?.name ?? '',
    }
  }

  const updateField = (field: TemplateCtaField, value: unknown): void => {
    payload.value = {
      ...payload.value,
      [field.key]: value,
    }
  }

  const submit = async (): Promise<void> => {
    try {
      loading.value = true
      error.value = ''
      success.value = false

      await templateStore.submitCta(templateId.value, cta.value, payload.value)
      success.value = true
      emitSubmitted(payload.value)

      if (cta.value.redirect_url) {
        window.location.href = cta.value.redirect_url
      }
    } catch {
      error.value = 'Please check the form and try again.'
    } finally {
      loading.value = false
    }
  }

  return {
    error,
    fields,
    inputTypeFor,
    loading,
    payload,
    submit,
    success,
    updateField,
    updateFileField,
  }
}
