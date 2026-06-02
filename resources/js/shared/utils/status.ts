import type { BadgeVariants } from '@/shared/components/ui/badge'

type BadgeVariant = NonNullable<BadgeVariants['variant']>

const statusLabels: Record<string, string> = {
  active: 'Active',
  approved: 'Approved',
  archived: 'Archived',
  disabled: 'Disabled',
  draft: 'Draft',
  enabled: 'Enabled',
  failed: 'Failed',
  inactive: 'Inactive',
  new: 'New',
  pending: 'Pending',
  published: 'Published',
  rejected: 'Rejected',
  signed_in: 'Signed in',
  under_review: 'Under Review',
  completed: 'Completed',
}

const statusVariants: Record<string, BadgeVariant> = {
  active: 'success',
  approved: 'success',
  completed: 'success',
  enabled: 'success',
  published: 'success',
  signed_in: 'success',
  draft: 'warning',
  pending: 'warning',
  under_review: 'info',
  new: 'info',
  archived: 'neutral',
  disabled: 'neutral',
  inactive: 'neutral',
  failed: 'destructive',
  rejected: 'destructive',
}

const normalizeStatus = (status: string): string => status.trim().toLowerCase().replace(/\s+/g, '_')

const titleizeStatus = (status: string): string => {
  return normalizeStatus(status)
    .split('_')
    .map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`)
    .join(' ')
}

export const getStatusLabel = (status: string): string => {
  const normalized = normalizeStatus(status)

  return statusLabels[normalized] ?? titleizeStatus(status)
}

export const getStatusBadgeVariant = (status: string): BadgeVariant => {
  return statusVariants[normalizeStatus(status)] ?? 'outline'
}
