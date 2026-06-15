<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { Label } from '@/shared/components/ui/label'
import { NativeSelect, NativeSelectOption } from '@/shared/components/ui/native-select'
import { Separator } from '@/shared/components/ui/separator'
import DynamicTemplateFields from '@/shared/templates/components/DynamicTemplateFields.vue'
import TemplatePreview from '@/tenant/templates/components/TemplatePreview.vue'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { getTemplateCatalogItem } from '@/shared/templates/template-catalog'
import { useTemplateStore } from '@/tenant/templates/template-store'
import {
  applyCatalogStyleDefaults,
  contentWithTemplateDetails,
  contentRecord,
  createBlankTemplate,
  mergeTemplateContent,
  payloadForSave,
  slugify,
  templateToPayload,
} from '@/shared/templates/utils/template-form'
import { useConfirmStore } from '@/shared/stores/confirm-store'
import { useToastStore } from '@/shared/stores/toast-store'
import type {
  TemplateCatalogItem,
  TemplateFieldSchema,
  TemplatePayload,
  TemplateRecord,
  TemplateStatus,
  WebsiteType,
} from '@/shared/types/templates'
import {
  AlertTriangle,
  Check,
  CheckCircle2,
  ExternalLink,
  Image,
  LayoutTemplate,
  Link,
  Plus,
  RotateCcw,
  Save,
  Send,
  Trash2,
} from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

type BuilderSection = 'setup' | 'details' | 'content' | 'style' | 'checks'

interface BuilderIssue {
  label: string
  value: string
  type: 'image' | 'link'
}

const templateStore = useTemplateStore()
const confirmStore = useConfirmStore()
const toastStore = useToastStore()
const router = useRouter()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedTemplateId = ref<string | null>(null)
const activeSection = ref<BuilderSection>('setup')
const formError = ref('')
const slugTouched = ref(false)

const fonts = ['Inter', 'Poppins', 'Arial', 'Georgia']

const sections: { key: BuilderSection; label: string; description: string }[] = [
  { key: 'setup', label: 'Setup', description: 'Type and base design' },
  { key: 'details', label: 'Details', description: 'Name and identity' },
  { key: 'content', label: 'Content', description: 'Template sections' },
  { key: 'style', label: 'Style', description: 'Brand colors and typography' },
  { key: 'checks', label: 'Checks', description: 'Launch readiness' },
]

const selectedWebsiteType = computed<WebsiteType | null>(() => {
  return templateStore.websiteTypes.find((type) => type.id === selectedWebsiteTypeId.value) ?? null
})

const selectedSavedTemplate = computed<TemplateRecord | null>(() => {
  return (
    templateStore.templates.find((template) => template.id === selectedTemplateId.value) ??
    (templateStore.template?.id === selectedTemplateId.value ? templateStore.template : null)
  )
})

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
  content: contentWithTemplateDetails(form.value),
  website_type_id: form.value.website_type_id,
  website_type: selectedWebsiteType.value ?? undefined,
  public_url: selectedSavedTemplate.value?.public_url,
}))

const publicUrl = computed(() => {
  if (form.value.status !== 'published') return ''

  return selectedSavedTemplate.value?.public_url ?? ''
})

const linkIssues = computed<BuilderIssue[]>(() => {
  const issues: BuilderIssue[] = []
  const linkValues: [string, string][] = [
    ['Logo URL', form.value.logo],
    ['Website', form.value.social_links.website],
    ['LinkedIn', form.value.social_links.linkedin],
    ['Instagram', form.value.social_links.instagram],
    ['Facebook', form.value.social_links.facebook],
  ]

  for (const [label, value] of linkValues) {
    if (value && !isValidUrl(value) && !isDataImage(value)) {
      issues.push({ label, value, type: label === 'Logo URL' ? 'image' : 'link' })
    }
  }

  collectContentIssues(contentRecord(form.value.content), issues)

  return issues
})

const requiredContentMissing = computed(() => {
  const content = contentWithTemplateDetails(form.value)

  return dynamicFieldSchema.value
    .filter((field) => field.required)
    .filter((field) => {
      const value = content[field.key]

      return value === null || value === undefined || String(value).trim() === ''
    })
})

const completionItems = computed(() => [
  {
    key: 'website-type',
    label: 'Choose a website type',
    completed: Boolean(selectedWebsiteType.value),
    section: 'setup' as BuilderSection,
  },
  {
    key: 'template',
    label: 'Choose a base design',
    completed: hasSelectedCatalogTemplate.value,
    section: 'setup' as BuilderSection,
  },
  {
    key: 'details',
    label: 'Complete site details',
    completed: Boolean(form.value.name && form.value.slug && form.value.business_name),
    section: 'details' as BuilderSection,
  },
  {
    key: 'content',
    label: 'Complete required content',
    completed: requiredContentMissing.value.length === 0,
    section: 'content' as BuilderSection,
  },
  {
    key: 'checks',
    label: 'Resolve broken links and images',
    completed: linkIssues.value.length === 0,
    section: 'checks' as BuilderSection,
  },
])

const completedCount = computed(() => completionItems.value.filter((item) => item.completed).length)
const completionPercent = computed(() =>
  Math.round((completedCount.value / completionItems.value.length) * 100)
)

const nextIncompleteItem = computed(() => completionItems.value.find((item) => !item.completed))

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
  activeSection.value = 'setup'

  await templateStore.loadAvailableTemplates(websiteType.id)
  await templateStore.index({ page: 1, website_type_id: websiteType.id })
}

const selectCatalogTemplate = (template: TemplateCatalogItem): void => {
  form.value = applyCatalogStyleDefaults(
    {
      ...form.value,
      template_key: template.key,
      content: mergeTemplateContent(template, contentRecord(form.value.content)),
    },
    selectedWebsiteType.value,
    template
  )
  activeSection.value = 'details'
}

const resetForm = (): void => {
  selectedTemplateId.value = null
  slugTouched.value = false
  form.value = createBlankTemplate(selectedWebsiteType.value)
  formError.value = ''
  templateStore.errors = {}
  activeSection.value = selectedWebsiteType.value ? 'setup' : 'setup'
}

const updateSlug = (): void => {
  slugTouched.value = true
  form.value.slug = slugify(form.value.slug)
}

const hydrateForm = (template: TemplateRecord): void => {
  selectedTemplateId.value = template.id
  selectedWebsiteTypeId.value = template.website_type_id
  slugTouched.value = true
  form.value = templateToPayload(template, templateStore.availableTemplates)
  formError.value = ''
  templateStore.errors = {}
  activeSection.value = 'content'
}

const openTemplate = async (template: TemplateRecord): Promise<void> => {
  if (selectedWebsiteTypeId.value !== template.website_type_id) {
    selectedWebsiteTypeId.value = template.website_type_id
    await templateStore.loadAvailableTemplates(template.website_type_id)
  }

  hydrateForm(template)
}

const saveTemplate = async (status: TemplateStatus): Promise<void> => {
  if (!canSave.value) return

  if (status === 'published' && requiredContentMissing.value.length) {
    activeSection.value = 'content'
    formError.value = 'Complete required template content before publishing.'

    return
  }

  if (status === 'published' && linkIssues.value.length) {
    activeSection.value = 'checks'
    formError.value = 'Resolve broken-looking links or image URLs before publishing.'

    return
  }

  const payload = payloadForSave(form.value, status)
  formError.value = ''

  try {
    const saved = selectedTemplateId.value
      ? await templateStore.update(selectedTemplateId.value, payload)
      : await templateStore.store(payload)

    hydrateForm(saved)
    toastStore.addAlert(
      'success',
      status === 'published' ? 'Site published' : 'Draft saved',
      status === 'published' ? 'Your public site is ready.' : 'Your builder changes were saved.'
    )
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
  activeSection.value = 'setup'
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

const openPreviewPage = (): void => {
  if (!selectedTemplateId.value || form.value.status !== 'published') return

  router.push({ name: 'templates.published', params: { id: selectedTemplateId.value } })
}

const jumpToNextAction = (): void => {
  if (nextIncompleteItem.value) {
    activeSection.value = nextIncompleteItem.value.section
  }
}

const isValidUrl = (value: string): boolean => {
  try {
    const url = new URL(value)

    return ['http:', 'https:', 'mailto:', 'tel:'].includes(url.protocol)
  } catch {
    return value.startsWith('/') || value.startsWith('#')
  }
}

const isDataImage = (value: string): boolean => value.startsWith('data:image/')

const collectContentIssues = (record: Record<string, unknown>, issues: BuilderIssue[]): void => {
  Object.entries(record).forEach(([key, value]) => {
    if (typeof value === 'string') {
      const looksLikeUrl =
        value.startsWith('http') || value.startsWith('/') || value.startsWith('data:image/')
      const looksLikeImage =
        key.toLowerCase().includes('image') || key.toLowerCase().includes('logo')

      if (looksLikeUrl && !isValidUrl(value) && !isDataImage(value)) {
        issues.push({ label: key, value, type: looksLikeImage ? 'image' : 'link' })
      }
    }

    if (Array.isArray(value)) {
      value.forEach((item) => {
        if (item && typeof item === 'object' && !Array.isArray(item)) {
          collectContentIssues(item as Record<string, unknown>, issues)
        }
      })
    }

    if (value && typeof value === 'object' && !Array.isArray(value)) {
      collectContentIssues(value as Record<string, unknown>, issues)
    }
  })
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
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-24">
      <div
        class="sticky top-0 z-20 -mx-4 mb-4 border-b bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80"
      >
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
          <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="truncate text-2xl font-semibold tracking-tight text-foreground">
                Website Builder
              </h2>
              <Badge :variant="getStatusBadgeVariant(form.status)">
                {{ getStatusLabel(form.status) }}
              </Badge>
              <Badge v-if="form.is_default" variant="outline">Default site</Badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
              Build, preview, and publish a complete tenant website.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <Button variant="create" size="sm" type="button" @click="resetForm">
              <Plus class="size-4" />
              New Site
            </Button>
            <Button
              variant="update"
              size="sm"
              type="button"
              :disabled="templateStore.loading || !canSave"
              @click="saveTemplate('draft')"
            >
              <Save class="size-4" />
              Save Draft
            </Button>
            <Button
              variant="publish"
              size="sm"
              type="button"
              :disabled="templateStore.loading || !canSave"
              @click="saveTemplate('published')"
            >
              <Send class="size-4" />
              Publish
            </Button>
            <Button
              variant="navigate"
              size="sm"
              type="button"
              :disabled="!selectedTemplateId || form.status !== 'published'"
              @click="openPreviewPage"
            >
              <ExternalLink class="size-4" />
              Preview
            </Button>
          </div>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[17rem_minmax(0,1fr)_minmax(22rem,36rem)]">
        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
          <Card class="gap-3 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Saved Sites</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 px-4">
              <Input
                v-field-help="'Search saved templates by name.'"
                v-model="templateStore.params.search"
                placeholder="Search sites"
                class="h-9"
                @keyup.enter="searchTemplates"
              />

              <div v-if="templateStore.templates.length" class="space-y-2">
                <button
                  v-for="template in templateStore.templates"
                  :key="template.id"
                  type="button"
                  class="w-full rounded border bg-background p-3 text-left text-sm transition hover:border-primary hover:bg-primary/5"
                  :class="{
                    'border-primary ring-2 ring-primary/20': selectedTemplateId === template.id,
                  }"
                  @click="openTemplate(template)"
                >
                  <span class="block truncate font-semibold">{{ template.name }}</span>
                  <span class="mt-1 block truncate text-xs text-muted-foreground">
                    {{ template.business_name }}
                  </span>
                  <span class="mt-2 flex flex-wrap gap-2">
                    <Badge :variant="getStatusBadgeVariant(template.status)">
                      {{ getStatusLabel(template.status) }}
                    </Badge>
                    <Badge v-if="template.is_default" variant="outline">Default</Badge>
                  </span>
                </button>
              </div>

              <Empty v-else class="p-4 text-left">
                <EmptyHeader>
                  <EmptyTitle class="text-sm">No saved sites</EmptyTitle>
                  <EmptyDescription>
                    Start with a website type, choose a design, then save your first draft.
                  </EmptyDescription>
                </EmptyHeader>
              </Empty>
            </CardContent>
          </Card>

          <Card class="gap-3 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Completion</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 px-4">
              <div class="h-2 overflow-hidden rounded bg-muted">
                <div
                  class="h-full rounded bg-primary transition-all"
                  :style="{ width: `${completionPercent}%` }"
                />
              </div>
              <p class="text-xs text-muted-foreground">
                {{ completedCount }} of {{ completionItems.length }} builder checks complete.
              </p>
              <button
                v-for="item in completionItems"
                :key="item.key"
                type="button"
                class="flex w-full items-start gap-2 rounded p-2 text-left text-sm transition hover:bg-muted"
                @click="activeSection = item.section"
              >
                <CheckCircle2
                  class="mt-0.5 size-4 shrink-0"
                  :class="item.completed ? 'text-emerald-600' : 'text-muted-foreground'"
                />
                <span>{{ item.label }}</span>
              </button>
              <Button
                v-if="nextIncompleteItem"
                variant="navigate"
                size="sm"
                type="button"
                class="w-full"
                @click="jumpToNextAction"
              >
                Continue Setup
              </Button>
            </CardContent>
          </Card>
        </aside>

        <main class="min-w-0 space-y-4">
          <Card class="gap-0 py-0">
            <div class="flex flex-wrap gap-1 border-b p-2">
              <button
                v-for="section in sections"
                :key="section.key"
                type="button"
                class="rounded px-3 py-2 text-left text-sm transition hover:bg-muted"
                :class="{
                  'bg-primary text-primary-foreground hover:bg-primary':
                    activeSection === section.key,
                  'text-muted-foreground': activeSection !== section.key,
                }"
                @click="activeSection = section.key"
              >
                <span class="block font-medium">{{ section.label }}</span>
                <span class="block text-xs opacity-80">{{ section.description }}</span>
              </button>
            </div>

            <form class="space-y-4 p-4" @submit.prevent>
              <section v-if="activeSection === 'setup'" class="space-y-4">
                <div>
                  <h3 class="text-base font-semibold">Choose a website type</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Website types filter the template designs and content fields available to this
                    site.
                  </p>
                </div>

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
                        <span class="block text-sm font-semibold">{{ websiteType.name }}</span>
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

                <Separator />

                <div class="flex flex-wrap items-center gap-2">
                  <h3 class="text-base font-semibold">Choose a base design</h3>
                  <Badge v-if="selectedWebsiteType" variant="outline">
                    {{ selectedWebsiteType.name }}
                  </Badge>
                </div>

                <Empty v-if="!selectedWebsiteType" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>Select a website type</EmptyTitle>
                    <EmptyDescription>
                      Pick a category first so the builder can show matching complete templates.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <Empty v-else-if="!templateOptions.length" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>No templates available yet</EmptyTitle>
                    <EmptyDescription>
                      {{ selectedWebsiteType.name }} is ready for future template designs.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <div v-else class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
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
                    <span class="mt-3 flex items-start justify-between gap-2">
                      <span>
                        <span class="block text-sm font-semibold">{{ templateOption.name }}</span>
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
              </section>

              <section v-else-if="activeSection === 'details'" class="space-y-4">
                <div>
                  <h3 class="text-base font-semibold">Site details</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Set the name, public slug, default site flag, logo, and contact information.
                  </p>
                </div>

                <Empty v-if="!canEditDetails" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>Choose a base design first</EmptyTitle>
                    <EmptyDescription>
                      Details become editable after selecting a website type and template.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <FieldGroup v-else>
                  <FieldSet>
                    <div class="grid gap-4 md:grid-cols-2">
                      <Field>
                        <FieldLabel for="template-name">Template Name</FieldLabel>
                        <Input
                          v-field-help="'Enter the saved site name shown in the app.'"
                          id="template-name"
                          v-model="form.name"
                        />
                        <Label v-if="templateStore.errors.name" class="text-destructive text-xs">
                          {{ templateStore.errors.name[0] }}
                        </Label>
                      </Field>

                      <Field>
                        <FieldLabel for="business-name">Public Business Name</FieldLabel>
                        <Input
                          v-field-help="'Enter the business name shown on the published website.'"
                          id="business-name"
                          v-model="form.business_name"
                        />
                        <Label
                          v-if="templateStore.errors.business_name"
                          class="text-destructive text-xs"
                        >
                          {{ templateStore.errors.business_name[0] }}
                        </Label>
                      </Field>

                      <Field>
                        <FieldLabel for="site-slug">Site Slug</FieldLabel>
                        <Input
                          v-field-help="'Enter the URL slug used for this public site.'"
                          id="site-slug"
                          v-model="form.slug"
                          maxlength="120"
                          @input="updateSlug"
                        />
                        <Label v-if="templateStore.errors.slug" class="text-destructive text-xs">
                          {{ templateStore.errors.slug[0] }}
                        </Label>
                      </Field>

                      <Field orientation="horizontal" class="items-center gap-3 self-end">
                        <Checkbox
                          v-field-help="'Use this site as the tenant default public website.'"
                          id="default-site"
                          v-model="form.is_default"
                        />
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
                        <Input
                          v-field-help="'Enter the logo image URL shown on the site.'"
                          id="logo-url"
                          v-model="form.logo"
                        />
                        <Label v-if="templateStore.errors.logo" class="text-destructive text-xs">
                          {{ templateStore.errors.logo[0] }}
                        </Label>
                        <Input
                          v-field-help="'Upload a logo image to fill the URL automatically.'"
                          type="file"
                          accept="image/*"
                          class="cursor-pointer text-muted-foreground"
                          @change="handleLogoUpload"
                        />
                      </Field>

                      <Field>
                        <FieldLabel for="contact-email">Contact Email</FieldLabel>
                        <Input id="contact-email" v-model="form.contact_info.email" type="email" />
                      </Field>

                      <Field>
                        <FieldLabel for="contact-phone">Contact Phone</FieldLabel>
                        <Input id="contact-phone" v-model="form.contact_info.phone" type="tel" />
                      </Field>

                      <Field class="md:col-span-2">
                        <FieldLabel for="contact-address">Contact Address</FieldLabel>
                        <Input id="contact-address" v-model="form.contact_info.address" />
                      </Field>
                    </div>
                  </FieldSet>
                </FieldGroup>
              </section>

              <section v-else-if="activeSection === 'content'" class="space-y-4">
                <div>
                  <h3 class="text-base font-semibold">Template content</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Fill the sections and CTA fields used by the selected website design.
                  </p>
                </div>

                <Empty v-if="!canEditDetails" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>No template selected</EmptyTitle>
                    <EmptyDescription>
                      Select a base design to reveal the content fields for this site.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <Empty v-else-if="!dynamicFieldSchema.length" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>No custom content fields</EmptyTitle>
                    <EmptyDescription>
                      This design does not require additional section content.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <template v-else>
                  <DynamicTemplateFields
                    :schema="dynamicFieldSchema"
                    :model-value="form.content"
                    @update:model-value="form.content = $event"
                  />
                  <Label v-if="templateStore.errors.content" class="text-destructive text-xs">
                    {{ templateStore.errors.content[0] }}
                  </Label>
                </template>
              </section>

              <section v-else-if="activeSection === 'style'" class="space-y-4">
                <div>
                  <h3 class="text-base font-semibold">Global style</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Tune typography and colors, then watch the preview update live.
                  </p>
                </div>

                <Empty v-if="!canEditDetails" class="min-h-[220px]">
                  <EmptyHeader>
                    <EmptyTitle>Choose a design first</EmptyTitle>
                    <EmptyDescription>
                      Style controls become available after the base template is selected.
                    </EmptyDescription>
                  </EmptyHeader>
                </Empty>

                <FieldGroup v-else>
                  <FieldSet>
                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                      <Field>
                        <FieldLabel for="font-family">Font Family</FieldLabel>
                        <NativeSelect
                          v-field-help="'Choose the font used across the public site.'"
                          id="font-family"
                          v-model="form.font_family"
                          class="w-full"
                        >
                          <NativeSelectOption v-for="font in fonts" :key="font" :value="font">
                            {{ font }}
                          </NativeSelectOption>
                        </NativeSelect>
                      </Field>

                      <Field>
                        <FieldLabel for="primary-color">Primary Color</FieldLabel>
                        <Input id="primary-color" v-model="form.primary_color" type="color" />
                      </Field>

                      <Field>
                        <FieldLabel for="secondary-color">Secondary Color</FieldLabel>
                        <Input id="secondary-color" v-model="form.secondary_color" type="color" />
                      </Field>

                      <Field>
                        <FieldLabel for="background-color">Background Color</FieldLabel>
                        <Input id="background-color" v-model="form.background_color" type="color" />
                      </Field>

                      <Field>
                        <FieldLabel for="text-color">Text Color</FieldLabel>
                        <Input id="text-color" v-model="form.text_color" type="color" />
                      </Field>
                    </div>
                  </FieldSet>
                </FieldGroup>
              </section>

              <section v-else class="space-y-4">
                <div>
                  <h3 class="text-base font-semibold">Launch checks</h3>
                  <p class="mt-1 text-sm text-muted-foreground">
                    Review readiness, public links, and common broken image or link issues.
                  </p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                  <div
                    v-for="item in completionItems"
                    :key="item.key"
                    class="flex items-start gap-3 rounded border p-3"
                  >
                    <CheckCircle2
                      class="mt-0.5 size-4 shrink-0"
                      :class="item.completed ? 'text-emerald-600' : 'text-muted-foreground'"
                    />
                    <div>
                      <p class="text-sm font-medium">{{ item.label }}</p>
                      <Button
                        v-if="!item.completed"
                        variant="link"
                        size="xs"
                        type="button"
                        class="h-auto p-0"
                        @click="activeSection = item.section"
                      >
                        Fix this
                      </Button>
                    </div>
                  </div>
                </div>

                <Card class="gap-3 py-4">
                  <CardHeader class="px-4">
                    <CardTitle class="flex items-center gap-2 text-sm">
                      <Link class="size-4" />
                      Public preview and share
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="space-y-3 px-4">
                    <div v-if="publicUrl" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                      <Input :model-value="publicUrl" readonly class="font-mono text-xs" />
                      <Button as-child variant="navigate" size="sm">
                        <a :href="publicUrl" target="_blank" rel="noreferrer">
                          <ExternalLink class="size-4" />
                          Open
                        </a>
                      </Button>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">
                      Save and publish this site before sharing the public URL.
                    </p>
                  </CardContent>
                </Card>

                <Card class="gap-3 py-4">
                  <CardHeader class="px-4">
                    <CardTitle class="flex items-center gap-2 text-sm">
                      <Image class="size-4" />
                      Link and image validation
                    </CardTitle>
                  </CardHeader>
                  <CardContent class="px-4">
                    <div v-if="linkIssues.length" class="space-y-2">
                      <div
                        v-for="issue in linkIssues"
                        :key="`${issue.label}-${issue.value}`"
                        class="flex items-start gap-2 rounded border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200"
                      >
                        <AlertTriangle class="mt-0.5 size-4 shrink-0" />
                        <span class="min-w-0">
                          <span class="block font-medium">{{ issue.label }}</span>
                          <span class="block break-all text-xs">{{ issue.value }}</span>
                        </span>
                      </div>
                    </div>
                    <div
                      v-else
                      class="rounded border bg-muted/30 p-3 text-sm text-muted-foreground"
                    >
                      No broken-looking links or image URLs found in the current builder values.
                    </div>
                  </CardContent>
                </Card>
              </section>

              <p
                v-if="formError"
                class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
              >
                {{ formError }}
              </p>
            </form>
          </Card>

          <div class="flex flex-wrap items-center justify-between gap-2 rounded border bg-card p-3">
            <div class="text-sm text-muted-foreground">
              {{
                canSave
                  ? 'Ready to save changes.'
                  : 'Choose a website type and base design to save.'
              }}
            </div>
            <div class="flex flex-wrap gap-2">
              <Button
                variant="restore"
                type="button"
                size="sm"
                :disabled="templateStore.loading || !selectedTemplateId"
                @click="resetTemplateDefault"
              >
                <RotateCcw class="size-4" />
                Reset
              </Button>
              <Button
                v-if="selectedTemplateId"
                variant="delete"
                type="button"
                size="sm"
                :disabled="templateStore.loading"
                @click="deleteTemplate"
              >
                <Trash2 class="size-4" />
                Delete
              </Button>
            </div>
          </div>
        </main>

        <aside class="xl:sticky xl:top-24 xl:self-start">
          <Card class="gap-3 py-4">
            <CardHeader class="px-4">
              <div class="flex items-center justify-between gap-3">
                <CardTitle class="text-sm">Live Previews</CardTitle>
                <Badge variant="outline">{{ selectedCatalogTemplate.name || 'No design' }}</Badge>
              </div>
            </CardHeader>
            <CardContent class="px-4">
              <div class="max-h-[calc(100vh-12rem)] overflow-auto rounded border bg-muted/30 p-3">
                <TemplatePreview :template="previewTemplate" />
              </div>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </div>
</template>
