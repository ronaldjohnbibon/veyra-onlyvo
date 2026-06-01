import type { CtaEventType, CtaTrackingPayload } from '@/types/analytics'
import type { TemplateCtaConfig } from '@/types/templates'

const socialHosts = [
  'facebook.com',
  'instagram.com',
  'linkedin.com',
  'x.com',
  'twitter.com',
  'tiktok.com',
  'youtube.com',
  'pinterest.com',
  'threads.net',
  'snapchat.com',
]

const trimText = (value?: string | null): string => {
  return (value ?? '').replace(/\s+/g, ' ').trim()
}

const hashText = (value: string): string => {
  let hash = 0

  for (let index = 0; index < value.length; index += 1) {
    hash = (hash << 5) - hash + value.charCodeAt(index)
    hash |= 0
  }

  return Math.abs(hash).toString(36)
}

const identifierFor = (eventType: CtaEventType, source: string): string => {
  return `${eventType}:${hashText(source || eventType)}`
}

const hrefFor = (element: HTMLElement): string => {
  if (element instanceof HTMLAnchorElement) {
    return element.href || element.getAttribute('href') || ''
  }

  return element.getAttribute('href') || ''
}

const linkEventType = (href: string): CtaEventType => {
  const lowerHref = href.toLowerCase()

  if (lowerHref.startsWith('tel:')) return 'phone_click'
  if (lowerHref.startsWith('mailto:')) return 'email_click'
  if (lowerHref.startsWith('whatsapp:') || lowerHref.includes('wa.me/') || lowerHref.includes('whatsapp.com')) return 'whatsapp_click'

  try {
    const host = new URL(href, window.location.href).hostname.replace(/^www\./, '')

    if (socialHosts.some((socialHost) => host === socialHost || host.endsWith(`.${socialHost}`))) {
      return 'social_media_click'
    }
  } catch {
    return 'link_click'
  }

  return 'link_click'
}

const ctaTypeFor = (eventType: CtaEventType, element: HTMLElement): string => {
  const explicitType = trimText(element.dataset.ctaType)

  if (explicitType) return explicitType
  if (element instanceof HTMLFormElement) return 'form'
  if (eventType === 'phone_click') return 'phone'
  if (eventType === 'email_click') return 'email'
  if (eventType === 'whatsapp_click') return 'whatsapp'
  if (eventType === 'social_media_click') return 'social_media'

  return element instanceof HTMLAnchorElement ? 'link' : 'button'
}

const labelFor = (element: HTMLElement, href: string): string => {
  return (
    trimText(element.dataset.ctaLabel) ||
    trimText(element.innerText) ||
    trimText(element.getAttribute('aria-label')) ||
    trimText(element.getAttribute('title')) ||
    href ||
    'CTA'
  )
}

const isIgnoredButton = (element: HTMLElement): boolean => {
  const label = trimText(element.getAttribute('aria-label')).toLowerCase()

  return label.includes('toggle') || label.includes('menu')
}

export const ctaPayloadFromElement = (
  templateId: string,
  element: HTMLElement
): CtaTrackingPayload | null => {
  if (element instanceof HTMLFormElement) {
    return null
  }

  // Form submits are tracked only after the submission succeeds.
  if (element instanceof HTMLButtonElement && element.type === 'submit') {
    return null
  }

  if (!(element instanceof HTMLAnchorElement) && isIgnoredButton(element)) {
    return null
  }

  const href = hrefFor(element)
  const eventType = element instanceof HTMLAnchorElement ? linkEventType(href) : 'button_click'
  const label = labelFor(element, href)
  const source = `${eventType}:${href || label}:${element.dataset.ctaId ?? ''}`

  return {
    template_id: templateId,
    cta_identifier: trimText(element.dataset.ctaId) || identifierFor(eventType, source),
    cta_label: label,
    cta_type: ctaTypeFor(eventType, element),
    event_type: eventType,
    url: window.location.href,
    referrer: document.referrer || null,
  }
}

export const ctaViewPayloadFromElement = (
  templateId: string,
  element: HTMLElement
): CtaTrackingPayload | null => {
  const href = hrefFor(element)
  const label = labelFor(element, href)
  const source = `cta_view:${href || label}:${element.dataset.ctaId ?? ''}`

  return {
    template_id: templateId,
    cta_identifier: trimText(element.dataset.ctaId) || identifierFor('cta_view', source),
    cta_label: label,
    cta_type: ctaTypeFor('cta_view', element),
    event_type: 'cta_view',
    url: window.location.href,
    referrer: document.referrer || null,
  }
}

export const submittedEventTypeFor = (ctaType: string): CtaEventType => {
  if (ctaType === 'booking') return 'booking_submitted'
  if (ctaType === 'contact_message') return 'message_submitted'
  if (ctaType === 'quote_request') return 'quote_request_submitted'
  if (ctaType === 'newsletter') return 'newsletter_signup_submitted'

  return 'form_submitted'
}

export const ctaPayloadFromForm = (
  templateId: string,
  cta: TemplateCtaConfig,
  eventType: CtaEventType
): CtaTrackingPayload => {
  const label = trimText(cta.label) || trimText(cta.title) || trimText(cta.submit_label) || cta.type
  const source = `${cta.type}:${label}:${templateId}`

  return {
    template_id: templateId,
    cta_identifier: identifierFor(eventType, source),
    cta_label: label,
    cta_type: cta.type,
    event_type: eventType,
    url: window.location.href,
    referrer: document.referrer || null,
  }
}
