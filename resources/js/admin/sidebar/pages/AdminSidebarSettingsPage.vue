<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Textarea } from '@/shared/components/ui/textarea'
import { useAdminSidebarStore } from '@/admin/sidebar/sidebar-store'
import { iconMap } from '@/shared/utils/iconMap'
import type { IconName } from '@/shared/utils/iconMap'
import type { SidebarData, SidebarNavChild, SidebarNavItem } from '@/shared/types/sidebar'
import {
  ChevronDown,
  ChevronUp,
  Circle,
  Eye,
  EyeOff,
  LayoutPanelLeft,
  Plus,
  Save,
  Trash2,
} from 'lucide-vue-next'
import type { Component } from 'vue'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

type BuilderLink = SidebarNavChild
type BuilderGroup = SidebarNavItem & { items: BuilderLink[] }

interface ValidationIssue {
  key: string
  message: string
  severity: 'error' | 'warning'
}

const sidebarStore = useAdminSidebarStore()
const router = useRouter()
const groups = ref<BuilderGroup[]>([])
const preservedData = ref<Omit<SidebarData, 'main_nav'>>({})
const saveNotice = ref('')
const formError = ref('')

const sidebar = computed(() => sidebarStore.sidebars[0] ?? null)
const iconOptions = computed(() => Object.keys(iconMap).sort() as IconName[])
const visibleGroups = computed(() => groups.value.filter((group) => group.is_active !== false))
const visibleLinkCount = computed(() => {
  return visibleGroups.value.reduce((count, group) => {
    const links = group.items.filter((item) => item.is_active !== false).length

    return count + Math.max(links, group.items.length ? 0 : 1)
  }, 0)
})

const knownAdminRoutePaths = computed(() => {
  return new Set(
    router
      .getRoutes()
      .map((route) => route.path)
      .filter((path) => path.startsWith('/admin') && !path.includes(':'))
      .map(normalizePath)
  )
})

const validationIssues = computed<ValidationIssue[]>(() => {
  const issues: ValidationIssue[] = []

  groups.value.forEach((group, groupIndex) => {
    const groupKey = `group-${groupIndex}`

    if (!group.title.trim()) {
      issues.push({
        key: `${groupKey}-title`,
        message: `Group ${groupIndex + 1} needs a title.`,
        severity: 'error',
      })
    }

    validateNavUrl(group.url, `Group "${group.title || groupIndex + 1}"`, issues, groupKey)

    group.items.forEach((item, itemIndex) => {
      const itemKey = `${groupKey}-item-${itemIndex}`

      if (!item.title.trim()) {
        issues.push({
          key: `${itemKey}-title`,
          message: `Link ${itemIndex + 1} in ${group.title || 'this group'} needs a title.`,
          severity: 'error',
        })
      }

      validateNavUrl(item.url, `Link "${item.title || itemIndex + 1}"`, issues, itemKey)
    })
  })

  return issues
})

const validationErrors = computed(() =>
  validationIssues.value.filter((issue) => issue.severity === 'error')
)
const validationWarnings = computed(() =>
  validationIssues.value.filter((issue) => issue.severity === 'warning')
)

const backendError = computed(() => {
  return Object.entries(sidebarStore.errors).find(([field]) => field.startsWith('data'))?.[1]?.[0]
})

const iconFor = (icon?: string): Component => {
  return icon && icon in iconMap ? iconMap[icon as IconName] : Circle
}

function normalizePath(path: string): string {
  if (!path) return '/'

  return `/${path}`.replace(/\/+/g, '/').replace(/\/$/, '') || '/'
}

function isExternalUrl(url: string): boolean {
  return /^(https?:|mailto:|tel:)/i.test(url)
}

function isValidExternalUrl(url: string): boolean {
  if (/^https?:/i.test(url)) {
    try {
      new URL(url)

      return true
    } catch {
      return false
    }
  }

  return /^(mailto:[^\s@]+@[^\s@]+\.[^\s@]+|tel:\+?[0-9\s().-]+)$/i.test(url)
}

function isValidInternalUrl(url: string): boolean {
  return url === '#' || /^\/?[a-z0-9][a-z0-9-/]*$/i.test(url)
}

function resolveAdminRoute(url: string): string | null {
  if (!url || url === '#' || isExternalUrl(url)) return null
  if (url.startsWith('/admin')) return normalizePath(url)
  if (url.startsWith('/')) return normalizePath(url)

  return normalizePath(`/admin/${url}`)
}

function validateNavUrl(url: string, label: string, issues: ValidationIssue[], key: string): void {
  const value = url.trim()

  if (!value) {
    issues.push({
      key: `${key}-url-required`,
      message: `${label} needs a URL.`,
      severity: 'error',
    })

    return
  }

  if (isExternalUrl(value)) {
    if (!isValidExternalUrl(value)) {
      issues.push({
        key: `${key}-url-external`,
        message: `${label} has an invalid external URL.`,
        severity: 'error',
      })
    }

    return
  }

  if (!isValidInternalUrl(value)) {
    issues.push({
      key: `${key}-url-format`,
      message: `${label} must use #, an internal admin route, or http/mailto/tel URL.`,
      severity: 'error',
    })

    return
  }

  const routePath = resolveAdminRoute(value)

  if (routePath && !knownAdminRoutePaths.value.has(routePath)) {
    issues.push({
      key: `${key}-route`,
      message: `${label} points to ${routePath}, which does not match a registered admin route.`,
      severity: 'warning',
    })
  }
}

const createGroup = (): BuilderGroup => ({
  title: 'New Group',
  url: '#',
  icon: 'Circle',
  description: '',
  is_active: true,
  items: [],
})

const createLink = (): BuilderLink => ({
  title: 'New Link',
  url: 'dashboard',
  icon: 'Circle',
  description: '',
  is_active: true,
})

const cloneSidebarData = (data: SidebarData): void => {
  const { main_nav, ...rest } = data
  preservedData.value = structuredClone(rest)
  groups.value = (main_nav ?? []).map((group) => ({
    title: group.title ?? '',
    url: group.url ?? '#',
    icon: group.icon ?? 'Circle',
    description: group.description ?? '',
    is_active: group.is_active !== false,
    items: (group.items ?? []).map((item) => ({
      title: item.title ?? '',
      url: item.url ?? '',
      icon: item.icon ?? 'Circle',
      description: item.description ?? '',
      is_active: item.is_active !== false,
    })),
  }))
}

const payloadData = (): SidebarData => ({
  ...structuredClone(preservedData.value),
  main_nav: groups.value.map((group) => ({
    title: group.title.trim(),
    url: group.url.trim(),
    icon: group.icon,
    description: group.description?.trim() ?? '',
    is_active: group.is_active !== false,
    items: group.items.map((item) => ({
      title: item.title.trim(),
      url: item.url.trim(),
      icon: item.icon,
      description: item.description?.trim() ?? '',
      is_active: item.is_active !== false,
    })),
  })),
})

const loadSidebar = async (): Promise<void> => {
  await sidebarStore.index()

  if (sidebar.value) {
    cloneSidebarData(sidebar.value.data)
  }
}

const addGroup = (): void => {
  groups.value.push(createGroup())
}

const deleteGroup = (index: number): void => {
  groups.value.splice(index, 1)
}

const addLink = (group: BuilderGroup): void => {
  group.items.push(createLink())
}

const deleteLink = (group: BuilderGroup, index: number): void => {
  group.items.splice(index, 1)
}

const moveItem = <T,>(items: T[], index: number, direction: -1 | 1): void => {
  const target = index + direction

  if (target < 0 || target >= items.length) return

  const [item] = items.splice(index, 1)
  items.splice(target, 0, item)
}

const saveSidebar = async (): Promise<void> => {
  if (!sidebar.value) return

  if (!groups.value.length) {
    formError.value = 'Add at least one navigation group before saving.'

    return
  }

  if (validationErrors.value.length) {
    formError.value = 'Fix the navigation errors before saving.'

    return
  }

  try {
    formError.value = ''
    saveNotice.value = ''
    await sidebarStore.update(sidebar.value.id, {
      name: sidebar.value.name,
      description: sidebar.value.description,
      data: payloadData(),
    })

    if (sidebarStore.sidebar) {
      cloneSidebarData(sidebarStore.sidebar.data)
    }

    saveNotice.value = 'Navigation saved'
  } catch {
    formError.value = 'Unable to save navigation. Review highlighted fields and try again.'
  }
}

onMounted(loadSidebar)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Admin Sidebar Settings</h2>
          <p class="module-container-description">
            Build the admin navigation visually while keeping the existing sidebar configuration
            format.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Badge variant="outline">{{ groups.length }} groups</Badge>
          <Badge variant="outline">{{ visibleLinkCount }} visible links</Badge>
          <Button
            variant="update"
            size="sm"
            :disabled="sidebarStore.loading || !sidebar"
            @click="saveSidebar"
          >
            <Save class="size-4" />
            {{ sidebarStore.loading ? 'Saving...' : 'Save Navigation' }}
          </Button>
        </div>
      </div>

      <div
        v-if="formError || backendError"
        class="mb-4 rounded border border-destructive/40 bg-destructive/10 p-3 text-sm font-medium text-destructive"
      >
        {{ formError || backendError }}
      </div>

      <div
        v-if="saveNotice"
        class="mb-4 rounded border border-emerald-200 bg-emerald-50 p-3 text-sm font-medium text-emerald-800"
      >
        {{ saveNotice }}
      </div>

      <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_26rem]">
        <main class="space-y-4">
          <section class="rounded border bg-background p-4">
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
              <div>
                <h3 class="text-base font-semibold">Navigation Builder</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                  Add, edit, hide, and reorder groups and links shown in the admin sidebar.
                </p>
              </div>
              <Button type="button" variant="create" size="sm" @click="addGroup">
                <Plus class="size-4" />
                Add Group
              </Button>
            </div>
          </section>

          <section
            v-for="(group, groupIndex) in groups"
            :key="`group-${groupIndex}`"
            class="rounded border bg-background"
          >
            <div class="border-b p-4">
              <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex min-w-0 flex-1 gap-3">
                  <div
                    class="flex size-10 shrink-0 items-center justify-center rounded border bg-primary/5 text-primary"
                  >
                    <component :is="iconFor(group.icon)" class="size-5" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="grid gap-3 md:grid-cols-[minmax(0,1fr)_11rem]">
                      <Input
                        v-field-help="'Group title shown in the sidebar.'"
                        v-model="group.title"
                        placeholder="Group title"
                      />
                      <NativeSelect
                        v-field-help="'Choose the group icon.'"
                        v-model="group.icon"
                        class="w-full"
                      >
                        <option v-for="icon in iconOptions" :key="icon" :value="icon">
                          {{ icon }}
                        </option>
                      </NativeSelect>
                    </div>
                    <div class="mt-3 grid gap-3 md:grid-cols-[minmax(0,1fr)_10rem]">
                      <Input
                        v-field-help="
                          'Use # for expandable groups, a relative admin route, or an external URL.'
                        "
                        v-model="group.url"
                        placeholder="# or dashboard"
                      />
                      <label
                        class="flex h-9 items-center justify-between gap-3 rounded border px-3 text-sm"
                      >
                        <span class="flex items-center gap-2">
                          <component
                            :is="group.is_active === false ? EyeOff : Eye"
                            class="size-4"
                          />
                          Visible
                        </span>
                        <Checkbox
                          :model-value="group.is_active !== false"
                          @update:model-value="group.is_active = Boolean($event)"
                        />
                      </label>
                    </div>
                    <Textarea
                      v-field-help="'Optional internal description for this navigation group.'"
                      v-model="group.description"
                      class="mt-3 min-h-16"
                      placeholder="Description"
                    />
                  </div>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                  <Button
                    type="button"
                    size="icon-sm"
                    variant="navigate"
                    :disabled="groupIndex === 0"
                    title="Move group up"
                    @click="moveItem(groups, groupIndex, -1)"
                  >
                    <ChevronUp class="size-4" />
                  </Button>
                  <Button
                    type="button"
                    size="icon-sm"
                    variant="navigate"
                    :disabled="groupIndex === groups.length - 1"
                    title="Move group down"
                    @click="moveItem(groups, groupIndex, 1)"
                  >
                    <ChevronDown class="size-4" />
                  </Button>
                  <Button
                    type="button"
                    size="icon-sm"
                    variant="delete"
                    title="Delete group"
                    @click="deleteGroup(groupIndex)"
                  >
                    <Trash2 class="size-4" />
                  </Button>
                </div>
              </div>
            </div>

            <div class="space-y-3 p-4">
              <div class="flex items-center justify-between gap-3">
                <h4 class="text-sm font-semibold">Links</h4>
                <Button type="button" variant="create" size="xs" @click="addLink(group)">
                  <Plus class="size-3" />
                  Add Link
                </Button>
              </div>

              <div
                v-if="!group.items.length"
                class="rounded border border-dashed bg-muted/30 p-4 text-sm text-muted-foreground"
              >
                This group has no child links. The group URL will be used as a direct navigation
                link.
              </div>

              <div
                v-for="(item, itemIndex) in group.items"
                :key="`group-${groupIndex}-item-${itemIndex}`"
                class="rounded border bg-muted/10 p-3"
              >
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_10rem_8rem_auto]">
                  <Input
                    v-field-help="'Link label shown under this group.'"
                    v-model="item.title"
                    placeholder="Link title"
                  />
                  <Input
                    v-field-help="'Use a relative admin route such as tenants or an external URL.'"
                    v-model="item.url"
                    placeholder="dashboard"
                  />
                  <NativeSelect
                    v-field-help="'Choose the link icon.'"
                    v-model="item.icon"
                    class="w-full"
                  >
                    <option v-for="icon in iconOptions" :key="icon" :value="icon">
                      {{ icon }}
                    </option>
                  </NativeSelect>
                  <div class="flex items-center justify-end gap-2">
                    <Checkbox
                      v-field-help="'Show or hide this link in the sidebar.'"
                      :model-value="item.is_active !== false"
                      @update:model-value="item.is_active = Boolean($event)"
                    />
                    <Button
                      type="button"
                      size="icon-sm"
                      variant="navigate"
                      :disabled="itemIndex === 0"
                      title="Move link up"
                      @click="moveItem(group.items, itemIndex, -1)"
                    >
                      <ChevronUp class="size-4" />
                    </Button>
                    <Button
                      type="button"
                      size="icon-sm"
                      variant="navigate"
                      :disabled="itemIndex === group.items.length - 1"
                      title="Move link down"
                      @click="moveItem(group.items, itemIndex, 1)"
                    >
                      <ChevronDown class="size-4" />
                    </Button>
                    <Button
                      type="button"
                      size="icon-sm"
                      variant="delete"
                      title="Delete link"
                      @click="deleteLink(group, itemIndex)"
                    >
                      <Trash2 class="size-4" />
                    </Button>
                  </div>
                </div>
                <Textarea
                  v-field-help="'Optional internal description for this link.'"
                  v-model="item.description"
                  class="mt-3 min-h-14"
                  placeholder="Description"
                />
              </div>
            </div>
          </section>

          <section
            v-if="!groups.length"
            class="rounded border border-dashed bg-muted/30 p-8 text-center"
          >
            <LayoutPanelLeft class="mx-auto size-8 text-muted-foreground" />
            <h3 class="mt-3 text-base font-semibold">No navigation groups</h3>
            <p class="mt-1 text-sm text-muted-foreground">
              Add a group to start rebuilding the admin sidebar.
            </p>
          </section>
        </main>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
          <section class="rounded border bg-background p-4">
            <div class="flex items-center gap-2">
              <LayoutPanelLeft class="size-4 text-primary" />
              <h3 class="text-sm font-semibold">Validation</h3>
            </div>
            <div class="mt-3 space-y-2">
              <div
                v-for="issue in validationIssues"
                :key="issue.key"
                class="rounded border p-3 text-sm"
                :class="
                  issue.severity === 'error'
                    ? 'border-destructive/40 bg-destructive/10 text-destructive'
                    : 'border-amber-300 bg-amber-50 text-amber-900'
                "
              >
                {{ issue.message }}
              </div>
              <div
                v-if="!validationIssues.length"
                class="rounded border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800"
              >
                Navigation routes and URLs look valid.
              </div>
            </div>
            <p v-if="validationWarnings.length" class="mt-3 text-xs text-muted-foreground">
              Warnings do not block saving, but they usually mean a link may land on a missing page.
            </p>
          </section>

          <section class="rounded border bg-background">
            <div class="border-b px-4 py-3">
              <h3 class="text-sm font-semibold">Expanded Preview</h3>
            </div>
            <div class="space-y-3 p-4">
              <div
                v-for="group in visibleGroups"
                :key="`expanded-${group.title}`"
                class="rounded border p-3"
              >
                <div class="flex items-center gap-2 text-sm font-semibold">
                  <component :is="iconFor(group.icon)" class="size-4 text-primary" />
                  <span>{{ group.title || 'Untitled group' }}</span>
                </div>
                <div v-if="group.items.length" class="mt-2 space-y-1 pl-6">
                  <div
                    v-for="item in group.items.filter((link) => link.is_active !== false)"
                    :key="`expanded-${group.title}-${item.title}`"
                    class="flex items-center gap-2 rounded px-2 py-1 text-sm text-muted-foreground"
                  >
                    <component :is="iconFor(item.icon)" class="size-3.5" />
                    <span>{{ item.title || 'Untitled link' }}</span>
                  </div>
                </div>
                <div v-else class="mt-2 pl-6 text-xs text-muted-foreground">
                  Direct link: {{ group.url }}
                </div>
              </div>
            </div>
          </section>

          <section class="rounded border bg-background">
            <div class="border-b px-4 py-3">
              <h3 class="text-sm font-semibold">Collapsed Preview</h3>
            </div>
            <div class="flex flex-wrap gap-2 p-4">
              <div
                v-for="group in visibleGroups"
                :key="`collapsed-${group.title}`"
                class="flex size-10 items-center justify-center rounded border bg-muted/30"
                :title="group.title"
              >
                <component :is="iconFor(group.icon)" class="size-4" />
              </div>
            </div>
          </section>
        </aside>
      </div>
    </div>
  </div>
</template>
