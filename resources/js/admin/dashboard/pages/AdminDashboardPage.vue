<script setup lang="ts">
import type { BadgeVariants } from '@/shared/components/ui/badge'
import type { Component } from 'vue'
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Skeleton } from '@/shared/components/ui/skeleton'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useAdminDashboardStore } from '@/admin/dashboard/dashboard-store'
import type {
  AdminDashboardHealthItem,
  AdminDashboardMetrics,
  DashboardHealthStatus,
} from '@/admin/dashboard/types'
import {
  Activity,
  BarChart3,
  Building2,
  CheckCircle2,
  CircleAlert,
  Clock,
  FileCheck2,
  Inbox,
  Mail,
  Palette,
  Plus,
  Settings,
  SlidersHorizontal,
  UploadCloud,
  Wrench,
} from 'lucide-vue-next'
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

type BadgeVariant = NonNullable<BadgeVariants['variant']>

interface MetricCard {
  key: keyof AdminDashboardMetrics
  label: string
  description: string
  icon: Component
  tone: 'default' | 'success' | 'warning' | 'neutral'
}

interface QuickAction {
  label: string
  route: Record<string, unknown>
  icon: Component
  variant: 'create' | 'navigate' | 'update' | 'publish'
}

const dashboardStore = useAdminDashboardStore()

const dashboard = computed(() => dashboardStore.dashboard)

const metricCards: MetricCard[] = [
  {
    key: 'total_tenants',
    label: 'Total tenants',
    description: 'All tenant workspaces',
    icon: Building2,
    tone: 'default',
  },
  {
    key: 'active_tenants',
    label: 'Active tenants',
    description: 'Currently enabled',
    icon: CheckCircle2,
    tone: 'success',
  },
  {
    key: 'inactive_tenants',
    label: 'Inactive tenants',
    description: 'Disabled workspaces',
    icon: CircleAlert,
    tone: 'neutral',
  },
  {
    key: 'new_tenants_this_week',
    label: 'New this week',
    description: 'Fresh tenant accounts',
    icon: Clock,
    tone: 'default',
  },
  {
    key: 'pending_design_requests',
    label: 'Design queue',
    description: 'Needs admin attention',
    icon: Palette,
    tone: 'warning',
  },
  {
    key: 'new_leads',
    label: 'New submissions',
    description: 'Unprocessed CTA leads',
    icon: Inbox,
    tone: 'warning',
  },
  {
    key: 'published_templates',
    label: 'Published sites',
    description: 'Live tenant templates',
    icon: FileCheck2,
    tone: 'success',
  },
]

const quickActions: QuickAction[] = [
  {
    label: 'Create tenant',
    route: { name: 'admin.tenants.index', query: { action: 'create' } },
    icon: Plus,
    variant: 'create',
  },
  {
    label: 'Review design requests',
    route: { name: 'admin.design-requests.index' },
    icon: Palette,
    variant: 'navigate',
  },
  {
    label: 'Manage templates',
    route: { name: 'admin.templates.index' },
    icon: FileCheck2,
    variant: 'publish',
  },
  {
    label: 'Open system settings',
    route: { name: 'admin.system-settings.index' },
    icon: SlidersHorizontal,
    variant: 'update',
  },
]

const attentionCount = computed(() => {
  const work = dashboard.value?.pending_work

  if (!work) return 0

  return (
    work.pending_design_requests +
    work.under_review_requests +
    work.changes_requested +
    work.new_leads
  )
})

const healthIcon = (item: AdminDashboardHealthItem): Component => {
  const icons: Record<string, Component> = {
    analytics: BarChart3,
    mail: Mail,
    maintenance: Wrench,
    queue: Activity,
    storage: UploadCloud,
  }

  return icons[item.key] ?? CheckCircle2
}

const healthStatusLabel = (status: DashboardHealthStatus): string => {
  if (status === 'healthy') return 'Healthy'
  if (status === 'critical') return 'Critical'

  return 'Review'
}

const healthStatusVariant = (status: DashboardHealthStatus): BadgeVariant => {
  if (status === 'healthy') return 'success'
  if (status === 'critical') return 'destructive'

  return 'warning'
}

const metricToneClass = (tone: MetricCard['tone']): string => {
  const classes: Record<MetricCard['tone'], string> = {
    default: 'bg-sky-50 text-sky-700 border-sky-100',
    neutral: 'bg-slate-50 text-slate-700 border-slate-100',
    success: 'bg-emerald-50 text-emerald-700 border-emerald-100',
    warning: 'bg-amber-50 text-amber-700 border-amber-100',
  }

  return classes[tone]
}

const formatNumber = (value?: number): string => {
  return new Intl.NumberFormat().format(value ?? 0)
}

const formatActivityDate = (value?: string | null): string => {
  if (!value || Number.isNaN(new Date(value).getTime())) {
    return 'Not available'
  }

  return formatDisplayDate(value, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const formatCtaType = (value?: string | null): string => {
  const normalized = value?.replace(/_/g, ' ').trim()

  return normalized || 'Submission'
}

onMounted(() => {
  dashboardStore.show()
})
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-16">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <h2 class="text-2xl font-semibold tracking-tight text-foreground">
            Platform Command Center
          </h2>
          <p class="mt-1 text-sm text-muted-foreground">
            Operational status, tenant movement, and work waiting across Onlyvo.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <Badge
            v-if="dashboard"
            :variant="healthStatusVariant(dashboard.platform_health.status)"
            class="h-8 px-3"
          >
            {{ healthStatusLabel(dashboard.platform_health.status) }}
          </Badge>
          <Badge v-if="attentionCount" variant="warning" class="h-8 px-3">
            {{ attentionCount }} need attention
          </Badge>
          <Button
            variant="outline_default"
            size="sm"
            :disabled="dashboardStore.loading"
            @click="dashboardStore.show"
          >
            <Activity class="size-4" />
            Refresh
          </Button>
        </div>
      </div>

      <div v-if="dashboardStore.loading && !dashboard" class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <Skeleton v-for="index in 8" :key="index" class="h-32 rounded border" />
        </div>
        <Skeleton class="h-80 rounded border" />
      </div>

      <template v-else-if="dashboard">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <Card v-for="metric in metricCards" :key="metric.key" class="gap-4 py-4">
            <CardHeader class="flex-row items-start justify-between px-4 pb-0">
              <div>
                <CardTitle class="text-sm text-muted-foreground">{{ metric.label }}</CardTitle>
                <p class="mt-1 text-xs text-muted-foreground">{{ metric.description }}</p>
              </div>
              <div
                class="flex size-9 items-center justify-center rounded border"
                :class="metricToneClass(metric.tone)"
              >
                <component :is="metric.icon" class="size-4" />
              </div>
            </CardHeader>
            <CardContent class="px-4">
              <p class="text-3xl font-semibold tracking-tight">
                {{ formatNumber(dashboard.metrics[metric.key]) }}
              </p>
            </CardContent>
          </Card>

          <Card class="gap-4 py-4 md:col-span-2 xl:col-span-1">
            <CardHeader class="px-4 pb-0">
              <CardTitle class="text-sm text-muted-foreground">Platform health</CardTitle>
              <p class="mt-1 text-xs text-muted-foreground">
                {{ dashboard.platform_health.summary }}
              </p>
            </CardHeader>
            <CardContent class="px-4">
              <Badge :variant="healthStatusVariant(dashboard.platform_health.status)">
                {{ healthStatusLabel(dashboard.platform_health.status) }}
              </Badge>
            </CardContent>
          </Card>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_minmax(22rem,0.8fr)]">
          <main class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                  <div>
                    <h3 class="text-base font-semibold">Attention Queue</h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Requests and submissions that can change customer outcomes.
                    </p>
                  </div>
                  <Button as-child variant="navigate" size="sm">
                    <RouterLink :to="{ name: 'admin.design-requests.index' }">
                      <Palette class="size-4" />
                      Open Queue
                    </RouterLink>
                  </Button>
                </div>
              </div>

              <div class="grid gap-4 p-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
                <div class="space-y-3">
                  <div class="rounded border bg-muted/20 p-4">
                    <p class="text-sm font-medium text-muted-foreground">Pending design requests</p>
                    <p class="mt-2 text-3xl font-semibold">
                      {{ dashboard.pending_work.pending_design_requests }}
                    </p>
                  </div>
                  <div class="rounded border bg-muted/20 p-4">
                    <p class="text-sm font-medium text-muted-foreground">Under review</p>
                    <p class="mt-2 text-3xl font-semibold">
                      {{ dashboard.pending_work.under_review_requests }}
                    </p>
                  </div>
                  <div class="rounded border bg-muted/20 p-4">
                    <p class="text-sm font-medium text-muted-foreground">Changes requested</p>
                    <p class="mt-2 text-3xl font-semibold">
                      {{ dashboard.pending_work.changes_requested }}
                    </p>
                  </div>
                  <div class="rounded border bg-muted/20 p-4">
                    <p class="text-sm font-medium text-muted-foreground">New submissions</p>
                    <p class="mt-2 text-3xl font-semibold">
                      {{ dashboard.pending_work.new_leads }}
                    </p>
                  </div>
                </div>

                <div class="space-y-4">
                  <div class="rounded border p-4">
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <p class="text-sm font-semibold">Oldest pending request</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                          The longest-waiting design task in the admin queue.
                        </p>
                      </div>
                      <Badge
                        v-if="dashboard.pending_work.oldest_pending_request"
                        :variant="
                          getStatusBadgeVariant(
                            dashboard.pending_work.oldest_pending_request.status
                          )
                        "
                      >
                        {{ getStatusLabel(dashboard.pending_work.oldest_pending_request.status) }}
                      </Badge>
                    </div>

                    <div v-if="dashboard.pending_work.oldest_pending_request" class="mt-4">
                      <p class="font-medium">
                        {{ dashboard.pending_work.oldest_pending_request.title }}
                      </p>
                      <p class="mt-1 text-sm text-muted-foreground">
                        {{
                          dashboard.pending_work.oldest_pending_request.tenant_name ||
                          'Unknown tenant'
                        }}
                        -
                        {{
                          formatActivityDate(
                            dashboard.pending_work.oldest_pending_request.created_at
                          )
                        }}
                      </p>
                    </div>

                    <Empty v-else class="mt-4 min-h-32 border bg-muted/20">
                      <EmptyHeader>
                        <EmptyTitle>No pending design requests</EmptyTitle>
                        <EmptyDescription>The design queue is clear.</EmptyDescription>
                      </EmptyHeader>
                    </Empty>
                  </div>

                  <div class="grid gap-4 lg:grid-cols-2">
                    <div class="rounded border">
                      <div class="border-b px-4 py-3">
                        <p class="text-sm font-semibold">Recent design requests</p>
                      </div>
                      <div
                        v-if="dashboard.pending_work.recent_design_requests.length"
                        class="divide-y"
                      >
                        <div
                          v-for="request in dashboard.pending_work.recent_design_requests"
                          :key="request.id"
                          class="flex items-start justify-between gap-3 p-4"
                        >
                          <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ request.title }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">
                              {{ request.tenant_name || 'Unknown tenant' }}
                            </p>
                          </div>
                          <Badge :variant="getStatusBadgeVariant(request.status)">
                            {{ getStatusLabel(request.status) }}
                          </Badge>
                        </div>
                      </div>
                      <Empty v-else class="min-h-48 border-0">
                        <EmptyHeader>
                          <EmptyTitle>No requests yet</EmptyTitle>
                          <EmptyDescription
                            >Tenant design requests will appear here.</EmptyDescription
                          >
                        </EmptyHeader>
                      </Empty>
                    </div>

                    <div class="rounded border">
                      <div class="border-b px-4 py-3">
                        <p class="text-sm font-semibold">Recent submissions</p>
                      </div>
                      <div v-if="dashboard.pending_work.recent_leads.length" class="divide-y">
                        <div
                          v-for="lead in dashboard.pending_work.recent_leads"
                          :key="lead.id"
                          class="flex items-start justify-between gap-3 p-4"
                        >
                          <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                              {{ formatCtaType(lead.cta_type) }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                              {{ lead.tenant_name || 'Unknown tenant' }}
                            </p>
                          </div>
                          <Badge :variant="getStatusBadgeVariant(lead.status)">
                            {{ getStatusLabel(lead.status) }}
                          </Badge>
                        </div>
                      </div>
                      <Empty v-else class="min-h-48 border-0">
                        <EmptyHeader>
                          <EmptyTitle>No submissions yet</EmptyTitle>
                          <EmptyDescription>New CTA submissions will appear here.</EmptyDescription>
                        </EmptyHeader>
                      </Empty>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Recent Tenant Activity</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  Recently changed workspaces and setup status.
                </p>
              </div>

              <div v-if="dashboard.recent_tenant_activity.length" class="divide-y">
                <div
                  v-for="tenant in dashboard.recent_tenant_activity"
                  :key="tenant.id"
                  class="grid gap-3 p-4 md:grid-cols-[minmax(0,1fr)_auto_auto]"
                >
                  <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ tenant.name }}</p>
                    <p class="mt-1 truncate font-mono text-xs text-muted-foreground">
                      {{ tenant.subdomain }}
                    </p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <Badge :variant="getStatusBadgeVariant(tenant.status)">
                      {{ getStatusLabel(tenant.status) }}
                    </Badge>
                    <Badge variant="outline">{{ tenant.users_count }} users</Badge>
                    <Badge variant="outline">{{ tenant.templates_count }} sites</Badge>
                  </div>
                  <div class="text-sm text-muted-foreground md:text-right">
                    <p class="font-medium text-foreground">{{ tenant.activity_label }}</p>
                    <p class="mt-1 text-xs">{{ formatActivityDate(tenant.updated_at) }}</p>
                  </div>
                </div>
              </div>

              <Empty v-else class="m-4 min-h-56 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No tenant activity</EmptyTitle>
                  <EmptyDescription
                    >Create the first tenant to start platform activity.</EmptyDescription
                  >
                </EmptyHeader>
              </Empty>
            </section>
          </main>

          <aside class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Quick Actions</h3>
              </div>
              <div class="grid gap-2 p-4">
                <Button
                  v-for="action in quickActions"
                  :key="action.label"
                  as-child
                  :variant="action.variant"
                  class="justify-start"
                >
                  <RouterLink :to="action.route">
                    <component :is="action.icon" class="size-4" />
                    {{ action.label }}
                  </RouterLink>
                </Button>
              </div>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <div class="flex items-center justify-between gap-3">
                  <h3 class="text-base font-semibold">Platform Health</h3>
                  <Badge :variant="healthStatusVariant(dashboard.platform_health.status)">
                    {{ healthStatusLabel(dashboard.platform_health.status) }}
                  </Badge>
                </div>
                <p class="mt-1 text-sm text-muted-foreground">
                  {{ dashboard.platform_health.summary }}
                </p>
              </div>

              <div class="divide-y">
                <div
                  v-for="item in dashboard.platform_health.items"
                  :key="item.key"
                  class="flex gap-3 p-4"
                >
                  <div
                    class="flex size-9 shrink-0 items-center justify-center rounded border bg-muted/30"
                  >
                    <component :is="healthIcon(item)" class="size-4" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                      <p class="text-sm font-semibold">{{ item.label }}</p>
                      <Badge :variant="healthStatusVariant(item.status)">
                        {{ item.value }}
                      </Badge>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ item.description }}</p>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <Settings class="size-4 text-primary" />
                <h3 class="text-sm font-semibold">Last refreshed</h3>
              </div>
              <p class="mt-2 text-sm text-muted-foreground">
                {{ formatActivityDate(dashboard.generated_at) }}
              </p>
            </section>
          </aside>
        </div>
      </template>

      <Empty v-else class="min-h-[420px] border bg-muted/20">
        <EmptyHeader>
          <EmptyTitle>Dashboard unavailable</EmptyTitle>
          <EmptyDescription>Refresh the page to load platform operations data.</EmptyDescription>
        </EmptyHeader>
      </Empty>
    </div>
  </div>
</template>
