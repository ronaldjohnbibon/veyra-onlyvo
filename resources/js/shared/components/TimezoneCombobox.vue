<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import { Input } from '@/shared/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/shared/components/ui/popover'
import { Check, ChevronsUpDown, Search } from 'lucide-vue-next'
import { computed, nextTick, ref, watch } from 'vue'

const props = withDefaults(
  defineProps<{
    id?: string
    modelValue?: string
    placeholder?: string
    help?: string
  }>(),
  {
    id: undefined,
    modelValue: '',
    placeholder: 'Select a timezone',
    help: '',
  }
)

const emit = defineEmits<{
  (event: 'update:modelValue', value: string): void
}>()

const open = ref(false)
const search = ref('')
const searchInput = ref<HTMLInputElement | null>(null)

const supportedTimezones = (() => {
  try {
    return Intl.supportedValuesOf('timeZone')
  } catch {
    return []
  }
})()

const timezones = computed(() => {
  return Array.from(new Set(['UTC', props.modelValue, ...supportedTimezones].filter(Boolean))).sort(
    (left, right) => {
      if (left === 'UTC') return -1
      if (right === 'UTC') return 1

      return left.localeCompare(right)
    }
  )
})

const filteredTimezones = computed(() => {
  const query = search.value.trim().toLocaleLowerCase()

  if (!query) return timezones.value

  return timezones.value.filter((timezone) => {
    const searchableTimezone = timezone
      .replaceAll('_', ' ')
      .replaceAll('/', ' ')
      .toLocaleLowerCase()

    return timezone.toLocaleLowerCase().includes(query) || searchableTimezone.includes(query)
  })
})

const selectTimezone = (timezone: string): void => {
  emit('update:modelValue', timezone)
  open.value = false
}

const selectFirstResult = (): void => {
  const firstTimezone = filteredTimezones.value[0]

  if (firstTimezone) selectTimezone(firstTimezone)
}

watch(open, async (isOpen) => {
  if (!isOpen) {
    search.value = ''
    return
  }

  await nextTick()
  searchInput.value?.focus()
})
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button
        v-field-help="help"
        :id="id"
        type="button"
        variant="outline"
        role="combobox"
        :aria-expanded="open"
        class="w-full justify-between font-normal"
      >
        <span class="truncate">{{ modelValue || placeholder }}</span>
        <ChevronsUpDown class="ml-2 size-4 shrink-0 opacity-50" />
      </Button>
    </PopoverTrigger>

    <PopoverContent align="start" class="w-(--reka-popover-trigger-width) p-0">
      <div class="flex items-center border-b px-3">
        <Search class="mr-2 size-4 shrink-0 opacity-50" />
        <Input
          ref="searchInput"
          v-model="search"
          type="search"
          placeholder="Search timezones..."
          aria-label="Search timezones"
          class="h-10 w-full border-0 px-0 shadow-none focus-visible:ring-0"
          @keydown.enter.prevent="selectFirstResult"
        />
      </div>

      <div class="max-h-64 overflow-y-auto p-1">
        <button
          v-for="timezone in filteredTimezones"
          :key="timezone"
          type="button"
          class="relative flex w-full cursor-default items-center rounded px-8 py-2 text-left text-sm outline-none hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground"
          @click="selectTimezone(timezone)"
        >
          <Check
            class="absolute left-2 size-4"
            :class="timezone === modelValue ? 'opacity-100' : 'opacity-0'"
          />
          {{ timezone }}
        </button>

        <p
          v-if="filteredTimezones.length === 0"
          class="px-3 py-6 text-center text-sm text-muted-foreground"
        >
          No timezone found.
        </p>
      </div>
    </PopoverContent>
  </Popover>
</template>
