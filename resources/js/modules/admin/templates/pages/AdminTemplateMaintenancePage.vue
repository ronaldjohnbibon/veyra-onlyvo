<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { useTemplateMaintenanceStore } from '@/modules/admin/templates/template-maintenance-store'
import type {
  TemplateCatalogItem,
  TemplateCatalogPayload,
  WebsiteType,
  WebsiteTypePayload,
} from '@/types/templates'
import { Check, FileCode2, Plus, Save, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'

const maintenanceStore = useTemplateMaintenanceStore()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedCatalogItemId = ref<string | null>(null)
const formError = ref('')
const typeSlugTouched = ref(false)
const itemKeyTouched = ref(false)
// Tracks which details card should show the active edit border.
const activeFormSection = ref<'websiteType' | 'template'>('websiteType')

const slugify = (value: string): string => {
  return (
    value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'template'
  )
}

const blankWebsiteType = (): WebsiteTypePayload => ({
  name: '',
  slug: '',
  description: '',
  is_active: true,
})

const blankCatalogItem = (): TemplateCatalogPayload => ({
  website_type_id: selectedWebsiteTypeId.value ?? '',
  key: '',
  name: '',
  description: '',
  preview_image: '',
  is_active: true,
})

const websiteTypeForm = ref<WebsiteTypePayload>(blankWebsiteType())
const catalogItemForm = ref<TemplateCatalogPayload>(blankCatalogItem())

const selectedWebsiteType = computed<WebsiteType | null>(() => {
  return (
    maintenanceStore.websiteTypes.find((type) => type.id === selectedWebsiteTypeId.value) ?? null
  )
})

const selectedCatalogItem = computed<TemplateCatalogItem | null>(() => {
  return (
    maintenanceStore.catalogItems.find((item) => item.id === selectedCatalogItemId.value) ?? null
  )
})

const renderPath = computed(() => {
  if (!selectedWebsiteType.value?.slug || !catalogItemForm.value.key) return ''

  // The catalog key must match a real Vue file in this folder.
  return `resources/js/modules/templates/templates/${selectedWebsiteType.value.slug}/${catalogItemForm.value.key}.vue`
})

const selectWebsiteType = async (websiteType: WebsiteType): Promise<void> => {
  selectedWebsiteTypeId.value = websiteType.id
  selectedCatalogItemId.value = null
  activeFormSection.value = 'websiteType'
  typeSlugTouched.value = true
  itemKeyTouched.value = false
  websiteTypeForm.value = {
    name: websiteType.name,
    slug: websiteType.slug,
    description: websiteType.description ?? '',
    is_active: websiteType.is_active,
  }
  catalogItemForm.value = blankCatalogItem()
  formError.value = ''

  await maintenanceStore.loadCatalogItems(websiteType.id)
}

const selectCatalogItem = (item: TemplateCatalogItem): void => {
  selectedCatalogItemId.value = item.id ?? null
  activeFormSection.value = 'template'
  itemKeyTouched.value = true
  catalogItemForm.value = {
    website_type_id: item.website_type_id ?? selectedWebsiteTypeId.value ?? '',
    key: item.key,
    name: item.name,
    description: item.description ?? '',
    preview_image: item.preview_image ?? '',
    is_active: item.is_active ?? true,
  }
  formError.value = ''
}

const newWebsiteType = (): void => {
  selectedWebsiteTypeId.value = null
  selectedCatalogItemId.value = null
  activeFormSection.value = 'websiteType'
  typeSlugTouched.value = false
  itemKeyTouched.value = false
  websiteTypeForm.value = blankWebsiteType()
  catalogItemForm.value = blankCatalogItem()
  maintenanceStore.catalogItems = []
  formError.value = ''
}

const newCatalogItem = (): void => {
  selectedCatalogItemId.value = null
  activeFormSection.value = 'template'
  itemKeyTouched.value = false
  catalogItemForm.value = blankCatalogItem()
  formError.value = ''
}

const saveWebsiteType = async (): Promise<void> => {
  activeFormSection.value = 'websiteType'

  if (!websiteTypeForm.value.name.trim()) {
    formError.value = 'Website type name is required.'
    return
  }

  websiteTypeForm.value.slug = slugify(websiteTypeForm.value.slug || websiteTypeForm.value.name)

  const saved = await maintenanceStore.saveWebsiteType(
    { ...websiteTypeForm.value },
    selectedWebsiteTypeId.value
  )

  await selectWebsiteType(saved)
}

const deleteWebsiteType = async (): Promise<void> => {
  if (!selectedWebsiteTypeId.value) return

  await maintenanceStore.deleteWebsiteType(selectedWebsiteTypeId.value)
  newWebsiteType()
}

const saveCatalogItem = async (): Promise<void> => {
  activeFormSection.value = 'template'

  if (!selectedWebsiteTypeId.value) {
    formError.value = 'Select a website type before saving a template.'
    return
  }

  if (!catalogItemForm.value.name.trim()) {
    formError.value = 'Template name is required.'
    return
  }

  catalogItemForm.value.website_type_id = selectedWebsiteTypeId.value
  catalogItemForm.value.key = slugify(catalogItemForm.value.key || catalogItemForm.value.name)

  const saved = await maintenanceStore.saveCatalogItem(
    { ...catalogItemForm.value },
    selectedCatalogItemId.value
  )

  selectCatalogItem(saved)
}

const deleteCatalogItem = async (): Promise<void> => {
  if (!selectedCatalogItemId.value) return

  await maintenanceStore.deleteCatalogItem(selectedCatalogItemId.value, selectedWebsiteTypeId.value)
  newCatalogItem()
}

onMounted(async () => {
  await maintenanceStore.loadWebsiteTypes()

  if (maintenanceStore.websiteTypes[0]) {
    await selectWebsiteType(maintenanceStore.websiteTypes[0])
  }
})

watch(
  () => websiteTypeForm.value.name,
  (name) => {
    if (!typeSlugTouched.value) {
      websiteTypeForm.value.slug = slugify(name)
    }
  }
)

watch(
  () => catalogItemForm.value.name,
  (name) => {
    if (!itemKeyTouched.value) {
      catalogItemForm.value.key = slugify(name)
    }
  }
)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Template Maintenance</h2>
          <p class="module-container-description">
            Manage website types and complete templates available to tenants.
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[20rem_minmax(0,1fr)]">
        <aside class="space-y-3">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-sm font-semibold">Website Types</h3>
            <Button size="sm" variant="outline" type="button" @click="newWebsiteType">
              <Plus class="size-4" />
              New
            </Button>
          </div>

          <div class="space-y-2">
            <button
              v-for="websiteType in maintenanceStore.websiteTypes"
              :key="websiteType.id"
              type="button"
              class="w-full rounded border bg-background p-3 text-left transition hover:border-primary hover:shadow-sm"
              :class="{
                'border-primary ring-2 ring-primary/20': selectedWebsiteTypeId === websiteType.id,
              }"
              @click="selectWebsiteType(websiteType)"
            >
              <span class="flex items-start justify-between gap-3">
                <span>
                  <span class="block text-sm font-semibold">{{ websiteType.name }}</span>
                  <span class="mt-1 block text-xs text-muted-foreground">{{
                    websiteType.slug
                  }}</span>
                  <span class="mt-2 flex flex-wrap gap-2">
                    <Badge variant="outline">
                      {{ websiteType.available_templates_count }} templates
                    </Badge>
                    <Badge v-if="!websiteType.is_active" variant="outline">Inactive</Badge>
                  </span>
                </span>
                <Check
                  v-if="selectedWebsiteTypeId === websiteType.id"
                  class="size-4 shrink-0 text-primary"
                />
              </span>
            </button>
          </div>
        </aside>

        <main class="grid gap-4 2xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
          <section class="space-y-4">
            <Card
              class="gap-4 py-4 transition"
              :class="{
                'border-primary ring-2 ring-primary/20': activeFormSection === 'websiteType',
              }"
              @focusin="activeFormSection = 'websiteType'"
            >
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Website Type Details</CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <FieldGroup>
                  <FieldSet>
                    <Field>
                      <FieldLabel for="website-type-name">Name</FieldLabel>
                      <Input id="website-type-name" v-model="websiteTypeForm.name" />
                    </Field>

                    <Field>
                      <FieldLabel for="website-type-slug">Slug</FieldLabel>
                      <Input
                        id="website-type-slug"
                        v-model="websiteTypeForm.slug"
                        @input="typeSlugTouched = true"
                      />
                    </Field>

                    <Field>
                      <FieldLabel for="website-type-description">Description</FieldLabel>
                      <Textarea
                        id="website-type-description"
                        v-model="websiteTypeForm.description"
                        class="min-h-24"
                      />
                    </Field>

                    <Field orientation="horizontal" class="items-center gap-3">
                      <Checkbox id="website-type-active" v-model="websiteTypeForm.is_active" />
                      <FieldLabel for="website-type-active">Active</FieldLabel>
                    </Field>
                  </FieldSet>
                </FieldGroup>
              </CardContent>
            </Card>

            <Card
              class="gap-4 py-4 transition"
              :class="{
                'border-primary ring-2 ring-primary/20': activeFormSection === 'template',
              }"
              @focusin="activeFormSection = 'template'"
            >
              <CardHeader class="px-4">
                <CardTitle class="text-sm">Template Details</CardTitle>
              </CardHeader>
              <CardContent class="px-4">
                <FieldGroup>
                  <FieldSet>
                    <Field>
                      <FieldLabel for="catalog-name">Name</FieldLabel>
                      <Input
                        id="catalog-name"
                        v-model="catalogItemForm.name"
                        :disabled="!selectedWebsiteType"
                      />
                    </Field>

                    <Field>
                      <FieldLabel for="catalog-key">Template Key</FieldLabel>
                      <Input
                        id="catalog-key"
                        v-model="catalogItemForm.key"
                        :disabled="!selectedWebsiteType"
                        @input="itemKeyTouched = true"
                      />
                    </Field>

                    <Field>
                      <FieldLabel for="catalog-description">Description</FieldLabel>
                      <Textarea
                        id="catalog-description"
                        v-model="catalogItemForm.description"
                        class="min-h-24"
                        :disabled="!selectedWebsiteType"
                      />
                    </Field>

                    <Field>
                      <FieldLabel for="catalog-preview">Preview Image</FieldLabel>
                      <Input
                        id="catalog-preview"
                        v-model="catalogItemForm.preview_image"
                        :disabled="!selectedWebsiteType"
                      />
                    </Field>

                    <Field orientation="horizontal" class="items-center gap-3">
                      <Checkbox
                        id="catalog-active"
                        v-model="catalogItemForm.is_active"
                        :disabled="!selectedWebsiteType"
                      />
                      <FieldLabel for="catalog-active">Active</FieldLabel>
                    </Field>
                  </FieldSet>
                </FieldGroup>

                <div
                  v-if="renderPath"
                  class="mt-4 rounded border bg-muted/30 p-3 text-xs text-muted-foreground"
                >
                  <div class="flex items-start gap-2">
                    <FileCode2 class="mt-0.5 size-4 shrink-0" />
                    <span>{{ renderPath }}</span>
                  </div>
                </div>
              </CardContent>
            </Card>

            <p
              v-if="formError"
              class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
            >
              {{ formError }}
            </p>
          </section>

          <section class="space-y-4">
            <Card class="gap-4 py-4">
              <CardHeader class="flex-row items-center justify-between px-4">
                <CardTitle class="text-sm">
                  Templates
                  <span v-if="selectedWebsiteType" class="text-muted-foreground">
                    for {{ selectedWebsiteType.name }}
                  </span>
                </CardTitle>
                <Button
                  size="sm"
                  variant="outline"
                  type="button"
                  :disabled="!selectedWebsiteType"
                  @click="newCatalogItem"
                >
                  <Plus class="size-4" />
                  New
                </Button>
              </CardHeader>

              <CardContent class="px-4">
                <Empty v-if="!selectedWebsiteType" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>Select a website type</EmptyTitle>
                    <EmptyDescription
                      >Templates appear after choosing a website type.</EmptyDescription
                    >
                  </EmptyHeader>
                </Empty>

                <Empty v-else-if="!maintenanceStore.catalogItems.length" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>No templates yet</EmptyTitle>
                    <EmptyDescription>Add a template catalog entry for this type.</EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <div v-else class="grid gap-3 lg:grid-cols-2">
                  <button
                    v-for="item in maintenanceStore.catalogItems"
                    :key="item.id"
                    type="button"
                    class="rounded border bg-background p-3 text-left transition hover:border-primary hover:shadow-sm"
                    :class="{
                      'border-primary ring-2 ring-primary/20': selectedCatalogItemId === item.id,
                    }"
                    @click="selectCatalogItem(item)"
                  >
                    <span class="flex items-start justify-between gap-3">
                      <span>
                        <span class="block text-sm font-semibold">{{ item.name }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">{{ item.key }}</span>
                        <span class="mt-2 flex flex-wrap gap-2">
                          <Badge variant="outline">{{ item.website_type_slug }}</Badge>
                          <Badge v-if="!item.is_active" variant="outline">Inactive</Badge>
                        </span>
                      </span>
                      <Check
                        v-if="selectedCatalogItemId === item.id"
                        class="size-4 shrink-0 text-primary"
                      />
                    </span>
                  </button>
                </div>
              </CardContent>
            </Card>
          </section>
        </main>
      </div>

      <div class="fixed bottom-6 right-6 z-20 flex w-[120px] flex-col gap-2 draggable">
        <Button
          variant="update"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading"
          @click="saveWebsiteType"
        >
          <Save class="size-3" />
          Type
        </Button>
        <Button
          variant="create"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedWebsiteType"
          @click="saveCatalogItem"
        >
          <Save class="size-3" />
          Template
        </Button>
        <Button
          variant="delete"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedCatalogItem"
          @click="deleteCatalogItem"
        >
          <Trash2 class="size-3" />
          Template
        </Button>
        <Button
          variant="delete"
          class="h-8 w-full rounded px-3 text-xs shadow"
          type="button"
          :disabled="maintenanceStore.loading || !selectedWebsiteType"
          @click="deleteWebsiteType"
        >
          <Trash2 class="size-3" />
          Type
        </Button>
      </div>
    </div>
  </div>
</template>
