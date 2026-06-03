<script setup lang="ts">
import { Button } from '@/shared/components/ui/button'
import { useAdminAuthStore } from '@/admin/auth/auth-store'
import { useAuthStore } from '@/tenant/auth/auth-store'
import {
  ArrowRight,
  BarChart3,
  CheckCircle2,
  LayoutDashboard,
  LayoutTemplate,
  Mail,
  MousePointerClick,
  Palette,
  ShieldCheck,
  Sparkles,
  Users,
} from 'lucide-vue-next'
import { computed } from 'vue'

const tenantAuthStore = useAuthStore()
const adminAuthStore = useAdminAuthStore()
const runtimeSettings =
  (
    window as unknown as {
      __SYSTEM_SETTINGS__?: Record<string, string | boolean | number | null>
    }
  ).__SYSTEM_SETTINGS__ ?? {}
const privacyPolicyUrl = String(runtimeSettings['compliance.privacy_policy_url'] || '')
const termsOfServiceUrl = String(runtimeSettings['compliance.terms_of_service_url'] || '')
const applicationName = String(runtimeSettings['general.application_name'] || 'Onlyvo')
const applicationDescription = String(
  runtimeSettings['general.application_description'] ||
    'Launch tenant websites, manage content, collect leads, and understand visitor activity without stitching together separate tools.'
)
const logoUrl = String(runtimeSettings['general.logo'] || '')
const supportEmail = String(runtimeSettings['general.support_email'] || '')
const supportPhone = String(runtimeSettings['general.support_phone'] || '')
const companyAddress = String(runtimeSettings['general.company_address'] || '')
const registrationEnabled = computed(
  () => runtimeSettings['authentication.allow_tenant_registration'] !== false
)
const socialLinks = computed(() =>
  [
    { label: 'Facebook', url: runtimeSettings['social.facebook_url'] },
    { label: 'Instagram', url: runtimeSettings['social.instagram_url'] },
    { label: 'LinkedIn', url: runtimeSettings['social.linkedin_url'] },
    { label: 'X', url: runtimeSettings['social.twitter_url'] },
    { label: 'YouTube', url: runtimeSettings['social.youtube_url'] },
  ]
    .map((item) => ({ label: item.label, url: String(item.url || '') }))
    .filter((item) => item.url)
)

const dashboardRoute = computed(() => {
  return adminAuthStore.isAuthenticated ? { name: 'admin.dashboard' } : { name: 'sidebar.index' }
})

const showDashboardCta = computed(() => {
  return tenantAuthStore.isAuthenticated || adminAuthStore.isAuthenticated
})

const heroImage = '/template-assets/infinite-loop/infinite-loop-01.jpg'

const navItems = [
  { label: 'Features', href: '#features' },
  { label: 'Benefits', href: '#benefits' },
  { label: 'Pricing', href: '#pricing' },
  { label: 'Contact', href: '#contact' },
]

const features = [
  {
    icon: LayoutTemplate,
    title: 'Template publishing',
    description:
      'Choose polished website templates, customize brand content, and publish tenant sites with a clear workflow.',
  },
  {
    icon: MousePointerClick,
    title: 'CTA collection',
    description:
      'Add contact, booking, lead, download, and custom calls to action that are ready for public visitors.',
  },
  {
    icon: BarChart3,
    title: 'Visitor analytics',
    description:
      'Review visits, unique visitors, referrers, top pages, and CTA performance from the tenant workspace.',
  },
  {
    icon: Palette,
    title: 'Design requests',
    description:
      'Give tenants a simple way to request custom designs when a standard template needs a more tailored finish.',
  },
]

const benefits = [
  'Launch public sites without creating a custom build for every tenant.',
  'Keep tenant content, posts, templates, and analytics in one authenticated workspace.',
  'Support both admin operations and tenant self-service without mixing their routes.',
  'Track visitor engagement and CTA activity for better follow-up decisions.',
]

const packageFits = [
  {
    title: 'Launch',
    subtitle: 'For teams starting their first tenant site.',
    items: ['Tenant registration', 'Template selection', 'Published public site'],
  },
  {
    title: 'Grow',
    subtitle: 'For teams that need content and lead capture.',
    items: ['Posts and pages', 'CTA tracking', 'Analytics dashboard'],
  },
  {
    title: 'Custom',
    subtitle: 'For tenants that need a tailored presence.',
    items: ['Design requests', 'Admin review tools', 'Custom launch support'],
  },
]
</script>

<template>
  <div class="min-h-screen bg-background text-foreground">
    <header
      class="fixed inset-x-0 top-0 z-40 border-b border-white/15 bg-zinc-950/55 text-white backdrop-blur-md"
    >
      <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-3 lg:px-8">
        <a href="#home" class="flex items-center gap-2 font-semibold">
          <span
            v-if="!logoUrl"
            class="flex size-9 items-center justify-center rounded bg-primary text-primary-foreground"
          >
            <Sparkles class="size-4" />
          </span>
          <img v-else :src="logoUrl" :alt="applicationName" class="size-9 rounded object-contain" />
          <span class="text-base">{{ applicationName }}</span>
        </a>

        <div class="hidden items-center gap-6 text-sm font-medium text-white/80 md:flex">
          <a
            v-for="item in navItems"
            :key="item.href"
            :href="item.href"
            class="transition hover:text-white"
          >
            {{ item.label }}
          </a>
        </div>

        <div class="flex items-center gap-2">
          <Button
            v-if="showDashboardCta"
            as-child
            size="sm"
            variant="secondary"
            class="hidden bg-white/15 text-white hover:bg-white/25 sm:inline-flex"
          >
            <RouterLink :to="dashboardRoute">
              <LayoutDashboard class="size-4" />
              Dashboard
            </RouterLink>
          </Button>
          <Button
            as-child
            size="sm"
            variant="ghost"
            class="hidden text-white hover:bg-white/15 hover:text-white sm:inline-flex"
          >
            <RouterLink to="/login">Login</RouterLink>
          </Button>
          <Button as-child size="sm">
            <RouterLink v-if="registrationEnabled" to="/register">Register</RouterLink>
            <a v-else href="#contact">Contact</a>
          </Button>
        </div>
      </nav>
    </header>

    <section
      id="home"
      class="relative flex min-h-[78vh] items-center overflow-hidden bg-zinc-950 px-5 pb-20 pt-28 text-white lg:px-8"
    >
      <img
        :src="heroImage"
        alt="Published website preview background"
        class="absolute inset-0 size-full object-cover opacity-45"
      />
      <div class="absolute inset-0 bg-linear-to-r from-zinc-950 via-zinc-950/75 to-teal-950/45" />

      <div class="relative mx-auto w-full max-w-7xl">
        <div class="max-w-3xl">
          <p
            class="mb-5 inline-flex items-center gap-2 rounded border border-white/20 bg-white/10 px-3 py-1 text-sm font-medium text-teal-50 backdrop-blur"
          >
            <ShieldCheck class="size-4 text-primary" />
            Public sites, tenant workspaces, and admin tools in one app
          </p>

          <h1 class="max-w-4xl text-5xl font-bold leading-tight text-white md:text-6xl lg:text-7xl">
            {{ applicationName }}
          </h1>
          <p class="mt-5 max-w-2xl text-lg leading-8 text-white/80 md:text-xl">
            {{ applicationDescription }}
          </p>

          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <Button as-child size="lg">
              <RouterLink v-if="registrationEnabled" to="/register">
                Register
                <ArrowRight class="size-4" />
              </RouterLink>
              <a v-else href="#contact">
                Contact Us
                <ArrowRight class="size-4" />
              </a>
            </Button>
            <Button
              as-child
              size="lg"
              variant="outline"
              class="border-white/35 bg-white/10 text-white hover:bg-white hover:text-zinc-950"
            >
              <RouterLink to="/login">Login</RouterLink>
            </Button>
            <Button
              as-child
              size="lg"
              variant="ghost"
              class="text-white hover:bg-white/15 hover:text-white"
            >
              <a href="#features">Learn More</a>
            </Button>
          </div>
        </div>
      </div>
    </section>

    <section id="details" class="border-b bg-background px-5 py-14 lg:px-8">
      <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-primary">App details</p>
          <h2 class="mt-3 max-w-3xl text-3xl font-bold tracking-tight md:text-4xl">
            A practical website platform for tenant-led businesses.
          </h2>
          <p class="mt-4 max-w-2xl leading-7 text-muted-foreground">
            {{ applicationName }} gives each tenant an authenticated workspace for templates, posts,
            design requests, navigation, and analytics while keeping public visitor pages available
            without login.
          </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
          <div class="rounded border bg-card p-5 shadow-sm">
            <p class="text-3xl font-bold">4</p>
            <p class="mt-2 text-sm text-muted-foreground">Website template categories</p>
          </div>
          <div class="rounded border bg-card p-5 shadow-sm">
            <p class="text-3xl font-bold">2</p>
            <p class="mt-2 text-sm text-muted-foreground">Separate admin and tenant flows</p>
          </div>
          <div class="rounded border bg-card p-5 shadow-sm">
            <p class="text-3xl font-bold">1</p>
            <p class="mt-2 text-sm text-muted-foreground">Shared launch workspace</p>
          </div>
        </div>
      </div>
    </section>

    <section id="features" class="px-5 py-20 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="max-w-2xl">
          <p class="text-sm font-semibold uppercase text-primary">Features</p>
          <h2 class="mt-3 text-3xl font-bold tracking-tight md:text-4xl">
            Everything tenants need to go live and keep improving.
          </h2>
        </div>

        <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="feature in features"
            :key="feature.title"
            class="rounded border bg-card p-6 text-card-foreground shadow-sm transition hover:border-primary/60 hover:shadow-md"
          >
            <component :is="feature.icon" class="size-6 text-primary" />
            <h3 class="mt-5 text-lg font-semibold">{{ feature.title }}</h3>
            <p class="mt-3 text-sm leading-6 text-muted-foreground">{{ feature.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="benefits" class="bg-zinc-950 px-5 py-20 text-white lg:px-8">
      <div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-primary">Benefits</p>
          <h2 class="mt-3 text-3xl font-bold tracking-tight md:text-4xl">
            Built for repeatable launches, not one-off handoffs.
          </h2>
          <p class="mt-4 leading-7 text-white/70">
            Admins can support many tenants while each customer gets a clean path to manage their
            own public presence.
          </p>
        </div>

        <div class="grid gap-3">
          <div
            v-for="benefit in benefits"
            :key="benefit"
            class="flex gap-3 rounded border border-white/10 bg-white/[0.07] p-4"
          >
            <CheckCircle2 class="mt-0.5 size-5 shrink-0 text-primary" />
            <p class="text-sm leading-6 text-white/80">{{ benefit }}</p>
          </div>
        </div>
      </div>
    </section>

    <section id="pricing" class="px-5 py-20 lg:px-8">
      <div class="mx-auto max-w-7xl">
        <div class="flex flex-col justify-between gap-5 md:flex-row md:items-end">
          <div class="max-w-2xl">
            <p class="text-sm font-semibold uppercase text-primary">Pricing</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight md:text-4xl">
              Choose the package fit, then confirm pricing with the team.
            </h2>
          </div>
          <Button as-child variant="outline_default">
            <a href="#contact">
              Contact Us
              <Mail class="size-4" />
            </a>
          </Button>
        </div>

        <div class="mt-10 grid gap-4 lg:grid-cols-3">
          <article
            v-for="packageFit in packageFits"
            :key="packageFit.title"
            class="rounded border bg-card p-6 shadow-sm"
          >
            <div class="flex items-start justify-between gap-4">
              <div>
                <h3 class="text-xl font-semibold">{{ packageFit.title }}</h3>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">
                  {{ packageFit.subtitle }}
                </p>
              </div>
              <Users class="size-5 text-primary" />
            </div>
            <ul class="mt-6 space-y-3">
              <li
                v-for="item in packageFit.items"
                :key="item"
                class="flex gap-2 text-sm text-muted-foreground"
              >
                <CheckCircle2 class="mt-0.5 size-4 shrink-0 text-primary" />
                <span>{{ item }}</span>
              </li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section id="contact" class="border-t bg-secondary/40 px-5 py-16 lg:px-8">
      <div class="mx-auto grid max-w-7xl gap-8 lg:grid-cols-[1fr_0.8fr] lg:items-center">
        <div>
          <p class="text-sm font-semibold uppercase text-primary">Contact us</p>
          <h2 class="mt-3 text-3xl font-bold tracking-tight md:text-4xl">
            Ready to bring tenants onto a clearer website workflow?
          </h2>
          <p class="mt-4 max-w-2xl leading-7 text-muted-foreground">
            Register to create a tenant account when registration is open, or sign in if your
            workspace is already set up. The app will keep authenticated tenant and admin areas on
            their existing routes.
          </p>
          <div class="mt-5 space-y-2 text-sm text-muted-foreground">
            <p v-if="supportEmail">
              <a :href="`mailto:${supportEmail}`" class="font-medium text-primary">
                {{ supportEmail }}
              </a>
            </p>
            <p v-if="supportPhone">{{ supportPhone }}</p>
            <p v-if="companyAddress" class="whitespace-pre-line">{{ companyAddress }}</p>
          </div>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row lg:justify-end">
          <Button v-if="registrationEnabled" as-child size="lg">
            <RouterLink to="/register">
              Register
              <ArrowRight class="size-4" />
            </RouterLink>
          </Button>
          <Button as-child size="lg" variant="outline_default">
            <RouterLink to="/login">Login</RouterLink>
          </Button>
        </div>
      </div>
    </section>

    <footer class="border-t px-5 py-8 lg:px-8">
      <div
        class="mx-auto flex max-w-7xl flex-col gap-3 text-sm text-muted-foreground md:flex-row md:items-center md:justify-between"
      >
        <p>{{ applicationName }} tenant website platform</p>
        <div class="flex gap-4">
          <a
            v-if="privacyPolicyUrl"
            :href="privacyPolicyUrl"
            class="hover:text-foreground"
            target="_blank"
            rel="noreferrer"
          >
            Privacy
          </a>
          <a
            v-if="termsOfServiceUrl"
            :href="termsOfServiceUrl"
            class="hover:text-foreground"
            target="_blank"
            rel="noreferrer"
          >
            Terms
          </a>
          <a href="#features" class="hover:text-foreground">Learn More</a>
          <a href="#contact" class="hover:text-foreground">Contact Us</a>
          <a
            v-for="link in socialLinks"
            :key="link.label"
            :href="link.url"
            class="hover:text-foreground"
            target="_blank"
            rel="noreferrer"
          >
            {{ link.label }}
          </a>
        </div>
      </div>
    </footer>
  </div>
</template>
