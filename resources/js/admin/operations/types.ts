export type PlatformHealthStatus = 'healthy' | 'warning' | 'critical'

export interface PlatformHealthSection {
  key: string
  label: string
  status: PlatformHealthStatus
  value: string
  description: string
  metrics?: Record<string, string | number | boolean | null>
  tasks?: PlatformScheduledTask[]
  items?: PlatformErrorSummary[]
}

export interface PlatformScheduledTask {
  command: string
  expression: string
  description: string
}

export interface PlatformErrorSummary {
  level: string
  message: string
  occurred_at: string | null
  is_recent: boolean
}

export interface PlatformFailedJob {
  id: string
  connection: string
  queue: string
  name: string
  exception: string
  failed_at: string | null
}

export interface PlatformEvent {
  type: string
  label: string
  description: string
  actor: string | null
  occurred_at: string | null
}

export interface PlatformOperations {
  status: PlatformHealthStatus
  summary: string
  sections: {
    queue: PlatformHealthSection
    mail: PlatformHealthSection
    storage: PlatformHealthSection
    analytics: PlatformHealthSection
    schedule: PlatformHealthSection
    errors: PlatformHealthSection
  }
  failed_jobs: PlatformFailedJob[]
  recent_platform_events: PlatformEvent[]
  generated_at: string
}
