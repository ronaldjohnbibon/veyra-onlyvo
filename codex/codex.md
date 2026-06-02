# Onlyvo Project Guide

## Stack

- Laravel 12 + PHP 8.2
- Vue 3 + TypeScript + Pinia + Vue Router
- Vite + Tailwind CSS + shadcn-vue
- Sanctum auth
- Sprout subdomain tenancy

## Architecture

Onlyvo is split into two apps plus shared code:

- Backend: `app/Admin`, `app/Tenant`, `app/Shared`
- Frontend: `resources/js/admin`, `resources/js/tenant`, `resources/js/shared`
- Routes: `routes/admin.php`, `routes/tenant.php`; `routes/api.php` only includes them.

Keep Admin and Tenant separate:

- Admin backend imports only `App\Admin` or `App\Shared`.
- Tenant backend imports only `App\Tenant` or `App\Shared`.
- Admin frontend imports only `@/admin` or `@/shared`.
- Tenant frontend imports only `@/tenant` or `@/shared`.
- Move code to shared only when it is truly context-neutral.

## Backend Rules

- Admin code must not depend on Sprout tenant scoping.
- Tenant code should keep Sprout tenant behavior where needed.
- Admin and Tenant models may point to the same DB table, but use separate classes.
- Tenant-owned models use Sprout `BelongsToTenant`; do not add duplicate tenant filters unless required.
- Keep controllers thin; use FormRequests, Resources, Services, typed code, and DB transactions for multi-record writes.
- Migrations may stay in feature/database paths.

## API Rules

- `admin/*` -> Admin APIs, no `Route::tenanted()`, uses `admin_token`.
- `app/*` -> Tenant authenticated APIs, tenant routes stay inside `Route::tenanted()`, uses `tenant_token`.
- `public/*` -> Public tenant APIs, keep tenant-context routes inside `Route::tenanted()`.
- Preserve existing route paths, response shapes, auth behavior, and UI behavior unless explicitly changing them.

## Frontend Rules

- Put files inside their feature folder. Avoid loose root feature files.
- Example: admin auth belongs in `resources/js/admin/auth`; admin dashboard belongs in `resources/js/admin/dashboard`.
- Shared UI, API client, stores, types, utilities, and neutral template helpers live under `resources/js/shared`.
- Vue pages render UI and call stores/composables.
- Stores hold feature state and call API services.
- Services only make API requests.
- Utils are pure helpers.

## Templates

- Tenant template files: `resources/js/tenant/templates/templates/{type}/{template}.vue`
- Tenant template registry: `resources/js/tenant/templates/components/registry.ts`
- Shared template helpers: `resources/js/shared/templates`
- CTA form composable: `resources/js/tenant/templates/composables/useTemplateCtaForm.ts`

## Verification

- Run checks relevant to the change.
- Backend: `php artisan test`
- Frontend: `npm.cmd run lint`, `npm.cmd run typecheck`, `npm.cmd run build`
- After moves, search for stale imports and Admin/Tenant cross-imports.

## General Rules

- Follow existing project structure, naming, and coding patterns.
- Keep code simple, direct, and human-readable.
- Avoid unnecessary abstraction, over-engineering, and unrelated refactoring.
- Reuse existing components, stores, composables, services, and utilities when they fit the current app context.
- Do not create tests.
- Add short comments only for important logic.
- When changing frontend code, check related backend code if needed.
- When changing backend code, check related frontend code if needed.
- Before creating a migration, check existing migrations first and extend them when appropriate.
