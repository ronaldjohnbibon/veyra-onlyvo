<script setup lang="ts">
import type { AlertDialogActionProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { AlertDialogAction } from 'reka-ui'
import { cn } from '@/shared/utils/utils'
import { buttonVariants, type ButtonVariants } from '@/shared/components/ui/button'

const props = defineProps<
  AlertDialogActionProps & {
    variant?: ButtonVariants['variant']
    size?: ButtonVariants['size']
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
