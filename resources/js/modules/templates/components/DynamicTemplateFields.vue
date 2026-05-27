<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldDescription, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Textarea } from '@/components/ui/textarea'
import type { TemplateContent, TemplateFieldSchema } from '@/types/templates'
import { Plus, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

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

const repeaterDialogOpen = ref(false)
const activeRepeaterField = ref<TemplateFieldSchema | null>(null)
const activeRepeaterIndex = ref<number | null>(null)
const repeaterDraft = ref<TemplateContent>({})

const repeaterDialogTitle = computed(() => {
  const label = activeRepeaterField.value?.label ?? 'Item'

  return activeRepeaterIndex.value === null ? `Add ${label}` : `Edit ${label}`
})

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

const openRepeaterDialog = (field: TemplateFieldSchema, rowIndex: number | null = null): void => {
  // Keep new and existing repeater rows editable in a dialog instead of expanding the page.
  activeRepeaterField.value = field
  activeRepeaterIndex.value = rowIndex
  repeaterDraft.value =
    rowIndex === null ? defaultRowFor(field.fields) : { ...repeaterRows(field)[rowIndex] }
  repeaterDialogOpen.value = true
}

const saveRepeaterDialog = (): void => {
  if (!activeRepeaterField.value) return

  const rows = [...repeaterRows(activeRepeaterField.value)]

  if (activeRepeaterIndex.value === null) {
    rows.push(repeaterDraft.value)
  } else {
    rows[activeRepeaterIndex.value] = repeaterDraft.value
  }

  updateField(activeRepeaterField.value, rows)
  repeaterDialogOpen.value = false
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
              <Button size="sm" variant="outline" type="button" @click="openRepeaterDialog(field)">
                <Plus class="size-4" />
                Add
              </Button>
            </div>

            <div class="mt-3 space-y-2">
              <div
                v-for="(row, rowIndex) in repeaterRows(field)"
                :key="rowIndex"
                class="flex items-center justify-between gap-3 rounded border bg-background p-3"
              >
                <div class="min-w-0">
                  <p class="text-sm font-semibold">{{ field.label }} {{ rowIndex + 1 }}</p>
                  <p class="mt-1 truncate text-xs text-muted-foreground">
                    Click edit to manage this item.
                  </p>
                </div>
                <div class="flex shrink-0 items-center gap-1">
                  <Button
                    size="sm"
                    variant="outline"
                    type="button"
                    @click="openRepeaterDialog(field, rowIndex)"
                  >
                    Edit
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    type="button"
                    @click="removeRepeaterRow(field, rowIndex)"
                  >
                    <Trash2 class="size-4" />
                  </Button>
                </div>
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

  <Dialog :open="repeaterDialogOpen" @update:open="repeaterDialogOpen = $event">
    <DialogScrollContent class="max-w-[calc(100%-2rem)] sm:max-w-2xl">
      <DialogHeader>
        <DialogTitle>{{ repeaterDialogTitle }}</DialogTitle>
        <DialogDescription>Manage the fields for this list item.</DialogDescription>
      </DialogHeader>

      <DynamicTemplateFields
        v-if="activeRepeaterField"
        :schema="activeRepeaterField.fields ?? []"
        :model-value="repeaterDraft"
        @update:model-value="repeaterDraft = $event"
      />

      <DialogFooter>
        <Button variant="outline" type="button" @click="repeaterDialogOpen = false">
          Cancel
        </Button>
        <Button variant="create" type="button" @click="saveRepeaterDialog">Save</Button>
      </DialogFooter>
    </DialogScrollContent>
  </Dialog>
</template>
