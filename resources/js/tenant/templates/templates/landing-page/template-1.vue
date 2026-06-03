<script setup lang="ts">
import { defaultTemplateCta } from '@/shared/templates/cta-presets'
import FieldHelp from '@/shared/components/FieldHelp.vue'
import TemplateCta from '@/tenant/templates/components/TemplateCta.vue'
import type { TemplateCtaConfig, TemplateCtaField, TemplateRecord } from '@/shared/types/templates'
import {
  BarChart3,
  ChevronDown,
  Fingerprint,
  Mail,
  MapPin,
  Menu,
  MessageCircle,
  MessageSquare,
  Phone,
  Users,
} from 'lucide-vue-next'
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

type ContentRow = Record<string, unknown>

const asset = (path: string): string => `/template-assets/infinite-loop/${path}`

const iconComponents = {
  analytics: BarChart3,
  support: MessageCircle,
  security: Fingerprint,
  community: Users,
} as const

const content = computed(() => props.template.content ?? {})

const isMenuOpen = ref(false)
const hasScrolled = ref(false)

const updateScrollState = (): void => {
  hasScrolled.value = window.scrollY > 120
}

const stringValue = (value: unknown, fallback = ''): string => {
  return typeof value === 'string' && value.trim() ? value : fallback
}

const ctaFieldDescription = (field: TemplateCtaField): string => {
  if (field.type === 'select') return 'Choose one option from the list.'
  if (field.type === 'checkbox') return 'Check this box if it applies to you.'
  if (field.type === 'file') return 'Choose a file to include with your submission.'
  if (field.type === 'email') return 'Enter a valid email address.'
  if (field.type === 'phone') return 'Enter a phone number where you can be reached.'
  if (field.type === 'textarea') return 'Enter the details you want to send.'

  return 'Enter the requested information for this field.'
}

const contentString = (key: string, fallback = ''): string => {
  if (Object.prototype.hasOwnProperty.call(content.value, key)) {
    return stringValue(content.value[key])
  }

  return stringValue(content.value[key], fallback)
}

const rowsFor = (key: string, fallback: ContentRow[]): ContentRow[] => {
  const value = content.value[key]

  return Array.isArray(value) ? (value as ContentRow[]) : fallback
}

const ctaConfigFor = (value: unknown): TemplateCtaConfig => {
  const fallback = {
    ...defaultTemplateCta('contact_message'),
    title: contentString('contact_title', 'Contact Us'),
    description: contentString(
      'contact_text',
      'Invite visitors to start a conversation, request details, or send a message from the page.'
    ),
    submit_label: contentString('submit_label', 'Submit'),
  }
  const record =
    value && typeof value === 'object' && !Array.isArray(value) ? (value as ContentRow) : {}

  return {
    ...fallback,
    ...record,
    fields: Array.isArray(record.fields) ? (record.fields as TemplateCtaField[]) : fallback.fields,
  } as TemplateCtaConfig
}

const ctaFieldPlaceholder = (field: TemplateCtaField): string => {
  const originalPlaceholders: Record<string, string> = {
    name: 'Your Name',
    email: 'Your Email',
    message: 'Message',
  }

  return originalPlaceholders[field.key] ?? stringValue(field.placeholder, field.label)
}

const businessName = computed(() => {
  return contentString('business_name', props.template.business_name)
})

const heroImage = computed(() => {
  return contentString('hero_image', asset('infinite-loop-01.jpg'))
})

const testimonialsImage = computed(() => {
  return contentString('testimonials_background_image', asset('infinite-loop-02.jpg'))
})

const contactImage = computed(() => {
  return contentString('contact_background_image', asset('infinite-loop-03.jpg'))
})

const navItems = computed(() => [
  { label: contentString('nav_home_label', 'Home'), href: '#infinite' },
  { label: contentString('nav_about_label', 'What We Do'), href: '#whatwedo' },
  { label: contentString('nav_testimonials_label', 'Testimonials'), href: '#testimonials' },
  { label: contentString('nav_gallery_label', 'Gallery'), href: '#gallery' },
  { label: contentString('nav_contact_label', 'Contact'), href: '#contact' },
])

const features = computed(() =>
  rowsFor('features', [
    {
      icon: 'analytics',
      title: 'Market Analysis',
      description:
        'Study your audience, sharpen the offer, and turn campaign data into clear next steps.',
    },
    {
      icon: 'support',
      title: 'Fast Support',
      description:
        'Give visitors a direct path to ask questions, request help, and keep momentum moving.',
    },
    {
      icon: 'security',
      title: 'Top Security',
      description:
        'Present privacy, reliability, and trust signals with a confident landing-page structure.',
      cta_label: 'Learn More',
      cta_link: '#testimonials',
    },
    {
      icon: 'community',
      title: 'Social Work',
      description:
        'Show community programs, partnerships, and social proof in a polished public layout.',
      cta_label: 'Details',
      cta_link: '#testimonials',
    },
  ])
)

const testimonials = computed(() =>
  rowsFor('testimonials', [
    {
      image: asset('testimonial-img-01.jpg'),
      quote:
        'The landing page feels modern, moves smoothly, and gives our visitors a clear reason to continue.',
      name: 'Catherine Win',
      role: 'Designer',
    },
    {
      image: asset('testimonial-img-02.jpg'),
      quote: 'The sections are easy to scan and the parallax moments make the page feel memorable.',
      name: 'Dual Rocker',
      role: 'CEO',
    },
    {
      image: asset('testimonial-img-03.jpg'),
      quote:
        'We launched quickly with strong copy, useful contact paths, and a gallery that shows the work.',
      name: 'Sandar Soft',
      role: 'Marketing',
    },
    {
      image: asset('testimonial-img-04.jpg'),
      quote:
        'A focused layout that keeps the message simple while still feeling rich and complete.',
      name: 'Oliva Htoo',
      role: 'Designer',
    },
  ])
)

const galleryItems = computed(() =>
  rowsFor('gallery_items', [
    {
      image: asset('gallery-tn-01.jpg'),
      full_image: asset('gallery-img-01.jpg'),
      title: 'Physical Health',
      highlight: 'Exercise!',
    },
    {
      image: asset('gallery-tn-02.jpg'),
      full_image: asset('gallery-img-02.jpg'),
      title: 'Rain on Glass',
      highlight: 'Second Image',
    },
    {
      image: asset('gallery-tn-03.jpg'),
      full_image: asset('gallery-img-03.jpg'),
      title: 'Sea View',
      highlight: 'Mega City',
    },
    {
      image: asset('gallery-tn-04.jpg'),
      full_image: asset('gallery-img-04.jpg'),
      title: 'Dream Girl',
      highlight: 'Thoughts',
    },
    {
      image: asset('gallery-tn-05.jpg'),
      full_image: asset('gallery-img-05.jpg'),
      title: 'Workstation',
      highlight: 'Offices',
    },
    {
      image: asset('gallery-tn-06.jpg'),
      full_image: asset('gallery-img-06.jpg'),
      title: 'Just Above',
      highlight: 'The City',
    },
  ])
)

const contact = computed(() => ({
  email: contentString('contact_email', props.template.contact_info.email),
  phone: contentString('contact_phone', props.template.contact_info.phone),
  location: contentString('contact_location', props.template.contact_info.address),
  chatLabel: contentString('chat_label', 'Chat Online'),
  chatUrl: contentString('chat_url', props.template.social_links.website),
}))

const primaryCta = computed(() => ctaConfigFor(content.value.primary_cta))

const iconFor = (name: unknown) => {
  const key = stringValue(name, 'analytics') as keyof typeof iconComponents

  return iconComponents[key] ?? iconComponents.analytics
}

const closeMenu = (): void => {
  isMenuOpen.value = false
}

onMounted(() => {
  updateScrollState()
  window.addEventListener('scroll', updateScrollState, { passive: true })
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', updateScrollState)
})
</script>

<template>
  <div class="infinite-loop min-h-screen bg-white text-[var(--template-text)]">
    <section
      id="infinite"
      class="tm-parallax relative flex min-h-[560px] flex-col text-white md:min-h-screen"
      :style="{ backgroundImage: `url(${heroImage})` }"
    >
      <nav class="tm-navbar" :class="{ scroll: hasScrolled, open: isMenuOpen }">
        <div class="mx-auto flex max-w-[1275px] items-center justify-between gap-4 px-4 py-4">
          <a href="#infinite" class="navbar-brand" @click="closeMenu">{{ businessName }}</a>

          <button
            type="button"
            class="navbar-toggle"
            :aria-expanded="isMenuOpen"
            aria-label="Toggle navigation"
            @click="isMenuOpen = !isMenuOpen"
          >
            <Menu class="size-6" />
          </button>

          <div class="navbar-links" :class="{ show: isMenuOpen }">
            <a
              v-for="item in navItems"
              :key="item.href"
              :href="item.href"
              class="tm-nav-link"
              @click="closeMenu"
            >
              {{ item.label }}
            </a>
          </div>
        </div>
      </nav>

      <div class="flex flex-1 items-center justify-center px-6 text-center">
        <div class="-mt-12">
          <h1 class="tm-hero-title">{{ contentString('hero_title', 'Infinite Loop') }}</h1>
          <p class="tm-hero-subtitle">
            {{ contentString('hero_subtitle', 'Bootstrap-inspired parallax landing page') }}
          </p>
        </div>
      </div>

      <div class="absolute inset-x-0 bottom-16 flex justify-center">
        <a href="#whatwedo" class="tm-down-arrow" aria-label="Jump to content">
          <ChevronDown class="size-8" />
        </a>
      </div>
    </section>

    <section id="whatwedo" class="px-6 py-20">
      <div class="mx-auto max-w-[1275px]">
        <div class="mb-10 max-w-5xl">
          <h2 class="tm-section-title">{{ contentString('intro_title', 'What We Do') }}</h2>
          <p class="tm-intro-text mt-6">
            {{
              contentString(
                'intro_text',
                'A flexible landing page with parallax hero sections, service highlights, testimonials, gallery images, and a direct contact area.'
              )
            }}
          </p>
        </div>

        <div class="grid gap-x-10 gap-y-14 lg:grid-cols-2">
          <article
            v-for="(feature, index) in features"
            :key="String(feature.title ?? index)"
            class="grid gap-5 sm:grid-cols-[4rem_minmax(0,1fr)]"
          >
            <component :is="iconFor(feature.icon)" class="tm-icon size-12 sm:mt-2" />
            <div>
              <h3 class="text-[1.7rem] font-normal leading-tight text-[var(--template-primary)]">
                {{ feature.title }}
              </h3>
              <p class="tm-intro-text mt-5">{{ feature.description }}</p>
              <a
                v-if="feature.cta_label"
                :href="String(feature.cta_link ?? '#testimonials')"
                class="tm-btn-primary mt-5 inline-flex"
              >
                {{ feature.cta_label }}
              </a>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section
      id="testimonials"
      class="tm-parallax-2 relative px-6 py-24 text-white"
      :style="{ backgroundImage: `url(${testimonialsImage})` }"
    >
      <div class="absolute inset-0 bg-[#144650]/20"></div>
      <div class="relative z-10 mx-auto max-w-[1100px] text-center">
        <h2 class="tm-section-title text-white">
          {{ contentString('testimonials_title', 'Testimonials') }}
        </h2>
        <p class="mx-auto mt-5 max-w-2xl text-[0.95rem] leading-8">
          {{
            contentString(
              'testimonials_intro',
              'Share customer quotes, team praise, or campaign proof in a smooth horizontal section.'
            )
          }}
        </p>

        <div class="tm-testimonials-carousel mt-14">
          <figure
            v-for="(testimonial, index) in testimonials"
            :key="String(testimonial.name ?? index)"
            class="tm-testimonial-item"
          >
            <img
              :src="String(testimonial.image ?? asset('testimonial-img-01.jpg'))"
              :alt="String(testimonial.name ?? 'Testimonial')"
            />
            <blockquote>{{ testimonial.quote }}</blockquote>
            <figcaption>
              {{ testimonial.name }}
              <span v-if="testimonial.role">({{ testimonial.role }})</span>
            </figcaption>
          </figure>
        </div>
      </div>
    </section>

    <section id="gallery" class="px-6 py-20">
      <div class="mx-auto max-w-[1290px] text-center">
        <h2 class="tm-section-title">{{ contentString('gallery_title', 'Gallery') }}</h2>
        <p class="mx-auto mt-5 max-w-2xl text-[0.95rem] leading-8">
          {{
            contentString(
              'gallery_intro',
              'Use this area for product images, campaign shots, office scenes, event highlights, or visual proof.'
            )
          }}
        </p>

        <div class="tm-gallery mt-16">
          <a
            v-for="(item, index) in galleryItems"
            :key="String(item.title ?? index)"
            :href="String(item.full_image ?? item.image ?? '#gallery')"
            class="tm-gallery-item"
          >
            <img
              :src="String(item.image ?? asset('gallery-tn-01.jpg'))"
              :alt="String(item.title ?? 'Gallery image')"
            />
            <span class="tm-gallery-caption">
              <span>{{ item.title }}</span>
              <strong>{{ item.highlight }}</strong>
            </span>
          </a>
        </div>
      </div>
    </section>

    <section
      id="contact"
      class="tm-contact relative px-6 pb-24 pt-24 text-white"
      :style="{ backgroundImage: `url(${contactImage})` }"
    >
      <div class="mx-auto max-w-[1063px]">
        <div class="mx-auto max-w-3xl text-center">
          <h2 class="tm-section-title text-white">
            {{ primaryCta.title }}
          </h2>
          <p class="mt-5 leading-8">
            {{ primaryCta.description }}
          </p>
        </div>

        <div class="mt-14 grid gap-10 md:grid-cols-2">
          <TemplateCta
            :template-id="props.template.id"
            :cta="primaryCta"
            class="contents"
            form-class="space-y-5"
            v-slot="{
              fields,
              payload,
              loading,
              success,
              error,
              submitLabel,
              successMessage,
              updateField,
              updateFileField,
              inputTypeFor,
            }"
          >
            <template v-for="field in fields" :key="field.key">
              <FieldHelp :description="ctaFieldDescription(field)">
                <textarea
                  v-if="field.type === 'textarea'"
                  class="tm-input min-h-44"
                  :placeholder="ctaFieldPlaceholder(field)"
                  :required="field.required"
                  :value="String(payload[field.key] ?? '')"
                  @input="updateField(field, ($event.target as HTMLTextAreaElement).value)"
                />
                <select
                  v-else-if="field.type === 'select'"
                  class="tm-input"
                  :required="field.required"
                  :value="String(payload[field.key] ?? '')"
                  @change="updateField(field, ($event.target as HTMLSelectElement).value)"
                >
                  <option value="">{{ ctaFieldPlaceholder(field) }}</option>
                  <option
                    v-for="option in field.options ?? []"
                    :key="String(option.value)"
                    :value="String(option.value)"
                  >
                    {{ option.label }}
                  </option>
                </select>
                <label v-else-if="field.type === 'checkbox'" class="tm-checkbox">
                  <input
                    type="checkbox"
                    :required="field.required"
                    :checked="Boolean(payload[field.key])"
                    @change="updateField(field, ($event.target as HTMLInputElement).checked)"
                  />
                  <span>{{ field.label }}</span>
                </label>
                <input
                  v-else-if="field.type === 'file'"
                  class="tm-input"
                  type="file"
                  :required="field.required"
                  @change="updateFileField(field, $event)"
                />
                <input
                  v-else
                  class="tm-input"
                  :type="inputTypeFor(field)"
                  :placeholder="ctaFieldPlaceholder(field)"
                  :required="field.required"
                  :value="String(payload[field.key] ?? '')"
                  @input="updateField(field, ($event.target as HTMLInputElement).value)"
                />
              </FieldHelp>
            </template>
            <button class="tm-btn-submit" type="submit" :disabled="loading">
              {{ loading ? 'Sending...' : submitLabel }}
            </button>
            <p v-if="success" class="tm-form-status tm-form-status-success" role="status">
              {{ successMessage }}
            </p>
            <p v-if="error" class="tm-form-status tm-form-status-error" role="alert">
              {{ error }}
            </p>
          </TemplateCta>

          <div class="space-y-8">
            <a :href="contact.chatUrl || '#contact'" class="contact-item">
              <MessageSquare class="size-8" />
              <span>{{ contact.chatLabel }}</span>
            </a>
            <a :href="`mailto:${contact.email}`" class="contact-item">
              <Mail class="size-8" />
              <span>{{ contact.email }}</span>
            </a>
            <a href="#contact" class="contact-item">
              <MapPin class="size-8" />
              <span>{{ contact.location }}</span>
            </a>
            <a :href="`tel:${contact.phone}`" class="contact-item">
              <Phone class="size-8" />
              <span>{{ contact.phone }}</span>
            </a>
          </div>
        </div>
      </div>

      <footer class="absolute inset-x-0 bottom-8 px-6 text-center text-sm">
        {{ contentString('footer_text', `Copyright 2026 ${businessName}`) }}
      </footer>
    </section>
  </div>
</template>

<style scoped>
.infinite-loop {
  font-family: Raleway, var(--template-font, Inter), sans-serif;
  font-size: 1.2em;
  color: var(--template-text, #707070);
  scroll-behavior: smooth;
}

.tm-parallax,
.tm-parallax-2,
.tm-contact {
  background-position: center;
  background-repeat: no-repeat;
  background-size: cover;
}

.tm-parallax {
  background-color: #222;
}

.tm-parallax-2,
.tm-contact {
  background-attachment: fixed;
}

.tm-navbar {
  position: sticky;
  top: 0;
  z-index: 30;
  width: 100%;
  color: white;
  transition:
    background-color 0.3s ease,
    border-color 0.3s ease,
    color 0.3s ease;
}

.tm-navbar.scroll,
.tm-navbar.open {
  border-bottom: 1px solid #e9ecef;
  background-color: white;
  color: var(--template-secondary, #369);
}

.navbar-brand {
  color: inherit;
  font-size: 1.4rem;
  font-weight: 700;
}

.navbar-brand:hover {
  color: var(--template-primary, #38b);
}

.navbar-toggle {
  display: inline-flex;
  border: 1px solid currentColor;
  border-radius: 3px;
  padding: 0.45rem 0.6rem;
  color: inherit;
}

.navbar-links {
  display: none;
  position: absolute;
  top: 100%;
  right: 1rem;
  width: min(220px, calc(100% - 2rem));
  flex-direction: column;
  align-items: stretch;
  border-radius: 3px;
  background: white;
  padding: 0.5rem;
  text-align: right;
}

.navbar-links.show {
  display: flex;
}

.tm-nav-link {
  color: #707070;
  padding: 0.8rem 1rem;
  font-size: 1rem;
  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}

.tm-nav-link:hover {
  background-color: var(--template-secondary, #369);
  color: white;
}

.tm-hero-title {
  font-size: clamp(3rem, 7vw, 3.5rem);
  line-height: 1.1;
  text-shadow: 2px 2px 2px #333;
}

.tm-hero-subtitle {
  margin-top: 1rem;
  line-height: 1.8;
  text-shadow: 2px 2px 2px #333;
}

.tm-down-arrow {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: var(--template-secondary, #357);
  color: white;
  padding: 15px 40px;
  transition:
    background-color 0.3s ease,
    padding 0.3s ease;
}

.tm-down-arrow:hover {
  background: var(--template-primary, #37a);
  color: white;
  padding-inline: 50px;
}

.tm-section-title {
  color: var(--template-primary, #37a);
  font-size: clamp(2.25rem, 6vw, 2.6rem);
  font-weight: 400;
  line-height: 1.2;
}

.tm-intro-text {
  color: var(--template-text, #707070);
  font-size: 1.2rem;
  line-height: 1.9;
}

.tm-icon {
  color: var(--template-primary, #37a);
}

.tm-btn-primary {
  background-color: var(--template-secondary, #369);
  color: white;
  padding: 14px 30px;
  transition: background-color 0.3s ease;
}

.tm-btn-primary:hover {
  background-color: var(--template-primary, #38b);
  color: white;
}

.tm-testimonials-carousel {
  display: grid;
  grid-auto-columns: minmax(240px, 1fr);
  grid-auto-flow: column;
  gap: 2rem;
  overflow-x: auto;
  padding: 0 0.5rem 2rem;
  scroll-snap-type: x mandatory;
}

.tm-testimonial-item {
  max-width: 290px;
  margin: 0 auto;
  scroll-snap-align: start;
}

.tm-testimonial-item img {
  width: 120px;
  height: 120px;
  margin: 0 auto 35px;
  border-radius: 999px;
  object-fit: cover;
}

.tm-testimonial-item blockquote {
  font-size: 0.9em;
  line-height: 1.8;
}

.tm-testimonial-item figcaption {
  margin-top: 1.5rem;
  text-align: right;
  font-size: 1.1rem;
  font-style: italic;
}

.tm-gallery {
  display: grid;
  grid-auto-columns: 220px;
  grid-auto-flow: column;
  gap: 30px;
  overflow-x: auto;
  padding-bottom: 4rem;
  scroll-snap-type: x mandatory;
}

.tm-gallery-item {
  position: relative;
  display: block;
  width: 220px;
  overflow: hidden;
  background: #4a3753;
  color: white;
  scroll-snap-align: start;
}

.tm-gallery-item img {
  width: 100%;
  height: 330px;
  object-fit: cover;
  opacity: 1;
  transition: opacity 0.35s ease;
}

.tm-gallery-item::after {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  height: 10px;
  background: var(--template-primary, #38c);
  content: '';
  transform: translateY(10px);
  transition: transform 0.35s ease;
}

.tm-gallery-caption {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  padding: 1rem 1.5rem 2.5rem;
  text-align: left;
  text-transform: uppercase;
  transform: translateY(34px);
  transition: transform 0.35s ease;
}

.tm-gallery-caption strong {
  display: block;
  font-weight: 600;
  opacity: 0;
  transform: translateY(-20px);
  transition:
    opacity 0.35s ease,
    transform 0.35s ease;
}

.tm-gallery-item:hover img {
  opacity: 0.4;
}

.tm-gallery-item:hover::after,
.tm-gallery-item:hover .tm-gallery-caption,
.tm-gallery-item:hover .tm-gallery-caption strong {
  opacity: 1;
  transform: translateY(0);
}

.tm-contact {
  min-height: 980px;
  background-color: #001828;
}

.tm-input {
  width: 90%;
  border: 1px solid white;
  border-radius: 6px;
  background: transparent;
  padding: 8px 20px;
  color: white;
}

.tm-input::placeholder {
  color: white;
  opacity: 1;
}

.tm-checkbox {
  display: flex;
  width: 90%;
  align-items: center;
  gap: 0.75rem;
  color: white;
  font-size: 0.9em;
}

.tm-btn-submit {
  width: min(260px, 90%);
  background-color: var(--template-secondary, #369);
  color: white;
  padding: 10px 32px;
  font-size: 0.9em;
  transition: background-color 0.3s ease;
}

.tm-btn-submit:hover {
  background-color: var(--template-primary, #38b);
}

.tm-form-status {
  width: 90%;
  font-size: 0.8em;
  font-weight: 600;
}

.tm-form-status-success {
  color: #86efac;
}

.tm-form-status-error {
  color: #fca5a5;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  color: white;
  transition: color 0.3s ease;
}

.contact-item:hover {
  color: var(--template-primary, #3496d8);
}

@media (min-width: 768px) {
  .navbar-toggle {
    display: none;
  }

  .navbar-links,
  .navbar-links.show {
    position: static;
    display: flex;
    width: auto;
    flex-direction: row;
    align-items: center;
    background: transparent;
    padding: 0;
    text-align: left;
  }

  .tm-navbar:not(.scroll) .tm-nav-link {
    color: white;
  }

  .tm-navbar.scroll .tm-nav-link {
    color: var(--template-secondary, #369);
  }

  .tm-navbar:not(.scroll) .tm-nav-link:hover,
  .tm-navbar.scroll .tm-nav-link:hover {
    background-color: var(--template-secondary, #369);
    color: white;
  }

  .tm-nav-link {
    padding-inline: 30px;
  }
}

@media (min-width: 992px) {
  .tm-testimonials-carousel {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-auto-flow: row;
    overflow: visible;
  }
}

@media (max-width: 767px) {
  .tm-contact {
    min-height: 1100px;
  }
}
</style>
