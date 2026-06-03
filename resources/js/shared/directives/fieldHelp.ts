import type { Directive, DirectiveBinding } from 'vue'
import { watch } from 'vue'
import { useFieldDescriptions } from '@/shared/composables/useFieldDescriptions'

type HelpElement = HTMLElement & {
  __fieldHelpDescription?: string
  __fieldHelpOriginalTitle?: string | null
  __fieldHelpStop?: () => void
  __fieldHelpTarget?: HTMLElement
}

const controlSelector = [
  'input',
  'select',
  'textarea',
  'button',
  '[role="checkbox"]',
  '[role="combobox"]',
  '[role="switch"]',
  'label[for]',
  '[tabindex]',
].join(',')

const normalizeDescription = (value: unknown): string => {
  return typeof value === 'string' ? value.trim() : ''
}

const targetFor = (el: HTMLElement): HTMLElement => {
  return el.matches(controlSelector) ? el : (el.querySelector(controlSelector) ?? el)
}

const applyDescription = (el: HelpElement): void => {
  const target = el.__fieldHelpTarget ?? targetFor(el)
  const description = el.__fieldHelpDescription ?? ''
  const { showFieldDescriptions } = useFieldDescriptions()

  el.__fieldHelpTarget = target

  if (el.__fieldHelpOriginalTitle === undefined) {
    el.__fieldHelpOriginalTitle = target.getAttribute('title')
  }

  if (showFieldDescriptions.value && description) {
    target.setAttribute('title', description)
    target.setAttribute('aria-description', description)
    return
  }

  if (el.__fieldHelpOriginalTitle) {
    target.setAttribute('title', el.__fieldHelpOriginalTitle)
  } else {
    target.removeAttribute('title')
  }

  target.removeAttribute('aria-description')
}

const setDescription = (el: HelpElement, binding: DirectiveBinding<unknown>): void => {
  el.__fieldHelpDescription = normalizeDescription(binding.value)
  applyDescription(el)
}

export const fieldHelp: Directive<HTMLElement, unknown> = {
  mounted(el, binding) {
    const fieldHelpEl = el as HelpElement
    const { showFieldDescriptions } = useFieldDescriptions()

    setDescription(fieldHelpEl, binding)
    fieldHelpEl.__fieldHelpStop = watch(showFieldDescriptions, () => applyDescription(fieldHelpEl))
  },
  updated(el, binding) {
    setDescription(el as HelpElement, binding)
  },
  unmounted(el) {
    const fieldHelpEl = el as HelpElement

    fieldHelpEl.__fieldHelpStop?.()
    fieldHelpEl.__fieldHelpStop = undefined
  },
}
