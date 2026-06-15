const allowedProtocols = ['http:', 'https:', 'mailto:', 'tel:']
const redirectProtocols = ['http:', 'https:']

export const safeHref = (value: unknown, fallback = '#'): string => {
  const raw = String(value ?? '').trim()

  if (!raw) return fallback
  if (raw.startsWith('#') || isRelativePath(raw)) return raw

  try {
    const url = new URL(raw, window.location.origin)

    return allowedProtocols.includes(url.protocol) ? raw : fallback
  } catch {
    return fallback
  }
}

export const safeRedirectUrl = (value: unknown, fallback = ''): string => {
  const raw = String(value ?? '').trim()

  if (!raw) return fallback
  if (isRelativePath(raw)) return raw

  try {
    const url = new URL(raw, window.location.origin)

    return redirectProtocols.includes(url.protocol) ? raw : fallback
  } catch {
    return fallback
  }
}

const isRelativePath = (value: string): boolean => {
  return value.startsWith('/') && !value.startsWith('//') && !value.startsWith('/\\')
}
