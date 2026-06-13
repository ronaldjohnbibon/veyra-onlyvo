<?php

namespace App\Tenant\Templates\Services;

use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Models\TemplateCatalogItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TemplateService
{
    public function __construct(private readonly TemplateCatalogService $catalog) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Template
    {
        return DB::transaction(function () use ($data): Template {
            $data         = $this->normalizeTemplate($data);
            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'] ?? $data['name']);
            $data         = $this->storeUploadedImages($data);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults($data['tenant_id']);
            }

            $template = Template::create($data);

            return $template->fresh(['tenant', 'websiteType']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Template $template, array $data): Template
    {
        return DB::transaction(function () use ($template, $data): Template {
            $data = $this->normalizeTemplate($data);

            if (! array_key_exists('slug', $data) || ! $data['slug']) {
                $data['slug'] = $data['name'];
            }

            $data['slug'] = $this->slugForTemplate($data['tenant_id'], $data['slug'], $template->id);
            $data         = $this->storeUploadedImages($data, $template);

            if (! empty($data['is_default'])) {
                $this->clearTenantDefaults($data['tenant_id'], $template->id);
            }

            $template->update($data);

            return $template->fresh(['tenant', 'websiteType']);
        });
    }

    public function resetToDefault(Template $template): Template
    {
        return DB::transaction(function () use ($template): Template {
            $catalogItem = TemplateCatalogItem::query()
                ->with('websiteType')
                ->where('website_type_id', $template->website_type_id)
                ->where('key', $template->template_key)
                ->where('is_active', true)
                ->first();

            if (! $catalogItem) {
                throw ValidationException::withMessages([
                    'template_key' => 'The original template defaults are no longer available.',
                ]);
            }

            $template->update($this->defaultDesignPayload($catalogItem));

            return $template->fresh(['tenant', 'websiteType']);
        });
    }

    public function delete(Template $template): void
    {
        $template->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTemplate(array $data): array
    {
        foreach (['name', 'slug', 'template_key', 'business_name', 'logo', 'font_family'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = trim((string) $data[$field]);
            }
        }

        if (isset($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }

        if (isset($data['is_default'])) {
            $data['is_default'] = (bool) $data['is_default'];
        }

        $templateKey = (string) ($data['template_key'] ?? '');

        if ($templateKey === '' || ! $this->catalog->exists($templateKey, $data['website_type_id'] ?? null)) {
            throw ValidationException::withMessages([
                'template_key' => 'Select an available website template.',
            ]);
        }

        return $this->syncContentFromDetails($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function storeUploadedImages(array $data, ?Template $template = null): array
    {
        $folder = 'templates/'.(string) $data['tenant_id'].'/'.($template?->id ?? 'shared').'/images';

        if (isset($data['logo'])) {
            $data['logo'] = $this->storeDataUrlImage((string) $data['logo'], $folder);
        }

        $catalogItem = TemplateCatalogItem::query()
            ->where('website_type_id', $data['website_type_id'] ?? null)
            ->where('key', $data['template_key'] ?? null)
            ->first();
        $schema = $catalogItem?->field_schema ?? [];

        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = $this->storeContentImages($data['content'], $schema, $folder);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    private function storeContentImages(array $content, array $schema, string $folder): array
    {
        foreach ($schema as $field) {
            $key = (string) ($field['key'] ?? '');

            if ($key === '' || ! array_key_exists($key, $content)) {
                continue;
            }

            if (($field['type'] ?? null) === 'image' && is_string($content[$key])) {
                $content[$key] = $this->storeDataUrlImage($content[$key], $folder);

                continue;
            }

            if (($field['type'] ?? null) === 'repeater' && is_array($content[$key])) {
                $nestedSchema  = is_array($field['fields'] ?? null) ? $field['fields'] : [];
                $content[$key] = array_map(function (mixed $row) use ($nestedSchema, $folder): mixed {
                    return is_array($row) ? $this->storeContentImages($row, $nestedSchema, $folder) : $row;
                }, $content[$key]);
            }
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function syncContentFromDetails(array $data): array
    {
        if (! isset($data['content']) || ! is_array($data['content'])) {
            $data['content'] = [];
        }

        $content = $data['content'];
        $this->setExistingContentValue($content, ['business_name', 'display_name'], (string) ($data['business_name'] ?? ''));

        $contactInfo = is_array($data['contact_info'] ?? null) ? $data['contact_info'] : [];
        $this->setExistingContentValue($content, ['contact_email', 'email'], (string) ($contactInfo['email'] ?? ''));
        $this->setExistingContentValue($content, ['contact_phone', 'phone'], (string) ($contactInfo['phone'] ?? ''));
        $this->setExistingContentValue($content, ['contact_address', 'contact_location', 'location', 'address'], (string) ($contactInfo['address'] ?? ''));

        $data['content'] = $content;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, string>  $keys
     */
    private function setExistingContentValue(array &$content, array $keys, string $value): void
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $content)) {
                $content[$key] = $value;
            }
        }
    }

    private function storeDataUrlImage(string $value, string $folder): string
    {
        if (! str_starts_with($value, 'data:image/')) {
            return $value;
        }

        if (! preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,(.+)$/i', $value, $matches)) {
            throw ValidationException::withMessages([
                'content' => 'Template image uploads must be PNG, JPG, WebP, or GIF files.',
            ]);
        }

        $binary = base64_decode($matches[2], true);

        if ($binary === false || strlen($binary) > 4 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'content' => 'Template image uploads must be valid image files under 4 MB.',
            ]);
        }

        $extension = strtolower($matches[1]) === 'jpeg' ? 'jpg' : strtolower($matches[1]);
        $path      = $folder.'/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($path, $binary);

        return '/storage/'.$path;
    }

    private function slugForTemplate(string $tenantId, string $value, ?string $ignoreId = null): string
    {
        $baseSlug  = Str::slug($value) ?: 'site';
        $slug      = $baseSlug;
        $nextIndex = 2;

        while ($this->slugExists($tenantId, $slug, $ignoreId)) {
            $slug = $baseSlug.'-'.$nextIndex++;
        }

        return $slug;
    }

    private function slugExists(string $tenantId, string $slug, ?string $ignoreId = null): bool
    {
        return Template::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }

    private function clearTenantDefaults(string $tenantId, ?string $ignoreId = null): void
    {
        // Keep one public default per tenant by clearing older defaults first.
        Template::query()
            ->where('tenant_id', $tenantId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->update(['is_default' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultDesignPayload(TemplateCatalogItem $catalogItem): array
    {
        $content = $catalogItem->default_content ?? [];

        $payload = [
            'business_name' => $this->stringContent($content, ['business_name', 'display_name'], 'Onlyvo Studio'),
            'logo'          => 'https://dummyimage.com/120x120/14b8a6/ffffff.png&text=OV',
            'contact_info'  => [
                'email'   => $this->stringContent($content, ['contact_email', 'email'], 'hello@example.com'),
                'phone'   => $this->stringContent($content, ['contact_phone', 'phone'], '+1 555 0100'),
                'address' => $this->stringContent($content, ['contact_address', 'contact_location', 'location', 'address']),
            ],
            'social_links' => [
                'website'   => $this->stringContent($content, ['website_url', 'portfolio_url', 'reservation_link', 'chat_url'], 'https://example.com'),
                'linkedin'  => '',
                'instagram' => '',
                'facebook'  => '',
            ],
            'content'          => $content,
            'font_family'      => 'Inter',
            'primary_color'    => '#14b8a6',
            'secondary_color'  => '#0f766e',
            'background_color' => '#ffffff',
            'text_color'       => '#111827',
        ];

        if ($catalogItem->websiteType?->slug === 'landing-page' && $catalogItem->key === 'template-1') {
            $payload = array_merge($payload, [
                'font_family'      => 'Arial',
                'primary_color'    => '#3377aa',
                'secondary_color'  => '#336699',
                'background_color' => '#ffffff',
                'text_color'       => '#707070',
            ]);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  array<int, string>  $keys
     */
    private function stringContent(array $content, array $keys, string $fallback = ''): string
    {
        foreach ($keys as $key) {
            $value = $content[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return $fallback;
    }
}
