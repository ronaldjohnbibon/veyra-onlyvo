import { defineStore } from 'pinia'
import { ref } from 'vue'

export type AlertType = 'success' | 'error' | 'warning' | 'info'

interface Alert {
  id: number
  type: AlertType
  title: string
  message: string
}

export const useToastStore = defineStore('toastStore', () => {
  const alerts = ref<Alert[]>([])

  const addAlert = (type: AlertType, title: string, message: string): void => {
    const id = Date.now()
    alerts.value.push({ id, type, title, message })

    window.setTimeout(() => {
      alerts.value = alerts.value.filter((alert) => alert.id !== id)
    }, 3000)
  }

  return { alerts, addAlert }
})
