import type { TemplateCtaConfig, TemplateCtaType } from '@/types/templates'

type TemplateCtaPreset = Omit<TemplateCtaConfig, 'type' | 'title' | 'label'> & {
  label: string
}

export const templateCtaTypes: TemplateCtaType[] = [
  'contact_message',
  'quote_request',
  'booking',
  'consultation',
  'support',
  'registration',
  'newsletter',
  'event_registration',
  'job_application',
  'feedback',
  'product_inquiry',
  'service_request',
  'demo_request',
  'lead_capture',
  'file_download',
  'donation',
  'callback_request',
  'custom_form',
]

export const templateCtaFieldTypes = [
  'text',
  'email',
  'phone',
  'textarea',
  'number',
  'date',
  'time',
  'select',
  'checkbox',
  'file',
  'url',
] as const

export const templateCtaPresets: Record<TemplateCtaType, TemplateCtaPreset> = {
  contact_message: {
    label: 'Contact message',
    description: 'Invite visitors to send a direct message.',
    submit_label: 'Send Message',
    success_message: 'Message sent. We will get back to you soon.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true, placeholder: 'Your name' },
      {
        key: 'email',
        label: 'Email',
        type: 'email',
        required: true,
        placeholder: 'you@example.com',
      },
      {
        key: 'message',
        label: 'Message',
        type: 'textarea',
        required: true,
        placeholder: 'How can we help?',
      },
    ],
  },
  quote_request: {
    label: 'Quote request',
    description: 'Collect project details for pricing follow-up.',
    submit_label: 'Request Quote',
    success_message: 'Quote request received. We will review it shortly.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'project_details', label: 'Project Details', type: 'textarea', required: true },
      { key: 'budget', label: 'Budget', type: 'text' },
    ],
  },
  booking: {
    label: 'Booking',
    description: 'Let visitors request an appointment time.',
    submit_label: 'Request Booking',
    success_message: 'Booking request sent. We will confirm availability.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'date', label: 'Preferred Date', type: 'date', required: true },
      { key: 'time', label: 'Preferred Time', type: 'time' },
    ],
  },
  consultation: {
    label: 'Consultation',
    description: 'Collect details for an intro consultation.',
    submit_label: 'Book Consultation',
    success_message: 'Consultation request sent. We will be in touch.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'phone', label: 'Phone', type: 'phone' },
      { key: 'topic', label: 'What would you like to discuss?', type: 'textarea', required: true },
    ],
  },
  support: {
    label: 'Support',
    description: 'Collect support questions or issue reports.',
    submit_label: 'Send Request',
    success_message: 'Support request sent. Our team will review it.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'issue', label: 'Issue', type: 'textarea', required: true },
    ],
  },
  registration: {
    label: 'Registration',
    description: 'Collect basic registration information.',
    submit_label: 'Register',
    success_message: 'Registration received.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'phone', label: 'Phone', type: 'phone' },
    ],
  },
  newsletter: {
    label: 'Newsletter',
    description: 'Invite visitors to subscribe for updates.',
    submit_label: 'Subscribe',
    success_message: 'You are subscribed.',
    fields: [
      {
        key: 'email',
        label: 'Email',
        type: 'email',
        required: true,
        placeholder: 'you@example.com',
      },
    ],
  },
  event_registration: {
    label: 'Event registration',
    description: 'Register visitors for an event.',
    submit_label: 'Reserve Spot',
    success_message: 'Your event registration was received.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'guests', label: 'Guests', type: 'number' },
    ],
  },
  job_application: {
    label: 'Job application',
    description: 'Collect candidate details.',
    submit_label: 'Apply Now',
    success_message: 'Application received.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'resume', label: 'Resume', type: 'file', required: true },
      { key: 'message', label: 'Cover Note', type: 'textarea' },
    ],
  },
  feedback: {
    label: 'Feedback',
    description: 'Ask visitors for feedback.',
    submit_label: 'Send Feedback',
    success_message: 'Thanks for the feedback.',
    fields: [
      { key: 'email', label: 'Email', type: 'email' },
      {
        key: 'rating',
        label: 'Rating',
        type: 'select',
        options: [
          { label: 'Excellent', value: 'excellent' },
          { label: 'Good', value: 'good' },
          { label: 'Needs work', value: 'needs_work' },
        ],
      },
      { key: 'feedback', label: 'Feedback', type: 'textarea', required: true },
    ],
  },
  product_inquiry: {
    label: 'Product inquiry',
    description: 'Collect interest in a product.',
    submit_label: 'Ask About Product',
    success_message: 'Product inquiry sent.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'product', label: 'Product', type: 'text', required: true },
      { key: 'question', label: 'Question', type: 'textarea' },
    ],
  },
  service_request: {
    label: 'Service request',
    description: 'Collect service needs from visitors.',
    submit_label: 'Request Service',
    success_message: 'Service request sent.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'service', label: 'Service Needed', type: 'text', required: true },
      { key: 'details', label: 'Details', type: 'textarea' },
    ],
  },
  demo_request: {
    label: 'Demo request',
    description: 'Let visitors ask for a product demo.',
    submit_label: 'Request Demo',
    success_message: 'Demo request sent.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Work Email', type: 'email', required: true },
      { key: 'company', label: 'Company', type: 'text' },
    ],
  },
  lead_capture: {
    label: 'Lead capture',
    description: 'Capture a simple sales lead.',
    submit_label: 'Get Started',
    success_message: 'Thanks. We will follow up soon.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'interest', label: 'Interest', type: 'textarea' },
    ],
  },
  file_download: {
    label: 'File download',
    description: 'Capture contact details before a file download.',
    submit_label: 'Get Download',
    success_message: 'Your download is ready.',
    fields: [
      { key: 'name', label: 'Name', type: 'text' },
      { key: 'email', label: 'Email', type: 'email', required: true },
    ],
  },
  donation: {
    label: 'Donation',
    description: 'Collect donation interest.',
    submit_label: 'Donate',
    success_message: 'Thank you for your support.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'email', label: 'Email', type: 'email', required: true },
      { key: 'amount', label: 'Amount', type: 'number', required: true },
    ],
  },
  callback_request: {
    label: 'Callback request',
    description: 'Let visitors ask for a callback.',
    submit_label: 'Request Callback',
    success_message: 'Callback request sent.',
    fields: [
      { key: 'name', label: 'Name', type: 'text', required: true },
      { key: 'phone', label: 'Phone', type: 'phone', required: true },
      { key: 'best_time', label: 'Best Time', type: 'text' },
    ],
  },
  custom_form: {
    label: 'Custom form',
    description: 'Build a custom form for this template.',
    submit_label: 'Submit',
    success_message: 'Thanks. Your submission was received.',
    fields: [],
  },
}

export const defaultTemplateCta = (
  type: TemplateCtaType = 'contact_message'
): TemplateCtaConfig => {
  const preset = templateCtaPresets[type]

  return {
    type,
    label: preset.label,
    title: preset.label,
    description: preset.description,
    submit_label: preset.submit_label,
    success_message: preset.success_message,
    fields: preset.fields.map((field) => ({
      ...field,
      options: field.options?.map((option) => ({ ...option })),
    })),
  }
}
