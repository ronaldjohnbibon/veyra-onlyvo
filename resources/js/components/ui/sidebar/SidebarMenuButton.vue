<script setup lang="ts">
import type { Component } from 'vue'
import type { SidebarMenuButtonProps } from './SidebarMenuButtonChild.vue'
import { reactiveOmit } from '@vueuse/core'
import { computed } from 'vue'
import SidebarMenuButtonChild from './SidebarMenuButtonChild.vue'
import { useSidebar } from './utils'

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(
  defineProps<
    SidebarMenuButtonProps & {
      tooltip?: string | Component
    }
  >(),
  {
    as: 'button',
    variant: 'default',
    size: 'default',
  }
)

const { isMobile, state } = useSidebar()

const delegatedProps = reactiveOmit(props, 'tooltip')

const tooltipTitle = computed(() => {
  if (typeof props.tooltip !== 'string') return undefined

  return state.value === 'collapsed' && !isMobile.value ? props.tooltip : undefined
})
</script>

<template>
  <SidebarMenuButtonChild v-bind="{ ...delegatedProps, ...$attrs }" :title="tooltipTitle">
    <slot />
  </SidebarMenuButtonChild>
</template>
