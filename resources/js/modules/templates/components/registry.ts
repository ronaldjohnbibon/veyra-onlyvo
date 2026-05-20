import type { TemplateSectionType } from '@/types/templates'
import type { Component } from 'vue'

const sectionComponents = import.meta.glob('./*/Design*.vue', {
  eager: true,
  import: 'default',
}) as Record<string, Component>

const designNumberFor = (sectionType: TemplateSectionType, designKey: string): string | null => {
  if (designKey === `${sectionType}-default`) {
    return '1'
  }

  const sectionMatch = designKey.match(new RegExp(`^${sectionType}-(\\d+)$`))

  if (sectionMatch?.[1]) {
    return sectionMatch[1]
  }

  const componentMatch = designKey.match(/^Design(\d+)$/)

  return componentMatch?.[1] ?? null
}

export const getTemplateSectionComponent = (
  sectionType: TemplateSectionType,
  designKey: string
): Component | null => {
  const designNumber = designNumberFor(sectionType, designKey)

  return (
    (designNumber ? sectionComponents[`./${sectionType}/Design${designNumber}.vue`] : null) ??
    sectionComponents[`./${sectionType}/Design1.vue`] ??
    null
  )
}
