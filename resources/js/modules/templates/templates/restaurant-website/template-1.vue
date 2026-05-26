<script setup lang="ts">
import type { TemplateRecord } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

const menuItems = ['Seasonal tasting plate', 'House pasta', 'Signature dessert']

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
          <img
            :src="template.logo"
            :alt="template.business_name"
            class="size-11 rounded object-cover"
          />
          <span class="font-semibold">{{ template.business_name }}</span>
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
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">Fresh daily</p>
          <h1 class="mt-4 text-4xl font-bold leading-tight md:text-6xl">
            A complete restaurant website for menus, bookings, and local discovery.
          </h1>
          <p class="mt-5 text-lg leading-8 opacity-75">
            {{ template.business_name }} can showcase signature dishes, opening details, and contact
            information in one polished layout.
          </p>
        </div>
        <div class="rounded border border-black/10 bg-black/[0.03] p-6">
          <p class="text-sm font-semibold text-[var(--template-primary)]">Today at a glance</p>
          <p class="mt-5 text-3xl font-bold">Dinner, drinks, and warm service.</p>
          <p class="mt-4 leading-7 opacity-75">{{ template.contact_info.address }}</p>
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
            Highlight a tight set of dishes and invite guests to book or call.
          </p>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <article v-for="item in menuItems" :key="item" class="rounded border border-black/10 p-5">
            <h3 class="text-lg font-semibold">{{ item }}</h3>
            <p class="mt-3 text-sm leading-6 opacity-70">
              Seasonal ingredients, careful preparation, and a clear place for pricing.
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
          <p>{{ template.contact_info.address }}</p>
          <p class="mt-2">{{ template.contact_info.phone }}</p>
          <p class="mt-2">{{ template.contact_info.email }}</p>
        </div>
      </div>
    </section>

    <footer id="booking" class="bg-[var(--template-secondary)] px-6 py-12 text-white">
      <div
        class="mx-auto flex max-w-6xl flex-col justify-between gap-6 md:flex-row md:items-center"
      >
        <div>
          <h2 class="text-3xl font-bold">Reserve a table with {{ template.business_name }}.</h2>
          <p class="mt-3 opacity-80">
            Call {{ template.contact_info.phone }} or send a booking request.
          </p>
        </div>
        <div class="flex flex-wrap gap-3">
          <a
            v-for="[name, url] in socialLinks"
            :key="name"
            :href="String(url)"
            class="rounded border border-white/30 px-4 py-2 text-sm"
          >
            {{ name }}
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>
