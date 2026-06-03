<script setup lang="ts">
import TemplateDynamicForm from '@/tenant/templates/components/TemplateDynamicForm.vue'
import type { TemplateCtaConfig, TemplateCtaField } from '@/shared/types/templates'
import { computed } from 'vue'

defineOptions({
  name: 'TemplateCta',
})

const props = withDefaults(
  defineProps<{
    templateId: string
    cta: TemplateCtaConfig
    class?: string
    titleClass?: string
    descriptionClass?: string
    formClass?: string
    fieldClass?: string
    labelClass?: string
    inputClass?: string
    textareaClass?: string
    selectClass?: string
    checkboxClass?: string
    buttonClass?: string
    enabled?: boolean
  }>(),
  {
    class: '',
    titleClass: 'text-2xl font-semibold',
    descriptionClass: 'text-sm',
    enabled: true,
  }
)

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
  header?: (props: { cta: TemplateCtaConfig }) => unknown
  'before-fields'?: () => unknown
  'after-fields'?: () => unknown
  submit?: (props: { loading: boolean; submitLabel: string }) => unknown
}>()

const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}
const ctaFormsEnabled = computed(
  () => props.enabled && runtimeSettings['feature_flags.enable_cta_forms'] !== false
)
</script>

<template>
  <div v-if="ctaFormsEnabled" :class="props.class">
    <slot name="header" :cta="props.cta" />

    <TemplateDynamicForm
      :template-id="props.templateId"
      :cta="props.cta"
      :form-class="props.formClass"
      :field-class="props.fieldClass"
      :label-class="props.labelClass"
      :input-class="props.inputClass"
      :textarea-class="props.textareaClass"
      :select-class="props.selectClass"
      :checkbox-class="props.checkboxClass"
      :button-class="props.buttonClass"
    >
      <template v-if="$slots.default" #default="slotProps">
        <slot v-bind="slotProps" />
      </template>
      <template #before-fields>
        <slot name="before-fields" />
      </template>
      <template #after-fields>
        <slot name="after-fields" />
      </template>
      <template #submit="{ loading, submitLabel }">
        <slot name="submit" :loading="loading" :submit-label="submitLabel">
          {{ loading ? 'Sending...' : submitLabel }}
        </slot>
      </template>
    </TemplateDynamicForm>
  </div>
</template>
