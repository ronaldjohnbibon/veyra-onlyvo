<script setup lang="ts">
import type { AlertDialogActionProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { AlertDialogAction } from 'reka-ui'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

type ButtonVariant =
  | 'default'
  | 'destructive'
  | 'outline'
  | 'secondary'
  | 'ghost'
  | 'link'
  | 'create'
  | 'update'
  | 'delete'

type ButtonSize = 'default' | 'sm' | 'lg' | 'icon' | 'icon-sm' | 'icon-lg'

const props = defineProps<
  AlertDialogActionProps & {
    variant?: ButtonVariant
    size?: ButtonSize
    class?: HTMLAttributes['class']
  }
>()

const delegatedProps = reactiveOmit(props, 'class', 'variant', 'size')
</script>

<template>
  <AlertDialogAction
    v-bind="delegatedProps"
    :class="
      cn(
        buttonVariants({
          variant: props.variant,
          size: props.size,
        }),
        props.class
      )
    "
  >
    <slot />
  </AlertDialogAction>
</template>
