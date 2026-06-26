<script setup lang="ts">
import { defaultTemplateCta } from '@/shared/templates/cta-presets'
import type { TemplateCtaConfig, TemplateCtaField, TemplateRecord } from '@/shared/types/templates'
import { safeHref } from '@/shared/utils/url'
import TemplateCta from '@/tenant/templates/components/TemplateCta.vue'
import { settingBoolean } from '@/tenant/templates/composables/useTenantPublicSettings'
import {
  ArrowRight,
  ArrowUp,
  Check,
  Code2,
  GitBranch,
  LineChart,
  Menu,
  Plus,
  X,
} from 'lucide-vue-next'
import { computed, onBeforeUnmount, ref, watch } from 'vue'

const props = defineProps<{
  template: TemplateRecord
}>()

type ContentRow = Record<string, unknown>
type BillingCycle = 'monthly' | 'yearly'

const content = computed(() => props.template.content ?? {})
const isMenuOpen = ref(false)
const billingCycle = ref<BillingCycle>('yearly')
const openFaqs = ref<Set<number>>(new Set())

const stringValue = (value: unknown, fallback = ''): string => {
  if (typeof value === 'string' && value.trim()) return value
  if (typeof value === 'number' || typeof value === 'boolean') return String(value)

  return fallback
}

const contentString = (key: string, fallback = ''): string => {
  return stringValue(content.value[key], fallback)
}

const rowsFor = (key: string, fallback: ContentRow[]): ContentRow[] => {
  const value = content.value[key]

  return Array.isArray(value) && value.length ? (value as ContentRow[]) : fallback
}

const booleanValue = (value: unknown, fallback = false): boolean => {
  if (typeof value === 'boolean') return value
  if (typeof value === 'string') return ['1', 'true', 'yes'].includes(value.toLowerCase())

  return fallback
}

const linesFrom = (value: unknown): string[] => {
  return stringValue(value)
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
}

const linkRowsFrom = (value: unknown): { label: string; url: string }[] => {
  return linesFrom(value).map((line) => {
    const [label, url] = line.split('|').map((part) => part.trim())

    return {
      label: label || line,
      url: url || '#',
    }
  })
}

const ctaFieldDescription = (field: TemplateCtaField): string => {
  const label = field.label.trim() || field.key

  if (field.type === 'select') return `Choose the response submitted for "${label}".`
  if (field.type === 'checkbox') return `Confirm whether "${label}" applies.`
  if (field.type === 'file') return `Attach the file requested by "${label}".`
  if (field.type === 'email') return `Provide the email address requested by "${label}".`
  if (field.type === 'phone') return `Provide the phone number requested by "${label}".`
  if (field.type === 'textarea') return `Provide the details requested by "${label}".`
  if (field.type === 'number') return `Provide the number requested by "${label}".`
  if (field.type === 'date') return `Choose the date requested by "${label}".`
  if (field.type === 'time') return `Choose the time requested by "${label}".`
  if (field.type === 'url') return `Provide the web address requested by "${label}".`

  return `Provide the information requested by "${label}".`
}

const ctaConfigFor = (value: unknown): TemplateCtaConfig => {
  const fallback = {
    ...defaultTemplateCta('demo_request'),
    title: contentString('cta_title', 'Ready to accelerate your data?'),
    description: contentString(
      'cta_text',
      'Join teams shipping faster with sub-second analytics. Free for 14 days.'
    ),
    submit_label: contentString('cta_submit_label', 'Request demo'),
  }
  const record =
    value && typeof value === 'object' && !Array.isArray(value) ? (value as ContentRow) : {}

  return {
    ...fallback,
    ...record,
    fields: Array.isArray(record.fields) ? (record.fields as TemplateCtaField[]) : fallback.fields,
  } as TemplateCtaConfig
}

const businessName = computed(() => contentString('business_name', props.template.business_name))

const navItems = computed(() =>
  rowsFor('nav_items', [
    { label: 'Performance', url: '#metrics' },
    { label: 'Features', url: '#engine' },
    { label: 'Pricing', url: '#pricing' },
    { label: 'FAQ', url: '#faq' },
  ])
)

const dashboardTabs = computed(() =>
  rowsFor('dashboard_tabs', [
    { label: 'Overview', active: true },
    { label: 'Funnels' },
    { label: 'Retention' },
    { label: 'Revenue' },
  ])
)

const activeDashboardTab = computed(() => {
  return dashboardTabs.value.find((tab) => booleanValue(tab.active)) ?? dashboardTabs.value[0]
})

const dashboardStats = computed(() =>
  rowsFor('dashboard_stats', [
    { label: 'Total Revenue', value: '$2.4M', change: '+18.2%', trend: 'up' },
    { label: 'Active Users', value: '48,291', change: '+7.4%', trend: 'up' },
    { label: 'Avg. Query Time', value: '42ms', change: '-23.5%', trend: 'down' },
  ])
)

const dashboardRows = computed(() =>
  rowsFor('dashboard_rows', [
    {
      badge: 'P1',
      name: 'Checkout Funnel',
      events: '1.2M',
      latency: '34ms',
      uptime: '99.98%',
      status: 'Active',
      status_variant: 'active',
    },
    {
      badge: 'P2',
      name: 'User Onboarding',
      events: '847K',
      latency: '52ms',
      uptime: '99.91%',
      status: 'Pending',
      status_variant: 'pending',
    },
    {
      badge: 'P3',
      name: 'Revenue Tracker',
      events: '2.1M',
      latency: '28ms',
      uptime: '99.99%',
      status: 'Active',
      status_variant: 'active',
    },
    {
      badge: 'P4',
      name: 'A/B Experiments',
      events: '423K',
      latency: '61ms',
      uptime: '99.87%',
      status: 'Draft',
      status_variant: 'draft',
    },
  ])
)

const metrics = computed(() =>
  rowsFor('metrics', [
    {
      value: '42',
      unit: 'ms',
      description: 'Average query response time across 10B+ row datasets',
      highlight: true,
    },
    {
      value: '99.99',
      unit: '%',
      description: 'Guaranteed uptime SLA for all enterprise customers',
    },
    {
      value: '10',
      unit: 'x',
      description: 'Faster than legacy warehouses at one-tenth the cost',
    },
  ])
)

const engineCards = computed(() =>
  rowsFor('engine_cards', [
    {
      icon: 'analytics',
      title: 'Real-Time Analytics',
      description:
        'Stream, transform, and visualize data the instant it arrives. No batching, no stale dashboards.',
      visual_type: 'bars',
    },
    {
      icon: 'pipeline',
      title: 'Pipeline Orchestration',
      description:
        'Build fault-tolerant data pipelines with visual drag-and-drop, auto-retry, and observability built in.',
      visual_type: 'pipeline',
      visual_primary: 'Ingest',
      visual_secondary: 'Transform',
      visual_tertiary: 'Deliver',
    },
    {
      icon: 'code',
      title: 'SQL-First Interface',
      description:
        'Write standard SQL against any source with auto-completion, version control, and collaboration.',
      visual_type: 'code',
      code_text: "SELECT user_id, COUNT(*)\nFROM events\nWHERE ts > '2026-01'\nGROUP BY 1",
    },
  ])
)

const pricingPlans = computed(() =>
  rowsFor('pricing_plans', [
    {
      name: 'Starter',
      description: 'For small teams getting started',
      monthly_price: '30',
      yearly_price: '22',
      yearly_billing: '$264 billed annually',
      monthly_billing: 'Billed monthly',
      period: 'per seat / month',
      button_label: 'Get Started',
      button_url: '#',
      features:
        'Up to 5 team members\n1 billion events / month\n7-day data retention\nCommunity support',
    },
    {
      name: 'Pro',
      description: 'For scaling data teams',
      monthly_price: '100',
      yearly_price: '75',
      yearly_billing: '$900 billed annually',
      monthly_billing: 'Billed monthly',
      period: 'per seat / month',
      button_label: 'Get Started',
      button_url: '#',
      featured: true,
      featured_label: 'Most Popular',
      features:
        'Unlimited team members\n50 billion events / month\nUnlimited data retention\nPriority support + SLA\nSSO and audit logs',
    },
    {
      name: 'Enterprise',
      description: 'For mission-critical deployments',
      monthly_price: '500',
      yearly_price: '375',
      yearly_billing: '$4,500 billed annually',
      monthly_billing: 'Billed monthly',
      period: 'starts from / month',
      button_label: 'Contact Sales',
      button_url: '#',
      features:
        'Everything in Pro\nDedicated infrastructure\nCustom integrations\n24/7 dedicated support\n99.99% SLA guarantee',
    },
  ])
)

const faqs = computed(() =>
  rowsFor('faqs', [
    {
      question: 'How long does the free trial last?',
      answer:
        'Every plan includes a 14-day free trial with full access to all features. No credit card required to get started.',
    },
    {
      question: 'Can I switch plans later?',
      answer:
        'Yes. Upgrade or downgrade any time from your account settings. We prorate upgrades and apply remaining balance as credit.',
    },
    {
      question: 'What data sources do you support?',
      answer:
        'Catalyst connects to PostgreSQL, MySQL, BigQuery, Snowflake, Redshift, S3, Kafka, and popular REST APIs.',
    },
    {
      question: 'Is my data secure?',
      answer:
        'Data is encrypted at rest and in transit. Enterprise customers can use dedicated infrastructure and custom encryption keys.',
    },
  ])
)

const footerColumns = computed(() =>
  rowsFor('footer_columns', [
    { title: 'Resources', links: 'Documentation|#\nAPI Reference|#\nBlog|#\nChangelog|#' },
    { title: 'Support', links: 'Help Center|#\nCommunity Forum|#\nContact Us|#\nSystem Status|#' },
    { title: 'Connect', links: 'Twitter / X|#\nGitHub|#\nLinkedIn|#\nYouTube|#' },
  ])
)

const primaryCta = computed(() => ctaConfigFor(content.value.primary_cta))
const ctaEnabled = computed(() =>
  settingBoolean(props.template.tenant_settings, 'website.contact_form_enabled', true)
)

const iconFor = (name: unknown) => {
  const key = stringValue(name, 'analytics')

  if (key === 'pipeline') return GitBranch
  if (key === 'code') return Code2

  return LineChart
}

const planPrice = (plan: ContentRow): string => {
  return stringValue(
    billingCycle.value === 'yearly' ? plan.yearly_price : plan.monthly_price,
    stringValue(plan.yearly_price, '0')
  )
}

const planBilling = (plan: ContentRow): string => {
  return stringValue(
    billingCycle.value === 'yearly' ? plan.yearly_billing : plan.monthly_billing,
    billingCycle.value === 'yearly' ? 'Billed annually' : 'Billed monthly'
  )
}

const setBillingCycle = (cycle: BillingCycle): void => {
  billingCycle.value = cycle
}

const toggleFaq = (index: number): void => {
  const next = new Set(openFaqs.value)

  if (next.has(index)) {
    next.delete(index)
  } else {
    next.add(index)
  }

  openFaqs.value = next
}

const expandAllFaqs = (): void => {
  openFaqs.value = new Set(faqs.value.map((_, index) => index))
}

const collapseAllFaqs = (): void => {
  openFaqs.value = new Set()
}

const closeMenu = (): void => {
  isMenuOpen.value = false
}

watch(isMenuOpen, (open) => {
  document.body.style.overflow = open ? 'hidden' : ''
})

onBeforeUnmount(() => {
  document.body.style.overflow = ''
})
</script>

<template>
  <div class="catalyst min-h-screen bg-[var(--cat-bg)] text-[var(--cat-text)]">
    <nav class="cat-nav" role="navigation" aria-label="Main navigation">
      <div class="cat-nav-inner">
        <a href="#" class="cat-brand" :aria-label="`${businessName} home`" @click="closeMenu">
          <span class="cat-brand-mark"></span>
          {{ businessName }}
        </a>

        <div class="cat-nav-links" :class="{ open: isMenuOpen }">
          <a
            v-for="item in navItems"
            :key="`${item.label}-${item.url}`"
            :href="safeHref(item.url, '#')"
            @click="closeMenu"
          >
            {{ item.label }}
          </a>
        </div>

        <div class="cat-nav-cta">
          <a :href="safeHref(content.login_url, '#')" class="cat-btn cat-btn-ghost">
            {{ contentString('login_label', 'Log In') }}
          </a>
          <a :href="safeHref(content.nav_cta_url, '#pricing')" class="cat-btn cat-btn-acid">
            {{ contentString('nav_cta_label', 'Start Free') }}
          </a>
        </div>

        <button
          type="button"
          class="cat-nav-toggle"
          :class="{ active: isMenuOpen }"
          :aria-expanded="isMenuOpen"
          aria-label="Toggle navigation"
          @click="isMenuOpen = !isMenuOpen"
        >
          <X v-if="isMenuOpen" class="size-5" />
          <Menu v-else class="size-5" />
        </button>
      </div>
    </nav>

    <section class="cat-hero">
      <div class="cat-container">
        <div class="cat-hero-badge">
          <span class="cat-hero-badge-dot"></span>
          {{ contentString('hero_badge', 'v4.2 - Now with real-time sync') }}
        </div>
        <h1>
          {{ contentString('hero_title', 'The analytics platform built for') }}
          <span>{{ contentString('hero_accent_word', 'speed') }}</span>
        </h1>
        <p class="cat-hero-sub">
          {{
            contentString(
              'hero_subtitle',
              'Stop waiting for insights. Catalyst delivers sub-second queries across billions of rows so your team ships decisions, not dashboards.'
            )
          }}
        </p>
        <div class="cat-hero-actions">
          <a
            :href="safeHref(content.hero_primary_url, '#pricing')"
            class="cat-btn cat-btn-acid cat-btn-large"
          >
            {{ contentString('hero_primary_label', 'Start Free Trial') }}
            <ArrowRight class="size-4" />
          </a>
          <a
            :href="safeHref(content.hero_secondary_url, '#')"
            class="cat-btn cat-btn-ghost cat-btn-large"
          >
            {{ contentString('hero_secondary_label', 'Watch Demo') }}
          </a>
        </div>

        <div class="cat-dashboard">
          <div class="cat-dashboard-frame">
            <div class="cat-dashboard-toolbar">
              <div class="cat-dashboard-dots"><span></span><span></span><span></span></div>
              <div class="cat-dashboard-tabs">
                <span
                  v-for="tab in dashboardTabs"
                  :key="String(tab.label)"
                  :class="{ active: tab === activeDashboardTab }"
                >
                  {{ tab.label }}
                </span>
              </div>
              <span class="cat-dashboard-spacer"></span>
            </div>

            <div class="cat-dashboard-body">
              <article
                v-for="stat in dashboardStats"
                :key="String(stat.label)"
                class="cat-dash-stat"
              >
                <p>{{ stat.label }}</p>
                <strong>{{ stat.value }}</strong>
                <span :class="{ down: stringValue(stat.trend) === 'down' }">
                  <ArrowUp class="size-3" />
                  {{ stat.change }}
                </span>
              </article>

              <div class="cat-dashboard-table">
                <div class="cat-table-header">
                  <span>{{ contentString('dashboard_table_title', 'Recent Pipelines') }}</span>
                  <span>{{ contentString('dashboard_filter_label', 'All teams') }}</span>
                </div>
                <div class="cat-table-row head">
                  <span>{{ contentString('dashboard_name_header', 'Pipeline') }}</span>
                  <span>{{ contentString('dashboard_events_header', 'Events / hr') }}</span>
                  <span>{{ contentString('dashboard_latency_header', 'Latency') }}</span>
                  <span>{{ contentString('dashboard_uptime_header', 'Uptime') }}</span>
                  <span>{{ contentString('dashboard_status_header', 'Status') }}</span>
                </div>
                <div v-for="row in dashboardRows" :key="String(row.name)" class="cat-table-row">
                  <span class="cat-table-name">
                    <span>{{ row.badge }}</span>
                    {{ row.name }}
                  </span>
                  <span>{{ row.events }}</span>
                  <span>{{ row.latency }}</span>
                  <span>{{ row.uptime }}</span>
                  <span class="cat-status" :class="String(row.status_variant ?? 'active')">
                    {{ row.status }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="metrics" class="cat-metrics">
      <div class="cat-container">
        <p class="cat-kicker">
          {{ contentString('metrics_label', 'Performance that speaks for itself') }}
        </p>
        <div class="cat-metrics-grid">
          <article v-for="metric in metrics" :key="String(metric.description)" class="cat-metric">
            <strong>
              <span :class="{ highlight: booleanValue(metric.highlight) }">{{ metric.value }}</span>
              <small>{{ metric.unit }}</small>
            </strong>
            <p>{{ metric.description }}</p>
          </article>
        </div>
      </div>
    </section>

    <section id="engine" class="cat-section">
      <div class="cat-container">
        <div class="cat-section-header">
          <p class="cat-section-tag">{{ contentString('engine_tag', 'The Engine') }}</p>
          <h2>{{ contentString('engine_title', 'Everything your data team actually needs') }}</h2>
        </div>
        <div class="cat-bento-grid">
          <article v-for="card in engineCards" :key="String(card.title)" class="cat-card cat-bento">
            <span class="cat-card-icon">
              <component :is="iconFor(card.icon)" class="size-6" />
            </span>
            <h3>{{ card.title }}</h3>
            <p>{{ card.description }}</p>
            <div class="cat-mini-visual">
              <div v-if="stringValue(card.visual_type, 'bars') === 'bars'" class="cat-mini-bars">
                <span
                  v-for="height in [35, 55, 40, 80, 60, 45, 95, 70, 50, 65]"
                  :key="height"
                  :style="{ height: `${height}%` }"
                ></span>
              </div>
              <div
                v-else-if="stringValue(card.visual_type) === 'pipeline'"
                class="cat-mini-pipeline"
              >
                <span>{{ stringValue(card.visual_primary, 'Ingest') }}</span>
                <ArrowRight class="size-4" />
                <span>{{ stringValue(card.visual_secondary, 'Transform') }}</span>
                <ArrowRight class="size-4" />
                <span>{{ stringValue(card.visual_tertiary, 'Deliver') }}</span>
              </div>
              <pre v-else class="cat-mini-code">{{ stringValue(card.code_text) }}</pre>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section id="pricing" class="cat-section">
      <div class="cat-container">
        <div class="cat-section-header">
          <p class="cat-section-tag">{{ contentString('pricing_tag', 'Pricing') }}</p>
          <h2>{{ contentString('pricing_title', 'Simple, transparent pricing') }}</h2>
        </div>
        <div class="cat-toggle-wrap">
          <div class="cat-toggle">
            <button
              type="button"
              :class="{ active: billingCycle === 'monthly' }"
              @click="setBillingCycle('monthly')"
            >
              {{ contentString('monthly_label', 'Monthly') }}
            </button>
            <button
              type="button"
              :class="{ active: billingCycle === 'yearly' }"
              @click="setBillingCycle('yearly')"
            >
              {{ contentString('yearly_label', 'Yearly') }}
            </button>
          </div>
          <span v-if="billingCycle === 'yearly'" class="cat-save">
            {{ contentString('yearly_save_label', 'Save 25%') }}
          </span>
        </div>
        <div class="cat-pricing-grid">
          <article
            v-for="plan in pricingPlans"
            :key="String(plan.name)"
            class="cat-card cat-pricing-card"
            :class="{ featured: booleanValue(plan.featured) }"
          >
            <span v-if="booleanValue(plan.featured)" class="cat-plan-badge">
              {{ stringValue(plan.featured_label, 'Most Popular') }}
            </span>
            <h3>{{ plan.name }}</h3>
            <p class="cat-plan-desc">{{ plan.description }}</p>
            <div class="cat-price">
              <span>{{ stringValue(plan.currency, '$') }}</span>
              <strong>{{ planPrice(plan) }}</strong>
            </div>
            <p class="cat-period">{{ plan.period }}</p>
            <p class="cat-billed" :class="{ annual: billingCycle === 'yearly' }">
              {{ planBilling(plan) }}
            </p>
            <a
              :href="safeHref(plan.button_url, '#')"
              class="cat-btn"
              :class="booleanValue(plan.featured) ? 'cat-btn-acid' : 'cat-btn-ghost'"
            >
              {{ plan.button_label }}
            </a>
            <ul>
              <li v-for="feature in linesFrom(plan.features)" :key="feature">
                <Check class="size-4" />
                {{ feature }}
              </li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <section id="faq" class="cat-section">
      <div class="cat-container">
        <div class="cat-section-header">
          <p class="cat-section-tag">{{ contentString('faq_tag', 'FAQ') }}</p>
          <h2>{{ contentString('faq_title', 'Common questions, straight answers') }}</h2>
        </div>
        <div class="cat-faq-controls">
          <button type="button" @click="expandAllFaqs">
            {{ contentString('faq_expand_label', 'Expand All') }}
          </button>
          <button type="button" @click="collapseAllFaqs">
            {{ contentString('faq_collapse_label', 'Collapse All') }}
          </button>
        </div>
        <div class="cat-faq-list">
          <article
            v-for="(faq, index) in faqs"
            :key="String(faq.question)"
            class="cat-faq-item"
            :class="{ open: openFaqs.has(index) }"
          >
            <button type="button" :aria-expanded="openFaqs.has(index)" @click="toggleFaq(index)">
              {{ faq.question }}
              <span><Plus class="size-4" /></span>
            </button>
            <div class="cat-faq-answer">
              <p>{{ faq.answer }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="cat-cta-banner">
      <div class="cat-container">
        <div class="cat-cta-inner">
          <div>
            <p class="cat-cta-tag">{{ contentString('cta_tag', 'Get started today') }}</p>
            <h2>{{ primaryCta.title }}</h2>
            <p>{{ primaryCta.description }}</p>
            <div class="cat-cta-actions">
              <a
                :href="safeHref(content.cta_primary_url, '#pricing')"
                class="cat-btn cat-btn-acid cat-btn-large"
              >
                {{ contentString('cta_primary_label', 'Start Free Trial') }}
                <ArrowRight class="size-4" />
              </a>
              <a
                :href="safeHref(content.cta_secondary_url, '#')"
                class="cat-btn cat-btn-dark-ghost cat-btn-large"
              >
                {{ contentString('cta_secondary_label', 'Talk to Sales') }}
              </a>
            </div>
          </div>

          <TemplateCta
            :template-id="props.template.id"
            :cta="primaryCta"
            :enabled="ctaEnabled"
            class="cat-cta-form"
            form-class="space-y-3"
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
              <textarea
                v-field-help="ctaFieldDescription(field)"
                v-if="field.type === 'textarea'"
                class="cat-input min-h-24"
                :placeholder="field.placeholder || field.label"
                :required="field.required"
                :value="String(payload[field.key] ?? '')"
                @input="updateField(field, ($event.target as HTMLTextAreaElement).value)"
              />
              <select
                v-field-help="ctaFieldDescription(field)"
                v-else-if="field.type === 'select'"
                class="cat-input"
                :required="field.required"
                :value="String(payload[field.key] ?? '')"
                @change="updateField(field, ($event.target as HTMLSelectElement).value)"
              >
                <option value="">{{ field.placeholder || field.label }}</option>
                <option
                  v-for="option in field.options ?? []"
                  :key="String(option.value)"
                  :value="String(option.value)"
                >
                  {{ option.label }}
                </option>
              </select>
              <label v-else-if="field.type === 'checkbox'" class="cat-checkbox">
                <input
                  v-field-help="ctaFieldDescription(field)"
                  type="checkbox"
                  :required="field.required"
                  :checked="Boolean(payload[field.key])"
                  @change="updateField(field, ($event.target as HTMLInputElement).checked)"
                />
                <span>{{ field.label }}</span>
              </label>
              <input
                v-field-help="ctaFieldDescription(field)"
                v-else-if="field.type === 'file'"
                class="cat-input"
                type="file"
                :required="field.required"
                @change="updateFileField(field, $event)"
              />
              <input
                v-field-help="ctaFieldDescription(field)"
                v-else
                class="cat-input"
                :type="inputTypeFor(field)"
                :placeholder="field.placeholder || field.label"
                :required="field.required"
                :value="String(payload[field.key] ?? '')"
                @input="updateField(field, ($event.target as HTMLInputElement).value)"
              />
            </template>
            <button class="cat-btn cat-btn-acid cat-btn-submit" type="submit" :disabled="loading">
              {{ loading ? 'Sending...' : submitLabel }}
            </button>
            <p v-if="success" class="cat-form-status success" role="status">
              {{ successMessage }}
            </p>
            <p v-if="error" class="cat-form-status error" role="alert">
              {{ error }}
            </p>
          </TemplateCta>
        </div>
      </div>
    </section>

    <footer class="cat-footer">
      <div class="cat-container">
        <div class="cat-footer-grid">
          <div>
            <a href="#" class="cat-brand" :aria-label="`${businessName} home`">
              <span class="cat-brand-mark"></span>
              {{ businessName }}
            </a>
            <p>
              {{
                contentString(
                  'footer_brand_description',
                  'The analytics platform built for speed. Sub-second queries, real-time pipelines, and infinite scale.'
                )
              }}
            </p>
          </div>
          <div v-for="column in footerColumns" :key="String(column.title)">
            <h4>{{ column.title }}</h4>
            <ul>
              <li v-for="link in linkRowsFrom(column.links)" :key="`${link.label}-${link.url}`">
                <a :href="safeHref(link.url, '#')">{{ link.label }}</a>
              </li>
            </ul>
          </div>
        </div>
        <div class="cat-footer-bottom">
          <p>
            {{ contentString('footer_copy', 'Copyright 2026 Catalyst Inc. All rights reserved.') }}
          </p>
          <ul>
            <li v-for="link in linkRowsFrom(content.footer_legal_links)" :key="link.label">
              <a :href="safeHref(link.url, '#')">{{ link.label }}</a>
            </li>
          </ul>
        </div>
      </div>
    </footer>
  </div>
</template>

<style scoped>
.catalyst {
  --cat-bg: var(--template-bg, #fafafa);
  --cat-surface: #f0f0f0;
  --cat-text: var(--template-text, #111111);
  --cat-muted: #666666;
  --cat-dim: #999999;
  --cat-accent: var(--template-primary, #d4ff00);
  --cat-border: #e5e5e5;
  --cat-border-light: #eeeeee;
  --cat-white: #ffffff;
  --cat-section-pad: clamp(80px, 10vw, 140px);
  --cat-container: 1200px;
  font-family:
    var(--template-font, Inter),
    -apple-system,
    BlinkMacSystemFont,
    'SF Pro Display',
    sans-serif;
  line-height: 1.6;
  scroll-behavior: smooth;
  overflow-x: hidden;
}

.cat-container {
  max-width: var(--cat-container);
  margin: 0 auto;
  padding: 0 24px;
}

.cat-nav {
  position: sticky;
  top: 0;
  z-index: 50;
  border-bottom: 1px solid var(--cat-border);
  background: rgb(250 250 250 / 0.88);
  backdrop-filter: blur(20px);
}

.cat-nav-inner {
  display: flex;
  max-width: var(--cat-container);
  height: 64px;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin: 0 auto;
  padding: 0 24px;
}

.cat-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 800;
}

.cat-brand-mark {
  position: relative;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  background: var(--cat-accent);
}

.cat-brand-mark::after {
  position: absolute;
  inset: 7px 5px;
  background: #111111;
  clip-path: polygon(0 50%, 36% 0, 100% 50%, 36% 100%);
  content: '';
}

.cat-nav-links {
  display: flex;
  align-items: center;
  gap: 36px;
}

.cat-nav-links a {
  color: var(--cat-muted);
  font-size: 14px;
  font-weight: 500;
  transition: color 0.2s ease;
}

.cat-nav-links a:hover {
  color: var(--cat-text);
}

.cat-nav-cta {
  display: flex;
  gap: 12px;
}

.cat-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 10px;
  padding: 12px 24px;
  font-size: 15px;
  font-weight: 600;
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.cat-btn:hover {
  transform: translateY(-2px);
}

.cat-btn-acid {
  border: 1px solid var(--cat-accent);
  background: var(--cat-accent);
  color: #111111;
}

.cat-btn-acid:hover,
.cat-btn-ghost:hover {
  box-shadow: 4px 4px 0 var(--cat-text);
}

.cat-btn-ghost {
  border: 1px solid var(--cat-border);
  background: transparent;
  color: var(--cat-text);
}

.cat-btn-large {
  padding: 14px 32px;
}

.cat-btn-dark-ghost {
  border: 1px solid rgb(255 255 255 / 0.16);
  color: rgb(255 255 255 / 0.74);
}

.cat-nav-toggle {
  display: none;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: var(--cat-text);
}

.cat-hero {
  padding-top: 96px;
  text-align: center;
}

.cat-hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 32px;
  border: 1px solid var(--cat-border);
  border-radius: 999px;
  background: var(--cat-surface);
  padding: 6px 16px 6px 8px;
  color: var(--cat-muted);
  font-size: 13px;
  font-weight: 500;
}

.cat-hero-badge-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #22c55e;
  animation: catalyst-pulse 2s ease-in-out infinite;
}

.cat-hero h1 {
  max-width: 820px;
  margin: 0 auto 24px;
  font-size: clamp(42px, 6vw, 76px);
  font-weight: 800;
  line-height: 1.02;
}

.cat-hero h1 span {
  display: inline-block;
  border-radius: 8px;
  background: var(--cat-accent);
  padding: 0 10px;
  color: #111111;
}

.cat-hero-sub {
  max-width: 560px;
  margin: 0 auto 40px;
  color: var(--cat-muted);
  font-size: clamp(16px, 1.8vw, 19px);
}

.cat-hero-actions,
.cat-cta-actions {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 16px;
}

.cat-dashboard {
  position: relative;
  max-width: 1060px;
  margin: 72px auto 0;
}

.cat-dashboard::after {
  position: absolute;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 2;
  height: 160px;
  background: linear-gradient(to top, var(--cat-bg), transparent);
  content: '';
  pointer-events: none;
}

.cat-dashboard-frame,
.cat-card {
  border: 1px solid var(--cat-border);
  background: var(--cat-surface);
  box-shadow: inset 0 1px 0 var(--cat-white);
}

.cat-dashboard-frame {
  overflow: hidden;
  border-radius: 16px 16px 0 0;
}

.cat-dashboard-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  border-bottom: 1px solid var(--cat-border);
  padding: 14px 20px;
}

.cat-dashboard-dots {
  display: flex;
  gap: 7px;
}

.cat-dashboard-dots span {
  width: 11px;
  height: 11px;
  border-radius: 999px;
  background: var(--cat-border);
}

.cat-dashboard-tabs {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 2px;
}

.cat-dashboard-tabs span {
  border-radius: 6px;
  padding: 5px 14px;
  color: var(--cat-dim);
  font-size: 12px;
  font-weight: 500;
}

.cat-dashboard-tabs .active {
  border: 1px solid var(--cat-border);
  background: var(--cat-white);
  color: var(--cat-text);
}

.cat-dashboard-spacer {
  width: 50px;
}

.cat-dashboard-body {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  padding: 20px;
}

.cat-dash-stat,
.cat-dashboard-table {
  border: 1px solid var(--cat-border-light);
  border-radius: 12px;
  background: var(--cat-white);
}

.cat-dash-stat {
  padding: 20px;
  text-align: left;
}

.cat-dash-stat p {
  margin-bottom: 8px;
  color: var(--cat-dim);
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
}

.cat-dash-stat strong {
  display: block;
  font-size: 28px;
  font-weight: 800;
}

.cat-dash-stat span {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 6px;
  color: #16a34a;
  font-size: 12px;
  font-weight: 700;
}

.cat-dash-stat span.down {
  color: #ef4444;
}

.cat-dashboard-table {
  grid-column: 1 / -1;
  overflow: hidden;
  text-align: left;
}

.cat-table-header,
.cat-table-row {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 100px;
  align-items: center;
  gap: 12px;
  border-bottom: 1px solid var(--cat-border-light);
  padding: 12px 20px;
}

.cat-table-header {
  display: flex;
  justify-content: space-between;
  font-size: 14px;
  font-weight: 700;
}

.cat-table-header span:last-child {
  border: 1px solid var(--cat-border);
  border-radius: 6px;
  padding: 4px 12px;
  color: var(--cat-dim);
  font-size: 12px;
}

.cat-table-row {
  font-size: 13px;
}

.cat-table-row.head {
  background: rgb(0 0 0 / 0.02);
  color: var(--cat-dim);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
}

.cat-table-name {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 700;
}

.cat-table-name span {
  display: inline-flex;
  width: 28px;
  height: 28px;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #dcfce7;
  color: #166534;
}

.cat-status {
  display: inline-flex;
  justify-content: center;
  border-radius: 999px;
  padding: 3px 10px;
  font-size: 11px;
  font-weight: 700;
}

.cat-status.active {
  background: #dcfce7;
  color: #166534;
}

.cat-status.pending {
  background: #fef3c7;
  color: #92400e;
}

.cat-status.draft {
  background: var(--cat-surface);
  color: var(--cat-muted);
}

.cat-metrics,
.cat-section,
.cat-cta-banner {
  padding: var(--cat-section-pad) 0;
}

.cat-kicker {
  margin-bottom: 56px;
  color: var(--cat-dim);
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
}

.cat-metrics-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 24px;
}

.cat-metric {
  padding: 48px 24px;
  text-align: center;
}

.cat-metric strong {
  display: block;
  margin-bottom: 12px;
  font-size: clamp(56px, 8vw, 88px);
  font-weight: 900;
  line-height: 1;
}

.cat-metric .highlight {
  border-radius: 6px;
  background: var(--cat-accent);
  padding: 0 6px;
  color: #111111;
}

.cat-metric small {
  color: var(--cat-muted);
  font-size: 0.45em;
}

.cat-metric p {
  max-width: 240px;
  margin: 0 auto;
  color: var(--cat-muted);
  font-size: 15px;
  font-weight: 500;
}

.cat-section-header {
  margin-bottom: 40px;
  text-align: center;
}

.cat-section-tag,
.cat-cta-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 16px;
  color: var(--cat-dim);
  font-size: 13px;
  font-weight: 700;
  text-transform: uppercase;
}

.cat-section-tag::before,
.cat-cta-tag::before {
  width: 6px;
  height: 6px;
  border-radius: 2px;
  background: var(--cat-accent);
  content: '';
}

.cat-section-header h2 {
  max-width: 650px;
  margin: 0 auto;
  font-size: clamp(32px, 4vw, 48px);
  font-weight: 800;
  line-height: 1.1;
}

.cat-bento-grid,
.cat-pricing-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.cat-card {
  border-radius: 16px;
  transition: transform 0.25s ease;
}

.cat-card:hover {
  transform: translateY(-2px);
}

.cat-bento,
.cat-pricing-card {
  padding: 32px 28px;
}

.cat-card-icon {
  display: inline-flex;
  width: 48px;
  height: 48px;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
  border: 1px solid var(--cat-border);
  border-radius: 12px;
  background: var(--cat-white);
}

.cat-bento h3,
.cat-pricing-card h3 {
  margin-bottom: 10px;
  font-size: 20px;
  font-weight: 800;
}

.cat-bento p,
.cat-plan-desc {
  color: var(--cat-muted);
  font-size: 15px;
}

.cat-mini-visual {
  min-height: 120px;
  margin-top: 28px;
  border: 1px solid var(--cat-border-light);
  border-radius: 12px;
  background: var(--cat-white);
  padding: 16px;
}

.cat-mini-bars {
  display: flex;
  height: 80px;
  align-items: flex-end;
  gap: 6px;
}

.cat-mini-bars span {
  flex: 1;
  border-radius: 4px 4px 0 0;
  background: var(--cat-border);
}

.cat-mini-bars span:nth-child(4),
.cat-mini-bars span:nth-child(7) {
  background: var(--cat-accent);
}

.cat-mini-pipeline {
  display: flex;
  align-items: center;
  gap: 12px;
}

.cat-mini-pipeline span {
  display: inline-flex;
  flex: 1;
  min-height: 40px;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  background: #e8e8e8;
  color: var(--cat-muted);
  font-size: 11px;
  font-weight: 700;
}

.cat-mini-pipeline span:first-child {
  background: var(--cat-accent);
  color: #111111;
}

.cat-mini-code {
  overflow-x: auto;
  color: var(--cat-muted);
  font-family: 'SF Mono', 'Fira Code', monospace;
  font-size: 12px;
  line-height: 1.7;
  white-space: pre-wrap;
}

.cat-toggle-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  margin-bottom: 40px;
}

.cat-toggle {
  display: flex;
  border: 1px solid var(--cat-border);
  border-radius: 12px;
  background: var(--cat-surface);
  padding: 4px;
}

.cat-toggle button {
  border-radius: 9px;
  padding: 10px 28px;
  color: var(--cat-muted);
  font-size: 14px;
  font-weight: 700;
}

.cat-toggle button.active {
  background: var(--cat-accent);
  color: #111111;
}

.cat-save {
  color: #16a34a;
  font-size: 12px;
  font-weight: 800;
}

.cat-pricing-card {
  position: relative;
}

.cat-pricing-card.featured {
  border-color: var(--cat-accent);
  border-width: 2px;
}

.cat-plan-badge {
  position: absolute;
  top: -13px;
  left: 50%;
  border-radius: 999px;
  background: var(--cat-accent);
  padding: 4px 16px;
  color: #111111;
  font-size: 12px;
  font-weight: 800;
  transform: translateX(-50%);
  white-space: nowrap;
}

.cat-price {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin-top: 24px;
}

.cat-price span {
  font-size: 24px;
  font-weight: 800;
}

.cat-price strong {
  font-size: 56px;
  font-weight: 900;
  line-height: 1;
}

.cat-period,
.cat-billed {
  color: var(--cat-dim);
  font-size: 14px;
  font-weight: 500;
}

.cat-billed {
  margin-bottom: 28px;
}

.cat-billed.annual {
  display: inline-block;
  border-radius: 6px;
  background: var(--cat-accent);
  padding: 4px 12px;
  color: #111111;
  font-weight: 800;
}

.cat-pricing-card .cat-btn {
  width: 100%;
  margin-bottom: 28px;
}

.cat-pricing-card ul,
.cat-footer ul {
  list-style: none;
}

.cat-pricing-card li {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 14px;
  color: var(--cat-muted);
  font-size: 14px;
  font-weight: 500;
}

.cat-pricing-card li svg {
  color: #22c55e;
}

.cat-faq-controls {
  display: flex;
  max-width: 720px;
  justify-content: flex-end;
  gap: 8px;
  margin: 0 auto 20px;
}

.cat-faq-controls button {
  border: 1px solid var(--cat-border);
  border-radius: 8px;
  background: var(--cat-surface);
  padding: 7px 16px;
  color: var(--cat-muted);
  font-size: 13px;
  font-weight: 700;
}

.cat-faq-list {
  display: grid;
  max-width: 720px;
  gap: 12px;
  margin: 0 auto;
}

.cat-faq-item {
  overflow: hidden;
  border: 1px solid var(--cat-border);
  border-radius: 14px;
  background: var(--cat-surface);
}

.cat-faq-item button {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 22px 24px;
  text-align: left;
  font-size: 15px;
  font-weight: 700;
}

.cat-faq-item button span {
  display: inline-flex;
  width: 28px;
  height: 28px;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--cat-border);
  border-radius: 8px;
  background: var(--cat-white);
  transition: transform 0.25s ease;
}

.cat-faq-item.open button span {
  background: var(--cat-accent);
  transform: rotate(45deg);
}

.cat-faq-answer {
  display: none;
  padding: 0 24px 22px;
  color: var(--cat-muted);
  font-size: 14px;
}

.cat-faq-item.open .cat-faq-answer {
  display: block;
}

.cat-cta-inner {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(280px, 420px);
  gap: 36px;
  align-items: center;
  overflow: hidden;
  border-radius: 24px;
  background: #2a2a2a;
  padding: clamp(48px, 7vw, 80px) clamp(28px, 5vw, 64px);
  color: var(--cat-white);
}

.cat-cta-tag {
  color: rgb(255 255 255 / 0.46);
}

.cat-cta-inner h2 {
  max-width: 580px;
  margin-bottom: 16px;
  font-size: clamp(32px, 4.5vw, 52px);
  font-weight: 800;
  line-height: 1.1;
}

.cat-cta-inner p {
  max-width: 460px;
  margin-bottom: 32px;
  color: rgb(255 255 255 / 0.58);
}

.cat-cta-actions {
  justify-content: flex-start;
}

.cat-cta-form {
  border: 1px solid rgb(255 255 255 / 0.14);
  border-radius: 16px;
  background: rgb(255 255 255 / 0.06);
  padding: 20px;
}

.cat-input {
  width: 100%;
  border: 1px solid rgb(255 255 255 / 0.18);
  border-radius: 10px;
  background: rgb(255 255 255 / 0.08);
  padding: 12px 14px;
  color: white;
  font-size: 14px;
}

.cat-input::placeholder {
  color: rgb(255 255 255 / 0.56);
}

.cat-checkbox {
  display: flex;
  align-items: center;
  gap: 10px;
  color: rgb(255 255 255 / 0.78);
  font-size: 14px;
}

.cat-btn-submit {
  width: 100%;
}

.cat-form-status {
  margin-top: 10px;
  font-size: 13px;
  font-weight: 700;
}

.cat-form-status.success {
  color: #86efac;
}

.cat-form-status.error {
  color: #fca5a5;
}

.cat-footer {
  border-top: 1px solid var(--cat-border);
  padding: 80px 0 40px;
}

.cat-footer-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr;
  gap: 48px;
  margin-bottom: 64px;
}

.cat-footer p {
  max-width: 300px;
  margin-top: 16px;
  color: var(--cat-muted);
  font-size: 14px;
}

.cat-footer h4 {
  margin-bottom: 20px;
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
}

.cat-footer li {
  margin-top: 12px;
}

.cat-footer a {
  color: var(--cat-dim);
  font-size: 14px;
  font-weight: 500;
}

.cat-footer a:hover {
  color: var(--cat-text);
}

.cat-footer-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  border-top: 1px solid var(--cat-border);
  padding-top: 32px;
}

.cat-footer-bottom p {
  margin: 0;
  color: var(--cat-dim);
  font-size: 13px;
}

.cat-footer-bottom ul {
  display: flex;
  gap: 24px;
}

@keyframes catalyst-pulse {
  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.4;
  }
}

@media (max-width: 1024px) {
  .cat-bento-grid,
  .cat-pricing-grid {
    grid-template-columns: 1fr;
    max-width: 480px;
    margin: 0 auto;
  }

  .cat-footer-grid {
    grid-template-columns: 1fr 1fr;
  }

  .cat-cta-inner {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .cat-nav-links,
  .cat-nav-cta {
    display: none;
  }

  .cat-nav-toggle {
    display: inline-flex;
  }

  .cat-nav-links.open {
    position: fixed;
    top: 64px;
    right: 0;
    left: 0;
    display: flex;
    height: calc(100dvh - 64px);
    flex-direction: column;
    align-items: stretch;
    gap: 0;
    overflow-y: auto;
    border-top: 1px solid var(--cat-border);
    background: var(--cat-bg);
    padding: 40px 24px;
  }

  .cat-nav-links.open a {
    padding: 16px 0;
    color: var(--cat-text);
    font-size: 20px;
    font-weight: 700;
  }

  .cat-hero {
    padding-top: 56px;
  }

  .cat-hero-actions,
  .cat-cta-actions {
    flex-direction: column;
  }

  .cat-btn-large {
    width: 100%;
  }

  .cat-dashboard-body,
  .cat-metrics-grid {
    grid-template-columns: 1fr;
  }

  .cat-table-row {
    grid-template-columns: 2fr 1fr 1fr;
  }

  .cat-table-row span:nth-child(4),
  .cat-table-row span:nth-child(5) {
    display: none;
  }

  .cat-dashboard-tabs {
    display: none;
  }

  .cat-dashboard-spacer {
    width: 0;
  }

  .cat-footer-grid {
    grid-template-columns: 1fr;
    gap: 32px;
  }

  .cat-footer-bottom {
    flex-direction: column;
    text-align: center;
  }

  .cat-footer-bottom ul {
    flex-wrap: wrap;
    justify-content: center;
  }
}
</style>
