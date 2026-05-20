<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded border border-border/60 py-6 shadow-lg shadow-black/5 hover:shadow-xl hover:shadow-black/10 transition-all duration-300',
        props.class
      )
    "
  >
    <slot />
  </div>
</template>
