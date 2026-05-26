<?php

namespace App\Modules\Templates\Services;

use App\Modules\Templates\Http\Resources\TemplateDesignResource;
use App\Modules\Templates\Models\TemplateDesign;
use Illuminate\Support\Collection;

class TemplatePresetService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(?string $websiteTypeId = null): array
    {
        $designs = TemplateDesign::query()
            ->where('is_active', true)
            ->when($websiteTypeId, fn ($query) => $query->where('website_type_id', $websiteTypeId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Designs are loaded from the database so each type can grow independently.
        return TemplateDesignResource::collection($designs)->resolve();
    }

    /**
     * @return array<int, string>
     */
    public function keys(?string $websiteTypeId = null): array
    {
        return Collection::make($this->all($websiteTypeId))->pluck('key')->filter()->values()->all();
    }

    public function defaultKey(?string $websiteTypeId = null): ?string
    {
        return $this->keys($websiteTypeId)[0] ?? null;
    }

    public function exists(string $key, ?string $websiteTypeId = null): bool
    {
        return TemplateDesign::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->when($websiteTypeId, fn ($query) => $query->where('website_type_id', $websiteTypeId))
            ->exists();
    }
}
