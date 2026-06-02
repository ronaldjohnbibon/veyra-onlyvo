import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useConfirmStore = defineStore('confirm', () => {
  const visible = ref(false)
  const message = ref('')
  const resolve = ref<((value: boolean) => void) | null>(null)

  const confirm = (msg: string): Promise<boolean> => {
    if (visible.value) return Promise.resolve(false)

    message.value = msg
    visible.value = true

    return new Promise<boolean>((res) => {
      resolve.value = res
    })
  }

  const accept = (): void => {
    visible.value = false
    resolve.value?.(true)
    resolve.value = null
  }

  const cancel = (): void => {
    visible.value = false
    resolve.value?.(false)
    resolve.value = null
  }

  return {
    visible,
    message,
    confirm,
    accept,
    cancel,
  }
})
