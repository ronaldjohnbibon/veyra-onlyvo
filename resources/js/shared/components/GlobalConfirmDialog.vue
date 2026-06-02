<script setup lang="ts">
import { useConfirmStore } from '@/shared/stores/confirm-store'
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/shared/components/ui/alert-dialog'

const confirmStore = useConfirmStore()
</script>

<template>
  <AlertDialog :open="confirmStore.visible">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Confirm Action</AlertDialogTitle>
        <AlertDialogDescription>
          {{ confirmStore.message }}
        </AlertDialogDescription>
      </AlertDialogHeader>

      <AlertDialogFooter>
        <AlertDialogCancel @click="confirmStore.cancel">Cancel</AlertDialogCancel>
        <AlertDialogAction variant="delete" @click="confirmStore.accept">
          Continue
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
