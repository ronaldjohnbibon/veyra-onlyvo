<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Skeleton } from '@/shared/components/ui/skeleton'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useTenantDashboardStore } from '@/tenant/dashboard/dashboard-store'
import type { TenantDashboardChecklistItem } from '@/tenant/dashboard/types'
import {
  ArrowRight,
  BarChart3,
  CheckCircle2,
  ExternalLink,
  FileText,
  Globe2,
  Inbox,
  MousePointerClick,
  Palette,
  RadioTower,
  Send,
  Sparkles,
} from 'lucide-vue-next'
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'

const dashboardStore = useTenantDashboardStore()
const dashboard = computed(() => dashboardStore.dashboard)

const completedChecklist = computed(() => {
  return dashboard.value?.launch_checklist.filter((item) => item.completed).length ?? 0
})

const checklistTotal = computed(() => dashboard.value?.launch_checklist.length ?? 0)

const checklistPercent = computed(() => {
  if (!checklistTotal.value) return 0

  return Math.round((completedChecklist.value / checklistTotal.value) * 100)
})

const nextChecklistItem = computed<TenantDashboardChecklistItem | null>(() => {
  return (
    dashboard.value?.launch_checklist.find(
      (item) => !item.completed && checklistItemEnabled(item)
    ) ?? null
  )
})

const metricCards = computed(() => {
  const metrics = dashboard.value?.metrics
  const modules = dashboard.value?.module_status

  return [
    {
      label: 'Recent Visits',
      value: metrics?.recent_visits ?? 0,
      detail:
        modules?.analytics === false ? 'Analytics disabled' : `${metrics?.today_visits ?? 0} today`,
      icon: BarChart3,
    },
    {
      label: 'CTA Activity',
      value: metrics?.recent_cta_events ?? 0,
      detail:
        modules?.analytics === false
          ? 'Analytics disabled'
          : `${metrics?.today_cta_events ?? 0} today`,
      icon: MousePointerClick,
    },
    {
      label: 'New Leads',
      value: metrics?.new_leads ?? 0,
      detail:
        modules?.cta_forms === false ? 'Forms disabled' : `${metrics?.recent_leads ?? 0} this week`,
      icon: Inbox,
    },
    {
      label: 'Pending Requests',
      value: metrics?.pending_design_requests ?? 0,
      detail: modules?.design_requests === false ? 'Requests disabled' : 'Design work in progress',
      icon: Palette,
    },
  ]
})

const formatNumber = (value: number): string => new Intl.NumberFormat().format(value)

const labelFor = (value: string): string => {
  return value
    .replace(/_/g, ' ')
    .replace(/\w\S*/g, (word) => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
}

const checklistItemEnabled = (item: TenantDashboardChecklistItem): boolean => {
  const modules = dashboard.value?.module_status

  if (!modules) return false
  if (item.key === 'publish_site') return modules.templates
  if (item.key === 'first_post') return modules.posts

  return true
}

onMounted(() => {
  dashboardStore.index()
})
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-24">
      <template v-if="dashboard">
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
          <div>
            <div class="flex flex-wrap items-center gap-2">
              <h2 class="text-2xl font-semibold tracking-tight text-foreground">
                {{ dashboard.site.name }}
              </h2>
              <Badge :variant="dashboard.site.status === 'live' ? 'success' : 'warning'">
                Site {{ dashboard.site.status === 'live' ? 'Live' : 'Draft' }}
              </Badge>
              <Badge :variant="dashboard.site.tracking_enabled ? 'info' : 'outline'">
                Tracking {{ dashboard.site.tracking_enabled ? 'On' : 'Off' }}
              </Badge>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">
              Launch, publish, and grow your tenant website from one workspace.
            </p>
          </div>

          <div class="flex flex-wrap gap-2">
            <Button v-if="dashboard.site.public_url" as-child variant="navigate" size="sm">
              <a :href="dashboard.site.public_url" target="_blank" rel="noreferrer">
                <ExternalLink class="size-4" />
                Preview Site
              </a>
            </Button>
            <Button v-if="dashboard.module_status.templates" as-child variant="update" size="sm">
              <RouterLink to="/templates">
                <Sparkles class="size-4" />
                Edit Website
              </RouterLink>
            </Button>
            <Button v-if="dashboard.module_status.posts" as-child variant="create" size="sm">
              <RouterLink to="/posts">
                <FileText class="size-4" />
                New Post
              </RouterLink>
            </Button>
          </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <section class="space-y-4">
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
              <Card v-for="card in metricCards" :key="card.label">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle class="text-sm font-medium text-muted-foreground">
                    {{ card.label }}
                  </CardTitle>
                  <component :is="card.icon" class="size-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div class="text-2xl font-semibold">{{ formatNumber(card.value) }}</div>
                  <p class="mt-1 text-xs text-muted-foreground">{{ card.detail }}</p>
                </CardContent>
              </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
              <Card>
                <CardHeader class="flex flex-row items-start justify-between gap-3">
                  <div>
                    <CardTitle class="text-base">Website</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Published/default site status and public access.
                    </p>
                  </div>
                  <Globe2 class="size-5 text-primary" />
                </CardHeader>
                <CardContent>
                  <div v-if="dashboard.default_template" class="space-y-4">
                    <div class="rounded border bg-muted/30 p-3">
                      <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                          <p class="truncate font-semibold">
                            {{ dashboard.default_template.business_name }}
                          </p>
                          <p class="mt-1 text-sm text-muted-foreground">
                            {{
                              dashboard.default_template.website_type_name ||
                              dashboard.default_template.name
                            }}
                          </p>
                        </div>
                        <div class="flex shrink-0 flex-wrap justify-end gap-2">
                          <Badge
                            :variant="getStatusBadgeVariant(dashboard.default_template.status)"
                          >
                            {{ getStatusLabel(dashboard.default_template.status) }}
                          </Badge>
                          <Badge v-if="dashboard.default_template.is_default" variant="outline">
                            Default
                          </Badge>
                        </div>
                      </div>
                      <p class="mt-3 truncate text-xs text-muted-foreground">
                        Updated
                        {{ formatDisplayDate(dashboard.default_template.updated_at) || 'recently' }}
                      </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                      <Button as-child variant="update" size="sm">
                        <RouterLink to="/templates">Edit Site</RouterLink>
                      </Button>
                      <Button
                        v-if="dashboard.default_template.public_url"
                        as-child
                        variant="navigate"
                        size="sm"
                      >
                        <a
                          :href="dashboard.default_template.public_url"
                          target="_blank"
                          rel="noreferrer"
                        >
                          <ExternalLink class="size-4" />
                          Open
                        </a>
                      </Button>
                    </div>
                  </div>
                  <div
                    v-else-if="!dashboard.module_status.templates"
                    class="rounded border border-dashed p-6 text-center"
                  >
                    <p class="font-medium">Templates module disabled</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Website setup actions are hidden until templates are enabled.
                    </p>
                  </div>
                  <div v-else class="rounded border border-dashed p-6 text-center">
                    <p class="font-medium">No public site yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Pick a design and publish it as your default website.
                    </p>
                    <Button as-child variant="create" size="sm" class="mt-4">
                      <RouterLink to="/templates">Create Site</RouterLink>
                    </Button>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader class="flex flex-row items-start justify-between gap-3">
                  <div>
                    <CardTitle class="text-base">Next Action</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                      {{ checklistPercent }}% of launch tasks complete.
                    </p>
                  </div>
                  <CheckCircle2 class="size-5 text-primary" />
                </CardHeader>
                <CardContent class="space-y-4">
                  <div class="h-2 overflow-hidden rounded bg-muted">
                    <div
                      class="h-full rounded bg-primary transition-all"
                      :style="{ width: `${checklistPercent}%` }"
                    />
                  </div>

                  <div v-if="nextChecklistItem" class="rounded border bg-muted/30 p-3">
                    <p class="font-semibold">{{ nextChecklistItem.label }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      {{ nextChecklistItem.description }}
                    </p>
                    <Button as-child variant="navigate" size="sm" class="mt-3">
                      <RouterLink :to="nextChecklistItem.to">
                        Continue
                        <ArrowRight class="size-4" />
                      </RouterLink>
                    </Button>
                  </div>
                  <div v-else class="rounded border bg-muted/30 p-3">
                    <p class="font-semibold">Launch checklist complete</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      Keep publishing content and reviewing analytics.
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>

            <div class="grid gap-4 xl:grid-cols-2">
              <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                  <CardTitle class="text-base">Recent Posts</CardTitle>
                  <Button
                    v-if="dashboard.module_status.posts"
                    as-child
                    variant="navigate"
                    size="sm"
                  >
                    <RouterLink to="/posts">View All</RouterLink>
                  </Button>
                </CardHeader>
                <CardContent>
                  <div v-if="dashboard.recent_posts.length" class="space-y-3">
                    <div
                      v-for="post in dashboard.recent_posts"
                      :key="post.id"
                      class="flex items-center justify-between gap-3 rounded border p-3"
                    >
                      <div class="min-w-0">
                        <p class="truncate font-medium">{{ post.title }}</p>
                        <p class="mt-1 truncate text-xs text-muted-foreground">
                          {{ post.template_name || 'Website post' }}
                        </p>
                      </div>
                      <div class="flex shrink-0 items-center gap-2">
                        <Badge :variant="getStatusBadgeVariant(post.status)">
                          {{ getStatusLabel(post.status) }}
                        </Badge>
                        <Button
                          v-if="post.public_url"
                          as-child
                          variant="navigate"
                          size="sm"
                          class="h-8 w-8 p-0"
                        >
                          <a
                            :href="post.public_url"
                            target="_blank"
                            rel="noreferrer"
                            aria-label="Open post"
                          >
                            <ExternalLink class="size-4" />
                          </a>
                        </Button>
                      </div>
                    </div>
                  </div>
                  <div v-else class="rounded border border-dashed p-6 text-center">
                    <p class="font-medium">No posts yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      {{
                        dashboard.module_status.posts
                          ? 'Publish your first update, story, or announcement.'
                          : 'Posts are hidden until the posts module is enabled.'
                      }}
                    </p>
                  </div>
                </CardContent>
              </Card>

              <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                  <CardTitle class="text-base">New Leads</CardTitle>
                  <div class="flex items-center gap-2">
                    <Badge :variant="dashboard.site.cta_forms_enabled ? 'info' : 'outline'">
                      Forms {{ dashboard.site.cta_forms_enabled ? 'On' : 'Off' }}
                    </Badge>
                    <Button
                      v-if="dashboard.module_status.cta_forms"
                      as-child
                      variant="navigate"
                      size="sm"
                    >
                      <RouterLink to="/leads">Open</RouterLink>
                    </Button>
                  </div>
                </CardHeader>
                <CardContent>
                  <div v-if="dashboard.recent_leads.length" class="space-y-3">
                    <div
                      v-for="lead in dashboard.recent_leads"
                      :key="lead.id"
                      class="flex items-start justify-between gap-3 rounded border p-3"
                    >
                      <div class="min-w-0">
                        <p class="truncate font-medium">{{ lead.summary }}</p>
                        <p class="mt-1 text-xs text-muted-foreground">
                          {{ labelFor(lead.cta_type) }} - {{ lead.template_name }}
                        </p>
                      </div>
                      <Badge :variant="lead.status === 'new' ? 'success' : 'outline'">
                        {{ labelFor(lead.status) }}
                      </Badge>
                    </div>
                  </div>
                  <div v-else class="rounded border border-dashed p-6 text-center">
                    <p class="font-medium">No form submissions yet</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                      {{
                        dashboard.module_status.cta_forms
                          ? 'Leads will appear after visitors submit website forms.'
                          : 'Lead collection is hidden until forms are enabled.'
                      }}
                    </p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </section>

          <aside class="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle class="text-base">Launch Checklist</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <component
                  :is="checklistItemEnabled(item) ? RouterLink : 'div'"
                  v-for="item in dashboard.launch_checklist"
                  :key="item.key"
                  :to="checklistItemEnabled(item) ? item.to : undefined"
                  class="flex items-start gap-3 rounded border p-3 transition hover:bg-muted/60"
                  :class="{ 'opacity-60 hover:bg-transparent': !checklistItemEnabled(item) }"
                >
                  <CheckCircle2
                    class="mt-0.5 size-4 shrink-0"
                    :class="item.completed ? 'text-emerald-600' : 'text-muted-foreground'"
                  />
                  <span class="min-w-0">
                    <span class="block text-sm font-medium">{{ item.label }}</span>
                    <span class="mt-1 block text-xs leading-5 text-muted-foreground">
                      {{ item.description }}
                    </span>
                  </span>
                </component>
              </CardContent>
            </Card>

            <Card>
              <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">Design Requests</CardTitle>
                <Button
                  v-if="dashboard.module_status.design_requests"
                  as-child
                  variant="navigate"
                  size="sm"
                >
                  <RouterLink to="/design-requests">Open</RouterLink>
                </Button>
              </CardHeader>
              <CardContent>
                <div v-if="dashboard.pending_design_requests.length" class="space-y-3">
                  <div
                    v-for="request in dashboard.pending_design_requests"
                    :key="request.id"
                    class="rounded border p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <p class="min-w-0 truncate font-medium">{{ request.title }}</p>
                      <Badge :variant="getStatusBadgeVariant(request.status)">
                        {{ getStatusLabel(request.status) }}
                      </Badge>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">
                      Updated {{ formatDisplayDate(request.updated_at) || 'recently' }}
                    </p>
                  </div>
                </div>
                <div v-else class="rounded border border-dashed p-6 text-center">
                  <p class="font-medium">No pending design work</p>
                  <p class="mt-1 text-sm text-muted-foreground">
                    {{
                      dashboard.module_status.design_requests
                        ? 'Request help when you want a custom layout or polish pass.'
                        : 'Design requests are hidden until the module is enabled.'
                    }}
                  </p>
                </div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle class="text-base">Workspace Health</CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="flex items-center justify-between gap-3 text-sm">
                  <span class="flex items-center gap-2 text-muted-foreground">
                    <RadioTower class="size-4" />
                    Tracking
                  </span>
                  <Badge :variant="dashboard.site.tracking_enabled ? 'success' : 'outline'">
                    {{ dashboard.site.tracking_enabled ? 'Ready' : 'Off' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm">
                  <span class="flex items-center gap-2 text-muted-foreground">
                    <Send class="size-4" />
                    Forms
                  </span>
                  <Badge :variant="dashboard.site.cta_forms_enabled ? 'success' : 'outline'">
                    {{ dashboard.site.cta_forms_enabled ? 'Ready' : 'Off' }}
                  </Badge>
                </div>
                <div class="flex items-center justify-between gap-3 text-sm">
                  <span class="flex items-center gap-2 text-muted-foreground">
                    <FileText class="size-4" />
                    Posts
                  </span>
                  <span class="font-medium">
                    {{ dashboard.metrics.published_posts }} live -
                    {{ dashboard.metrics.draft_posts }} draft
                  </span>
                </div>
              </CardContent>
            </Card>
          </aside>
        </div>
      </template>

      <template v-else-if="dashboardStore.loading || !dashboardStore.loaded">
        <div class="mb-4 flex items-center justify-between">
          <div class="space-y-2">
            <Skeleton class="h-7 w-48" />
            <Skeleton class="h-4 w-80" />
          </div>
          <Skeleton class="h-9 w-40" />
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
          <Skeleton v-for="item in 4" :key="item" class="h-32 rounded" />
        </div>
        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <div class="grid gap-4 xl:grid-cols-2">
            <Skeleton class="h-72 rounded" />
            <Skeleton class="h-72 rounded" />
            <Skeleton class="h-80 rounded" />
            <Skeleton class="h-80 rounded" />
          </div>
          <Skeleton class="h-[34rem] rounded" />
        </div>
      </template>

      <Empty v-else class="min-h-[420px] border bg-muted/20">
        <EmptyHeader>
          <EmptyTitle>Dashboard unavailable</EmptyTitle>
          <EmptyDescription>
            {{ dashboardStore.error || 'Refresh the dashboard to load workspace data.' }}
          </EmptyDescription>
        </EmptyHeader>
        <Button variant="outline_default" size="sm" @click="dashboardStore.index"> Refresh </Button>
      </Empty>
    </div>
  </div>
</template>
