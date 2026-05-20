# Onlyvo

Onlyvo is a Laravel 12 application with a Vue 3 single-page frontend. The current app supports tenant registration and login, tenant sidebar management, and a separate admin login/dashboard flow.

The backend is organized by feature modules under `app/Modules`, while the frontend mirrors that idea under `resources/js/modules`.

## Tech Stack

- Laravel 12, PHP 8.2, Sanctum API tokens
- Sprout multi tenancy with tenant resolution by subdomain
- Vue 3, TypeScript, Vue Router, Pinia
- Vite, Tailwind CSS 4, reka-ui based UI components
- Axios through a shared API client

## Architecture

The app uses a modular monolith structure. Each backend feature owns its routes, HTTP layer, business logic, database model, and migrations inside `app/Modules/{Feature}`. The frontend follows the same feature-oriented layout so pages, route definitions, Pinia stores, and API services stay close to the feature they support.

Laravel serves the SPA from `routes/web.php`:

```php
Route::view('/{any?}', 'app')->where('any', '.*');
```

The Blade view at `resources/views/app.blade.php` loads `resources/css/app.css` and `resources/js/app.ts` through Vite. After that, Vue Router owns navigation in the browser.

API routes are loaded from `routes/api.php`:

```php
require base_path('app/Modules/Auth/routes/api.php');
require base_path('app/Modules/Sidebar/routes/api.php');
```

## Backend Flow

All API responses follow a consistent JSON shape through the base controller:

- Success: `status`, `message`, `data`
- Error: `status`, `message`, `errors`

`bootstrap/app.php` also normalizes JSON errors for validation, domain errors, authentication, authorization, and missing resources.

## Frontend Flow

`resources/js/app.ts` creates the Vue app, installs Pinia and Vue Router, then mounts `App.vue`.

`resources/js/router.ts` combines routes from:

- `modules/auth/routes.ts`
- `modules/admin/routes.ts`
- `modules/admin/sidebar/routes.ts`
- `modules/sidebar/routes.ts`

The global route guard initializes stored sessions before allowing navigation:

- `requiresAuth` protects tenant pages.
- `requiresAdminAuth` protects admin pages.
- Authenticated tenant users are redirected away from login/register pages.
- Authenticated admin users are redirected away from the admin login page.

The shared Axios client handles common API behavior:

- Uses `VITE_API_URL` or defaults to `/api`.
- Prefixes tenant API calls with `app/` when no `app/` or `admin/` prefix is present.
- Uses `tenant_token` for app routes and `admin_token` for admin routes.
- Shows success toasts for non-GET requests.
- Shows error toasts for failed requests.
- Opens the global confirmation dialog before DELETE requests.

## Coding Patterns

Backend patterns:

- Feature code is grouped under `app/Modules`.
- Routes stay inside each module and are included from `routes/api.php`.
- Form requests own validation rules.
- Controllers stay thin and delegate business logic to services or actions.
- Resources shape API output.
- Models define fillable fields, casts, and relationships.
- Sanctum tokens are used for API authentication.
- Sprout handles tenant resolution and tenant relationship enforcement.

Frontend patterns:

- Feature modules own their routes, pages, API service, and store.
- Pinia setup stores hold form state, loading state, API errors, auth tokens, and navigation side effects.
- API service files wrap Axios calls and keep pages/stores away from raw URLs.
- Vue components use `<script setup lang="ts">`.
- Shared UI primitives live in `resources/js/components/ui`.
- `@` aliases to `resources/js`.

## Coding Style

PHP follows Laravel conventions:

- PSR-4 namespaces under `App\Modules`.
- Typed method signatures and return types.
- Constructor property promotion for dependencies.
- `readonly` dependencies where mutation is not needed.
- Enums for fixed user types.
- Translation strings for auth messages.

Vue and TypeScript follow these conventions:

- Composition API with `<script setup>`.
- Strict TypeScript types for form payloads and users.
- Pinia stores named by feature, such as `tenant-auth` and `admin-auth`.
- Route names use descriptive names like `TenantLogin`, `admin.dashboard`, and `sidebar.index`.
- Tailwind utility classes are used directly in pages and UI components.

## Setup

Install dependencies:

```bash
composer install
npm install
```

Create the environment file and app key:

```bash
cp .env.example .env
php artisan key:generate
```

For the default SQLite setup, make sure the database file exists:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Run migrations:

```bash
php artisan migrate
```

Start development:

```bash
composer run dev
```

This runs the Laravel server, queue listener, log tailing, and Vite dev server together.

Build frontend assets:

```bash
npm run build
```

Useful checks:

```bash
npm run typecheck
npm run lint
composer run test
```

## Environment Notes

Important values in `.env.example`:

- `APP_URL=http://localhost`
- `TENANTED_DOMAIN=localhost`
- `DB_CONNECTION=sqlite`
- `VITE_API_URL=` defaults the frontend API base URL to `/api`

Tenant routes depend on subdomain resolution. In local development, tenant registration redirects to a subdomain such as `company.localhost/login`.

Admin users must have `user_type` set to `admin`. The current admin flow expects an existing admin account; create one through a seeder, tinker, or a database migration before using `/admin/login`.

## Development Notes

- Add new backend features as modules under `app/Modules/{Feature}`.
- Add new frontend features under `resources/js/modules/{feature}`.
- Register backend API routes from `routes/api.php`.
- Register frontend route modules from `resources/js/router.ts`.
- Use the shared `http` client instead of creating new Axios instances.
- Keep tenant-protected backend routes inside `Route::tenanted()` and `api.auth` middleware when they require both tenant context and authentication.
- Keep admin routes under the `admin` API prefix so the shared HTTP client uses the correct token.
