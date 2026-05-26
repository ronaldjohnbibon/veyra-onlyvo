<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import { getTemplatePreset } from '@/modules/templates/template-presets'
import { useTemplateStore } from '@/modules/templates/template-store'
import type {
  TemplatePayload,
  TemplatePreset,
  TemplateRecord,
  TemplateSection,
  TemplateStatus,
  WebsiteType,
} from '@/types/templates'
import { Check, ExternalLink, Layers3, Plus, Save, Send } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const templateStore = useTemplateStore()
const router = useRouter()
const selectedWebsiteTypeId = ref<string | null>(null)
const selectedTemplateId = ref<string | null>(null)
const selectedTemplateSections = ref<TemplateSection[] | undefined>(undefined)
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
    template_key: null,
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

const presetOptions = computed<TemplatePreset[]>(() => templateStore.presets)

const selectedPreset = computed<TemplatePreset>(() => {
  return getTemplatePreset(form.value.template_key, presetOptions.value)
})

const hasSelectedDesign = computed(() =>
  Boolean(form.value.template_key && selectedPreset.value.key)
)

const canEditDetails = computed(() => Boolean(selectedTemplateId.value || hasSelectedDesign.value))

const canSave = computed(() => {
  return Boolean(
    form.value.website_type_id && (selectedTemplateId.value || form.value.template_key)
  )
})

const previewTemplate = computed<TemplateRecord>(() => ({
  id: selectedTemplateId.value ?? 'preview',
  tenant_id: '',
  ...form.value,
  website_type_id: form.value.website_type_id,
  website_type: selectedWebsiteType.value ?? undefined,
  sections: selectedTemplateSections.value ?? selectedPreset.value.sections ?? [],
}))

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
  selectedTemplateSections.value = undefined
  slugTouched.value = false
  form.value = createBlankTemplate(websiteType)
  formError.value = ''

  await templateStore.loadPresets(websiteType.id)
  await templateStore.index({ page: 1, website_type_id: websiteType.id })
}

const selectPreset = (preset: TemplatePreset): void => {
  form.value.template_key = preset.key
  selectedTemplateSections.value = preset.sections ?? []
}

const resetForm = (): void => {
  selectedTemplateId.value = null
  selectedTemplateSections.value = undefined
  slugTouched.value = false
  form.value = createBlankTemplate(selectedWebsiteType.value)
  formError.value = ''
}

const updateSlug = (): void => {
  slugTouched.value = true
  form.value.slug = slugify(form.value.slug)
}

const hydrateForm = (template: TemplateRecord): void => {
  selectedTemplateId.value = template.id
  selectedWebsiteTypeId.value = template.website_type_id
  selectedTemplateSections.value = template.sections ?? []
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
    font_family: template.font_family,
    primary_color: template.primary_color,
    secondary_color: template.secondary_color,
    background_color: template.background_color,
    text_color: template.text_color,
    status: template.status,
    is_default: template.is_default,
  }
  formError.value = ''
}

const openTemplate = async (template: TemplateRecord): Promise<void> => {
  if (selectedWebsiteTypeId.value !== template.website_type_id) {
    selectedWebsiteTypeId.value = template.website_type_id
    await templateStore.loadPresets(template.website_type_id)
  }

  hydrateForm(template)
}

const validate = (): boolean => {
  if (!form.value.website_type_id) {
    formError.value = 'Select a website type first.'
    return false
  }

  if (!selectedTemplateId.value && !form.value.template_key) {
    formError.value = 'Choose a design before saving a new template.'
    return false
  }

  const required = [
    form.value.name,
    form.value.business_name,
    form.value.logo,
    form.value.contact_info.email,
    form.value.contact_info.phone,
  ]

  if (required.some((value) => !String(value).trim())) {
    formError.value = 'Template, business name, logo, email, and phone are required.'
    return false
  }

  if (!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(form.value.slug) || form.value.slug.length > 120) {
    formError.value = 'Site slug must use lowercase letters, numbers, and hyphens only.'
    return false
  }

  formError.value = ''
  return true
}

const saveTemplate = async (status: TemplateStatus): Promise<void> => {
  if (!validate()) return

  form.value.status = status

  const saved = selectedTemplateId.value
    ? await templateStore.update(selectedTemplateId.value, { ...form.value })
    : await templateStore.store({ ...form.value })

  hydrateForm(saved)
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
            Select a website type first, then choose an available design under that type.
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[16rem_minmax(0,1fr)_30rem]">
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
              variant="outline"
              class="h-auto w-full flex-col items-start justify-start gap-1 whitespace-normal border bg-background p-3 text-left hover:border-primary"
              :class="{
                'border-primary ring-2 ring-primary/20': selectedTemplateId === template.id,
              }"
              type="button"
              @click="openTemplate(template)"
            >
              <span class="block font-semibold">{{ template.name }}</span>
              <span class="block text-muted-foreground">{{ template.business_name }}</span>
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
                Saved tenant templates will appear here after designs are added.
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        </aside>

        <form class="space-y-4" @submit.prevent>
          <Card class="gap-4 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Website Type</CardTitle>
              <CardDescription>Choose the site category before viewing designs.</CardDescription>
            </CardHeader>

            <CardContent class="px-4">
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
                        {{ websiteType.designs_count }} designs available
                      </span>
                    </span>
                    <Check
                      v-if="selectedWebsiteTypeId === websiteType.id"
                      class="size-4 shrink-0 text-primary"
                    />
                  </span>
                </button>
              </div>
            </CardContent>
          </Card>

          <Card class="gap-4 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Available Designs</CardTitle>
              <CardAction>
                <Badge v-if="selectedWebsiteType" variant="outline">
                  {{ selectedWebsiteType.name }}
                </Badge>
              </CardAction>
            </CardHeader>

            <CardContent class="px-4">
              <Empty v-if="!selectedWebsiteType" class="min-h-[220px]">
                <EmptyHeader>
                  <EmptyTitle>Select a website type</EmptyTitle>
                  <EmptyDescription>
                    Designs are organized by website type and appear after a type is selected.
                  </EmptyDescription>
                </EmptyHeader>
              </Empty>

              <Empty v-else-if="!presetOptions.length" class="min-h-[220px]">
                <EmptyHeader>
                  <EmptyTitle>No designs available yet</EmptyTitle>
                  <EmptyDescription>
                    {{ selectedWebsiteType.name }} is ready for future designs.
                  </EmptyDescription>
                </EmptyHeader>
              </Empty>

              <div v-else class="grid gap-3 md:grid-cols-3">
                <button
                  v-for="preset in presetOptions"
                  :key="preset.key"
                  type="button"
                  class="group rounded border bg-background p-2 text-left transition hover:border-primary hover:shadow-sm"
                  :class="{
                    'border-primary ring-2 ring-primary/20': form.template_key === preset.key,
                  }"
                  @click="selectPreset(preset)"
                >
                  <div
                    class="flex aspect-video w-full items-center justify-center rounded bg-muted text-muted-foreground"
                  >
                    <img
                      v-if="preset.preview_image"
                      :src="preset.preview_image"
                      :alt="preset.name"
                      class="h-full w-full rounded object-cover"
                    />
                    <Layers3 v-else class="size-6" />
                  </div>
                  <span class="mt-3 flex items-center justify-between gap-2">
                    <span>
                      <span class="block text-sm font-semibold">{{ preset.name }}</span>
                      <span class="mt-1 block text-xs leading-5 text-muted-foreground">
                        {{ preset.description }}
                      </span>
                    </span>
                    <Check
                      v-if="form.template_key === preset.key"
                      class="size-4 shrink-0 text-primary"
                    />
                  </span>
                </button>
              </div>
            </CardContent>
          </Card>

          <Card v-if="canEditDetails" class="gap-4 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Business Details</CardTitle>
              <CardAction>
                <Badge variant="outline">{{ form.status }}</Badge>
              </CardAction>
            </CardHeader>

            <CardContent class="px-4">
              <FieldGroup>
                <FieldSet>
                  <div class="grid gap-4 md:grid-cols-2">
                    <Field>
                      <FieldLabel for="template-name">Template Name</FieldLabel>
                      <Input id="template-name" v-model="form.name" required />
                    </Field>

                    <Field>
                      <FieldLabel for="business-name">Business Name</FieldLabel>
                      <Input id="business-name" v-model="form.business_name" required />
                    </Field>

                    <Field>
                      <FieldLabel for="site-slug">Site Slug</FieldLabel>
                      <Input
                        id="site-slug"
                        v-model="form.slug"
                        readonly
                        class="cursor-not-allowed"
                        maxlength="120"
                        required
                        @input="updateSlug"
                      />
                    </Field>

                    <Field orientation="horizontal" class="items-center gap-3 self-end">
                      <Checkbox id="default-site" v-model="form.is_default" />
                      <FieldLabel for="default-site">Default public site</FieldLabel>
                    </Field>

                    <Field class="md:col-span-2">
                      <FieldLabel for="logo-url">Logo URL</FieldLabel>
                      <Input id="logo-url" v-model="form.logo" required />
                      <Input
                        type="file"
                        accept="image/*"
                        class="cursor-pointer text-muted-foreground"
                        @change="handleLogoUpload"
                      />
                    </Field>

                    <Field>
                      <FieldLabel for="contact-email">Email</FieldLabel>
                      <Input id="contact-email" v-model="form.contact_info.email" required />
                    </Field>

                    <Field>
                      <FieldLabel for="contact-phone">Phone</FieldLabel>
                      <Input id="contact-phone" v-model="form.contact_info.phone" required />
                    </Field>

                    <Field class="md:col-span-2">
                      <FieldLabel for="contact-address">Address</FieldLabel>
                      <Input id="contact-address" v-model="form.contact_info.address" />
                    </Field>

                    <Field>
                      <FieldLabel for="website-url">Website</FieldLabel>
                      <Input id="website-url" v-model="form.social_links.website" />
                    </Field>

                    <Field>
                      <FieldLabel for="linkedin-url">LinkedIn</FieldLabel>
                      <Input id="linkedin-url" v-model="form.social_links.linkedin" />
                    </Field>

                    <Field>
                      <FieldLabel for="instagram-url">Instagram</FieldLabel>
                      <Input id="instagram-url" v-model="form.social_links.instagram" />
                    </Field>

                    <Field>
                      <FieldLabel for="facebook-url">Facebook</FieldLabel>
                      <Input id="facebook-url" v-model="form.social_links.facebook" />
                    </Field>
                  </div>
                </FieldSet>
              </FieldGroup>
            </CardContent>
          </Card>

          <Card v-if="canEditDetails" class="gap-4 py-4">
            <CardHeader class="px-4">
              <CardTitle class="text-sm">Global Styles</CardTitle>
            </CardHeader>

            <CardContent class="px-4">
              <FieldGroup>
                <FieldSet>
                  <div class="grid gap-4 md:grid-cols-5">
                    <Field>
                      <FieldLabel for="font-family">Font Family</FieldLabel>
                      <NativeSelect id="font-family" v-model="form.font_family" class="w-full">
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
            </CardContent>
          </Card>

          <p
            v-if="formError"
            class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
          >
            {{ formError }}
          </p>
        </form>

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
          :disabled="templateStore.loading || !canSave"
          @click="saveTemplate('draft')"
        >
          <Save class="size-3" />
          Draft
        </Button>
        <Button
          variant="create"
          class="w-full rounded shadow h-8 px-3 text-xs"
          type="button"
          :disabled="templateStore.loading || !canSave"
          @click="saveTemplate('published')"
        >
          <Send class="size-3" />
          Publish
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
