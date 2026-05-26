<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Http\Resources\TemplateCatalogItemResource;
use App\Modules\Templates\Models\TemplateCatalogItem;
use App\Modules\Templates\Models\WebsiteType;

class TemplateCatalogService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function forWebsiteType(WebsiteType|string|null $websiteType): array
    {
        $slug = $this->slugFor($websiteType);

        if (! $slug) {
            return [];
        }

        $websiteTypeId = $websiteType instanceof WebsiteType
            ? $websiteType->id
            : WebsiteType::query()->where('slug', $slug)->value('id');

        if (! $websiteTypeId) {
            return [];
        }

        $items = TemplateCatalogItem::query()
            ->with('websiteType')
            ->where('website_type_id', $websiteTypeId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Catalog records describe complete Vue templates managed by admins.
        return TemplateCatalogItemResource::collection($items)->resolve();
    }

    /**
     * @return array<int, string>
     */
    public function keys(WebsiteType|string|null $websiteType): array
    {
        return collect($this->forWebsiteType($websiteType))->pluck('key')->filter()->values()->all();
    }

    public function defaultKey(WebsiteType|string|null $websiteType): ?string
    {
        return $this->keys($websiteType)[0] ?? null;
    }

    public function exists(string $key, WebsiteType|string|null $websiteType): bool
    {
        return in_array($key, $this->keys($websiteType), true);
    }

    private function slugFor(WebsiteType|string|null $websiteType): ?string
    {
        if ($websiteType instanceof WebsiteType) {
            return $websiteType->slug;
        }

        if (! $websiteType) {
            return null;
        }

        // Template keys are scoped by website type, so IDs are resolved to slugs.
        return WebsiteType::query()
            ->whereKey($websiteType)
            ->orWhere('slug', $websiteType)
            ->value('slug');
    }
}
