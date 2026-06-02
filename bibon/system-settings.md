# System Settings Field Guide

## General Settings

### Application Name

The global name of the platform.
Used for browser titles, app branding, and default platform identity.

### Application Description

A short description of the platform.
Used as a default public/meta description when no more specific SEO description is set.

### Logo

The platform logo image.
Uploaded by an administrator and stored as a public file URL.
Used for branding where the app displays the platform logo.

### Favicon

The small browser tab icon.
Uploaded by an administrator and stored as a public file URL.
If no favicon is uploaded, the app falls back to the default `/favicon.ico`.

### Support Email

The public support email address.
Used anywhere the platform needs to show or reference support contact information.

### Support Phone

The public support phone number.
Used for platform support/contact display.

### Company Address

The company or platform business address.
Used for public/company/contact display when needed.

## Authentication Settings

### Allow Tenant Registration

Controls whether public tenant registration is open.
If disabled, users cannot register new tenant accounts from the public registration page.

### Require Email Verification

Controls whether tenant users must have a verified email before logging in.
If enabled, users without `email_verified_at` are blocked from tenant login.

Note: this setting enforces verification, but the app still needs a full email verification flow if one is not already implemented.

### Default Trial Days

Legacy/default trial length setting.
Kept for compatibility, but tenant creation now primarily uses the Tenant Defaults trial days setting.

## Security Settings

### Session Lifetime Minutes

Controls how long login sessions/tokens should remain valid.
Used for Sanctum token expiry and runtime session configuration.

### Login Rate Limit Attempts

The maximum number of failed login attempts allowed before rate limiting starts.

### Login Rate Limit Window Minutes

The time window for login rate limiting.
Example: 5 attempts within 1 minute.

### Require Strong Passwords

When enabled, new passwords must include:

- lowercase letter
- uppercase letter
- number
- symbol

Applies to tenant registration, password reset, and admin-created tenant owner passwords.

### Minimum Password Length

The minimum number of characters required for new passwords.

### Enable Admin Two-Factor Authentication

Stores whether admin two-factor authentication should be enabled.
Currently saved as a platform policy setting.

Note: this does not create a complete 2FA challenge flow unless that feature is implemented separately.

### Allowed Admin IPs

Restricts admin access to specific IP addresses or CIDR ranges.
If blank, all IPs are allowed.

Example:
203.0.113.10
198.51.100.0/24

## Tenant Defaults

### Default Tenant Timezone

The timezone used when creating new tenants if no timezone is explicitly provided.
Example: UTC, Asia/Shanghai, America/New_York.

### Default Tenant Status

The default status for new tenants.
Usually `active` or `inactive`.

### Default Tenant Trial Days

The trial length assigned to new tenants.

### Default Tenant Template Type

Optional default template category/type for new tenants.
Example: business-website, landing-page.

### Default Tenant Template Key

Optional default template key for new tenants.
Example: template-1.

## Feature Flags

### Enable Templates Module

Controls whether the tenant Templates module is available.
If disabled, template routes are hidden/blocked where practical.

### Enable Posts Module

Controls whether the tenant Posts module is available.
If disabled, posts routes are hidden/blocked where practical.

### Enable Analytics Module

Controls whether the Analytics module is available.
This is the master switch for analytics dashboard and analytics behavior.

### Enable Design Requests Module

Controls whether tenants can use Design Requests.
If disabled, design request routes are hidden/blocked where practical.

### Enable CTA Forms

Controls whether public CTA forms are shown and accepted.
If disabled, CTA forms are hidden and submissions are blocked.

### Enable Tracking Logs

Controls whether tenant Tracking Logs are available.
If disabled, tracking log routes are hidden/blocked where practical.

## Email Settings

### Mail Driver

The mail transport used by the platform.
Examples: smtp, sendmail, mailgun, ses, postmark, log.

### SMTP Host

The SMTP server host.
Used when the selected mail driver requires SMTP.

### SMTP Port

The SMTP server port.
Common values: 587, 465, 25.

### SMTP Username

The SMTP username used for authentication.

### SMTP Password

The SMTP password used for authentication.

### Sender Name

The default name used in outgoing emails.
Example: Onlyvo Support.

### Sender Email

The default email address used in outgoing emails.
Example: support@example.com.

## Analytics Settings

### Enable Visitor Tracking

Controls whether Onlyvo records public visitor visits.
If disabled, visitor tracking requests are ignored.

### Enable CTA Tracking

Controls whether Onlyvo records CTA views/clicks/submissions.
If disabled, CTA tracking requests are ignored.

Note: Google Analytics ID and Meta Pixel ID were removed because the platform is not using those third-party tracking tools.

## Storage Settings

### Maximum Upload Size

The maximum allowed upload size, in KB.
Used by image/file upload validation.

Example:
4096 = 4 MB.

### Allowed File Types

Comma-separated list of allowed upload extensions.
Used by upload validation.

Example:
jpg,jpeg,png,webp,gif

## Maintenance Settings

### Maintenance Mode

Turns maintenance mode on or off.
When enabled, affected public/app areas show a maintenance screen.

### Maintenance Message

The message shown to users during maintenance mode.

### Maintenance Start Time

Optional start time for maintenance mode.
If blank, maintenance can start immediately when enabled.

### Maintenance End Time

Optional end time for maintenance mode.
If blank, maintenance continues until manually disabled.

### Allow Admin Bypass

Allows admin routes to remain accessible during maintenance.
Recommended to keep enabled so administrators can still log in and turn maintenance off.

### Maintenance Affected Areas

Limits maintenance mode to specific paths.
If blank, maintenance applies globally.

Examples:
/templates
/posts
/

If set to `/templates`, only paths starting with `/templates` are affected.

## Public SEO Settings

### Default Meta Title

The default browser/SEO title for the platform.
Used when a page does not provide its own title.

### Default Meta Description

The default SEO description for the platform.
Used when a page does not provide its own description.

### Open Graph Image

The default social sharing preview image.
Shown when links are shared on platforms like Facebook, LinkedIn, Slack, Discord, etc.

Recommended size:
1200x630 pixels.

### Allow Search Engine Indexing

Controls whether search engines are allowed to index the platform.
If disabled, the app outputs a noindex/nofollow robots meta tag.

### Canonical Domain

The official public domain for SEO canonical links.
Helps search engines understand the main/original domain for pages.

Example:
https://example.com

If blank, no canonical URL is generated.

## Compliance Settings

### Privacy Policy URL

Public URL to the platform privacy policy.
Shown in public compliance links where practical.

### Terms of Service URL

Public URL to the platform terms of service.
Shown in public compliance links where practical.

### Cookie Notice Enabled

Controls whether the public cookie notice/banner is shown.

### Data Retention Days

The number of days data should be retained.
Stored as a compliance policy setting and can be used by cleanup/data retention processes.

## Social Links

### Facebook URL

Public Facebook profile/page URL.

### Instagram URL

Public Instagram profile URL.

### LinkedIn URL

Public LinkedIn profile/company page URL.

### X/Twitter URL

Public X/Twitter profile URL.

### YouTube URL

Public YouTube channel URL.

## Settings History

### Changed Setting Key

The setting key that was changed.
Example: security.session_lifetime_minutes.

### Previous Value

The value before the change.

### New Value

The value after the change.

### Changed By

The administrator who made the change.

### Changed At

The timestamp when the change was recorded.

Settings History is read-only and helps admins audit configuration changes.
