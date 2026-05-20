<script setup lang="ts">
import TemplatePreview from '@/modules/templates/components/TemplatePreview.vue'
import {
  contentForDesign,
  designForSection,
  fieldsForDesign,
} from '@/modules/templates/field-registry'
import { useTemplateStore } from '@/modules/templates/template-store'
import type {
  TemplateSectionContent,
  TemplatePayload,
  TemplateRecord,
  TemplateSection,
  TemplateSectionType,
  TemplateStatus,
} from '@/types/templates'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Checkbox } from '@/components/ui/checkbox'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { Field, FieldGroup, FieldLabel, FieldSet } from '@/components/ui/field'
import { Input } from '@/components/ui/input'
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select'
import { Textarea } from '@/components/ui/textarea'
import { ArrowDown, ArrowUp, ChevronDown, Plus, Save, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const templateStore = useTemplateStore()
const router = useRouter()
const selectedTemplateId = ref<string | null>(null)
const formError = ref('')

const fonts = ['Inter', 'Poppins', 'Arial', 'Georgia']

const defaultSectionDesigns = computed(() => {
  const usedSectionTypes = new Set<TemplateSectionType>()

  // Pick the first active design for each section type in display order.
  return [...templateStore.designs]
    .sort((a, b) => {
      return (
        a.default_sort_order - b.default_sort_order ||
        a.section_type.localeCompare(b.section_type) ||
        a.name.localeCompare(b.name)
      )
    })
    .filter((design) => {
      if (usedSectionTypes.has(design.section_type)) return false

      usedSectionTypes.add(design.section_type)
      return true
    })
})

const createDefaultSections = (): TemplateSection[] => {
  return defaultSectionDesigns.value.map((design, index) => ({
    section_type: design.section_type,
    design_key: design.design_key,
    sort_order: design.default_sort_order || index + 1,
    is_enabled: design.default_enabled,
    content_json: contentForDesign(templateStore.designs, design.section_type, design.design_key),
  }))
}

const createBlankTemplate = (): TemplatePayload => ({
  name: 'Business Website',
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
  sections: createDefaultSections(),
})

const form = ref<TemplatePayload>(createBlankTemplate())

const orderedSections = computed(() => {
  return [...form.value.sections].sort((a, b) => a.sort_order - b.sort_order)
})

const previewTemplate = computed<TemplateRecord>(() => ({
  id: selectedTemplateId.value ?? 'preview',
  tenant_id: '',
  ...form.value,
}))

const designsFor = (sectionType: TemplateSectionType) => {
  return templateStore.designs.filter((design) => design.section_type === sectionType)
}

const sectionLabelFor = (sectionType: TemplateSectionType) => {
  const design = designForSection(templateStore.designs, sectionType)

  return (
    design?.section_label ||
    sectionType.replace(/(^|_)(\w)/g, (_, __, letter) => ` ${letter.toUpperCase()}`).trim()
  )
}

const fieldsForSection = (section: TemplateSection) => {
  return fieldsForDesign(templateStore.designs, section.section_type, section.design_key)
}

const updateSortOrder = (sections: TemplateSection[]): void => {
  sections.forEach((section, index) => {
    section.sort_order = index + 1
  })
}

const moveSection = (section: TemplateSection, direction: -1 | 1): void => {
  const sections = orderedSections.value
  const index = sections.findIndex((item) => item.section_type === section.section_type)
  const target = index + direction

  if (index < 0 || target < 0 || target >= sections.length) return

  const [movedSection] = sections.splice(index, 1)
  sections.splice(target, 0, movedSection)

  updateSortOrder(sections)
  form.value.sections = sections
}

const ensureList = (section: TemplateSection, key: string): string[] => {
  if (!Array.isArray(section.content_json[key])) section.content_json[key] = []
  return section.content_json[key]
}

const addListItem = (section: TemplateSection, key: string): void => {
  ensureList(section, key).push('New item')
}

const removeListItem = (section: TemplateSection, key: string, index: number): void => {
  ensureList(section, key).splice(index, 1)
}

const selectDesign = (section: TemplateSection, designKey: string): void => {
  section.design_key = designKey
  // Keep existing values that still match the new design fields.
  section.content_json = contentForDesign(
    templateStore.designs,
    section.section_type,
    designKey,
    section.content_json
  )
}

const resetForm = (): void => {
  selectedTemplateId.value = null
  form.value = createBlankTemplate()
  formError.value = ''
}

const hydrateForm = (template: TemplateRecord): void => {
  const defaults = createDefaultSections()
  // Merge saved sections over current defaults so newly added designs still appear.
  const sections = defaults.map((defaultSection) => {
    const existing = template.sections.find(
      (section) => section.section_type === defaultSection.section_type
    )

    return existing
      ? {
          ...defaultSection,
          ...existing,
          content_json: contentForDesign(
            templateStore.designs,
            defaultSection.section_type,
            existing.design_key,
            existing.content_json
          ),
        }
      : defaultSection
  })

  selectedTemplateId.value = template.id
  form.value = {
    name: template.name,
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
    sections,
  }
  formError.value = ''
}

const openTemplate = async (template: TemplateRecord): Promise<void> => {
  // Open any saved template from the management page.
  await router.push({ name: 'templates.published', params: { id: template.id } })
}

const validate = (): boolean => {
  const required = [
    form.value.name,
    form.value.business_name,
    form.value.logo,
    form.value.contact_info.email,
    form.value.contact_info.phone,
  ]

  if (required.some((value) => !String(value).trim())) {
    formError.value = 'Template name, business name, logo, email, and phone are required.'
    return false
  }

  formError.value = ''
  return true
}

const cleanedContent = (section: TemplateSection): TemplateSectionContent => {
  return fieldsForSection(section).reduce<TemplateSectionContent>((content, field) => {
    const value = section.content_json[field.key]

    content[field.key] = Array.isArray(value) ? value.filter(Boolean) : (value ?? '')

    return content
  }, {})
}

const saveTemplate = async (status: TemplateStatus): Promise<void> => {
  if (!validate()) return

  form.value.status = status
  const payload = {
    ...form.value,
    sections: form.value.sections.map((section) => ({
      ...section,
      content_json: cleanedContent(section),
    })),
  }

  const saved = selectedTemplateId.value
    ? await templateStore.update(selectedTemplateId.value, payload)
    : await templateStore.store(payload)

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
  await templateStore.loadDesigns()
  form.value = createBlankTemplate()
  await templateStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Templates</h2>
          <p class="module-container-description">
            Build and publish business websites from reusable section designs.
          </p>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[16rem_minmax(0,1fr)_30rem]">
        <aside class="space-y-3">
          <Input
            v-model="templateStore.params.search"
            placeholder="Search templates"
            @keyup.enter="templateStore.index({ search: templateStore.params.search })"
            class="w-full max-w-[300px]"
          />

          <div class="space-y-2">
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
              <Badge class="mt-2" variant="outline">{{ template.status }}</Badge>
            </Button>
          </div>
        </aside>

        <form class="space-y-4" @submit.prevent>
          <Card class="gap-4 py-4">
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

          <Card class="gap-4 py-4">
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

          <section class="space-y-3">
            <div class="module-heading-container mb-0 p-0">
              <div>
                <h3 class="text-sm font-semibold">Sections</h3>
                <p class="module-container-description">
                  Disabled sections are excluded from preview and publish.
                </p>
              </div>
            </div>

            <Collapsible
              v-for="(section, index) in orderedSections"
              :key="section.section_type"
              class="w-full rounded border bg-background hover:shadow-lg"
            >
              <div class="flex items-center justify-between gap-2 p-3">
                <Field orientation="horizontal" class="gap-3">
                  <Checkbox
                    :id="`section-enabled-${section.section_type}`"
                    v-model="section.is_enabled"
                  />
                  <FieldLabel
                    :for="`section-enabled-${section.section_type}`"
                    class="font-semibold"
                  >
                    {{ sectionLabelFor(section.section_type) }}
                  </FieldLabel>
                </Field>

                <div class="flex items-center gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    :disabled="index === 0"
                    type="button"
                    @click="moveSection(section, -1)"
                  >
                    <ArrowUp class="size-4" />
                  </Button>
                  <Button
                    size="sm"
                    variant="outline"
                    :disabled="index === orderedSections.length - 1"
                    type="button"
                    @click="moveSection(section, 1)"
                  >
                    <ArrowDown class="size-4" />
                  </Button>
                  <CollapsibleTrigger class="rounded p-2 hover:bg-accent">
                    <ChevronDown class="size-4" />
                  </CollapsibleTrigger>
                </div>
              </div>

              <CollapsibleContent class="border-t p-4">
                <div class="mb-4 grid gap-3 sm:grid-cols-2">
                  <Button
                    v-for="design in designsFor(section.section_type)"
                    :key="design.id"
                    variant="outline"
                    class="h-auto w-full flex-col items-start justify-start whitespace-normal border bg-card p-2 text-left hover:border-primary"
                    :class="{
                      'border-primary ring-2 ring-primary/20':
                        section.design_key === design.design_key,
                    }"
                    type="button"
                    @click="selectDesign(section, design.design_key)"
                  >
                    <img
                      :src="design.preview_image"
                      :alt="design.name"
                      class="mb-2 aspect-video w-full rounded object-cover"
                    />
                    <span class="w-full text-sm font-semibold">{{ design.name }}</span>
                  </Button>
                </div>

                <FieldGroup>
                  <FieldSet>
                    <div class="grid gap-4 md:grid-cols-2">
                      <Field
                        v-for="fieldConfig in fieldsForSection(section)"
                        :key="fieldConfig.key"
                        :class="fieldConfig.class"
                      >
                        <template v-if="fieldConfig.type === 'textarea'">
                          <FieldLabel
                            :for="`section-${section.section_type}-${section.design_key}-${fieldConfig.key}`"
                          >
                            {{ fieldConfig.label }}
                          </FieldLabel>
                          <Textarea
                            :id="`section-${section.section_type}-${section.design_key}-${fieldConfig.key}`"
                            v-model="section.content_json[fieldConfig.key]"
                          />
                        </template>

                        <template v-else-if="fieldConfig.type === 'list'">
                          <div class="flex items-center justify-between gap-3">
                            <p class="text-sm font-medium">{{ fieldConfig.label }}</p>
                            <Button
                              size="sm"
                              variant="outline"
                              type="button"
                              @click="addListItem(section, fieldConfig.key)"
                            >
                              <Plus class="size-4" />
                              Add
                            </Button>
                          </div>

                          <div
                            v-for="(_, itemIndex) in ensureList(section, fieldConfig.key)"
                            :key="`${fieldConfig.key}-${itemIndex}`"
                            class="mt-2 flex gap-2"
                          >
                            <Input v-model="section.content_json[fieldConfig.key][itemIndex]" />
                            <Button
                              size="sm"
                              variant="outline"
                              type="button"
                              @click="removeListItem(section, fieldConfig.key, itemIndex)"
                            >
                              <Trash2 class="size-4" />
                            </Button>
                          </div>
                        </template>

                        <template v-else>
                          <FieldLabel
                            :for="`section-${section.section_type}-${section.design_key}-${fieldConfig.key}`"
                          >
                            {{ fieldConfig.label }}
                          </FieldLabel>
                          <Input
                            :id="`section-${section.section_type}-${section.design_key}-${fieldConfig.key}`"
                            v-model="section.content_json[fieldConfig.key]"
                            :type="fieldConfig.type === 'url' ? 'url' : 'text'"
                          />
                        </template>
                      </Field>

                      <Field v-if="!fieldsForSection(section).length" class="md:col-span-2">
                        <FieldLabel>No fields configured for this design.</FieldLabel>
                        <Input
                          :model-value="`${section.section_type}/${section.design_key}`"
                          disabled
                        />
                      </Field>
                    </div>
                  </FieldSet>
                </FieldGroup>
              </CollapsibleContent>
            </Collapsible>
          </section>

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

      <div class="fixed bottom-6 right-6 z-20 flex w-[100px] flex-col gap-2 draggable">
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
          :disabled="templateStore.loading"
          @click="saveTemplate(form.status)"
        >
          <Save class="size-3" />
          Save
        </Button>
      </div>
    </div>
  </div>
</template>
