import type { Component } from 'vue'

const templateComponents = import.meta.glob('../templates/*/*.vue', {
  eager: true,
  import: 'default',
}) as Record<string, Component>

export const getWebsiteTemplateComponent = (
  websiteTypeSlug?: string | null,
  templateKey?: string | null
): Component | null => {
  if (!websiteTypeSlug || !templateKey) return null

  return templateComponents[`../templates/${websiteTypeSlug}/${templateKey}.vue`] ?? null
}
