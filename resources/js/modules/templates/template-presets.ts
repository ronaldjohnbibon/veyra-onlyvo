import type { TemplatePreset } from '@/types/templates'

export const templatePresets: TemplatePreset[] = []

export const defaultTemplateKey = null

export const emptyTemplatePreset: TemplatePreset = {
  key: '',
  name: 'No design selected',
  description: 'Select a website type and design to build a preview.',
  preview_image: null,
  sections: [],
}

export const getTemplatePreset = (
  key?: string | null,
  presets: TemplatePreset[] = []
): TemplatePreset => {
  // The fallback keeps previews stable when a website type has no designs yet.
  return presets.find((preset) => preset.key === key) ?? emptyTemplatePreset
}
