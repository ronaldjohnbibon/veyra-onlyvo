<?php

namespace App\Admin\Templates\Services;

use App\Admin\Templates\Models\WebsiteType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WebsiteTypeService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        return WebsiteType::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->withCount([
                'templates',
                'catalogItems as available_templates_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->orderBy('name')
            ->paginate(
                (int) ($filters['pageSize'] ?? 100),
                ['*'],
                'page',
                (int) ($filters['page'] ?? 1),
            );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): WebsiteType
    {
        return WebsiteType::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(WebsiteType $websiteType, array $data): WebsiteType
    {
        $websiteType->update($data);

        return $websiteType->fresh();
    }

    public function delete(WebsiteType $websiteType): bool
    {
        $websiteType->loadCount(['templates', 'catalogItems']);

        if ($websiteType->templates_count || $websiteType->catalog_items_count) {
            return false;
        }

        $websiteType->delete();

        return true;
    }
}
