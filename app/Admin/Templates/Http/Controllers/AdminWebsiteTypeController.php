<?php

namespace App\Admin\Templates\Http\Controllers;

use App\Admin\Templates\Http\Requests\WebsiteTypeRequest;
use App\Admin\Templates\Http\Resources\WebsiteTypeResource;
use App\Admin\Templates\Models\WebsiteType;
use App\Admin\Templates\Services\WebsiteTypeService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminWebsiteTypeController extends Controller
{
    public function __construct(
        private readonly WebsiteTypeService $service,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin();

        $types = $this->service->paginate($request->only(['search', 'pageSize', 'page']));

        return $this->success(WebsiteTypeResource::collection($types), 'Website types retrieved.');
    }

    public function store(WebsiteTypeRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $type = $this->service->create($request->validated());

        return $this->success(new WebsiteTypeResource($type), 'Website type created.', 201);
    }

    public function update(WebsiteTypeRequest $request, string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        return $this->success(new WebsiteTypeResource($this->service->update($type, $request->validated())), 'Website type updated.');
    }

    public function destroy(string $websiteType): JsonResponse
    {
        $this->authorizeAdmin();

        $type = WebsiteType::query()->find($websiteType);

        if (! $type) {
            return $this->error('Website type not found.', 404);
        }

        if (! $this->service->delete($type)) {
            return $this->error('Website type is still in use.', 422);
        }

        return $this->success(null, 'Website type deleted.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
