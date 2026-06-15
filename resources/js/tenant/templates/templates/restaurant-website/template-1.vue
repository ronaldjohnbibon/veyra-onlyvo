<script setup lang="ts">
import type { TemplateRecord } from '@/shared/types/templates'
import { safeHref } from '@/shared/utils/url'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

const content = computed(() => props.template.content ?? {})

const businessName = computed(() => {
  return String(content.value.business_name ?? props.template.business_name)
})

const menuItems = computed(() => {
  const values = content.value.menu_items

  return Array.isArray(values) && values.length
    ? values
    : [
        {
          name: 'Seasonal tasting plate',
          description: 'Seasonal ingredients, careful preparation, and a clear place for pricing.',
          price: '',
        },
        {
          name: 'House pasta',
          description: 'Seasonal ingredients, careful preparation, and a clear place for pricing.',
          price: '',
        },
        {
          name: 'Signature dessert',
          description: 'Seasonal ingredients, careful preparation, and a clear place for pricing.',
          price: '',
        },
      ]
})

const hours = computed(() => {
  const values = content.value.hours

  return Array.isArray(values) ? values : []
})

const contact = computed(() => ({
  email: String(content.value.contact_email ?? props.template.contact_info.email ?? ''),
  phone: String(content.value.contact_phone ?? props.template.contact_info.phone ?? ''),
  location: String(content.value.location ?? props.template.contact_info.address ?? ''),
}))

const socialLinks = computed(() => {
  // Show social links only when the tenant has provided a URL.
  return Object.entries(props.template.social_links ?? {}).filter(([, value]) => value)
})
</script>

<template>
  <div class="min-h-screen bg-[var(--template-bg)] text-[var(--template-text)]">
    <header class="border-b border-black/10 px-6 py-5">
      <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
        <a href="#top" class="flex items-center gap-3">
          <img :src="template.logo" :alt="businessName" class="size-11 rounded object-cover" />
          <span class="font-semibold">{{ businessName }}</span>
        </a>
        <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
          <a href="#menu" class="hover:text-[var(--template-primary)]">Menu</a>
          <a href="#visit" class="hover:text-[var(--template-primary)]">Visit</a>
          <a href="#booking" class="hover:text-[var(--template-primary)]">Booking</a>
        </nav>
      </div>
    </header>

    <section id="top" class="px-6 py-16 md:py-24">
      <div class="mx-auto grid max-w-6xl gap-10 md:grid-cols-[0.95fr_1.05fr] md:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">
            {{ content.hero_eyebrow ?? 'Fresh daily' }}
          </p>
          <h1 class="mt-4 text-4xl font-bold leading-tight md:text-6xl">
            {{
              content.hero_title ??
              'A complete restaurant website for menus, bookings, and local discovery.'
            }}
          </h1>
          <p class="mt-5 text-lg leading-8 opacity-75">
            {{
              content.hero_text ??
              `${businessName} can showcase signature dishes, opening details, and contact information in one polished layout.`
            }}
          </p>
        </div>
        <div class="rounded border border-black/10 bg-black/[0.03] p-6">
          <p class="text-sm font-semibold text-[var(--template-primary)]">Today at a glance</p>
          <p class="mt-5 text-3xl font-bold">Dinner, drinks, and warm service.</p>
          <p class="mt-4 leading-7 opacity-75">{{ contact.location }}</p>
        </div>
      </div>
    </section>

    <section id="menu" class="border-y border-black/10 bg-white px-6 py-14">
      <div class="mx-auto max-w-6xl">
        <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
          <div>
            <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">Menu</p>
            <h2 class="mt-3 text-3xl font-bold">House favorites</h2>
          </div>
          <p class="max-w-md text-sm leading-6 opacity-70">
            {{
              content.menu_intro ??
              'Highlight a tight set of dishes and invite guests to book or call.'
            }}
          </p>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <article
            v-for="(item, index) in menuItems"
            :key="String(item.name ?? index)"
            class="rounded border border-black/10 p-5"
          >
            <div class="flex items-start justify-between gap-3">
              <h3 class="text-lg font-semibold">{{ item.name }}</h3>
              <span v-if="item.price" class="text-sm font-semibold text-[var(--template-primary)]">
                {{ item.price }}
              </span>
            </div>
            <p class="mt-3 text-sm leading-6 opacity-70">
              {{ item.description }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="visit" class="px-6 py-14">
      <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-2">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">Visit</p>
          <h2 class="mt-3 text-3xl font-bold">Easy details for hungry guests.</h2>
        </div>
        <div class="rounded border border-black/10 p-5">
          <p>{{ contact.location }}</p>
          <p class="mt-2">{{ contact.phone }}</p>
          <p class="mt-2">{{ contact.email }}</p>
          <div v-if="hours.length" class="mt-4 space-y-2 border-t border-black/10 pt-4">
            <p
              v-for="(hour, index) in hours"
              :key="String(hour.day ?? index)"
              class="flex justify-between gap-4 text-sm"
            >
              <span>{{ hour.day }}</span>
              <span class="font-medium">{{ hour.time }}</span>
            </p>
          </div>
        </div>
      </div>
    </section>

    <footer id="booking" class="bg-[var(--template-secondary)] px-6 py-12 text-white">
      <div
        class="mx-auto flex max-w-6xl flex-col justify-between gap-6 md:flex-row md:items-center"
      >
        <div>
          <h2 class="text-3xl font-bold">Reserve a table with {{ businessName }}.</h2>
          <p class="mt-3 opacity-80">Call {{ contact.phone }} or send a booking request.</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <a
            v-if="content.reservation_link"
            :href="safeHref(content.reservation_link)"
            class="rounded border border-white/30 px-4 py-2 text-sm"
          >
            Reservations
          </a>
          <a
            v-for="[name, url] in socialLinks"
            :key="name"
            :href="safeHref(url)"
            class="rounded border border-white/30 px-4 py-2 text-sm"
          >
            {{ name }}
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>
