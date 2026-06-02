export interface SystemSettingItem {
  key: string
  name: string
  label: string
  type: 'string' | 'text' | 'boolean' | 'integer' | 'password'
  value: string | number | boolean | null
  is_public: boolean
}

export interface SystemSettingGroup {
  key: string
  label: string
  settings: SystemSettingItem[]
}

export interface SystemSettingHistoryRecord {
  id: string
  setting_key: string
  previous_value: string | number | boolean | null
  new_value: string | number | boolean | null
  changed_by: {
    id: number | string | null
    name: string | null
    email: string | null
  }
  changed_at: string | null
}

export interface SystemSettingsValues {
  [key: string]: string | number | boolean | null
}

export interface SystemSettingsPayload {
  general: {
    application_name: string
    application_description: string
    logo: string
    favicon: string
    support_email: string
    support_phone: string
    company_address: string
  }
  authentication: {
    allow_tenant_registration: boolean
    require_email_verification: boolean
    default_trial_days: number
  }
  security: {
    session_lifetime_minutes: number
    login_rate_limit_attempts: number
    login_rate_limit_window_minutes: number
    require_strong_passwords: boolean
    minimum_password_length: number
    enable_admin_two_factor: boolean
    allowed_admin_ips: string
  }
  tenant_defaults: {
    default_tenant_timezone: string
    default_tenant_status: string
    default_tenant_trial_days: number
    default_tenant_template_type: string
    default_tenant_template_key: string
  }
  feature_flags: {
    enable_templates_module: boolean
    enable_posts_module: boolean
    enable_analytics_module: boolean
    enable_design_requests_module: boolean
    enable_cta_forms: boolean
    enable_tracking_logs: boolean
  }
  email: {
    mail_driver: string
    smtp_host: string
    smtp_port: number
    smtp_username: string
    smtp_password: string
    sender_name: string
    sender_email: string
  }
  analytics: {
    enable_visitor_tracking: boolean
    enable_cta_tracking: boolean
  }
  storage: {
    maximum_upload_size: number
    allowed_file_types: string
  }
  maintenance: {
    maintenance_mode: boolean
    maintenance_message: string
    maintenance_start_time: string
    maintenance_end_time: string
    allow_admin_bypass: boolean
    maintenance_affected_areas: string
  }
  seo: {
    default_meta_title: string
    default_meta_description: string
    open_graph_image: string
    allow_search_engine_indexing: boolean
    canonical_domain: string
  }
  compliance: {
    privacy_policy_url: string
    terms_of_service_url: string
    cookie_notice_enabled: boolean
    data_retention_days: number
  }
  social: {
    facebook_url: string
    instagram_url: string
    linkedin_url: string
    twitter_url: string
    youtube_url: string
  }
}
