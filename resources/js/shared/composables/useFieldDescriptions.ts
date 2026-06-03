import { computed, ref } from 'vue'

type RuntimeSettingValue = string | number | boolean | null

const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, RuntimeSettingValue>
    }
  ).__SYSTEM_SETTINGS__ ?? {}

const visible = ref(runtimeSettings['general.show_field_descriptions'] !== false)

export const useFieldDescriptions = () => {
  const showFieldDescriptions = computed(() => visible.value)

  const setShowFieldDescriptions = (value: RuntimeSettingValue | undefined): void => {
    // Keep shared field help in sync with the persisted system setting.
    visible.value = value !== false
  }

  return {
    setShowFieldDescriptions,
    showFieldDescriptions,
  }
}
