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

const projects = computed(() => {
  const values = content.value.projects

  return Array.isArray(values) && values.length
    ? values
    : [
        {
          title: 'Identity system',
          description: 'A concise case-study area for outcomes, visuals, and client context.',
        },
        {
          title: 'Editorial website',
          description: 'A concise case-study area for outcomes, visuals, and client context.',
        },
        {
          title: 'Product launch',
          description: 'A concise case-study area for outcomes, visuals, and client context.',
        },
      ]
})

const skills = computed(() => {
  const values = content.value.skills

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
    <header class="px-6 py-6">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
        <a href="#top" class="flex items-center gap-3">
          <img :src="template.logo" :alt="businessName" class="size-11 rounded-full object-cover" />
          <span class="font-semibold">{{ businessName }}</span>
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
          {{ content.bio_title ?? 'Independent portfolio' }}
        </p>
        <h1 class="mt-5 max-w-4xl text-4xl font-bold leading-tight md:text-6xl">
          {{
            content.headline ??
            `Selected work, clear thinking, and a direct path to hire ${businessName}.`
          }}
        </h1>
        <p class="mt-6 max-w-2xl text-lg leading-8 opacity-75">
          {{
            content.intro_text ??
            'A complete portfolio website for showcasing projects, process, background, and contact information without extra setup.'
          }}
        </p>
      </div>
    </section>

    <section id="work" class="px-6 py-12">
      <div class="mx-auto max-w-5xl">
        <div class="grid gap-5 md:grid-cols-3">
          <article
            v-for="(project, index) in projects"
            :key="String(project.title ?? index)"
            class="rounded border border-black/10 bg-white p-5 shadow-sm"
          >
            <p class="text-sm font-semibold text-[var(--template-primary)]">0{{ index + 1 }}</p>
            <h2 class="mt-10 text-2xl font-bold">{{ project.title }}</h2>
            <p class="mt-3 text-sm leading-6 opacity-70">
              {{ project.description }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <section id="about" class="border-y border-black/10 bg-black/[0.03] px-6 py-14">
      <div class="mx-auto grid max-w-5xl gap-8 md:grid-cols-[0.8fr_1.2fr]">
        <div>
          <p class="text-sm font-semibold uppercase text-[var(--template-primary)]">About</p>
          <h2 class="mt-3 text-3xl font-bold">{{ businessName }}</h2>
        </div>
        <div>
          <p class="text-base leading-8 opacity-75">
            {{
              content.bio ??
              'Use this template to present a focused creative practice with room for work samples, capabilities, background, and a simple contact path.'
            }}
          </p>
          <div v-if="skills.length" class="mt-5 flex flex-wrap gap-2">
            <span
              v-for="(skill, index) in skills"
              :key="String(skill.name ?? index)"
              class="rounded border border-black/10 px-3 py-1 text-sm"
            >
              {{ skill.name }}
            </span>
          </div>
        </div>
      </div>
    </section>

    <footer id="contact" class="px-6 py-12">
      <div
        class="mx-auto flex max-w-5xl flex-col justify-between gap-6 border-t border-black/10 pt-8 md:flex-row"
      >
        <div>
          <h2 class="text-2xl font-bold">
            {{ content.contact_cta ?? 'Available for selected projects.' }}
          </h2>
          <p class="mt-3 opacity-70">{{ contact.location }}</p>
        </div>
        <div class="space-y-2 md:text-right">
          <p>{{ contact.email }}</p>
          <p>{{ contact.phone }}</p>
          <div class="flex flex-wrap gap-3 md:justify-end">
            <a
              v-for="[name, url] in socialLinks"
              :key="name"
              :href="safeHref(url)"
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
