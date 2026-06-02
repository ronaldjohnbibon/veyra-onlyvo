import type { TemplateCatalogItem } from '@/shared/types/templates'

export const emptyTemplateCatalogItem: TemplateCatalogItem = {
  key: '',
  name: 'No template selected',
  description: 'Select a website type and template to build a preview.',
  preview_image: null,
}

export const getTemplateCatalogItem = (
  key?: string | null,
  templates: TemplateCatalogItem[] = []
): TemplateCatalogItem => {
  // The fallback keeps previews stable while a website type is loading.
  return templates.find((template) => template.key === key) ?? emptyTemplateCatalogItem
}
