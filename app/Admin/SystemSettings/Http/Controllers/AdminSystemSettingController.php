<?php

namespace App\Admin\SystemSettings\Http\Controllers;

use App\Admin\SystemSettings\Http\Requests\SystemSettingBulkRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingImageUploadRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingRequest;
use App\Admin\SystemSettings\Http\Resources\SystemSettingHistoryResource;
use App\Admin\SystemSettings\Http\Resources\SystemSettingResource;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use App\Shared\SystemSettings\Models\SystemSetting;
use App\Shared\SystemSettings\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminSystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $service,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->success([
            'groups'  => $this->service->groups(),
            'values'  => $this->service->values(),
            'history' => SystemSettingHistoryResource::collection($this->service->history()),
        ], 'System settings retrieved.');
    }

    public function store(SystemSettingRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $setting = $this->service->upsert(
            (string) $request->validated('key'),
            $request->validated('value'),
            Auth::user(),
        );

        return $this->success(new SystemSettingResource($setting), 'System setting created.', 201);
    }

    public function show(string $systemSetting): JsonResponse
    {
        $this->authorizeAdmin();

        $setting = SystemSetting::query()
            ->where('id', $systemSetting)
            ->orWhere('key', $systemSetting)
            ->first();

        if (! $setting) {
            return $this->error('System setting not found.', 404);
        }

        return $this->success(new SystemSettingResource($setting), 'System setting retrieved.');
    }

    public function update(SystemSettingRequest $request, string $systemSetting): JsonResponse
    {
        $this->authorizeAdmin();

        $setting = SystemSetting::query()
            ->where('id', $systemSetting)
            ->orWhere('key', $systemSetting)
            ->first();

        if (! $setting) {
            return $this->error('System setting not found.', 404);
        }

        $updated = $this->service->upsert($setting->key, $request->validated('value'), Auth::user());

        return $this->success(new SystemSettingResource($updated), 'System setting updated.');
    }

    public function updateBulk(SystemSettingBulkRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $this->service->upsertGrouped($request->validated('settings'), Auth::user());
        $this->service->applyRuntimeConfig();

        return $this->success([
            'groups'  => $this->service->groups(),
            'values'  => $this->service->values(),
            'history' => SystemSettingHistoryResource::collection($this->service->history()),
        ], 'System settings updated.');
    }

    public function uploadImage(SystemSettingImageUploadRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        $image = $request->file('image');

        abort_unless($image, 422);

        $path = $image->storePublicly('system-settings', 'public');

        return $this->success([
            'key'  => $request->validated('key'),
            'url'  => '/storage/'.$path,
            'path' => $path,
        ], 'Image uploaded.', 201);
    }

    public function destroy(string $systemSetting): JsonResponse
    {
        $this->authorizeAdmin();

        $setting = SystemSetting::query()
            ->where('id', $systemSetting)
            ->orWhere('key', $systemSetting)
            ->first();

        if (! $setting) {
            return $this->error('System setting not found.', 404);
        }

        $this->service->delete($setting);

        return $this->success(null, 'System setting reset.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
