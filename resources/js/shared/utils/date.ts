export const formatDisplayDate = (
  value?: string | null,
  options: Intl.DateTimeFormatOptions = {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }
): string => {
  if (!value) return ''

  return new Intl.DateTimeFormat(undefined, options).format(new Date(value))
}
