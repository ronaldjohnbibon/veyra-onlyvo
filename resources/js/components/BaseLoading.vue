<script setup lang="ts">
import { useLoadingStore } from '@/store/loading-store'

const loadingStore = useLoadingStore()
</script>

<template>
  <Transition
    enter-active-class="transition-opacity duration-200 ease-out"
    leave-active-class="transition-opacity duration-150 ease-in"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="loadingStore.anyLoading"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/20 backdrop-blur-sm"
    >
      <div class="dot-loader text-primary" aria-label="Loading" role="status">
        <span class="dot"></span>
        <span class="dot"></span>
        <span class="dot"></span>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.dot-loader {
  display: flex;
  gap: 0.6rem;
  align-items: center;
  justify-content: center;
}

.dot {
  display: inline-block;
  width: 0.6rem;
  height: 0.6rem;
  border-radius: 9999px;
  background: currentColor;
  animation: dotFade 1.4s infinite ease-in-out both;
}

.dot:nth-child(1) {
  animation-delay: 0s;
}

.dot:nth-child(2) {
  animation-delay: 0.2s;
}

.dot:nth-child(3) {
  animation-delay: 0.4s;
}

@keyframes dotFade {
  0%,
  80%,
  100% {
    opacity: 0.2;
    transform: translateY(0);
  }

  40% {
    opacity: 1;
    transform: translateY(-3px);
  }
}
</style>
