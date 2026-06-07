<?php

namespace App\Admin\Templates\Services;

use App\Admin\AuditLogs\Services\AuditLogService;
use App\Admin\Templates\Models\Template;
use App\Admin\Templates\Models\TemplateCatalogItem;
use App\Admin\Templates\Models\TemplateCatalogItemVersion;
use App\Admin\Templates\Models\WebsiteType;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TemplateCatalogMaintenanceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return DB::transaction(function () use ($data, $actor): TemplateCatalogItem {
            $item = TemplateCatalogItem::query()->create($this->catalogPayload($data));

            $this->recordVersion($item, 'created', $data['changelog'] ?? 'Initial catalog version.', $actor);
            app(AuditLogService::class)->recordModel('template.created', $item, $actor, request(), null, $this->catalogPayload($item->toArray()), 'template');

            return $item->fresh('websiteType');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(TemplateCatalogItem $item, array $data, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return DB::transaction(function () use ($item, $data, $actor): TemplateCatalogItem {
            $previous = $this->catalogPayload($item->toArray());

            $item->update($this->catalogPayload($data));
            $updated = $item->fresh();

            $this->recordVersion($updated, 'updated', $data['changelog'] ?? 'Catalog item updated.', $actor);
            app(AuditLogService::class)->recordModel('template.updated', $updated, $actor, request(), $previous, $this->catalogPayload($updated->toArray()), 'template');

            return $item->fresh('websiteType');
        });
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    public function cloneItem(TemplateCatalogItem $item, array $overrides = [], ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return DB::transaction(function () use ($item, $overrides, $actor): TemplateCatalogItem {
            $key = Str::slug((string) ($overrides['key'] ?? $item->key.' copy'));
            $key = $this->uniqueKey($item->website_type_id, $key ?: $item->key.'-copy');

            $clone = TemplateCatalogItem::query()->create([
                'website_type_id' => $item->website_type_id,
                'key'             => $key,
                'name'            => trim((string) ($overrides['name'] ?? $item->name.' Copy')),
                'description'     => $overrides['description'] ?? $item->description,
                'preview_image'   => $item->preview_image,
                'field_schema'    => $item->field_schema    ?? [],
                'default_content' => $item->default_content ?? [],
                'is_active'       => false,
            ]);

            $this->recordVersion($clone, 'cloned', $overrides['changelog'] ?? 'Cloned from '.$item->name.'.', $actor);
            app(AuditLogService::class)->recordModel('template.cloned', $clone, $actor, request(), $this->catalogPayload($item->toArray()), $this->catalogPayload($clone->toArray()), 'template');

            return $clone->fresh('websiteType');
        });
    }

    public function publish(TemplateCatalogItem $item, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return $this->setActive($item, true, 'published', 'Template published to tenant catalog.', $actor);
    }

    public function unpublish(TemplateCatalogItem $item, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return $this->setActive($item, false, 'unpublished', 'Template unpublished from tenant catalog.', $actor);
    }

    public function delete(TemplateCatalogItem $item, ?Authenticatable $actor = null): void
    {
        $previous = $item->attributesToArray();

        $item->delete();
        app(AuditLogService::class)->recordModel('template.deleted', $item, $actor, request(), $previous, null, 'template');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function importSchema(TemplateCatalogItem $item, array $data, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        return DB::transaction(function () use ($item, $data, $actor): TemplateCatalogItem {
            $previous = [
                'field_schema'    => $item->field_schema    ?? [],
                'default_content' => $item->default_content ?? [],
            ];

            $item->update([
                'field_schema'    => $data['field_schema']    ?? [],
                'default_content' => $data['default_content'] ?? [],
            ]);
            $updated = $item->fresh();

            $this->recordVersion($updated, 'imported', $data['changelog'] ?? 'Schema and default content imported.', $actor);
            app(AuditLogService::class)->recordModel('template.schema_imported', $updated, $actor, request(), $previous, [
                'field_schema'    => $updated->field_schema    ?? [],
                'default_content' => $updated->default_content ?? [],
            ], 'template');

            return $item->fresh('websiteType');
        });
    }

    public function rollback(TemplateCatalogItem $item, TemplateCatalogItemVersion $version, ?Authenticatable $actor = null): TemplateCatalogItem
    {
        abort_unless($version->template_catalog_item_id === $item->id, 404);

        return DB::transaction(function () use ($item, $version, $actor): TemplateCatalogItem {
            $previous = $this->catalogPayload($item->toArray());

            $item->update($this->catalogPayload($version->snapshot));
            $updated = $item->fresh();

            $this->recordVersion($updated, 'rolled_back', 'Rolled back to version '.$version->version.'.', $actor);
            app(AuditLogService::class)->recordModel('template.rolled_back', $updated, $actor, request(), $previous, $this->catalogPayload($updated->toArray()), 'template', null, [
                'version' => $version->version,
            ]);

            return $item->fresh('websiteType');
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function versions(TemplateCatalogItem $item): array
    {
        if (! Schema::hasTable('template_catalog_item_versions')) {
            return [];
        }

        return TemplateCatalogItemVersion::query()
            ->where('template_catalog_item_id', $item->id)
            ->latest('version')
            ->get()
            ->map(fn (TemplateCatalogItemVersion $version): array => [
                'id'                 => $version->id,
                'version'            => $version->version,
                'action'             => $version->action,
                'changelog'          => $version->changelog,
                'created_by_name'    => $version->created_by_name,
                'created_by_email'   => $version->created_by_email,
                'created_at'         => $version->created_at?->toISOString(),
                'field_schema_count' => count($version->snapshot['field_schema'] ?? []),
                'is_active'          => (bool) ($version->snapshot['is_active'] ?? false),
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(TemplateCatalogItem $item): array
    {
        return [
            'website_type_slug' => $item->websiteType?->slug,
            'template_key'      => $item->key,
            'name'              => $item->name,
            'description'       => $item->description,
            'field_schema'      => $item->field_schema    ?? [],
            'default_content'   => $item->default_content ?? [],
            'exported_at'       => now()->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function usageSummary(TemplateCatalogItem $item): array
    {
        $baseQuery = Template::query()
            ->where('website_type_id', $item->website_type_id)
            ->where('template_key', $item->key);

        return [
            'total'             => (clone $baseQuery)->count(),
            'published'         => (clone $baseQuery)->where('status', 'published')->count(),
            'draft'             => (clone $baseQuery)->where('status', 'draft')->count(),
            'tenants'           => (clone $baseQuery)->distinct('tenant_id')->count('tenant_id'),
            'default_instances' => (clone $baseQuery)->where('is_default', true)->count(),
            'recent'            => (clone $baseQuery)
                ->with('tenant')
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (Template $template): array => [
                    'id'            => $template->id,
                    'name'          => $template->name,
                    'business_name' => $template->business_name,
                    'status'        => $template->status,
                    'tenant_id'     => $template->tenant_id,
                    'tenant_name'   => $template->tenant?->name,
                    'updated_at'    => $template->updated_at?->toISOString(),
                ])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validation(TemplateCatalogItem $item): array
    {
        $websiteType = $item->websiteType ?: WebsiteType::query()->find($item->website_type_id);
        $relative    = 'resources/js/tenant/templates/templates/'.($websiteType?->slug ?? '').'/'.$item->key.'.vue';
        $path        = base_path($relative);
        $exists      = is_file($path);
        $missingDeps = $exists ? $this->missingDependencies($path) : [];

        return [
            'template_file' => [
                'path'   => $relative,
                'exists' => $exists,
            ],
            'registry_key' => '../templates/'.($websiteType?->slug ?? '').'/'.$item->key.'.vue',
            'dependencies' => [
                'missing' => $missingDeps,
                'ok'      => $missingDeps === [],
            ],
            'render' => [
                'ok'      => $exists && $missingDeps === [],
                'message' => $exists
                    ? ($missingDeps === [] ? 'Template component is resolvable by the Vite registry.' : 'Template file has missing local dependencies.')
                    : 'Template Vue file is missing.',
            ],
        ];
    }

    /**
     * @return array<string, array<int, array{key: string, label: string, passed: bool}>>
     */
    public function qaChecklist(TemplateCatalogItem $item): array
    {
        $validation = $this->validation($item);
        $schema     = $item->field_schema    ?? [];
        $content    = $item->default_content ?? [];

        return [
            'mobile' => [
                ['key' => 'file_exists', 'label' => 'Template file exists', 'passed' => (bool) data_get($validation, 'template_file.exists')],
                ['key' => 'renders', 'label' => 'Template can resolve without missing dependencies', 'passed' => (bool) data_get($validation, 'render.ok')],
                ['key' => 'cta_schema', 'label' => 'CTA fields include mobile-friendly labels', 'passed' => $this->schemaHasReadableLabels($schema)],
                ['key' => 'preview_image', 'label' => 'Preview image is configured', 'passed' => trim((string) $item->preview_image) !== ''],
            ],
            'desktop' => [
                ['key' => 'file_exists', 'label' => 'Template file exists', 'passed' => (bool) data_get($validation, 'template_file.exists')],
                ['key' => 'dependencies', 'label' => 'No missing local dependencies', 'passed' => (bool) data_get($validation, 'dependencies.ok')],
                ['key' => 'default_content', 'label' => 'Default content is populated', 'passed' => $content !== []],
                ['key' => 'schema_fields', 'label' => 'Schema fields are configured', 'passed' => count($schema) > 0],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function catalogPayload(array $data): array
    {
        return [
            'website_type_id' => $data['website_type_id'],
            'key'             => $data['key'],
            'name'            => $data['name'],
            'description'     => $data['description']     ?? null,
            'preview_image'   => $data['preview_image']   ?? null,
            'field_schema'    => $data['field_schema']    ?? [],
            'default_content' => $data['default_content'] ?? [],
            'is_active'       => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function setActive(TemplateCatalogItem $item, bool $active, string $action, string $changelog, ?Authenticatable $actor): TemplateCatalogItem
    {
        return DB::transaction(function () use ($item, $active, $action, $changelog, $actor): TemplateCatalogItem {
            $previous = ['is_active' => (bool) $item->is_active];
            $item->update(['is_active' => $active]);
            $updated = $item->fresh();

            $this->recordVersion($updated, $action, $changelog, $actor);
            app(AuditLogService::class)->recordModel('template.'.$action, $updated, $actor, request(), $previous, ['is_active' => (bool) $updated->is_active], 'template');

            return $item->fresh('websiteType');
        });
    }

    private function recordVersion(TemplateCatalogItem $item, string $action, ?string $changelog, ?Authenticatable $actor): void
    {
        if (! Schema::hasTable('template_catalog_item_versions')) {
            return;
        }

        $version = ((int) TemplateCatalogItemVersion::query()
            ->where('template_catalog_item_id', $item->id)
            ->max('version')) + 1;

        TemplateCatalogItemVersion::query()->create([
            'template_catalog_item_id' => $item->id,
            'version'                  => $version,
            'action'                   => $action,
            'changelog'                => $changelog,
            'snapshot'                 => $this->catalogPayload($item->toArray()),
            'created_by_user_id'       => $actor?->getAuthIdentifier(),
            'created_by_name'          => data_get($actor, 'name'),
            'created_by_email'         => data_get($actor, 'email'),
        ]);
    }

    private function uniqueKey(string $websiteTypeId, string $baseKey): string
    {
        $key = $baseKey;
        $i   = 2;

        while (TemplateCatalogItem::query()->where('website_type_id', $websiteTypeId)->where('key', $key)->exists()) {
            $key = $baseKey.'-'.$i;
            $i++;
        }

        return $key;
    }

    /**
     * @return array<int, string>
     */
    private function missingDependencies(string $path): array
    {
        $contents = file_get_contents($path) ?: '';
        preg_match_all('/(?:from\s+[\'"]|import\(\s*[\'"])([^\'"]+)[\'"]/', $contents, $matches);

        return collect($matches[1] ?? [])
            ->filter(fn (string $import): bool => str_starts_with($import, '.') || str_starts_with($import, '@/'))
            ->reject(fn (string $import): bool => $this->dependencyExists($path, $import))
            ->values()
            ->all();
    }

    private function dependencyExists(string $sourcePath, string $import): bool
    {
        $base = str_starts_with($import, '@/')
            ? resource_path('js/'.substr($import, 2))
            : dirname($sourcePath).DIRECTORY_SEPARATOR.$import;

        $base = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $base);

        foreach (['', '.ts', '.js', '.vue', '.json', DIRECTORY_SEPARATOR.'index.ts', DIRECTORY_SEPARATOR.'index.js', DIRECTORY_SEPARATOR.'index.vue'] as $suffix) {
            if (is_file($base.$suffix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     */
    private function schemaHasReadableLabels(array $schema): bool
    {
        if ($schema === []) {
            return false;
        }

        return collect($schema)->every(fn (array $field): bool => trim((string) ($field['label'] ?? '')) !== '');
    }
}
