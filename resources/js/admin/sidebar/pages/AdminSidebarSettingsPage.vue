<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/shared/components/ui/card'
import { Checkbox } from '@/shared/components/ui/checkbox'
import { Field, FieldLabel } from '@/shared/components/ui/field'
import { Input } from '@/shared/components/ui/input'
import { NativeSelect } from '@/shared/components/ui/native-select'
import { Separator } from '@/shared/components/ui/separator'
import { useAdminSidebarStore } from '@/admin/sidebar/sidebar-store'
import { iconMap, type IconName } from '@/shared/utils/iconMap'
import type { SidebarData, SidebarNavChild, SidebarNavItem } from '@/shared/types/sidebar'
import { AlertCircle, ChevronDown, ChevronUp, Eye, EyeOff, Link, Plus, Save } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

type BuilderGroup = SidebarNavItem & { items: BuilderLink[] }
type BuilderLink = SidebarNavChild
type Selection =
  | { type: 'group'; groupIndex: number }
  | { type: 'link'; groupIndex: number; linkIndex: number }
type DragPayload = Selection

interface ValidationIssue {
  key: string
  message: string
  level: 'error' | 'warning'
}

const sidebarStore = useAdminSidebarStore()
const router = useRouter()
const groups = ref<BuilderGroup[]>([])
const selected = ref<Selection>({ type: 'group', groupIndex: 0 })
const dragging = ref<DragPayload | null>(null)
const previewMode = ref<'expanded' | 'collapsed'>('expanded')
const saveError = ref('')
const saveSuccess = ref('')

const sidebar = computed(() => sidebarStore.sidebar ?? sidebarStore.sidebars[0] ?? null)
const iconNames = Object.keys(iconMap) as IconName[]
const knownAdminRoutePaths = computed(() => {
  return new Set(
    router
      .getRoutes()
      .filter((route) => route.meta?.requiresAdminAuth === true && !route.path.includes(':'))
      .map((route) => normalizePath(route.path))
  )
})
const routeSuggestions = computed(() => {
  return [...knownAdminRoutePaths.value]
    .map((path) => path.replace(/^\/admin\/?/, ''))
    .filter(Boolean)
    .sort()
})

const selectedGroup = computed(() => groups.value[selected.value.groupIndex] ?? null)
const selectedLink = computed(() => {
  if (selected.value.type !== 'link') return null

  return selectedGroup.value?.items[selected.value.linkIndex] ?? null
})
const selectedItem = computed(() =>
  selected.value.type === 'link' ? selectedLink.value : selectedGroup.value
)
const selectedIsLink = computed(() => selected.value.type === 'link' && Boolean(selectedLink.value))
const selectedLinkCanMoveUp = computed(
  () => selected.value.type === 'link' && selected.value.linkIndex > 0
)
const selectedLinkCanMoveDown = computed(() => {
  return (
    selected.value.type === 'link' &&
    Boolean(selectedGroup.value) &&
    selected.value.linkIndex < selectedGroup.value.items.length - 1
  )
})
const visibleGroupCount = computed(
  () => groups.value.filter((group) => group.is_active !== false).length
)
const linkCount = computed(() => groups.value.flatMap((group) => group.items).length)
const visibleLinkCount = computed(() => {
  return groups.value.flatMap((group) => group.items).filter((link) => link.is_active !== false)
    .length
})
const hiddenCount = computed(() => {
  const hiddenGroups = groups.value.filter((group) => group.is_active === false).length
  const hiddenLinks = groups.value
    .flatMap((group) => group.items)
    .filter((link) => link.is_active === false).length

  return hiddenGroups + hiddenLinks
})

const validationIssues = computed<ValidationIssue[]>(() => {
  const issues: ValidationIssue[] = []

  if (!groups.value.length) {
    issues.push({
      key: 'empty',
      level: 'error',
      message: 'Add at least one navigation group before saving.',
    })
  }

  groups.value.forEach((group, groupIndex) => {
    const groupLabel = group.title || `Group ${groupIndex + 1}`

    if (!group.title.trim()) {
      issues.push({
        key: `group-${groupIndex}-title`,
        level: 'error',
        message: `${groupLabel} needs a title.`,
      })
    }

    issues.push(...urlIssues(group.url, `${groupLabel} group URL`, group.items.length > 0))

    group.items.forEach((link, linkIndex) => {
      const linkLabel = link.title || `Link ${linkIndex + 1}`

      if (!link.title.trim()) {
        issues.push({
          key: `group-${groupIndex}-link-${linkIndex}-title`,
          level: 'error',
          message: `${groupLabel} > ${linkLabel} needs a title.`,
        })
      }

      issues.push(...urlIssues(link.url, `${groupLabel} > ${linkLabel}`))
    })
  })

  return issues
})

const errors = computed(() => validationIssues.value.filter((issue) => issue.level === 'error'))
const warnings = computed(() => validationIssues.value.filter((issue) => issue.level === 'warning'))
const canSave = computed(
  () => Boolean(sidebar.value) && !errors.value.length && !sidebarStore.loading
)

const sidebarDataError = computed(() => {
  return Object.entries(sidebarStore.errors).find(([field]) => field.startsWith('data'))?.[1]?.[0]
})
const isInitialLoading = computed(
  () => sidebarStore.loading && !sidebar.value && !groups.value.length
)

const iconFor = (icon?: string) => {
  return icon && icon in iconMap ? iconMap[icon as IconName] : iconMap.Circle
}

const normalizeIcon = (icon?: string): IconName => {
  return icon && icon in iconMap ? (icon as IconName) : 'Circle'
}

const stringValue = (value: unknown, fallback = ''): string => {
  return typeof value === 'string' ? value : fallback
}

const normalizePath = (path: string): string => {
  if (!path) return '/'

  return `/${path}`.replace(/\/+/g, '/').replace(/\/$/, '') || '/'
}

const isExternalUrl = (url: string): boolean => /^(https?:|mailto:|tel:)/i.test(url)

const resolveAdminRoute = (url: string): string | null => {
  if (!url || url === '#' || isExternalUrl(url)) return null
  if (url.startsWith('/admin')) return normalizePath(url)
  if (url.startsWith('/')) return normalizePath(`/admin${url}`)

  return normalizePath(`/admin/${url}`)
}

const cloneGroups = (data?: SidebarData): BuilderGroup[] => {
  const mainNav = Array.isArray(data?.main_nav) ? data.main_nav : []

  return mainNav.map((group) => ({
    title: stringValue(group.title),
    url: stringValue(group.url, '#'),
    icon: normalizeIcon(group.icon),
    description: stringValue(group.description),
    is_active: group.is_active !== false,
    items: (Array.isArray(group.items) ? group.items : []).map((link) => ({
      title: stringValue(link.title),
      url: stringValue(link.url),
      icon: normalizeIcon(link.icon),
      description: stringValue(link.description),
      is_active: link.is_active !== false,
    })),
  }))
}

const normalizeSelection = (): void => {
  if (!groups.value.length) {
    selected.value = { type: 'group', groupIndex: 0 }
    return
  }

  const groupIndex = Math.max(0, Math.min(selected.value.groupIndex, groups.value.length - 1))

  if (selected.value.type === 'link') {
    const group = groups.value[groupIndex]

    if (group?.items[selected.value.linkIndex]) {
      selected.value = { type: 'link', groupIndex, linkIndex: selected.value.linkIndex }
      return
    }
  }

  selected.value = { type: 'group', groupIndex }
}

const loadSidebar = async (): Promise<void> => {
  await sidebarStore.index()
  groups.value = cloneGroups(sidebar.value?.data)
  normalizeSelection()
}

const selectGroup = (groupIndex: number): void => {
  selected.value = { type: 'group', groupIndex }
}

const selectLink = (groupIndex: number, linkIndex: number): void => {
  selected.value = { type: 'link', groupIndex, linkIndex }
}

const addGroup = (): void => {
  groups.value.push({
    title: 'New Group',
    url: '#',
    icon: 'Circle',
    description: '',
    is_active: true,
    items: [],
  })
  selected.value = { type: 'group', groupIndex: groups.value.length - 1 }
}

const addLink = (groupIndex: number): void => {
  const group = groups.value[groupIndex]
  if (!group) return

  group.items.push({
    title: 'New Link',
    url: '',
    icon: 'Circle',
    description: '',
    is_active: true,
  })
  selected.value = { type: 'link', groupIndex, linkIndex: group.items.length - 1 }
}

const moveGroup = (from: number, to: number): void => {
  if (from === to || from < 0 || from >= groups.value.length || to < 0 || to >= groups.value.length)
    return

  const [group] = groups.value.splice(from, 1)
  groups.value.splice(to, 0, group)
  selected.value = { type: 'group', groupIndex: to }
}

const moveLink = (groupIndex: number, from: number, to: number): void => {
  const group = groups.value[groupIndex]
  if (
    !group ||
    from === to ||
    from < 0 ||
    from >= group.items.length ||
    to < 0 ||
    to >= group.items.length
  )
    return

  const [link] = group.items.splice(from, 1)
  group.items.splice(to, 0, link)
  selected.value = { type: 'link', groupIndex, linkIndex: to }
}

const moveSelectedLink = (direction: -1 | 1): void => {
  if (selected.value.type !== 'link') return

  moveLink(
    selected.value.groupIndex,
    selected.value.linkIndex,
    selected.value.linkIndex + direction
  )
}

const moveLinkToGroup = (
  fromGroupIndex: number,
  fromLinkIndex: number,
  toGroupIndex: number,
  toLinkIndex: number
): void => {
  const fromGroup = groups.value[fromGroupIndex]
  const toGroup = groups.value[toGroupIndex]
  if (!fromGroup || !toGroup || !fromGroup.items[fromLinkIndex]) return

  const [link] = fromGroup.items.splice(fromLinkIndex, 1)
  const targetIndex = Math.max(0, Math.min(toLinkIndex, toGroup.items.length))
  const adjustedIndex =
    fromGroupIndex === toGroupIndex && fromLinkIndex < toLinkIndex ? targetIndex - 1 : targetIndex

  toGroup.items.splice(Math.max(0, adjustedIndex), 0, link)
  selected.value = {
    type: 'link',
    groupIndex: toGroupIndex,
    linkIndex: Math.max(0, adjustedIndex),
  }
}

const startDrag = (event: DragEvent, payload: DragPayload): void => {
  dragging.value = payload
  event.dataTransfer?.setData('text/plain', JSON.stringify(payload))
  if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move'
}

const dropOnGroup = (groupIndex: number): void => {
  const payload = dragging.value
  const group = groups.value[groupIndex]
  if (!payload || !group) return

  if (payload.type === 'group') {
    moveGroup(payload.groupIndex, groupIndex)
  } else {
    moveLinkToGroup(payload.groupIndex, payload.linkIndex, groupIndex, group.items.length)
  }

  dragging.value = null
}

const dropOnLink = (groupIndex: number, linkIndex: number): void => {
  const payload = dragging.value
  if (!payload || payload.type !== 'link') return

  moveLinkToGroup(payload.groupIndex, payload.linkIndex, groupIndex, linkIndex)
  dragging.value = null
}

const updateSelectedVisibility = (value: boolean | 'indeterminate'): void => {
  if (!selectedItem.value || value === 'indeterminate') return

  selectedItem.value.is_active = value
}

const applyRouteSuggestion = (route: string): void => {
  if (!selectedItem.value) return

  selectedItem.value.url = route
}

const urlIssues = (value: string, label: string, groupWithLinks = false): ValidationIssue[] => {
  const url = value.trim()
  const issues: ValidationIssue[] = []

  if (!url) {
    return [{ key: `${label}-url-empty`, level: 'error', message: `${label} needs a URL.` }]
  }

  if (url === '#') {
    return issues
  }

  if (isExternalUrl(url)) {
    try {
      if (/^https?:/i.test(url)) new URL(url)
      return issues
    } catch {
      return [
        {
          key: `${label}-url-invalid`,
          level: 'error',
          message: `${label} has an invalid external URL.`,
        },
      ]
    }
  }

  if (!/^[a-z0-9][a-z0-9-/]*$/i.test(url.replace(/^\//, '').replace(/^admin\//, ''))) {
    issues.push({
      key: `${label}-url-format`,
      level: 'error',
      message: `${label} should be an admin route like dashboard, /admin/dashboard, #, or a full URL.`,
    })
  }

  const routePath = resolveAdminRoute(url)
  if (routePath && !knownAdminRoutePaths.value.has(routePath)) {
    issues.push({
      key: `${label}-url-warning`,
      level: 'warning',
      message: `${label} does not match a registered admin route.`,
    })
  }

  if (groupWithLinks && url !== '#') {
    issues.push({
      key: `${label}-group-url-warning`,
      level: 'warning',
      message: `${label} has child links. Use # if this is only a collapsible group.`,
    })
  }

  return issues
}

const payloadData = (): SidebarData => {
  const current = sidebar.value?.data ?? { main_nav: [] }

  return {
    ...current,
    main_nav: groups.value.map((group) => ({
      title: group.title.trim(),
      url: group.url.trim() || '#',
      icon: normalizeIcon(group.icon),
      description: group.description?.trim() || '',
      is_active: group.is_active !== false,
      items: group.items.map((link) => ({
        title: link.title.trim(),
        url: link.url.trim(),
        icon: normalizeIcon(link.icon),
        description: link.description?.trim() || '',
        is_active: link.is_active !== false,
      })),
    })),
  }
}

const saveSidebar = async (): Promise<void> => {
  if (!sidebar.value || !canSave.value) return

  try {
    saveError.value = ''
    saveSuccess.value = ''
    await sidebarStore.update(sidebar.value.id, {
      name: sidebar.value.name,
      description: sidebar.value.description,
      data: payloadData(),
    })
    groups.value = cloneGroups(sidebar.value?.data)
    saveSuccess.value = 'Navigation saved. The admin sidebar will use this structure.'
  } catch {
    saveSuccess.value = ''
    saveError.value = 'Unable to save the navigation builder changes.'
  }
}

onMounted(loadSidebar)
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-16">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Navigation Builder</h2>
          <p class="module-container-description">
            Build the admin sidebar by editing one group or link at a time.
          </p>
        </div>
      </div>

      <div
        class="mb-4 flex flex-col gap-3 rounded border bg-card p-3 md:flex-row md:items-center md:justify-between"
      >
        <div class="flex flex-wrap gap-2">
          <Badge variant="secondary">{{ groups.length }} groups</Badge>
          <Badge variant="secondary">{{ linkCount }} links</Badge>
          <Badge variant="outline">{{ visibleGroupCount }} groups visible</Badge>
          <Badge variant="outline">{{ visibleLinkCount }} links visible</Badge>
          <Badge v-if="hiddenCount" variant="outline">{{ hiddenCount }} hidden</Badge>
        </div>
        <div class="flex flex-wrap gap-2">
          <Button type="button" variant="create" size="sm" @click="addGroup">
            <Plus class="size-4" />
            Add Group
          </Button>
          <Button
            type="button"
            variant="update"
            size="sm"
            :disabled="!canSave"
            @click="saveSidebar"
          >
            <Save class="size-4" />
            {{ sidebarStore.loading ? 'Saving...' : 'Save Navigation' }}
          </Button>
        </div>
      </div>

      <div class="grid gap-4 xl:grid-cols-[18rem_minmax(0,1fr)_23rem]">
        <section class="rounded border bg-background">
          <div class="flex items-center justify-between gap-3 border-b p-3">
            <div>
              <h3 class="text-sm font-semibold">Navigation</h3>
              <p class="text-xs text-muted-foreground">Select an item to edit.</p>
            </div>
            <Button type="button" variant="outline" size="sm" @click="addGroup">
              <Plus class="size-4" />
              Group
            </Button>
          </div>

          <div v-if="isInitialLoading" class="space-y-2 p-3">
            <div v-for="item in 4" :key="item" class="h-10 animate-pulse rounded bg-primary/10" />
          </div>

          <div v-else-if="!groups.length" class="p-5 text-sm text-muted-foreground">
            No navigation groups yet. Add a group to start.
          </div>

          <div v-else class="space-y-2 p-3">
            <div
              v-for="(group, groupIndex) in groups"
              :key="`group-outline-${groupIndex}`"
              class="space-y-1"
            >
              <button
                type="button"
                draggable="true"
                class="flex w-full items-center gap-2 rounded border px-3 py-2 text-left text-sm transition hover:bg-muted/60"
                :class="
                  selected.type === 'group' && selected.groupIndex === groupIndex
                    ? 'border-primary bg-primary/5'
                    : 'border-input'
                "
                @click="selectGroup(groupIndex)"
                @dragstart="startDrag($event, { type: 'group', groupIndex })"
                @dragend="dragging = null"
                @dragover.prevent
                @drop.prevent="dropOnGroup(groupIndex)"
              >
                <component
                  :is="iconFor(group.icon)"
                  class="size-4 shrink-0 text-muted-foreground"
                />
                <span class="min-w-0 flex-1 truncate font-medium">{{
                  group.title || 'Untitled group'
                }}</span>
                <Badge v-if="group.is_active === false" variant="outline">Hidden</Badge>
              </button>

              <div class="ml-4 space-y-1 border-l pl-2">
                <button
                  v-for="(linkItem, linkIndex) in group.items"
                  :key="`link-outline-${groupIndex}-${linkIndex}`"
                  type="button"
                  draggable="true"
                  class="flex w-full items-center gap-2 rounded px-3 py-1.5 text-left text-sm transition hover:bg-muted/60"
                  :class="
                    selected.type === 'link' &&
                    selected.groupIndex === groupIndex &&
                    selected.linkIndex === linkIndex
                      ? 'bg-primary/10 text-primary'
                      : 'text-muted-foreground'
                  "
                  @click="selectLink(groupIndex, linkIndex)"
                  @dragstart="startDrag($event, { type: 'link', groupIndex, linkIndex })"
                  @dragend="dragging = null"
                  @dragover.prevent
                  @drop.prevent="dropOnLink(groupIndex, linkIndex)"
                >
                  <component :is="iconFor(linkItem.icon)" class="size-3.5 shrink-0" />
                  <span class="min-w-0 flex-1 truncate">{{
                    linkItem.title || 'Untitled link'
                  }}</span>
                  <EyeOff v-if="linkItem.is_active === false" class="size-3.5 shrink-0" />
                </button>

                <button
                  type="button"
                  class="flex w-full items-center gap-2 rounded px-3 py-1.5 text-left text-xs font-medium text-muted-foreground transition hover:bg-muted/60 hover:text-foreground"
                  @click="addLink(groupIndex)"
                  @dragover.prevent
                  @drop.prevent="dropOnGroup(groupIndex)"
                >
                  <Plus class="size-3.5" />
                  Add link
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="rounded border bg-background">
          <div
            class="flex flex-col gap-3 border-b p-4 md:flex-row md:items-center md:justify-between"
          >
            <div>
              <h3 class="text-base font-semibold">
                {{ selectedIsLink ? 'Edit Link' : 'Edit Group' }}
              </h3>
              <p class="text-sm text-muted-foreground">
                {{
                  selectedIsLink
                    ? 'Set the link label, route, icon, and visibility.'
                    : 'Set the group label, icon, behavior, and child links.'
                }}
              </p>
            </div>

            <div v-if="selectedItem" class="flex flex-wrap gap-2">
              <Button
                v-if="selected.type === 'group'"
                type="button"
                size="icon-sm"
                variant="navigate"
                :disabled="selected.groupIndex === 0"
                aria-label="Move group up"
                @click="moveGroup(selected.groupIndex, selected.groupIndex - 1)"
              >
                <ChevronUp class="size-4" />
              </Button>
              <Button
                v-if="selected.type === 'group'"
                type="button"
                size="icon-sm"
                variant="navigate"
                :disabled="selected.groupIndex === groups.length - 1"
                aria-label="Move group down"
                @click="moveGroup(selected.groupIndex, selected.groupIndex + 1)"
              >
                <ChevronDown class="size-4" />
              </Button>
              <Button
                v-if="selectedIsLink"
                type="button"
                size="icon-sm"
                variant="navigate"
                :disabled="!selectedLinkCanMoveUp"
                aria-label="Move link up"
                @click="moveSelectedLink(-1)"
              >
                <ChevronUp class="size-4" />
              </Button>
              <Button
                v-if="selectedIsLink"
                type="button"
                size="icon-sm"
                variant="navigate"
                :disabled="!selectedLinkCanMoveDown"
                aria-label="Move link down"
                @click="moveSelectedLink(1)"
              >
                <ChevronDown class="size-4" />
              </Button>
            </div>
          </div>

          <div v-if="!selectedItem" class="p-8 text-center">
            <h3 class="text-base font-semibold">Nothing selected</h3>
            <p class="mt-2 text-sm text-muted-foreground">
              Add or select a group to edit the sidebar.
            </p>
            <Button type="button" variant="create" class="mt-4" @click="addGroup">
              <Plus class="size-4" />
              Add Group
            </Button>
          </div>

          <div v-else class="space-y-5 p-4">
            <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_12rem]">
              <Field>
                <FieldLabel for="selected-title">Title</FieldLabel>
                <Input
                  id="selected-title"
                  v-model="selectedItem.title"
                  placeholder="Navigation label"
                />
              </Field>

              <Field>
                <FieldLabel for="selected-icon">Icon</FieldLabel>
                <NativeSelect id="selected-icon" v-model="selectedItem.icon">
                  <option v-for="icon in iconNames" :key="icon" :value="icon">{{ icon }}</option>
                </NativeSelect>
              </Field>
            </div>

            <Field>
              <FieldLabel for="selected-url">Route or URL</FieldLabel>
              <Input
                id="selected-url"
                v-model="selectedItem.url"
                list="admin-routes"
                :placeholder="selectedIsLink ? 'dashboard' : '#'"
              />
            </Field>

            <div class="rounded border bg-muted/30 p-3">
              <p class="mb-2 text-xs font-medium text-muted-foreground">Known admin routes</p>
              <div class="flex flex-wrap gap-2">
                <Button
                  v-for="route in routeSuggestions"
                  :key="route"
                  type="button"
                  variant="outline"
                  size="xs"
                  @click="applyRouteSuggestion(route)"
                >
                  {{ route }}
                </Button>
              </div>
            </div>

            <Field>
              <FieldLabel for="selected-description">Description</FieldLabel>
              <Input
                id="selected-description"
                v-model="selectedItem.description"
                placeholder="Optional internal note"
              />
            </Field>

            <label class="flex items-center justify-between gap-4 rounded border p-3 text-sm">
              <span>
                <span class="block font-medium">Visible in admin sidebar</span>
                <span class="block text-xs text-muted-foreground"
                  >Hidden items stay saved but do not show in navigation.</span
                >
              </span>
              <Checkbox
                :model-value="selectedItem.is_active !== false"
                @update:model-value="updateSelectedVisibility"
              />
            </label>

            <div v-if="!selectedIsLink && selectedGroup" class="rounded border p-3">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h4 class="text-sm font-semibold">Links in this group</h4>
                  <p class="text-xs text-muted-foreground">
                    {{ selectedGroup.items.length || 'No' }} links added.
                  </p>
                </div>
                <Button
                  type="button"
                  size="sm"
                  variant="outline"
                  @click="addLink(selected.groupIndex)"
                >
                  <Plus class="size-4" />
                  Add Link
                </Button>
              </div>
            </div>
          </div>

          <datalist id="admin-routes">
            <option v-for="route in routeSuggestions" :key="route" :value="route" />
          </datalist>
        </section>

        <aside class="space-y-4">
          <Card>
            <CardHeader>
              <div class="flex items-center justify-between gap-3">
                <CardTitle class="text-base">Preview</CardTitle>
                <Button
                  type="button"
                  size="sm"
                  variant="navigate"
                  @click="previewMode = previewMode === 'expanded' ? 'collapsed' : 'expanded'"
                >
                  <component :is="previewMode === 'expanded' ? EyeOff : Eye" class="size-4" />
                  {{ previewMode === 'expanded' ? 'Collapse' : 'Expand' }}
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <div
                class="rounded border bg-sidebar text-sidebar-foreground transition-all"
                :class="previewMode === 'collapsed' ? 'w-14' : 'w-full'"
              >
                <div class="border-b p-3">
                  <div class="flex items-center gap-2">
                    <div
                      class="flex size-8 items-center justify-center rounded bg-teal-600 text-white"
                    >
                      <component :is="iconFor(sidebar?.data.teams?.[0]?.logo)" class="size-4" />
                    </div>
                    <div v-if="previewMode === 'expanded'" class="min-w-0">
                      <p class="truncate text-sm font-medium">
                        {{ sidebar?.data.teams?.[0]?.name || 'Admin' }}
                      </p>
                      <p class="truncate text-xs text-muted-foreground">
                        {{ sidebar?.data.teams?.[0]?.plan || 'Control Center' }}
                      </p>
                    </div>
                  </div>
                </div>

                <div class="space-y-2 p-3">
                  <p
                    v-if="previewMode === 'expanded'"
                    class="text-xs font-medium text-muted-foreground"
                  >
                    Navigation
                  </p>
                  <template v-for="(group, groupIndex) in groups" :key="`preview-${groupIndex}`">
                    <div v-if="group.is_active !== false" class="space-y-1">
                      <div
                        class="flex items-center gap-2 rounded px-2 py-2 text-sm hover:bg-sidebar-accent"
                      >
                        <component :is="iconFor(group.icon)" class="size-4 shrink-0" />
                        <span v-if="previewMode === 'expanded'" class="truncate">{{
                          group.title || 'Untitled group'
                        }}</span>
                        <Badge
                          v-if="previewMode === 'expanded' && group.items.length"
                          variant="outline"
                          class="ml-auto"
                        >
                          {{
                            group.items.filter((linkItem) => linkItem.is_active !== false).length
                          }}
                        </Badge>
                      </div>
                      <div
                        v-if="previewMode === 'expanded' && group.items.length"
                        class="ml-6 space-y-1 border-l pl-2"
                      >
                        <div
                          v-for="(linkItem, linkIndex) in group.items.filter(
                            (linkItem) => linkItem.is_active !== false
                          )"
                          :key="`preview-${groupIndex}-${linkIndex}`"
                          class="flex items-center gap-2 rounded px-2 py-1.5 text-sm text-muted-foreground"
                        >
                          <component :is="iconFor(linkItem.icon)" class="size-3.5 shrink-0" />
                          <span class="truncate">{{ linkItem.title || 'Untitled link' }}</span>
                        </div>
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle class="text-base">Checks</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
              <div
                v-if="!errors.length && !warnings.length && !sidebarDataError && !saveError"
                class="rounded border bg-emerald-50 p-3 text-sm text-emerald-800"
              >
                {{ saveSuccess || 'Navigation looks ready to save.' }}
              </div>
              <div
                v-for="issue in errors"
                :key="issue.key"
                class="flex gap-2 rounded border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
              >
                <AlertCircle class="mt-0.5 size-4 shrink-0" />
                <span>{{ issue.message }}</span>
              </div>
              <div
                v-for="issue in warnings"
                :key="issue.key"
                class="flex gap-2 rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-800"
              >
                <AlertCircle class="mt-0.5 size-4 shrink-0" />
                <span>{{ issue.message }}</span>
              </div>
              <div
                v-if="sidebarDataError"
                class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
              >
                {{ sidebarDataError }}
              </div>
              <div
                v-if="saveError"
                class="rounded border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
              >
                {{ saveError }}
              </div>

              <Separator />

              <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Link class="size-3.5" />
                Use # for a collapsible group with child links.
              </div>
            </CardContent>
          </Card>
        </aside>
      </div>
    </div>
  </div>
</template>
