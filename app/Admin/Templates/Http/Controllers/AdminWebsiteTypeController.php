<?php

namespace App\Admin\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use App\Admin\Templates\Http\Requests\WebsiteTypeRequest;
use App\Admin\Templates\Http\Resources\WebsiteTypeResource;
use App\Admin\Templates\Models\WebsiteType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebsiteTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $types = WebsiteType::query()
            ->when($request->input('search'), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('slug', 'like', '%'.$search.'%');
                });
            })
            ->withCount(['templates', 'catalogItems as available_templates_count'])
            ->orderBy('name')
            ->paginate(
                (int) $request->input('pageSize', 100),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(WebsiteTypeResource::collection($types), 'Website types retrieved.');
    }

    public function store(WebsiteTypeRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->create($request->validated());

        return $this->success(new WebsiteTypeResource($type), 'Website type created.', 201);
    }

    public function update(WebsiteTypeRequest $request, string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        $type->update($request->validated());

        return $this->success(new WebsiteTypeResource($type->fresh()), 'Website type updated.');
    }

    public function destroy(string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->withCount(['templates', 'catalogItems'])->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        // Prevent deleting types that still power tenant sites or catalog entries.
        if ($type->templates_count || $type->catalog_items_count) {
            return $this->error('Website type is still in use.', 422);
        }

        $type->delete();

        return $this->success(null, 'Website type deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
