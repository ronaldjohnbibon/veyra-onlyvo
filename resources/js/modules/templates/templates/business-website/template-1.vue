<script setup lang="ts">
import type { TemplateRecord } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

const services = ['Brand strategy', 'Website design', 'Growth consulting']
const stats = ['12+ years', '240 projects', '98% client retention']

const socialLinks = computed(() => {
  // Show social links only when the tenant has provided a URL.
  return Object.entries(props.template.social_links ?? {}).filter(([, value]) => value)
})
</script>

<template>
  <div class="min-h-screen bg-[var(--template-bg)] text-[var(--template-text)]">
    <header class="border-b border-black/10 bg-white/80 px-6 py-4 backdrop-blur">
      <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
        <a href="#top" class="flex items-center gap-3">
          <img
            :src="template.logo"
            :alt="template.business_name"
            class="size-10 rounded object-cover"
          />
          <span class="text-base font-semibold">{{ template.business_name }}</span>
        </a>
        <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
          <a href="#services" class="hover:text-[var(--template-primary)]">Services</a>
          <a href="#proof" class="hover:text-[var(--template-primary)]">Proof</a>
          <a href="#contact" class="hover:text-[var(--template-primary)]">Contact</a>
        </nav>
      </div>
    </header>

    <section id="top" class="px-6 py-16 md:py-24">
      <div class="mx-auto grid max-w-6xl gap-10 md:grid-cols-[1.1fr_0.9fr] md:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">
            Practical digital systems
          </p>
          <h1 class="mt-4 max-w-3xl text-4xl font-bold leading-tight md:text-6xl">
            A complete business website for teams ready to look sharper online.
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 opacity-75">
            {{ template.business_name }} helps customers understand what you do, why it matters, and
            how to start a conversation.
          </p>
          <div class="mt-8 flex flex-wrap gap-3">
            <a
              href="#contact"
              class="rounded bg-[var(--template-primary)] px-5 py-3 text-sm font-semibold text-white"
            >
              Start a project
            </a>
            <a
              href="#services"
              class="rounded border border-black/15 px-5 py-3 text-sm font-semibold"
            >
              View services
            </a>
          </div>
        </div>

        <div class="rounded border border-black/10 bg-white p-6 shadow-sm">
          <p class="text-sm font-semibold text-[var(--template-primary)]">Trusted outcomes</p>
          <div class="mt-6 grid gap-4">
            <div v-for="stat in stats" :key="stat" class="rounded border border-black/10 p-4">
              <p class="text-2xl font-bold">{{ stat }}</p>
              <p class="mt-1 text-sm opacity-70">Measured delivery for growing companies.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="services" class="border-y border-black/10 bg-black/[0.03] px-6 py-14">
      <div class="mx-auto max-w-6xl">
        <div class="max-w-2xl">
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">Services</p>
          <h2 class="mt-3 text-3xl font-bold">Everything a modern business site needs.</h2>
        </div>
        <div class="mt-8 grid gap-4 md:grid-cols-3">
          <article
            v-for="service in services"
            :key="service"
            class="rounded border border-black/10 bg-white p-5"
          >
            <h3 class="text-lg font-semibold">{{ service }}</h3>
            <p class="mt-3 text-sm leading-6 opacity-70">
              Clear positioning, strong visuals, and useful calls to action across every page.
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="proof" class="px-6 py-14">
      <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-2 md:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">Why it works</p>
          <h2 class="mt-3 text-3xl font-bold">Built for quick scanning and confident decisions.</h2>
        </div>
        <p class="text-base leading-8 opacity-75">
          This template gives your brand a complete structure: focused messaging, service cards,
          credibility markers, and a direct contact area.
        </p>
      </div>
    </section>

    <footer id="contact" class="bg-[var(--template-secondary)] px-6 py-12 text-white">
      <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-2">
        <div>
          <h2 class="text-3xl font-bold">Let us talk about your next step.</h2>
          <p class="mt-4 opacity-80">{{ template.contact_info.address }}</p>
        </div>
        <div class="space-y-2 md:text-right">
          <p>{{ template.contact_info.email }}</p>
          <p>{{ template.contact_info.phone }}</p>
          <div class="flex flex-wrap gap-3 md:justify-end">
            <a
              v-for="[name, url] in socialLinks"
              :key="name"
              :href="String(url)"
              class="text-sm underline"
            >
              {{ name }}
            </a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
