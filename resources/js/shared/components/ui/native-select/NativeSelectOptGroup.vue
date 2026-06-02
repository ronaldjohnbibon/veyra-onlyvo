<!-- @fallthroughAttributes true -->
<!-- @strictTemplates true -->

<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/shared/utils/utils'

const props = defineProps<{ class?: HTMLAttributes['class'] }>()
const dataAttrs: Record<string, string> = {
  'data-slot': 'native-select-optgroup',
}
</script>

<template>
  <optgroup v-bind="dataAttrs" :class="cn('bg-popover text-popover-foreground', props.class)">
    <slot />
  </optgroup>
</template>
