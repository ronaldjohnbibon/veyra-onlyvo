# Development Notes

- Add new backend features as modules under `app/Modules/{Feature}`.
- Add new frontend features under `resources/js/modules/{feature}`.
- Register backend API routes from `routes/api.php`.
- Register frontend route modules from `resources/js/router.ts`.
- Use the shared `http` client instead of creating new Axios instances.
- Keep tenant-protected backend routes inside `Route::tenanted()` and `api.auth` middleware when they require both tenant context and authentication.
- Keep admin routes under the `admin` API prefix so the shared HTTP client uses the correct token.
