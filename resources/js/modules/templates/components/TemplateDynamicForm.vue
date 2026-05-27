<script setup lang="ts">
import { templateService } from '@/modules/templates/api/templates'
import { useLoadingStore } from '@/store/loading-store'
import type { TemplateCtaConfig, TemplateCtaField } from '@/types/templates'
import { computed, ref } from 'vue'

defineOptions({
  name: 'TemplateDynamicForm',
})

const props = withDefaults(
  defineProps<{
    templateId: string
    cta: TemplateCtaConfig
    formClass?: string
    fieldClass?: string
    labelClass?: string
    inputClass?: string
    textareaClass?: string
    selectClass?: string
    checkboxClass?: string
    buttonClass?: string
  }>(),
  {
    formClass: 'space-y-4',
    fieldClass: 'space-y-2',
    labelClass: 'block text-sm font-medium',
    inputClass: 'w-full rounded border px-3 py-2',
    textareaClass: 'min-h-32 w-full rounded border px-3 py-2',
    selectClass: 'w-full rounded border px-3 py-2',
    checkboxClass: 'size-4',
    buttonClass: 'rounded bg-primary px-4 py-2 text-primary-foreground',
  }
)

const emit = defineEmits<{
  submitted: [payload: Record<string, unknown>]
}>()

type FormSlotProps = {
  fields: TemplateCtaField[]
  payload: Record<string, unknown>
  loading: boolean
  success: boolean
  error: string
  submitLabel: string
  successMessage: string
  updateField: (field: TemplateCtaField, value: unknown) => void
  updateFileField: (field: TemplateCtaField, event: Event) => void
  inputTypeFor: (field: TemplateCtaField) => string
}

defineSlots<{
  default?: (props: FormSlotProps) => unknown
  'before-fields'?: () => unknown
  'after-fields'?: () => unknown
  submit?: (props: { loading: boolean; submitLabel: string }) => unknown
}>()

const loading = ref(false)
const loadingStore = useLoadingStore()
const success = ref(false)
const error = ref('')
const payload = ref<Record<string, unknown>>({})

const fields = computed(() => props.cta.fields ?? [])

const inputTypeFor = (field: TemplateCtaField): string => {
  if (field.type === 'phone') return 'tel'
  if (['email', 'number', 'date', 'time', 'url', 'file'].includes(field.type)) return field.type

  return 'text'
}

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

    await loadingStore.run(() =>
      templateService.submitCta(props.templateId, props.cta, payload.value)
    )
    success.value = true
    emit('submitted', payload.value)

    if (props.cta.redirect_url) {
      window.location.href = props.cta.redirect_url
    }
  } catch {
    error.value = 'Please check the form and try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <form :class="props.formClass" @submit.prevent="submit">
    <slot
      :fields="fields"
      :payload="payload"
      :loading="loading"
      :success="success"
      :error="error"
      :submit-label="props.cta.submit_label"
      :success-message="props.cta.success_message"
      :update-field="updateField"
      :update-file-field="updateFileField"
      :input-type-for="inputTypeFor"
    >
      <slot name="before-fields" />

      <div v-for="field in fields" :key="field.key" :class="props.fieldClass">
        <label :for="`cta-${props.templateId}-${field.key}`" :class="props.labelClass">
          {{ field.label }}
        </label>

        <textarea
          v-if="field.type === 'textarea'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.textareaClass"
          :placeholder="field.placeholder || field.label"
          :required="field.required"
          :value="String(payload[field.key] ?? '')"
          @input="updateField(field, ($event.target as HTMLTextAreaElement).value)"
        />

        <select
          v-else-if="field.type === 'select'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.selectClass"
          :required="field.required"
          :value="String(payload[field.key] ?? '')"
          @change="updateField(field, ($event.target as HTMLSelectElement).value)"
        >
          <option value="">Select...</option>
          <option
            v-for="option in field.options ?? []"
            :key="String(option.value)"
            :value="String(option.value)"
          >
            {{ option.label }}
          </option>
        </select>

        <input
          v-else-if="field.type === 'checkbox'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.checkboxClass"
          type="checkbox"
          :required="field.required"
          :checked="Boolean(payload[field.key])"
          @change="updateField(field, ($event.target as HTMLInputElement).checked)"
        />

        <input
          v-else-if="field.type === 'file'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.inputClass"
          type="file"
          :required="field.required"
          @change="updateFileField(field, $event)"
        />

        <input
          v-else
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.inputClass"
          :type="inputTypeFor(field)"
          :placeholder="field.placeholder || field.label"
          :required="field.required"
          :value="String(payload[field.key] ?? '')"
          @input="updateField(field, ($event.target as HTMLInputElement).value)"
        />
      </div>

      <slot name="after-fields" />

      <button :class="props.buttonClass" type="submit" :disabled="loading">
        <slot name="submit" :loading="loading" :submit-label="props.cta.submit_label">
          {{ loading ? 'Sending...' : props.cta.submit_label }}
        </slot>
      </button>

      <p
        v-if="success"
        class="text-sm font-medium text-emerald-700 dark:text-emerald-300"
        role="status"
      >
        {{ props.cta.success_message }}
        <a
          v-if="props.cta.type === 'file_download' && props.cta.downloadable_file"
          :href="props.cta.downloadable_file"
          class="underline"
        >
          Download file
        </a>
      </p>
      <p v-if="error" class="text-sm font-medium text-destructive" role="alert">{{ error }}</p>
    </slot>
  </form>
</template>
