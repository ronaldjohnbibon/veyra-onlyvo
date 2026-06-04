const escapeHtml = (value: string): string => {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const isSafeUrl = (value: string): boolean => {
  if (value.startsWith('/') || value.startsWith('#')) return true

  try {
    const url = new URL(value)

    return ['http:', 'https:', 'mailto:', 'tel:'].includes(url.protocol)
  } catch {
    return false
  }
}

const inlineMarkdown = (value: string): string => {
  return value
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/\*([^*]+)\*/g, '<em>$1</em>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_match, label: string, href: string) => {
      const cleanHref = href.trim()

      if (!isSafeUrl(cleanHref)) {
        return label
      }

      return `<a href="${escapeHtml(cleanHref)}" target="_blank" rel="noopener noreferrer">${label}</a>`
    })
}

export const renderMarkdown = (value: string): string => {
  const lines = escapeHtml(value).split(/\r?\n/)
  const blocks: string[] = []
  let paragraph: string[] = []
  let listItems: string[] = []

  const flushParagraph = (): void => {
    if (!paragraph.length) return

    blocks.push(`<p>${inlineMarkdown(paragraph.join('<br>'))}</p>`)
    paragraph = []
  }

  const flushList = (): void => {
    if (!listItems.length) return

    blocks.push(`<ul>${listItems.map((item) => `<li>${inlineMarkdown(item)}</li>`).join('')}</ul>`)
    listItems = []
  }

  for (const line of lines) {
    const trimmed = line.trim()

    if (!trimmed) {
      flushParagraph()
      flushList()
      continue
    }

    const heading = /^(#{1,3})\s+(.+)$/.exec(trimmed)

    if (heading) {
      flushParagraph()
      flushList()
      blocks.push(`<h${heading[1].length}>${inlineMarkdown(heading[2])}</h${heading[1].length}>`)
      continue
    }

    const listItem = /^[-*]\s+(.+)$/.exec(trimmed)

    if (listItem) {
      flushParagraph()
      listItems.push(listItem[1])
      continue
    }

    flushList()
    paragraph.push(trimmed)
  }

  flushParagraph()
  flushList()

  return blocks.join('')
}
