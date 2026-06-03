<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, useSlots } from 'vue'
import { CircleHelp } from 'lucide-vue-next'
import { useFieldDescriptions } from '@/shared/composables/useFieldDescriptions'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/shared/components/ui/tooltip'
import { cn } from '@/shared/utils/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
  description?: string | null
  helpClass?: HTMLAttributes['class']
}>()

const { showFieldDescriptions } = useFieldDescriptions()
const slots = useSlots()
const hasDescription = computed(() => Boolean(props.description?.trim()))
const hasDefaultSlot = computed(() => Boolean(slots.default))
</script>

<template>
  <div :class="cn(hasDefaultSlot ? 'relative' : 'inline-flex items-center', props.class)">
    <slot />

    <TooltipProvider v-if="showFieldDescriptions && hasDescription" :delay-duration="150">
      <Tooltip>
        <TooltipTrigger as-child>
          <button
            type="button"
            aria-label="Field help"
            :class="
              cn(
                'inline-flex size-4 shrink-0 items-center justify-center rounded-full text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                hasDefaultSlot ? 'absolute -right-2 -top-2 z-10 bg-background/90' : ''
              )
            "
          >
            <CircleHelp class="size-3.5" aria-hidden="true" />
          </button>
        </TooltipTrigger>
        <TooltipContent :class="cn('max-w-64 leading-snug', props.helpClass)">
          {{ props.description }}
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  </div>
</template>
