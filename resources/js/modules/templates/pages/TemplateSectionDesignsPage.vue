<script setup lang="ts">
import BaseTable from '@/components/BaseTable.vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldLabel } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { useTemplateDesignStore } from '@/modules/templates/template-design-store'
import type { TemplateDesign, TemplateDesignPayload } from '@/types/templates'
import { Pencil, Trash2 } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'

const designStore = useTemplateDesignStore()
const dialogOpen = ref(false)
const editingDesign = ref<TemplateDesign | null>(null)
const formError = ref('')
const fieldsText = ref('[]')
const defaultContentText = ref('{}')

const tableColumns = [
  { key: 'section', label: 'Section', sortable: true, sortKey: 'section_type' },
  { key: 'name', label: 'Name', sortable: true },
  { key: 'design_key', label: 'Key', sortable: true },
  { key: 'default', label: 'Default', sortable: true, sortKey: 'default_sort_order' },
  { key: 'status', label: 'Status', sortable: true, sortKey: 'is_active' },
  { key: 'actions', label: 'Actions', headerClass: 'w-28 text-right', cellClass: 'text-right' },
] as const

const blankForm = (): TemplateDesignPayload => ({
  section_type: '',
  section_label: '',
  name: '',
  design_key: '',
  preview_image: '',
  fields_json: [],
  default_content_json: {},
  default_enabled: true,
  default_sort_order: 0,
  is_active: true,
})

const form = ref<TemplateDesignPayload>(blankForm())

const openCreate = () => {
  editingDesign.value = null
  form.value = blankForm()
  fieldsText.value = '[]'
  defaultContentText.value = '{}'
  formError.value = ''
  dialogOpen.value = true
}

const openEdit = (design: TemplateDesign) => {
  editingDesign.value = design
  form.value = {
    section_type: design.section_type,
    section_label: design.section_label,
    name: design.name,
    design_key: design.design_key,
    preview_image: design.preview_image,
    fields_json: design.fields_json ?? [],
    default_content_json: design.default_content_json ?? {},
    default_enabled: design.default_enabled,
    default_sort_order: design.default_sort_order,
    is_active: design.is_active,
  }
  fieldsText.value = JSON.stringify(form.value.fields_json, null, 2)
  defaultContentText.value = JSON.stringify(form.value.default_content_json, null, 2)
  formError.value = ''
  dialogOpen.value = true
}

const parseJson = (value: string, fallback: unknown): unknown => {
  const trimmed = value.trim()

  return trimmed ? JSON.parse(trimmed) : fallback
}

const saveDesign = async () => {
  try {
    formError.value = ''
    const fields = parseJson(fieldsText.value, [])
    const defaultContent = parseJson(defaultContentText.value, {})

    if (!Array.isArray(fields)) {
      formError.value = 'Fields JSON must be an array.'
      return
    }

    if (
      typeof defaultContent !== 'object' ||
      defaultContent === null ||
      Array.isArray(defaultContent)
    ) {
      formError.value = 'Default content JSON must be an object.'
      return
    }

    const payload = {
      ...form.value,
      fields_json: fields as TemplateDesignPayload['fields_json'],
      default_content_json: defaultContent as TemplateDesignPayload['default_content_json'],
    }

    if (editingDesign.value) {
      await designStore.update(editingDesign.value.id, payload)
    } else {
      await designStore.store(payload)
    }

    dialogOpen.value = false
  } catch (error) {
    formError.value =
      error instanceof SyntaxError ? 'Design JSON is invalid.' : 'Unable to save design.'
  }
}

const updateSort = (sort: string, direction: 'asc' | 'desc' | '') => {
  designStore.index({
    direction: direction || undefined,
    page: 1,
    sort: direction ? sort : undefined,
  })
}

onMounted(() => designStore.index())
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Designs</h2>
          <p class="module-container-description">Manage template section design records.</p>
        </div>
      </div>

      <div class="space-y-4">
        <BaseTable
          :columns="tableColumns"
          :data="designStore.designs"
          :loading="designStore.loading"
          :page="designStore.params.page ?? 1"
          :page-size="designStore.params.pageSize ?? 15"
          :search="designStore.params.search ?? ''"
          :sort-direction="designStore.params.direction ?? ''"
          :sort-key="designStore.params.sort ?? ''"
          :total="designStore.total"
          create-label="Create"
          empty-text="No designs found."
          search-placeholder="Search designs"
          with-create
          with-page-size
          with-search
          @create="openCreate"
          @update:page="designStore.index({ page: $event })"
          @update:page-size="designStore.index({ page: 1, pageSize: $event })"
          @update:search="designStore.index({ page: 1, search: $event })"
          @update:sort="updateSort"
        >
          <template #cell-section="{ row }">
            <div class="font-medium">{{ row.section_label }}</div>
            <div class="text-xs text-muted-foreground">{{ row.section_type }}</div>
          </template>

          <template #cell-design_key="{ row }">
            <span class="font-mono text-xs">{{ row.design_key }}</span>
          </template>

          <template #cell-default="{ row }">
            <div class="text-sm">
              {{ row.default_enabled ? 'Enabled' : 'Disabled' }}
            </div>
            <div class="text-xs text-muted-foreground">Order {{ row.default_sort_order }}</div>
          </template>

          <template #cell-status="{ row }">
            <Badge :variant="row.is_active ? 'default' : 'secondary'">
              {{ row.is_active ? 'Active' : 'Inactive' }}
            </Badge>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex justify-end gap-2">
              <Button
                variant="outline"
                size="sm"
                aria-label="Edit design"
                title="Edit design"
                @click.stop="openEdit(row)"
              >
                <Pencil class="size-4" />
              </Button>
              <Button
                variant="outline"
                size="sm"
                aria-label="Delete design"
                :disabled="designStore.loading"
                title="Delete design"
                @click.stop="designStore.destroy(row.id)"
              >
                <Trash2 class="size-4" />
              </Button>
            </div>
          </template>
        </BaseTable>
      </div>
    </div>

    <Dialog v-model:open="dialogOpen">
      <DialogContent class="sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>{{ editingDesign ? 'Edit design' : 'Add design' }}</DialogTitle>
          <DialogDescription
            >Save the section design used by the template builder.</DialogDescription
          >
        </DialogHeader>

        <div class="grid max-h-[70vh] gap-4 overflow-y-auto pr-1 sm:grid-cols-2">
          <Field>
            <FieldLabel for="section-type">Section Type</FieldLabel>
            <Input id="section-type" v-model="form.section_type" placeholder="hero" />
          </Field>

          <Field>
            <FieldLabel for="section-label">Section Label</FieldLabel>
            <Input id="section-label" v-model="form.section_label" placeholder="Hero" />
          </Field>

          <Field>
            <FieldLabel for="design-name">Name</FieldLabel>
            <Input id="design-name" v-model="form.name" placeholder="Hero Design 1" />
          </Field>

          <Field>
            <FieldLabel for="design-key">Design Key</FieldLabel>
            <Input id="design-key" v-model="form.design_key" placeholder="hero-1" />
          </Field>

          <Field>
            <FieldLabel for="sort-order">Default Sort Order</FieldLabel>
            <Input id="sort-order" v-model.number="form.default_sort_order" type="number" min="0" />
          </Field>

          <Field class="gap-3 pt-6" orientation="horizontal">
            <Checkbox id="default-enabled" v-model="form.default_enabled" />
            <FieldLabel for="default-enabled">Enabled by default</FieldLabel>
          </Field>

          <Field class="gap-3" orientation="horizontal">
            <Checkbox id="active" v-model="form.is_active" />
            <FieldLabel for="active">Active</FieldLabel>
          </Field>

          <Field class="sm:col-span-2">
            <FieldLabel for="preview-image">Preview Image</FieldLabel>
            <Textarea id="preview-image" v-model="form.preview_image" class="min-h-24" />
          </Field>

          <Field class="sm:col-span-2">
            <FieldLabel for="fields-json">Fields JSON</FieldLabel>
            <Textarea
              id="fields-json"
              v-model="fieldsText"
              class="min-h-40 font-mono text-xs"
              spellcheck="false"
            />
          </Field>

          <Field class="sm:col-span-2">
            <FieldLabel for="default-content-json">Default Content JSON</FieldLabel>
            <Textarea
              id="default-content-json"
              v-model="defaultContentText"
              class="min-h-40 font-mono text-xs"
              spellcheck="false"
            />
          </Field>
        </div>

        <p v-if="formError" class="text-sm font-medium text-destructive">{{ formError }}</p>

        <DialogFooter>
          <Button variant="outline" @click="dialogOpen = false">Cancel</Button>
          <Button
            :variant="editingDesign ? 'update' : 'create'"
            :disabled="designStore.loading"
            @click="saveDesign"
          >
            {{ designStore.loading ? 'Saving...' : 'Save design' }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
