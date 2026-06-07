<script setup lang="ts">
import type { BadgeVariants } from '@/shared/components/ui/badge'
import type { Component } from 'vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Skeleton } from '@/shared/components/ui/skeleton'
import { formatDisplayDate } from '@/shared/utils/date'
import { useAdminOperationsStore } from '@/admin/operations/operations-store'
import type {
  PlatformEvent,
  PlatformHealthSection,
  PlatformHealthStatus,
} from '@/admin/operations/types'
import {
  Activity,
  AlertTriangle,
  BarChart3,
  CalendarClock,
  CheckCircle2,
  Database,
  HardDrive,
  Mail,
  RefreshCw,
  ServerCrash,
  UploadCloud,
} from 'lucide-vue-next'
import { computed, onMounted } from 'vue'

type BadgeVariant = NonNullable<BadgeVariants['variant']>

const operationsStore = useAdminOperationsStore()
const operations = computed(() => operationsStore.operations)

const sectionIcons: Record<string, Component> = {
  analytics: BarChart3,
  errors: ServerCrash,
  mail: Mail,
  queue: Activity,
  schedule: CalendarClock,
  storage: HardDrive,
}

const sortedSections = computed(() => {
  const order: Record<PlatformHealthStatus, number> = {
    critical: 0,
    warning: 1,
    healthy: 2,
  }

  return Object.values(operations.value?.sections ?? {}).sort((a, b) => {
    return order[a.status] - order[b.status] || a.label.localeCompare(b.label)
  })
})

const attentionSections = computed(() => {
  return sortedSections.value.filter((section) => section.status !== 'healthy')
})

const statusVariant = (status: PlatformHealthStatus): BadgeVariant => {
  if (status === 'healthy') return 'success'
  if (status === 'critical') return 'destructive'

  return 'warning'
}

const statusLabel = (status: PlatformHealthStatus): string => {
  if (status === 'healthy') return 'Healthy'
  if (status === 'critical') return 'Critical'

  return 'Review'
}

const iconFor = (section: PlatformHealthSection): Component =>
  sectionIcons[section.key] ?? CheckCircle2

const metricLabel = (key: string): string => {
  return key.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase())
}

const metricValue = (value: string | number | boolean | null | undefined): string => {
  if (value === null || value === undefined || value === '') return 'Not set'
  if (typeof value === 'boolean') return value ? 'Enabled' : 'Disabled'

  return String(value)
}

const formatDate = (value: string | null): string => {
  return formatDisplayDate(value, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const eventTone = (event: PlatformEvent): BadgeVariant => {
  if (event.type === 'failed_job') return 'destructive'
  if (event.type === 'settings') return 'info'

  return 'outline'
}

const refresh = (): void => {
  operationsStore.index()
}

onMounted(refresh)
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-16">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-foreground">Platform Operations</h2>
          <p class="mt-1 text-sm text-muted-foreground">
            Queue, mail, storage, analytics, schedule, error, and event visibility for
            administrators.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Badge v-if="operations" :variant="statusVariant(operations.status)" class="h-8 px-3">
            {{ statusLabel(operations.status) }}
          </Badge>
          <Badge v-if="attentionSections.length" variant="warning" class="h-8 px-3">
            {{ attentionSections.length }} need attention
          </Badge>
          <Button
            variant="outline_default"
            size="sm"
            :disabled="operationsStore.loading"
            @click="refresh"
          >
            <RefreshCw class="size-4" :class="{ 'animate-spin': operationsStore.loading }" />
            Refresh
          </Button>
        </div>
      </div>

      <div v-if="operationsStore.loading && !operations" class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <Skeleton v-for="index in 6" :key="index" class="h-40 rounded border" />
        </div>
        <Skeleton class="h-72 rounded border" />
      </div>

      <template v-else-if="operations">
        <section
          class="mb-4 rounded border p-4"
          :class="
            operations.status === 'critical'
              ? 'border-destructive/40 bg-destructive/10'
              : operations.status === 'warning'
                ? 'border-amber-300 bg-amber-50'
                : 'border-emerald-200 bg-emerald-50'
          "
        >
          <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
              <h3 class="text-base font-semibold">{{ operations.summary }}</h3>
              <p class="mt-1 text-sm opacity-80">
                Generated {{ formatDate(operations.generated_at) }}
              </p>
            </div>
            <Badge :variant="statusVariant(operations.status)">
              {{ statusLabel(operations.status) }}
            </Badge>
          </div>
        </section>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <Card v-for="section in sortedSections" :key="section.key" class="gap-4 py-4">
            <CardHeader class="flex-row items-start justify-between px-4 pb-0">
              <div class="min-w-0">
                <CardTitle class="text-sm text-muted-foreground">{{ section.label }}</CardTitle>
                <p class="mt-1 text-2xl font-semibold tracking-tight">{{ section.value }}</p>
              </div>
              <div class="flex size-10 items-center justify-center rounded border bg-muted/30">
                <component :is="iconFor(section)" class="size-5" />
              </div>
            </CardHeader>
            <CardContent class="space-y-3 px-4">
              <Badge :variant="statusVariant(section.status)">
                {{ statusLabel(section.status) }}
              </Badge>
              <p class="text-sm text-muted-foreground">{{ section.description }}</p>
              <div v-if="section.metrics" class="grid gap-2">
                <div
                  v-for="(value, key) in section.metrics"
                  :key="key"
                  class="flex items-center justify-between gap-3 rounded border bg-background px-3 py-2 text-sm"
                >
                  <span class="text-muted-foreground">{{ metricLabel(String(key)) }}</span>
                  <span class="max-w-40 truncate font-medium">{{ metricValue(value) }}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(24rem,0.85fr)]">
          <main class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center gap-2">
                  <AlertTriangle class="size-4 text-amber-600" />
                  <h3 class="text-base font-semibold">Failed Jobs</h3>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  Failed queue jobs are surfaced first because they usually require intervention.
                </p>
              </div>

              <div v-if="operations.failed_jobs.length" class="divide-y">
                <div
                  v-for="job in operations.failed_jobs"
                  :key="job.id"
                  class="grid gap-3 p-4 lg:grid-cols-[minmax(0,1fr)_12rem]"
                >
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <Badge variant="destructive">{{ job.queue }}</Badge>
                      <p class="truncate text-sm font-semibold">{{ job.name }}</p>
                    </div>
                    <p class="mt-2 line-clamp-2 text-sm text-muted-foreground">
                      {{ job.exception }}
                    </p>
                  </div>
                  <div class="text-sm text-muted-foreground lg:text-right">
                    <p class="font-medium text-foreground">{{ job.connection }}</p>
                    <p class="mt-1">{{ formatDate(job.failed_at) }}</p>
                  </div>
                </div>
              </div>

              <Empty v-else class="m-4 min-h-48 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No failed jobs</EmptyTitle>
                  <EmptyDescription>The failed job table is clear.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center gap-2">
                  <ServerCrash class="size-4 text-destructive" />
                  <h3 class="text-base font-semibold">Error Summaries</h3>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  Recent Laravel log errors and warnings from the application log.
                </p>
              </div>

              <div v-if="operations.sections.errors.items?.length" class="divide-y">
                <div
                  v-for="error in operations.sections.errors.items"
                  :key="`${error.level}-${error.occurred_at}-${error.message}`"
                  class="p-4"
                >
                  <div class="flex flex-wrap items-center gap-2">
                    <Badge :variant="error.is_recent ? 'destructive' : 'warning'">
                      {{ error.level }}
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                      {{ formatDate(error.occurred_at) }}
                    </span>
                  </div>
                  <p class="mt-2 break-words text-sm">{{ error.message }}</p>
                </div>
              </div>

              <Empty v-else class="m-4 min-h-48 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No error summaries</EmptyTitle>
                  <EmptyDescription>No recent Laravel log errors were found.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center gap-2">
                  <CalendarClock class="size-4 text-primary" />
                  <h3 class="text-base font-semibold">Scheduled Tasks</h3>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  Commands registered with Laravel's scheduler.
                </p>
              </div>

              <div v-if="operations.sections.schedule.tasks?.length" class="divide-y">
                <div
                  v-for="task in operations.sections.schedule.tasks"
                  :key="`${task.command}-${task.expression}`"
                  class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_9rem]"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ task.command }}</p>
                    <p v-if="task.description" class="mt-1 text-sm text-muted-foreground">
                      {{ task.description }}
                    </p>
                  </div>
                  <code class="rounded bg-muted px-2 py-1 text-xs md:text-right">
                    {{ task.expression }}
                  </code>
                </div>
              </div>

              <Empty v-else class="m-4 min-h-40 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No scheduled tasks</EmptyTitle>
                  <EmptyDescription>No scheduler commands are registered.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>
          </main>

          <aside class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center gap-2">
                  <Database class="size-4 text-primary" />
                  <h3 class="text-base font-semibold">Recent Platform Events</h3>
                </div>
              </div>

              <div v-if="operations.recent_platform_events.length" class="divide-y">
                <div
                  v-for="event in operations.recent_platform_events"
                  :key="`${event.type}-${event.occurred_at}-${event.description}`"
                  class="p-4"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <p class="truncate text-sm font-semibold">{{ event.label }}</p>
                      <p class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                        {{ event.description }}
                      </p>
                    </div>
                    <Badge :variant="eventTone(event)">{{ event.type.replace(/_/g, ' ') }}</Badge>
                  </div>
                  <p class="mt-2 text-xs text-muted-foreground">
                    {{ formatDate(event.occurred_at) }}
                    <span v-if="event.actor"> by {{ event.actor }}</span>
                  </p>
                </div>
              </div>

              <Empty v-else class="m-4 min-h-48 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No platform events</EmptyTitle>
                  <EmptyDescription>Operational events will appear here.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center gap-2">
                  <UploadCloud class="size-4 text-primary" />
                  <h3 class="text-base font-semibold">Upload Limits</h3>
                </div>
              </div>
              <div class="space-y-3 p-4 text-sm">
                <div class="flex items-center justify-between gap-3 rounded border p-3">
                  <span class="text-muted-foreground">Maximum upload</span>
                  <span class="font-medium">
                    {{ metricValue(operations.sections.storage.metrics?.upload_limit) }}
                  </span>
                </div>
                <div class="rounded border p-3">
                  <p class="text-muted-foreground">Allowed file types</p>
                  <p class="mt-1 break-words font-medium">
                    {{ metricValue(operations.sections.storage.metrics?.allowed_file_types) }}
                  </p>
                </div>
              </div>
            </section>
          </aside>
        </div>
      </template>

      <Empty v-else class="min-h-[420px] border bg-muted/20">
        <EmptyHeader>
          <EmptyTitle>Operations unavailable</EmptyTitle>
          <EmptyDescription>Refresh the page to load platform health data.</EmptyDescription>
        </EmptyHeader>
      </Empty>
    </div>
  </div>
</template>
