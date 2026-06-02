# Onlyvo Project Guide

## Tech Stack

- Laravel 12 + PHP 8.2
- Vue 3 + TypeScript + Pinia + Vue Router
- Vite + Tailwind CSS + shadcn-vue
- Sanctum Authentication
- Sprout Multi-Tenancy (subdomain based)

## Project Structure

### Backend

app/Modules/{Feature}

- Controllers
- Requests
- Resources
- Services
- Models
- Database/Migrations
- routes/api.php

### Frontend

resources/js/modules/{feature}

- pages
- components
- composables
- api
- store
- routes.ts

## Rules

- Follow existing project structure, naming, and coding patterns.
- Keep code simple, direct, and human-readable.
- Avoid unnecessary abstraction, over-engineering, and unrelated refactoring.
- Reuse existing components, stores, composables, services, and utilities before creating new ones.
- Do not create tests.
- Add short comments only for important logic.
- When changing frontend code, check related backend code if needed.
- When changing backend code, check related frontend code if needed.
- Before creating a migration, check existing migrations first and extend them when appropriate.

## Backend Standards

- Keep controllers thin.
- Use FormRequests for validation.
- Put business logic in Services.
- Use Resources for API responses.
- Use DB transactions for multi-record writes.
- Use typed properties, constructor injection, and return types.
- Tenant-owned Eloquent models use Sprout Multi-Tenancy with `BelongsToTenant`; rely on Sprout's automatic tenant scoping instead of adding duplicate `tenant_id` filters.

## API Conventions

### Route Prefixes

- app/\* → Tenant authenticated APIs
- admin/\* → Admin authenticated APIs
- public/\* → Public tenant APIs

### Tokens

- tenant_token
- admin_token

## Frontend Standards

### Vue Components

Responsible for:

- UI rendering
- Form display
- Button clicks
- Local modal state
- Calling stores/composables

### Stores

Responsible for:

- Shared module state
- Loading states
- Selected records
- List data
- Calling services

### Services

Responsible for:

- API requests only
- Endpoints
- Request/response formatting

### Composables

Responsible for:

- Reusable frontend logic
- Form logic
- Modal logic
- Pagination logic

### Utils

Responsible for:

- Pure helper functions
- No Vue refs
- No API calls

## UI Standards

- Use existing shadcn-vue components whenever possible.
- Use existing Button variants.
- Use BaseTable for standard listings when suitable.
- Use existing status helpers and shared components before creating new ones.
- Use lucide icons already registered in the project.

## Templates

- Template files:
  resources/js/modules/templates/templates/{type}/{template}.vue

- Template components are registered through:
  components/registry.ts

- CTA forms use:
  useTemplateCtaForm.ts

## TypeScript

- Avoid any.
- Use existing shared types when possible.
- Create proper interfaces for payloads and responses.

## Verification

- Run only checks relevant to the files changed.
- Ensure final code is working.
