<script setup lang="ts">
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import {
  Dialog,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogScrollContent,
  DialogTitle,
} from '@/components/ui/dialog'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import DynamicTemplateFields from '@/modules/templates/components/DynamicTemplateFields.vue'
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import { getTemplateCatalogItem } from '@/modules/templates/template-catalog'
import { useTemplateStore } from '@/modules/templates/template-store'
import { useConfirmStore } from '@/store/confirm-store'
import { useToastStore } from '@/store/toast-store'
import type {
  TemplateContent,
  TemplateCatalogItem,
  TemplateFieldSchema,
  TemplatePayload,
  TemplateRecord,
  TemplateStatus,
  WebsiteType,
} from '@/types/templates'
import {
  Check,
  ExternalLink,
  LayoutTemplate,
  Plus,
  RotateCcw,
  Save,
  Send,
  Trash2,
} from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const templateStore = useTemplateStore()
const confirmStore = useConfirmStore()
const toastStore = useToastStore()
const router = useRouter()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedTemplateId = ref<string | null>(null)
const templateDialogOpen = ref(false)
const formError = ref('')
const slugTouched = ref(false)

const fonts = ['Inter', 'Poppins', 'Arial', 'Georgia']

const slugify = (value: string): string => {
  return (
    value
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'site'
  )
}

const selectedWebsiteType = computed<WebsiteType | null>(() => {
  return templateStore.websiteTypes.find((type) => type.id === selectedWebsiteTypeId.value) ?? null
})

const createBlankTemplate = (
  websiteType: WebsiteType | null = selectedWebsiteType.value
): TemplatePayload => {
  const templateName = websiteType?.name ?? 'Website'

  return {
    website_type_id: websiteType?.id ?? '',
    name: templateName,
    slug: slugify(templateName),
    template_key: '',
    business_name: 'Onlyvo Studio',
    logo: 'https://dummyimage.com/120x120/14b8a6/ffffff.png&text=OV',
    contact_info: {
      email: 'hello@example.com',
      phone: '+1 555 0100',
      address: '123 Market Street',
    },
    social_links: {
      website: 'https://example.com',
      linkedin: '',
      instagram: '',
      facebook: '',
    },
    content: {},
    font_family: 'Inter',
    primary_color: '#14b8a6',
    secondary_color: '#0f766e',
    background_color: '#ffffff',
    text_color: '#111827',
    status: 'draft',
    is_default: false,
  }
}

const form = ref<TemplatePayload>(createBlankTemplate())

const templateOptions = computed<TemplateCatalogItem[]>(() => templateStore.availableTemplates)

const selectedCatalogTemplate = computed<TemplateCatalogItem>(() => {
  return getTemplateCatalogItem(form.value.template_key, templateOptions.value)
})

const dynamicFieldSchema = computed<TemplateFieldSchema[]>(() => {
  return selectedCatalogTemplate.value.field_schema ?? []
})

const hasSelectedCatalogTemplate = computed(() =>
  Boolean(form.value.template_key && selectedCatalogTemplate.value.key)
)

const canEditDetails = computed(() =>
  Boolean(selectedTemplateId.value || hasSelectedCatalogTemplate.value)
)

const canSave = computed(() => {
  return Boolean(form.value.website_type_id && form.value.template_key)
})

const previewTemplate = computed<TemplateRecord>(() => ({
  id: selectedTemplateId.value ?? 'preview',
  tenant_id: '',
  ...form.value,
  website_type_id: form.value.website_type_id,
  website_type: selectedWebsiteType.value ?? undefined,
}))

const contentRecord = (value: unknown): TemplateContent => {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? (value as TemplateContent)
    : {}
}

const mergeTemplateContent = (
  catalogTemplate: TemplateCatalogItem,
  existingContent: TemplateContent = {}
): TemplateContent => {
  // Catalog defaults fill new schema fields without overwriting saved answers.
  return {
    ...(catalogTemplate.default_content ?? {}),
    ...existingContent,
  }
}

const applyCatalogStyleDefaults = (catalogTemplate: TemplateCatalogItem): void => {
  if (selectedWebsiteType.value?.slug !== 'landing-page' || catalogTemplate.key !== 'template-1') {
    return
  }

  form.value.font_family = 'Arial'
  form.value.primary_color = '#3377aa'
  form.value.secondary_color = '#336699'
  form.value.background_color = '#ffffff'
  form.value.text_color = '#707070'
}

const stringContent = (content: TemplateContent, keys: string[], fallback = ''): string => {
  for (const key of keys) {
    const value = content[key]

    if (typeof value === 'string' && value.trim()) return value
  }

  return fallback
}

const payloadForSave = (status: TemplateStatus): TemplatePayload => {
  const content = contentRecord(form.value.content)
  const businessName = stringContent(
    content,
    ['business_name', 'display_name'],
    form.value.business_name
  )
  const email = stringContent(content, ['contact_email', 'email'], form.value.contact_info.email)
  const phone = stringContent(content, ['contact_phone', 'phone'], form.value.contact_info.phone)
  const address = stringContent(
    content,
    ['contact_address', 'contact_location', 'location', 'address'],
    form.value.contact_info.address
  )

  return {
    ...form.value,
    business_name: businessName || form.value.name,
    contact_info: {
      email: email || 'hello@example.com',
      phone: phone || '+1 555 0100',
      address,
    },
    social_links: {
      ...form.value.social_links,
      website: stringContent(
        content,
        ['website_url', 'portfolio_url', 'reservation_link', 'chat_url'],
        form.value.social_links.website
      ),
    },
    content,
    status,
  }
}

const searchTemplates = async (): Promise<void> => {
  await templateStore.index({
    page: 1,
    search: templateStore.params.search,
    website_type_id: selectedWebsiteTypeId.value ?? undefined,
  })
}

const selectWebsiteType = async (websiteType: WebsiteType): Promise<void> => {
  selectedWebsiteTypeId.value = websiteType.id
  selectedTemplateId.value = null
  slugTouched.value = false
  form.value = createBlankTemplate(websiteType)
  formError.value = ''
  templateStore.errors = {}

  await templateStore.loadAvailableTemplates(websiteType.id)
  await templateStore.index({ page: 1, website_type_id: websiteType.id })
}

const selectCatalogTemplate = (template: TemplateCatalogItem): void => {
  form.value.template_key = template.key
  form.value.content = mergeTemplateContent(template, contentRecord(form.value.content))
  applyCatalogStyleDefaults(template)
}

const resetForm = (): void => {
  selectedTemplateId.value = null
  slugTouched.value = false
  form.value = createBlankTemplate(selectedWebsiteType.value)
  formError.value = ''
  templateStore.errors = {}
  templateDialogOpen.value = true
}

const updateSlug = (): void => {
  slugTouched.value = true
  form.value.slug = slugify(form.value.slug)
}

const hydrateForm = (template: TemplateRecord): void => {
  selectedTemplateId.value = template.id
  selectedWebsiteTypeId.value = template.website_type_id
  slugTouched.value = true
  form.value = {
    website_type_id: template.website_type_id,
    name: template.name,
    slug: template.slug,
    template_key: template.template_key,
    business_name: template.business_name,
    logo: template.logo,
    contact_info: {
      email: template.contact_info?.email ?? '',
      phone: template.contact_info?.phone ?? '',
      address: template.contact_info?.address ?? '',
    },
    social_links: {
      website: template.social_links?.website ?? '',
      linkedin: template.social_links?.linkedin ?? '',
      instagram: template.social_links?.instagram ?? '',
      facebook: template.social_links?.facebook ?? '',
    },
    content: mergeTemplateContent(
      getTemplateCatalogItem(template.template_key, templateStore.availableTemplates),
      contentRecord(template.content)
    ),
    font_family: template.font_family,
    primary_color: template.primary_color,
    secondary_color: template.secondary_color,
    background_color: template.background_color,
    text_color: template.text_color,
    status: template.status,
    is_default: template.is_default,
  }
  formError.value = ''
  templateStore.errors = {}
}

const openTemplate = async (template: TemplateRecord): Promise<void> => {
  if (selectedWebsiteTypeId.value !== template.website_type_id) {
    selectedWebsiteTypeId.value = template.website_type_id
    await templateStore.loadAvailableTemplates(template.website_type_id)
  }

  hydrateForm(template)
  // templateDialogOpen.value = true
}

const saveTemplate = async (status: TemplateStatus): Promise<void> => {
  if (!canSave.value) return

  const payload = payloadForSave(status)
  formError.value = ''

  try {
    const saved = selectedTemplateId.value
      ? await templateStore.update(selectedTemplateId.value, payload)
      : await templateStore.store(payload)

    hydrateForm(saved)
    templateDialogOpen.value = false
  } catch {
    formError.value = 'Please check the form and try again.'
  }
}

const deleteTemplate = async (): Promise<void> => {
  if (!selectedTemplateId.value) return

  await templateStore.destroy(selectedTemplateId.value)
  selectedTemplateId.value = null
  slugTouched.value = false
  form.value = createBlankTemplate(selectedWebsiteType.value)
  formError.value = ''
  templateStore.errors = {}
  templateDialogOpen.value = false
}

const resetTemplateDefault = async (): Promise<void> => {
  if (!selectedTemplateId.value) return

  const confirmed = await confirmStore.confirm(
    'Restore this template to its original default design? This will replace customized content, images, contact details, and style settings.'
  )

  if (!confirmed) return

  formError.value = ''

  try {
    const reset = await templateStore.resetDefault(selectedTemplateId.value)
    hydrateForm(reset)
    toastStore.addAlert('success', 'Template restored', 'The default design has been restored.')
  } catch {
    formError.value = 'Unable to restore the default design.'
  }
}

const handleLogoUpload = (event: Event): void => {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  const reader = new FileReader()
  reader.onload = () => {
    form.value.logo = String(reader.result)
  }
  reader.readAsDataURL(file)
}

onMounted(async () => {
  await templateStore.loadWebsiteTypes()
  await templateStore.index()
})

watch(
  () => form.value.name,
  (name) => {
    if (!slugTouched.value) {
      form.value.slug = slugify(name)
    }
  }
)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Templates</h2>
          <p class="module-container-description">
            Select a website type first, then choose a complete website template.
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="space-y-3">
          <Input
            v-model="templateStore.params.search"
            placeholder="Search templates"
            class="w-full max-w-[300px]"
            @keyup.enter="searchTemplates"
          />

          <div v-if="templateStore.templates.length" class="space-y-2">
            <Button
              v-for="template in templateStore.templates"
              :key="template.id"
              as="div"
              variant="outline"
              role="button"
              tabindex="0"
              class="h-auto w-full cursor-pointer flex-col items-start justify-start gap-1 whitespace-normal border bg-background p-3 text-left hover:border-primary"
              :class="{
                'border-primary ring-2 ring-primary/20': selectedTemplateId === template.id,
              }"
              @click="openTemplate(template)"
              @keydown.enter="openTemplate(template)"
              @keydown.space.prevent="openTemplate(template)"
            >
              <span class="block font-semibold">{{ template.name }}</span>
              <span class="block text-muted-foreground">{{ template.business_name }}</span>
              <a
                v-if="template.public_url"
                :href="template.public_url"
                target="_blank"
                rel="noreferrer"
                class="mt-1 flex max-w-full items-center gap-1 rounded bg-muted px-2 py-1 text-xs font-normal text-muted-foreground underline-offset-4 hover:text-primary hover:underline"
                @click.stop
                @keydown.stop
              >
                <ExternalLink class="size-3 shrink-0" />
                <span class="truncate">{{ template.public_url }}</span>
              </a>
              <span class="mt-2 flex flex-wrap gap-2">
                <Badge variant="outline">{{ template.status }}</Badge>
                <Badge v-if="template.website_type" variant="outline">
                  {{ template.website_type.name }}
                </Badge>
                <Badge v-if="template.is_default" variant="outline">Default</Badge>
              </span>
            </Button>
          </div>

          <Empty v-else class="p-4 text-left">
            <EmptyHeader>
              <EmptyTitle class="text-sm">No saved templates</EmptyTitle>
              <EmptyDescription>
                Saved tenant templates will appear here after publishing or saving drafts.
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        </aside>

        <Dialog :open="templateDialogOpen" @update:open="templateDialogOpen = $event">
          <DialogScrollContent class="max-w-[calc(100%-2rem)] md:max-w-5xl xl:max-w-7xl">
            <DialogHeader>
              <DialogTitle>
                {{ selectedTemplateId ? 'Edit Template' : 'Create Template' }}
              </DialogTitle>
              <DialogDescription>
                Choose a website type, complete the template fields, and save or publish the site.
              </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent>
              <Card class="gap-4 py-4">
                <CardHeader class="px-4">
                  <CardTitle class="text-sm">Template Setup</CardTitle>
                </CardHeader>

                <CardContent class="px-4">
                  <Accordion type="multiple" :default-value="[]" class="space-y-2">
                    <AccordionItem
                      value="website-type"
                      class="rounded border bg-background px-3 last:border-b"
                    >
                      <AccordionTrigger class="py-3 hover:no-underline">
                        <span>
                          <span class="block text-sm font-semibold">Website Type</span>
                          <span class="mt-1 block text-xs text-muted-foreground">
                            Choose the site category before viewing templates.
                          </span>
                        </span>
                      </AccordionTrigger>
                      <AccordionContent class="space-y-3 pb-3">
                        <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
                          <button
                            v-for="websiteType in templateStore.websiteTypes"
                            :key="websiteType.id"
                            type="button"
                            class="group rounded border bg-background p-3 text-left transition hover:border-primary hover:shadow-sm"
                            :class="{
                              'border-primary ring-2 ring-primary/20':
                                selectedWebsiteTypeId === websiteType.id,
                            }"
                            @click="selectWebsiteType(websiteType)"
                          >
                            <span class="flex items-start justify-between gap-3">
                              <span>
                                <span class="block text-sm font-semibold">
                                  {{ websiteType.name }}
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-muted-foreground">
                                  {{ websiteType.available_templates_count }} templates available
                                </span>
                              </span>
                              <Check
                                v-if="selectedWebsiteTypeId === websiteType.id"
                                class="size-4 shrink-0 text-primary"
                              />
                            </span>
                          </button>
                        </div>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem
                      value="available-templates"
                      class="rounded border bg-background px-3 last:border-b"
                    >
                      <AccordionTrigger class="py-3 hover:no-underline">
                        <span>
                          <span class="flex flex-wrap items-center gap-2">
                            <span class="block text-sm font-semibold">Available Templates</span>
                            <Badge v-if="selectedWebsiteType" variant="outline">
                              {{ selectedWebsiteType.name }}
                            </Badge>
                          </span>
                          <span class="mt-1 block text-xs text-muted-foreground">
                            Complete templates are grouped by website type.
                          </span>
                        </span>
                      </AccordionTrigger>
                      <AccordionContent class="space-y-3 pb-3">
                        <Empty v-if="!selectedWebsiteType" class="min-h-[220px]">
                          <EmptyHeader>
                            <EmptyTitle>Select a website type</EmptyTitle>
                            <EmptyDescription>
                              Complete templates are grouped by website type.
                            </EmptyDescription>
                          </EmptyHeader>
                        </Empty>

                        <Empty v-else-if="!templateOptions.length" class="min-h-[220px]">
                          <EmptyHeader>
                            <EmptyTitle>No templates available yet</EmptyTitle>
                            <EmptyDescription>
                              {{ selectedWebsiteType.name }} is ready for future templates.
                            </EmptyDescription>
                          </EmptyHeader>
                        </Empty>

                        <div v-else class="grid gap-3 md:grid-cols-3">
                          <button
                            v-for="templateOption in templateOptions"
                            :key="templateOption.key"
                            type="button"
                            class="group rounded border bg-background p-2 text-left transition hover:border-primary hover:shadow-sm"
                            :class="{
                              'border-primary ring-2 ring-primary/20':
                                form.template_key === templateOption.key,
                            }"
                            @click="selectCatalogTemplate(templateOption)"
                          >
                            <div
                              class="flex aspect-video w-full items-center justify-center rounded bg-muted text-muted-foreground"
                            >
                              <img
                                v-if="templateOption.preview_image"
                                :src="templateOption.preview_image"
                                :alt="templateOption.name"
                                class="h-full w-full rounded object-cover"
                              />
                              <LayoutTemplate v-else class="size-6" />
                            </div>
                            <span class="mt-3 flex items-center justify-between gap-2">
                              <span>
                                <span class="block text-sm font-semibold">
                                  {{ templateOption.name }}
                                </span>
                                <span class="mt-1 block text-xs leading-5 text-muted-foreground">
                                  {{ templateOption.description }}
                                </span>
                              </span>
                              <Check
                                v-if="form.template_key === templateOption.key"
                                class="size-4 shrink-0 text-primary"
                              />
                            </span>
                          </button>
                        </div>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem
                      v-if="canEditDetails"
                      value="site-details"
                      class="rounded border bg-background px-3 last:border-b"
                    >
                      <AccordionTrigger class="py-3 hover:no-underline">
                        <span>
                          <span class="flex flex-wrap items-center gap-2">
                            <span class="block text-sm font-semibold">Site Details</span>
                            <Badge variant="outline">{{ form.status }}</Badge>
                          </span>
                          <span class="mt-1 block text-xs text-muted-foreground">
                            Name, slug, default setting, and logo.
                          </span>
                        </span>
                      </AccordionTrigger>
                      <AccordionContent class="space-y-3 pb-3">
                        <FieldGroup>
                          <FieldSet>
                            <div class="grid gap-4 md:grid-cols-2">
                              <Field>
                                <FieldLabel for="template-name">Template Name</FieldLabel>
                                <Input id="template-name" v-model="form.name" />
                                <Label
                                  v-if="templateStore.errors.name"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.name[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="site-slug">Site Slug</FieldLabel>
                                <Input
                                  id="site-slug"
                                  v-model="form.slug"
                                  maxlength="120"
                                  @input="updateSlug"
                                />
                                <Label
                                  v-if="templateStore.errors.slug"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.slug[0] }}
                                </Label>
                              </Field>

                              <Field orientation="horizontal" class="items-center gap-3 self-end">
                                <Checkbox id="default-site" v-model="form.is_default" />
                                <FieldLabel for="default-site">Default public site</FieldLabel>
                                <Label
                                  v-if="templateStore.errors.is_default"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.is_default[0] }}
                                </Label>
                              </Field>

                              <Field class="md:col-span-2">
                                <FieldLabel for="logo-url">Logo URL</FieldLabel>
                                <Input id="logo-url" v-model="form.logo" />
                                <Label
                                  v-if="templateStore.errors.logo"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.logo[0] }}
                                </Label>
                                <Input
                                  type="file"
                                  accept="image/*"
                                  class="cursor-pointer text-muted-foreground"
                                  @change="handleLogoUpload"
                                />
                              </Field>
                            </div>
                          </FieldSet>
                        </FieldGroup>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem
                      v-if="canEditDetails && dynamicFieldSchema.length"
                      value="template-content"
                      class="rounded border bg-background px-3 last:border-b"
                    >
                      <AccordionTrigger class="py-3 hover:no-underline">
                        <span>
                          <span class="block text-sm font-semibold">Template Content</span>
                          <span class="mt-1 block text-xs text-muted-foreground">
                            Complete fields from the selected catalog template.
                          </span>
                        </span>
                      </AccordionTrigger>
                      <AccordionContent class="space-y-3 pb-3">
                        <DynamicTemplateFields
                          :schema="dynamicFieldSchema"
                          :model-value="form.content"
                          @update:model-value="form.content = $event"
                        />
                        <Label v-if="templateStore.errors.content" class="text-destructive text-xs">
                          {{ templateStore.errors.content[0] }}
                        </Label>
                      </AccordionContent>
                    </AccordionItem>

                    <AccordionItem
                      v-if="canEditDetails"
                      value="global-styles"
                      class="rounded border bg-background px-3 last:border-b"
                    >
                      <AccordionTrigger class="py-3 hover:no-underline">
                        <span>
                          <span class="block text-sm font-semibold">Global Styles</span>
                          <span class="mt-1 block text-xs text-muted-foreground">
                            Font and color settings for the published site.
                          </span>
                        </span>
                      </AccordionTrigger>
                      <AccordionContent class="space-y-3 pb-3">
                        <FieldGroup>
                          <FieldSet>
                            <div class="grid gap-4 md:grid-cols-5">
                              <Field>
                                <FieldLabel for="font-family">Font Family</FieldLabel>
                                <NativeSelect
                                  id="font-family"
                                  v-model="form.font_family"
                                  class="w-full"
                                >
                                  <NativeSelectOption
                                    v-for="font in fonts"
                                    :key="font"
                                    :value="font"
                                  >
                                    {{ font }}
                                  </NativeSelectOption>
                                </NativeSelect>
                                <Label
                                  v-if="templateStore.errors.font_family"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.font_family[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="primary-color">Primary Color</FieldLabel>
                                <Input
                                  id="primary-color"
                                  v-model="form.primary_color"
                                  type="color"
                                />
                                <Label
                                  v-if="templateStore.errors.primary_color"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.primary_color[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="secondary-color">Secondary Color</FieldLabel>
                                <Input
                                  id="secondary-color"
                                  v-model="form.secondary_color"
                                  type="color"
                                />
                                <Label
                                  v-if="templateStore.errors.secondary_color"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.secondary_color[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="background-color">Background Color</FieldLabel>
                                <Input
                                  id="background-color"
                                  v-model="form.background_color"
                                  type="color"
                                />
                                <Label
                                  v-if="templateStore.errors.background_color"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.background_color[0] }}
                                </Label>
                              </Field>

                              <Field>
                                <FieldLabel for="text-color">Text Color</FieldLabel>
                                <Input id="text-color" v-model="form.text_color" type="color" />
                                <Label
                                  v-if="templateStore.errors.text_color"
                                  class="text-destructive text-xs"
                                >
                                  {{ templateStore.errors.text_color[0] }}
                                </Label>
                              </Field>
                            </div>
                          </FieldSet>
                        </FieldGroup>
                      </AccordionContent>
                    </AccordionItem>
                  </Accordion>
                </CardContent>
              </Card>

              <p
                v-if="formError"
                class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
              >
                {{ formError }}
              </p>
            </form>

            <DialogFooter class="gap-2 sm:justify-between">
              <div class="flex flex-col gap-2 sm:flex-row">
                <Button
                  variant="outline"
                  type="button"
                  :disabled="templateStore.loading || !selectedTemplateId"
                  @click="resetTemplateDefault"
                >
                  <RotateCcw class="size-4" />
                  Reset to Default
                </Button>
                <Button
                  v-if="selectedTemplateId"
                  variant="delete"
                  type="button"
                  :disabled="templateStore.loading"
                  @click="deleteTemplate"
                >
                  <Trash2 class="size-4" />
                  Delete
                </Button>
              </div>

              <div class="flex flex-col-reverse gap-2 sm:flex-row">
                <Button
                  variant="update"
                  type="button"
                  :disabled="templateStore.loading || !canSave"
                  @click="saveTemplate('draft')"
                >
                  <Save class="size-4" />
                  Draft
                </Button>
                <Button
                  variant="create"
                  type="button"
                  :disabled="templateStore.loading || !canSave"
                  @click="saveTemplate('published')"
                >
                  <Send class="size-4" />
                  Publish
                </Button>
              </div>
            </DialogFooter>
          </DialogScrollContent>
        </Dialog>

        <aside class="xl:sticky xl:top-5 xl:self-start">
          <div class="h-[calc(100vh-10rem)] overflow-auto rounded border bg-muted/30 p-3">
            <TemplatePreview :template="previewTemplate" />
          </div>
        </aside>
      </div>

      <div class="fixed bottom-6 right-6 z-20 flex w-[112px] flex-col gap-2 draggable">
        <Button
          variant="outline"
          class="w-full rounded shadow h-8 px-3 text-xs"
          type="button"
          @click="resetForm"
        >
          <Plus class="size-3" />
          New
        </Button>
        <Button
          variant="update"
          class="w-full rounded shadow h-8 px-3 text-xs"
          type="button"
          :disabled="!canEditDetails"
          @click="templateDialogOpen = true"
        >
          <Save class="size-3" />
          Edit
        </Button>
        <Button
          v-if="selectedTemplateId"
          variant="outline"
          class="w-full rounded shadow h-8 px-3 text-xs"
          type="button"
          @click="router.push({ name: 'templates.published', params: { id: selectedTemplateId } })"
        >
          <ExternalLink class="size-3" />
          Open
        </Button>
      </div>
    </div>
  </div>
</template>
