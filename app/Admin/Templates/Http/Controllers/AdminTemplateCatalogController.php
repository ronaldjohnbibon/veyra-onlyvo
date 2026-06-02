<?php

namespace App\Admin\Templates\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use App\Admin\Templates\Http\Requests\TemplateCatalogItemRequest;
use App\Admin\Templates\Http\Resources\TemplateCatalogItemResource;
use App\Admin\Templates\Models\TemplateCatalogItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTemplateCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $items = TemplateCatalogItem::query()
            ->with('websiteType')
            ->filter([
                'search'          => $request->input('search'),
                'website_type_id' => $request->input('website_type_id'),
            ])
            ->latest()
            ->paginate(
                (int) $request->input('pageSize', 15),
                ['*'],
                'page',
                (int) $request->input('page', 1),
            );

        return $this->success(TemplateCatalogItemResource::collection($items), 'Template catalog retrieved.');
    }

    public function store(TemplateCatalogItemRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->create($request->validated());

        return $this->success(new TemplateCatalogItemResource($item->load('websiteType')), 'Template catalog item created.', 201);
    }

    public function show(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->with('websiteType')->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        return $this->success(new TemplateCatalogItemResource($item), 'Template catalog item retrieved.');
    }

    public function update(TemplateCatalogItemRequest $request, string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $item->update($request->validated());

        return $this->success(new TemplateCatalogItemResource($item->fresh('websiteType')), 'Template catalog item updated.');
    }

    public function destroy(string $templateCatalogItem): JsonResponse
    {
        $this->authorizeAdmin();

        $item = TemplateCatalogItem::query()->find($templateCatalogItem);

        if (! $item) {
            return $this->error('Template catalog item not found.', 404);
        }

        $item->delete();

        return $this->success(null, 'Template catalog item deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
