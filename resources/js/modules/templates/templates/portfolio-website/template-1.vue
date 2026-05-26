<script setup lang="ts">
import type { TemplateRecord } from '@/types/templates'
import { computed } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

const projects = ['Identity system', 'Editorial website', 'Product launch']

const socialLinks = computed(() => {
  // Show social links only when the tenant has provided a URL.
  return Object.entries(props.template.social_links ?? {}).filter(([, value]) => value)
})
</script>

<template>
  <div class="min-h-screen bg-[var(--template-bg)] text-[var(--template-text)]">
    <header class="px-6 py-6">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
        <a href="#top" class="flex items-center gap-3">
          <img
            :src="template.logo"
            :alt="template.business_name"
            class="size-11 rounded-full object-cover"
          />
          <span class="font-semibold">{{ template.business_name }}</span>
        </a>
        <nav class="hidden items-center gap-5 text-sm md:flex">
          <a href="#work" class="hover:text-[var(--template-primary)]">Work</a>
          <a href="#about" class="hover:text-[var(--template-primary)]">About</a>
          <a href="#contact" class="hover:text-[var(--template-primary)]">Contact</a>
        </nav>
      </div>
    </header>

    <section id="top" class="px-6 py-14 md:py-20">
      <div class="mx-auto max-w-5xl">
        <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">
          Independent portfolio
        </p>
        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-tight md:text-6xl">
          Selected work, clear thinking, and a direct path to hire {{ template.business_name }}.
        </h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 opacity-75">
          A complete portfolio website for showcasing projects, process, background, and contact
          information without extra setup.
        </p>
      </div>
    </section>

    <section id="work" class="px-6 py-12">
      <div class="mx-auto max-w-5xl">
        <div class="grid gap-5 md:grid-cols-3">
          <article
            v-for="(project, index) in projects"
            :key="project"
            class="rounded border border-black/10 bg-white p-5 shadow-sm"
          >
            <p class="text-sm font-semibold text-[var(--template-primary)]">0{{ index + 1 }}</p>
            <h2 class="mt-10 text-2xl font-bold">{{ project }}</h2>
            <p class="mt-3 text-sm leading-6 opacity-70">
              A concise case-study area for outcomes, visuals, and client context.
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="about" class="border-y border-black/10 bg-black/[0.03] px-6 py-14">
      <div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-[0.8fr_1.2fr]">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">About</p>
          <h2 class="mt-3 text-3xl font-bold">{{ template.business_name }}</h2>
        </div>
        <p class="text-base leading-8 opacity-75">
          Use this template to present a focused creative practice with room for work samples,
          capabilities, background, and a simple contact path.
        </p>
      </div>
    </section>

    <footer id="contact" class="px-6 py-12">
      <div
        class="mx-auto flex max-w-5xl flex-col justify-between gap-6 border-t border-black/10 pt-8 md:flex-row"
      >
        <div>
          <h2 class="text-2xl font-bold">Available for selected projects.</h2>
          <p class="mt-3 opacity-70">{{ template.contact_info.address }}</p>
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
