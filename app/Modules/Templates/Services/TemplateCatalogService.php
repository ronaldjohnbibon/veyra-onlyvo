<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Models\WebsiteType;
use Illuminate\Support\Collection;

class TemplateCatalogService
{
    /**
     * @var array<string, array<int, array<string, mixed>>>
     */
    private const TEMPLATES = [
        'business-website' => [
            [
                'key'           => 'template-1',
                'name'          => 'Business Classic',
                'description'   => 'A polished company website with hero, services, proof, and contact areas.',
                'preview_image' => null,
            ],
        ],
        'portfolio-website' => [
            [
                'key'           => 'template-1',
                'name'          => 'Portfolio Studio',
                'description'   => 'A clean personal portfolio with project highlights and contact details.',
                'preview_image' => null,
            ],
        ],
        'restaurant-website' => [
            [
                'key'           => 'template-1',
                'name'          => 'Restaurant Showcase',
                'description'   => 'A warm restaurant website with menu highlights, hours, and booking prompts.',
                'preview_image' => null,
            ],
        ],
    ];

    /**
     * @return array<int, array<string, mixed>>
     */
    public function forWebsiteType(WebsiteType|string|null $websiteType): array
    {
        $slug = $this->slugFor($websiteType);

        if (! $slug) {
            return [];
        }

        return Collection::make(self::TEMPLATES[$slug] ?? [])
            ->map(fn (array $template): array => array_merge($template, [
                'website_type_id'   => $websiteType instanceof WebsiteType ? $websiteType->id : null,
                'website_type_slug' => $slug,
            ]))
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function keys(WebsiteType|string|null $websiteType): array
    {
        return Collection::make($this->forWebsiteType($websiteType))->pluck('key')->filter()->values()->all();
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

        if (array_key_exists($websiteType, self::TEMPLATES)) {
            return $websiteType;
        }

        // Template keys are scoped by website type, so IDs are resolved to slugs.
        return WebsiteType::query()->whereKey($websiteType)->value('slug');
    }
}
