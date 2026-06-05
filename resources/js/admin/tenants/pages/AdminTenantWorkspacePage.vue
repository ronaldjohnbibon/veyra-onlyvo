<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/shared/components/ui/empty'
import { Skeleton } from '@/shared/components/ui/skeleton'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/shared/components/ui/table'
import { formatDisplayDate } from '@/shared/utils/date'
import { getStatusBadgeVariant, getStatusLabel } from '@/shared/utils/status'
import { useAdminTenantStore } from '@/admin/tenants/tenant-store'
import type { TenantWorkspaceRecentItem } from '@/admin/tenants/types'
import {
  Activity,
  ArrowLeft,
  BarChart3,
  Building2,
  ExternalLink,
  FileText,
  Globe2,
  Inbox,
  KeyRound,
  LayoutTemplate,
  Mail,
  Palette,
  Settings,
  ShieldCheck,
  UserRound,
  Users,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'

interface SummaryCard {
  label: string
  value: string | number
  description: string
  icon: Component
}

const tenantStore = useAdminTenantStore()
const route = useRoute()

const workspace = computed(() => tenantStore.workspace)
const tenantId = computed(() => String(route.params.tenantId ?? ''))
const tenantBaseUrl = computed(() => {
  const subdomain = workspace.value?.tenant.subdomain

  if (!subdomain) return ''

  const { protocol, hostname, port } = window.location
  const localHosts = ['localhost', '127.0.0.1']
  const baseHost = localHosts.includes(hostname)
    ? 'localhost'
    : hostname.endsWith('.localhost')
      ? 'localhost'
      : hostname.split('.').slice(1).join('.') || hostname
  const targetPort = port ? `:${port}` : ''

  return `${protocol}//${subdomain}.${baseHost}${targetPort}`
})
const workspaceUrl = computed(() => {
  return tenantBaseUrl.value ? `${tenantBaseUrl.value}/dashboard` : ''
})
const publicSiteUrl = computed(() => {
  const slug = workspace.value?.published_template?.slug

  if (!tenantBaseUrl.value || !slug) return ''

  return `${tenantBaseUrl.value}/${slug.replace(/^\/+/, '')}`
})

const summaryCards = computed<SummaryCard[]>(() => {
  const data = workspace.value

  if (!data) return []

  return [
    {
      label: 'Users',
      value: data.usage_metrics.users,
      description: 'Active tenant accounts',
      icon: Users,
    },
    {
      label: 'Published sites',
      value: data.usage_metrics.published_templates,
      description: `${data.usage_metrics.templates} total templates`,
      icon: LayoutTemplate,
    },
    {
      label: 'New leads',
      value: data.leads_summary.new,
      description: `${data.leads_summary.total} total submissions`,
      icon: Inbox,
    },
    {
      label: 'Open requests',
      value:
        data.design_request_summary.pending +
        data.design_request_summary.under_review +
        data.design_request_summary.changes_requested,
      description: `${data.design_request_summary.total} total design requests`,
      icon: Palette,
    },
    {
      label: 'Visits',
      value: data.usage_metrics.visits_last_7_days,
      description: `${data.usage_metrics.visits_today} today`,
      icon: BarChart3,
    },
    {
      label: 'Storage',
      value: data.usage_metrics.storage_label,
      description: 'Tracked uploaded request files',
      icon: FileText,
    },
  ]
})

const loadWorkspace = async (): Promise<void> => {
  if (!tenantId.value) return

  await tenantStore.showWorkspace(tenantId.value)
}

const formatDate = (value?: string | null): string => {
  return formatDisplayDate(value, {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  })
}

const formatDateTime = (value?: string | null): string => {
  return formatDisplayDate(value, {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  })
}

const valueLabel = (value: unknown): string => {
  if (value === true) return 'Enabled'
  if (value === false) return 'Disabled'
  if (value === null || value === undefined || value === '') return 'Not set'
  if (typeof value === 'object') return JSON.stringify(value)

  return String(value)
}

const itemTitle = (item: TenantWorkspaceRecentItem): string => {
  return item.title || item.summary || item.cta_type?.replace(/_/g, ' ') || 'Recent item'
}

onMounted(loadWorkspace)

watch(tenantId, loadWorkspace)
</script>

<template>
  <div class="flex flex-1 flex-col p-4">
    <div class="min-h-screen pb-16">
      <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div>
          <Button as-child variant="ghost" size="sm" class="mb-2 px-0">
            <RouterLink :to="{ name: 'admin.tenants.index' }">
              <ArrowLeft class="size-4" />
              Tenants
            </RouterLink>
          </Button>

          <h2 class="text-2xl font-semibold tracking-tight text-foreground">
            {{ workspace?.tenant.name || 'Tenant Workspace' }}
          </h2>
          <p class="mt-1 text-sm text-muted-foreground">
            Operational profile, site health, usage, activity, and audit history.
          </p>
        </div>

        <div v-if="workspace" class="flex flex-wrap items-center gap-2">
          <Badge :variant="getStatusBadgeVariant(workspace.tenant.status)" class="h-8 px-3">
            {{ getStatusLabel(workspace.tenant.status) }}
          </Badge>

          <Button
            v-if="workspace.actions.open_workspace.available && workspaceUrl"
            as-child
            variant="navigate"
            size="sm"
          >
            <a :href="workspaceUrl" target="_blank" rel="noreferrer">
              <ExternalLink class="size-4" />
              Open Workspace
            </a>
          </Button>

          <Button variant="cancel" size="sm" disabled>
            <KeyRound class="size-4" />
            Impersonation
          </Button>
        </div>
      </div>

      <div v-if="tenantStore.loading && !workspace" class="space-y-4">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <Skeleton v-for="index in 6" :key="index" class="h-32 rounded border" />
        </div>
        <Skeleton class="h-96 rounded border" />
      </div>

      <template v-else-if="workspace">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
          <Card v-for="card in summaryCards" :key="card.label" class="gap-4 py-4">
            <CardHeader class="flex-row items-start justify-between px-4 pb-0">
              <div>
                <CardTitle class="text-sm text-muted-foreground">{{ card.label }}</CardTitle>
                <p class="mt-1 text-xs text-muted-foreground">{{ card.description }}</p>
              </div>
              <div class="flex size-9 items-center justify-center rounded border bg-muted/30">
                <component :is="card.icon" class="size-4" />
              </div>
            </CardHeader>
            <CardContent class="px-4">
              <p class="text-3xl font-semibold tracking-tight">{{ card.value }}</p>
            </CardContent>
          </Card>
        </div>

        <div class="mt-4 grid gap-4 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.65fr)]">
          <main class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Tenant Overview</h3>
              </div>
              <div class="grid gap-4 p-4 md:grid-cols-2">
                <div class="space-y-3">
                  <div class="flex gap-3">
                    <Building2 class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                      <p class="text-sm font-medium">{{ workspace.tenant.name }}</p>
                      <p class="text-sm text-muted-foreground">
                        {{ workspace.tenant.subdomain }} / {{ workspace.tenant.timezone }}
                      </p>
                    </div>
                  </div>
                  <div class="flex gap-3">
                    <ShieldCheck class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                      <p class="text-sm font-medium">{{ workspace.plan.plan_name }}</p>
                      <p class="text-sm text-muted-foreground">
                        Trial {{ getStatusLabel(workspace.plan.trial_status) }}
                        <span v-if="workspace.plan.trial_ends_at">
                          / ends {{ formatDate(workspace.plan.trial_ends_at) }}
                        </span>
                      </p>
                    </div>
                  </div>
                </div>

                <div class="space-y-3">
                  <div class="flex gap-3">
                    <Globe2 class="mt-0.5 size-4 text-muted-foreground" />
                    <div class="min-w-0">
                      <p class="text-sm font-medium">{{ workspace.domain.status_label }}</p>
                      <p class="text-sm text-muted-foreground">
                        {{ workspace.domain.status_detail }}
                      </p>
                      <a
                        v-if="publicSiteUrl"
                        :href="publicSiteUrl"
                        target="_blank"
                        rel="noreferrer"
                        class="mt-1 inline-flex max-w-full items-center gap-1 truncate text-sm font-medium text-primary"
                      >
                        {{ publicSiteUrl }}
                        <ExternalLink class="size-3" />
                      </a>
                    </div>
                  </div>
                  <div class="flex gap-3">
                    <KeyRound class="mt-0.5 size-4 text-muted-foreground" />
                    <div>
                      <p class="text-sm font-medium">Impersonation flow</p>
                      <p class="text-sm text-muted-foreground">
                        {{ workspace.actions.impersonation.reason }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Owner and Users</h3>
              </div>
              <div class="grid gap-4 p-4 lg:grid-cols-[20rem_minmax(0,1fr)]">
                <div class="rounded border bg-muted/20 p-4">
                  <div class="flex items-center gap-2">
                    <UserRound class="size-4 text-primary" />
                    <h4 class="text-sm font-semibold">Tenant Owner</h4>
                  </div>
                  <div v-if="workspace.owner" class="mt-4 space-y-2 text-sm">
                    <p class="font-medium">{{ workspace.owner.name }}</p>
                    <p class="text-muted-foreground">{{ workspace.owner.email }}</p>
                    <p class="text-muted-foreground">{{ workspace.owner.phone || 'No phone' }}</p>
                    <Badge :variant="workspace.owner.is_active ? 'success' : 'neutral'">
                      {{ workspace.owner.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                  </div>
                  <Empty v-else class="mt-4 min-h-36 border bg-background">
                    <EmptyHeader>
                      <EmptyTitle>No owner</EmptyTitle>
                      <EmptyDescription>Add an owner from tenant management.</EmptyDescription>
                    </EmptyHeader>
                  </Empty>
                </div>

                <div class="overflow-hidden rounded border">
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>User</TableHead>
                        <TableHead>Role</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Joined</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      <TableRow v-for="user in workspace.users" :key="user.id">
                        <TableCell>
                          <div class="font-medium">{{ user.name }}</div>
                          <div class="text-xs text-muted-foreground">{{ user.email }}</div>
                        </TableCell>
                        <TableCell>{{ getStatusLabel(user.user_type) }}</TableCell>
                        <TableCell>
                          <Badge :variant="user.is_active ? 'success' : 'neutral'">
                            {{ user.is_active ? 'Active' : 'Inactive' }}
                          </Badge>
                        </TableCell>
                        <TableCell>{{ formatDate(user.created_at) }}</TableCell>
                      </TableRow>
                      <TableRow v-if="!workspace.users.length">
                        <TableCell
                          colspan="4"
                          class="h-32 text-center text-sm text-muted-foreground"
                        >
                          No users found for this tenant.
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
              </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
              <div class="rounded border bg-background">
                <div class="border-b px-4 py-3">
                  <h3 class="text-base font-semibold">Published Template</h3>
                </div>
                <div v-if="workspace.published_template" class="space-y-3 p-4">
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <p class="font-medium">{{ workspace.published_template.business_name }}</p>
                      <p class="text-sm text-muted-foreground">
                        {{ workspace.published_template.website_type_name || 'Website' }}
                      </p>
                    </div>
                    <Badge :variant="getStatusBadgeVariant(workspace.published_template.status)">
                      {{ getStatusLabel(workspace.published_template.status) }}
                    </Badge>
                  </div>
                  <div class="grid grid-cols-2 gap-2">
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Posts</p>
                      <p class="mt-1 text-lg font-semibold">
                        {{ workspace.published_template.posts_count }}
                      </p>
                    </div>
                    <div class="rounded border p-3">
                      <p class="text-xs text-muted-foreground">Leads</p>
                      <p class="mt-1 text-lg font-semibold">
                        {{ workspace.published_template.leads_count }}
                      </p>
                    </div>
                  </div>
                </div>
                <Empty v-else class="min-h-56 border-0">
                  <EmptyHeader>
                    <EmptyTitle>No published site</EmptyTitle>
                    <EmptyDescription
                      >This tenant has not published a template yet.</EmptyDescription
                    >
                  </EmptyHeader>
                </Empty>
              </div>

              <div class="rounded border bg-background">
                <div class="border-b px-4 py-3">
                  <h3 class="text-base font-semibold">Content and Demand</h3>
                </div>
                <div class="grid grid-cols-2 gap-3 p-4">
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Posts</p>
                    <p class="mt-1 text-2xl font-semibold">{{ workspace.posts_summary.total }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ workspace.posts_summary.published }} published
                    </p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Leads</p>
                    <p class="mt-1 text-2xl font-semibold">{{ workspace.leads_summary.total }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ workspace.leads_summary.new }} new
                    </p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">Design requests</p>
                    <p class="mt-1 text-2xl font-semibold">
                      {{ workspace.design_request_summary.total }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ workspace.design_request_summary.pending }} pending
                    </p>
                  </div>
                  <div class="rounded border p-3">
                    <p class="text-xs text-muted-foreground">CTA events</p>
                    <p class="mt-1 text-2xl font-semibold">
                      {{ workspace.usage_metrics.cta_events_last_7_days }}
                    </p>
                    <p class="text-xs text-muted-foreground">last 7 days</p>
                  </div>
                </div>
              </div>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Activity Timeline</h3>
              </div>
              <div v-if="workspace.activity_timeline.length" class="divide-y">
                <div
                  v-for="item in workspace.activity_timeline"
                  :key="item.id"
                  class="flex gap-3 p-4"
                >
                  <div
                    class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded border bg-muted/30"
                  >
                    <Activity class="size-4" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                      <p class="font-medium">{{ item.title }}</p>
                      <span class="text-xs text-muted-foreground">
                        {{ formatDateTime(item.occurred_at) }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-muted-foreground">{{ item.description }}</p>
                  </div>
                </div>
              </div>
              <Empty v-else class="m-4 min-h-56 bg-muted/20">
                <EmptyHeader>
                  <EmptyTitle>No activity yet</EmptyTitle>
                  <EmptyDescription>Tenant events will appear as work happens.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>
          </main>

          <aside class="space-y-4">
            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Feature Overrides</h3>
              </div>
              <div v-if="workspace.feature_overrides.length" class="divide-y">
                <div
                  v-for="override in workspace.feature_overrides"
                  :key="override.key"
                  class="p-4"
                >
                  <p class="break-all text-sm font-medium">{{ override.key }}</p>
                  <p class="mt-1 text-sm text-muted-foreground">
                    {{ valueLabel(override.value) }} · {{ override.source }}
                  </p>
                </div>
              </div>
              <Empty v-else class="min-h-44 border-0">
                <EmptyHeader>
                  <EmptyTitle>No overrides</EmptyTitle>
                  <EmptyDescription>This tenant is using platform defaults.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Recent Leads</h3>
              </div>
              <div v-if="workspace.leads_summary.recent.length" class="divide-y">
                <div v-for="lead in workspace.leads_summary.recent" :key="lead.id" class="p-4">
                  <div class="flex items-start justify-between gap-3">
                    <p class="text-sm font-medium">{{ itemTitle(lead) }}</p>
                    <Badge :variant="getStatusBadgeVariant(lead.status)">
                      {{ getStatusLabel(lead.status) }}
                    </Badge>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatDateTime(lead.created_at) }}
                  </p>
                </div>
              </div>
              <Empty v-else class="min-h-44 border-0">
                <EmptyHeader>
                  <EmptyTitle>No leads</EmptyTitle>
                  <EmptyDescription>CTA submissions will appear here.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background">
              <div class="border-b px-4 py-3">
                <h3 class="text-base font-semibold">Audit History</h3>
              </div>
              <div v-if="workspace.audit_history.length" class="divide-y">
                <div v-for="audit in workspace.audit_history" :key="audit.id" class="p-4">
                  <div class="flex items-start justify-between gap-3">
                    <p class="break-all text-sm font-medium">{{ audit.setting_key }}</p>
                    <Badge variant="outline">{{ getStatusLabel(audit.action) }}</Badge>
                  </div>
                  <p class="mt-1 text-xs text-muted-foreground">
                    {{ audit.changed_by_name || audit.changed_by_email || 'System' }} ·
                    {{ formatDateTime(audit.changed_at) }}
                  </p>
                </div>
              </div>
              <Empty v-else class="min-h-44 border-0">
                <EmptyHeader>
                  <EmptyTitle>No audit records</EmptyTitle>
                  <EmptyDescription>Tenant setting changes will appear here.</EmptyDescription>
                </EmptyHeader>
              </Empty>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <Mail class="size-4 text-primary" />
                <h3 class="text-sm font-semibold">Owner Contact</h3>
              </div>
              <p class="mt-3 text-sm text-muted-foreground">
                {{ workspace.owner?.email || 'No owner email available' }}
              </p>
            </section>

            <section class="rounded border bg-background p-4">
              <div class="flex items-center gap-2">
                <Settings class="size-4 text-primary" />
                <h3 class="text-sm font-semibold">Workspace Data</h3>
              </div>
              <p class="mt-3 text-sm text-muted-foreground">
                Last refreshed {{ formatDateTime(workspace.generated_at) }}
              </p>
            </section>
          </aside>
        </div>
      </template>

      <Empty v-else class="min-h-[420px] border bg-muted/20">
        <EmptyHeader>
          <EmptyTitle>Tenant workspace unavailable</EmptyTitle>
          <EmptyDescription
            >Return to tenants and try opening the workspace again.</EmptyDescription
          >
        </EmptyHeader>
      </Empty>
    </div>
  </div>
</template>
