<script setup lang="ts">
import { computed } from 'vue'
import {
  CircleCheckIcon,
  InfoIcon,
  OctagonXIcon,
  TriangleAlertIcon,
  type LucideIcon,
} from 'lucide-vue-next'
import type { AlertType } from '@/shared/stores/toast-store'

interface AlertProps {
  variant: string
  icon: LucideIcon
}

const props = withDefaults(
  defineProps<{
    type?: AlertType
    title: string
    message: string
    duration?: number
    showProgress?: boolean
  }>(),
  {
    type: 'success',
    duration: 3000,
    showProgress: true,
  }
)

const typeMap: Record<AlertType, AlertProps> = {
  success: {
    variant: 'toast-success',
    icon: CircleCheckIcon,
  },
  info: {
    variant: 'toast-info',
    icon: InfoIcon,
  },
  warning: {
    variant: 'toast-warning',
    icon: TriangleAlertIcon,
  },
  error: {
    variant: 'toast-error',
    icon: OctagonXIcon,
  },
}

const toastVariant = computed(() => typeMap[props.type].variant)
const ToastIcon = computed(() => typeMap[props.type].icon)
</script>

<template>
  <Teleport to="body">
    <div class="toast-container" :class="toastVariant">
      <div class="toast-icon-wrapper">
        <component :is="ToastIcon" class="toast-icon" />
      </div>

      <div class="toast-content">
        <div class="toast-header">
          <span class="toast-title">{{ title }}</span>
        </div>
        <p class="toast-message">{{ message }}</p>
      </div>

      <div
        v-if="showProgress"
        class="toast-progress-bar"
        :style="{ animationDuration: `${duration}ms` }"
      />
    </div>
  </Teleport>
</template>

<style scoped>
.toast-container {
  position: fixed;
  top: 1.25rem;
  right: 1.25rem;
  display: flex;
  width: calc(100% - 2.5rem);
  max-width: 24rem;
  overflow: hidden;
  border: 1px solid var(--border);
  border-radius: var(--radius);
  background: var(--card);
  box-shadow:
    0 10px 15px -3px rgb(0 0 0 / 0.1),
    0 4px 6px -4px rgb(0 0 0 / 0.1),
    0 0 0 1px rgb(0 0 0 / 0.05);
  z-index: 80;
  animation: slideInRight 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  backdrop-filter: blur(8px);
}

.toast-container:hover {
  box-shadow:
    0 20px 25px -5px rgb(0 0 0 / 0.1),
    0 8px 10px -6px rgb(0 0 0 / 0.1),
    0 0 0 1px rgb(0 0 0 / 0.05);
  transform: translateY(-2px);
  transition: all 0.2s ease;
}

.toast-icon-wrapper {
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 1rem 0 1rem 1rem;
}

.toast-icon {
  width: 1.5rem;
  height: 1.5rem;
}

.toast-content {
  flex: 1;
  padding: 1rem 1rem 1rem 0.75rem;
}

.toast-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.25rem;
}

.toast-title {
  color: var(--foreground);
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.25rem;
}

.toast-message {
  margin: 0;
  color: var(--muted-foreground);
  font-size: 0.8125rem;
  line-height: 1.375rem;
}

.toast-progress-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  width: 100%;
  transform-origin: left;
  animation-name: shrink;
  animation-timing-function: linear;
  animation-fill-mode: forwards;
}

.toast-success .toast-icon {
  color: #10b981;
}

.toast-success .toast-progress-bar {
  background: linear-gradient(to right, #10b981, #059669);
}

.toast-info .toast-icon {
  color: #3b82f6;
}

.toast-info .toast-progress-bar {
  background: linear-gradient(to right, #3b82f6, #2563eb);
}

.toast-warning .toast-icon {
  color: #f59e0b;
}

.toast-warning .toast-progress-bar {
  background: linear-gradient(to right, #f59e0b, #d97706);
}

.toast-error .toast-icon {
  color: #ef4444;
}

.toast-error .toast-progress-bar {
  background: linear-gradient(to right, #ef4444, #dc2626);
}

@keyframes slideInRight {
  from {
    opacity: 0;
    transform: translateX(100%);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

@keyframes shrink {
  from {
    transform: scaleX(1);
  }

  to {
    transform: scaleX(0);
  }
}
</style>
