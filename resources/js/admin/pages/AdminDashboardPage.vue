<script setup lang="ts">
import { Badge } from '@/shared/components/ui/badge'
import { Button } from '@/shared/components/ui/button'
import { getStatusBadgeVariant } from '@/shared/utils/status'
import { useAdminAuthStore } from '../admin-auth-store'

const adminAuthStore = useAdminAuthStore()
</script>

<template>
  <div class="flex flex-1 flex-col gap-2 p-4">
    <div class="min-h-screen rounded pb-28">
      <div class="module-heading-container">
        <div>
          <h2 class="module-container-title">Admin Dashboard</h2>
          <p class="module-container-description">Manage your platform admin session.</p>
        </div>
      </div>

      <div class="rounded border bg-background p-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div>
            <p class="text-sm font-semibold">
              {{ adminAuthStore.user?.name || adminAuthStore.user?.email }}
            </p>
            <p class="text-sm text-muted-foreground">Your admin session is active.</p>
          </div>
          <Badge :variant="getStatusBadgeVariant('signed_in')">Signed in</Badge>
        </div>
      </div>

      <div class="fixed bottom-6 right-6 flex w-[100px] flex-col gap-2">
        <Button
          variant="delete"
          size="sm"
          type="button"
          class="w-full rounded shadow-lg"
          :disabled="adminAuthStore.loading"
          @click="adminAuthStore.logout"
        >
          {{ adminAuthStore.loading ? 'Logout...' : 'Logout' }}
        </Button>
      </div>
    </div>
  </div>
</template>
