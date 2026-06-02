import { ctaPayloadFromForm, submittedEventTypeFor } from '@/modules/analytics/cta-tracking'
import { useAnalyticsStore } from '@/modules/analytics/analytics-store'
import { useTemplateStore } from '@/modules/templates/template-store'
import type { TemplateCtaConfig, TemplateCtaField } from '@/types/templates'
import { computed, ref, type Ref } from 'vue'
import { useRoute } from 'vue-router'

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
  const analyticsStore = useAnalyticsStore()
  const templateStore = useTemplateStore()
  const route = useRoute()
  const loading = ref(false)
  const success = ref(false)
  const error = ref('')
  const payload = ref<Record<string, unknown>>({})
  const formOpened = ref(false)
  const fields = computed(() => cta.value.fields ?? [])
  const shouldTrackCta = computed(() => String(route.name ?? '').startsWith('public.sites.'))

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

  // Record the first form interaction as a form open event.
  const trackFormOpened = (): void => {
    if (!shouldTrackCta.value || formOpened.value) {
      return
    }

    formOpened.value = true
    void analyticsStore.trackPublicCta(
      ctaPayloadFromForm(templateId.value, cta.value, 'form_opened')
    )
  }

  const submit = async (): Promise<void> => {
    try {
      loading.value = true
      error.value = ''
      success.value = false

      await templateStore.submitCta(templateId.value, cta.value, payload.value)
      success.value = true
      emitSubmitted(payload.value)

      if (shouldTrackCta.value) {
        const eventType = submittedEventTypeFor(cta.value.type)

        void analyticsStore.trackPublicCta(
          ctaPayloadFromForm(templateId.value, cta.value, eventType)
        )
      }

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
    trackFormOpened,
    updateField,
    updateFileField,
  }
}
