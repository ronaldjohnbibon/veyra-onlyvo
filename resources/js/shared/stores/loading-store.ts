import { defineStore } from 'pinia'
import { computed, ref } from 'vue'

export const useLoadingStore = defineStore('loading', () => {
  const activeTransactions = ref(0)

  const start = (): void => {
    activeTransactions.value += 1
  }

  const stop = (): void => {
    activeTransactions.value = Math.max(0, activeTransactions.value - 1)
  }

  // Tracks standalone transactions that do not have their own module store.
  const run = async <T>(transaction: () => Promise<T>): Promise<T> => {
    start()

    try {
      return await transaction()
    } finally {
      stop()
    }
  }

  const anyLoading = computed(() => activeTransactions.value > 0)

  const states = computed(() => ({
    activeTransactions: activeTransactions.value,
  }))

  return { anyLoading, run, states, start, stop }
})
