<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Field, FieldDescription, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Textarea } from '@/components/ui/textarea'
import type { TemplateContent, TemplateFieldSchema } from '@/types/templates'
import { Plus, Trash2 } from 'lucide-vue-next'

defineOptions({
  name: 'DynamicTemplateFields',
})

const props = defineProps<{
  schema: TemplateFieldSchema[]
  modelValue: TemplateContent
}>()

const emit = defineEmits<{
  'update:modelValue': [value: TemplateContent]
}>()

const fieldId = (field: TemplateFieldSchema): string => `template-content-${field.key}`

const asRecord = (value: unknown): TemplateContent => {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as TemplateContent)
    : {}
}

const valueFor = (field: TemplateFieldSchema): unknown => {
  return props.modelValue?.[field.key] ?? field.default ?? defaultValueFor(field)
}

const defaultValueFor = (field: TemplateFieldSchema): unknown => {
  if (field.type === 'boolean') return false
  if (field.type === 'repeater') return []
  if (field.type === 'number') return null

  return ''
}

const defaultRowFor = (fields: TemplateFieldSchema[] = []): TemplateContent => {
  return fields.reduce<TemplateContent>((row, field) => {
    row[field.key] = field.default ?? defaultValueFor(field)

    return row
  }, {})
}

const updateField = (field: TemplateFieldSchema, value: unknown): void => {
  // Emit a new object so nested edits update parent previews.
  emit('update:modelValue', {
    ...(props.modelValue ?? {}),
    [field.key]: value,
  })
}

const updateNumberField = (field: TemplateFieldSchema, event: Event): void => {
  const value = (event.target as HTMLInputElement).value

  updateField(field, value === '' ? null : Number(value))
}

const updateImageField = (field: TemplateFieldSchema, event: Event): void => {
  const file = (event.target as HTMLInputElement).files?.[0]

  if (!file) return

  const reader = new FileReader()
  reader.onload = () => updateField(field, String(reader.result))
  reader.readAsDataURL(file)
}

const repeaterRows = (field: TemplateFieldSchema): TemplateContent[] => {
  const value = valueFor(field)

  return Array.isArray(value) ? value.map((row) => asRecord(row)) : []
}

const addRepeaterRow = (field: TemplateFieldSchema): void => {
  updateField(field, [...repeaterRows(field), defaultRowFor(field.fields)])
}

const updateRepeaterRow = (
  field: TemplateFieldSchema,
  rowIndex: number,
  value: TemplateContent
): void => {
  const rows = [...repeaterRows(field)]
  rows[rowIndex] = value
  updateField(field, rows)
}

const removeRepeaterRow = (field: TemplateFieldSchema, rowIndex: number): void => {
  updateField(
    field,
    repeaterRows(field).filter((_, index) => index !== rowIndex)
  )
}
</script>

<template>
  <FieldGroup>
    <FieldSet>
      <div class="grid gap-4 md:grid-cols-2">
        <template v-for="field in props.schema" :key="field.key">
          <Field
            v-if="field.type === 'boolean'"
            orientation="horizontal"
            class="items-center gap-3 self-end"
          >
            <Checkbox
              :id="fieldId(field)"
              :model-value="Boolean(valueFor(field))"
              @update:model-value="updateField(field, Boolean($event))"
            />
            <div>
              <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
              <FieldDescription v-if="field.description">
                {{ field.description }}
              </FieldDescription>
            </div>
          </Field>

          <Field
            v-else-if="field.type === 'textarea' || field.type === 'rich_text'"
            class="md:col-span-2"
          >
            <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
            <Textarea
              :id="fieldId(field)"
              :model-value="String(valueFor(field) ?? '')"
              :placeholder="field.placeholder"
              class="min-h-28"
              :required="field.required"
              @update:model-value="updateField(field, $event)"
            />
            <FieldDescription v-if="field.description">{{ field.description }}</FieldDescription>
          </Field>

          <Field v-else-if="field.type === 'select'">
            <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
            <NativeSelect
              :id="fieldId(field)"
              class="w-full"
              :model-value="String(valueFor(field) ?? '')"
              :required="field.required"
              @update:model-value="updateField(field, $event)"
            >
              <NativeSelectOption value="">Select...</NativeSelectOption>
              <NativeSelectOption
                v-for="option in field.options ?? []"
                :key="String(option.value)"
                :value="String(option.value)"
              >
                {{ option.label }}
              </NativeSelectOption>
            </NativeSelect>
            <FieldDescription v-if="field.description">{{ field.description }}</FieldDescription>
          </Field>

          <Field v-else-if="field.type === 'repeater'" class="md:col-span-2">
            <div class="flex items-start justify-between gap-3">
              <div>
                <FieldLabel>{{ field.label }}</FieldLabel>
                <FieldDescription v-if="field.description">{{
                  field.description
                }}</FieldDescription>
              </div>
              <Button size="sm" variant="outline" type="button" @click="addRepeaterRow(field)">
                <Plus class="size-4" />
                Add
              </Button>
            </div>

            <div class="mt-3 space-y-3">
              <div
                v-for="(row, rowIndex) in repeaterRows(field)"
                :key="rowIndex"
                class="rounded border bg-background p-3"
              >
                <div class="mb-3 flex items-center justify-between gap-3">
                  <p class="text-xs font-semibold text-muted-foreground">
                    {{ field.label }} {{ rowIndex + 1 }}
                  </p>
                  <Button
                    size="sm"
                    variant="ghost"
                    type="button"
                    @click="removeRepeaterRow(field, rowIndex)"
                  >
                    <Trash2 class="size-4" />
                  </Button>
                </div>

                <DynamicTemplateFields
                  :schema="field.fields ?? []"
                  :model-value="row"
                  @update:model-value="updateRepeaterRow(field, rowIndex, $event)"
                />
              </div>
            </div>
          </Field>

          <Field v-else>
            <FieldLabel :for="fieldId(field)">{{ field.label }}</FieldLabel>
            <Input
              v-if="field.type === 'number'"
              :id="fieldId(field)"
              type="number"
              :model-value="String(valueFor(field) ?? '')"
              :placeholder="field.placeholder"
              :min="field.min"
              :max="field.max"
              :step="field.step"
              :required="field.required"
              @input="updateNumberField(field, $event)"
            />
            <div v-else-if="field.type === 'image'" class="space-y-2">
              <Input
                :id="fieldId(field)"
                type="url"
                :model-value="String(valueFor(field) ?? '')"
                :placeholder="field.placeholder"
                :required="field.required"
                @update:model-value="updateField(field, $event)"
              />
              <Input
                type="file"
                accept="image/*"
                class="cursor-pointer text-muted-foreground"
                @change="updateImageField(field, $event)"
              />
            </div>
            <Input
              v-else
              :id="fieldId(field)"
              :type="
                field.type === 'url' || field.type === 'email' || field.type === 'color'
                  ? field.type
                  : field.type === 'phone'
                    ? 'tel'
                    : 'text'
              "
              :model-value="String(valueFor(field) ?? '')"
              :placeholder="field.placeholder"
              :required="field.required"
              @update:model-value="updateField(field, $event)"
            />
            <FieldDescription v-if="field.description">{{ field.description }}</FieldDescription>
          </Field>
        </template>
      </div>
    </FieldSet>
  </FieldGroup>
</template>
