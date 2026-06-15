<script setup lang="ts">
import { useTemplateCtaForm } from '@/tenant/templates/composables/useTemplateCtaForm'
import { safeHref } from '@/shared/utils/url'
import type { TemplateCtaConfig, TemplateCtaField } from '@/shared/types/templates'
import { computed } from 'vue'

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

const templateId = computed(() => props.templateId)
const cta = computed(() => props.cta)
const ctaLabel = computed(
  () => props.cta.label || props.cta.title || props.cta.submit_label || props.cta.type
)

const descriptionForField = (field: TemplateCtaField): string => {
  if (field.type === 'select') return 'Choose one option from the list.'
  if (field.type === 'checkbox') return 'Check this box if it applies to you.'
  if (field.type === 'file') return 'Choose a file to include with your submission.'
  if (field.type === 'email') return 'Enter a valid email address.'
  if (field.type === 'phone') return 'Enter a phone number where you can be reached.'
  if (field.type === 'textarea') return 'Enter the details you want to send.'

  return 'Enter the requested information for this field.'
}

const {
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
} = useTemplateCtaForm(templateId, cta, (submittedPayload) => emit('submitted', submittedPayload))
</script>

<template>
  <form
    :class="props.formClass"
    data-cta-track="true"
    :data-cta-type="props.cta.type"
    :data-cta-label="ctaLabel"
    @focusin="trackFormOpened"
    @submit.prevent="submit"
  >
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
          v-field-help="descriptionForField(field)"
          v-if="field.type === 'textarea'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.textareaClass"
          :placeholder="field.placeholder || field.label"
          :required="field.required"
          :value="String(payload[field.key] ?? '')"
          @input="updateField(field, ($event.target as HTMLTextAreaElement).value)"
        />

        <select
          v-field-help="descriptionForField(field)"
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
          v-field-help="descriptionForField(field)"
          v-else-if="field.type === 'checkbox'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.checkboxClass"
          type="checkbox"
          :required="field.required"
          :checked="Boolean(payload[field.key])"
          @change="updateField(field, ($event.target as HTMLInputElement).checked)"
        />

        <input
          v-field-help="descriptionForField(field)"
          v-else-if="field.type === 'file'"
          :id="`cta-${props.templateId}-${field.key}`"
          :class="props.inputClass"
          type="file"
          :required="field.required"
          @change="updateFileField(field, $event)"
        />

        <input
          v-field-help="descriptionForField(field)"
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
          :href="safeHref(props.cta.downloadable_file)"
          class="underline"
        >
          Download file
        </a>
      </p>
      <p v-if="error" class="text-sm font-medium text-destructive" role="alert">{{ error }}</p>
    </slot>
  </form>
</template>
