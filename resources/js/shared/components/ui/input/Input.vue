<script setup lang="ts">
import { computed, useAttrs } from 'vue'
import type { HTMLAttributes } from 'vue'
import { useVModel } from '@vueuse/core'
import { cn } from '@/shared/utils/utils'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number | null
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const attrs = useAttrs()
const isFileInput = computed(() => attrs.type === 'file')
const inputClass = computed(() =>
  cn(
    'flex rounded border border-input bg-background px-2 py-1.5 text-sm outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:ring-offset-1',
    props.class
  )
)
</script>

<template>
  <input v-if="isFileInput" data-slot="input" :class="inputClass" />
  <input v-else v-model="modelValue" data-slot="input" :class="inputClass" />
</template>
