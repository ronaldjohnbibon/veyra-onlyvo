<?php

namespace App\Admin\SystemSettings\Http\Controllers;

use App\Admin\SystemSettings\Http\Requests\SystemSettingBulkRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingEmailTestRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingHistoryIndexRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingImageUploadRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingMaintenancePreviewRequest;
use App\Admin\SystemSettings\Http\Requests\SystemSettingRequest;
use App\Admin\SystemSettings\Http\Resources\SystemSettingHistoryResource;
use App\Admin\SystemSettings\Http\Resources\SystemSettingResource;
use App\Admin\SystemSettings\Models\SystemSetting;
use App\Admin\SystemSettings\Models\SystemSettingHistory;
use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Http\Controllers\Controller;
use App\Shared\Enums\UserType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AdminSystemSettingController extends Controller
{
    public function __construct(
        private readonly SystemSettingService $service,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorizeAdmin();
        $this->service->reconcileStoredSettings();
        $history = $this->historyPayload();

        return $this->success([
            'groups'  => $this->service->groups(maskSensitive: true),
            'values'  => $this->service->values(maskSensitive: true),
            'history' => $history,
        ], 'System settings retrieved.');
    }

    public function history(SystemSettingHistoryIndexRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->success($this->historyPayload($request->validated()), 'System settings history retrieved.');
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

        $this->service->reconcileStoredSettings();
        $this->service->upsertGrouped($request->validated('settings'), Auth::user());
        $this->service->applyRuntimeConfig();
        $this->service->reconcileStoredSettings();
        $history = $this->historyPayload();

        return $this->success([
            'groups'  => $this->service->groups(maskSensitive: true),
            'values'  => $this->service->values(maskSensitive: true),
            'history' => $history,
        ], 'System settings updated.');
    }

    public function export(): StreamedResponse
    {
        $this->authorizeAdmin();

        return $this->downloadJson($this->service->exportPayload(Auth::user()), 'system-settings-export.json');
    }

    public function backup(): StreamedResponse
    {
        $this->authorizeAdmin();

        return $this->downloadJson($this->service->exportPayload(Auth::user(), 'backup'), 'system-settings-backup.json');
    }

    public function testSmtp(): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->success($this->service->testSmtpConnection(), 'SMTP test completed.');
    }

    public function testEmail(SystemSettingEmailTestRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        try {
            return $this->success(
                $this->service->sendTestEmail($request->validated('recipient'), Auth::user()),
                'Test email queued for delivery.',
            );
        } catch (Throwable $exception) {
            return $this->error($exception->getMessage(), 422);
        }
    }

    public function maintenancePreview(SystemSettingMaintenancePreviewRequest $request): JsonResponse
    {
        $this->authorizeAdmin();

        return $this->success(
            $this->service->maintenancePreview(
                $request->validated('settings') ?? [],
                (string) ($request->validated('path') ?? '/'),
            ),
            'Maintenance dry-run completed.',
        );
    }

    public function restoreHistory(string $history): JsonResponse
    {
        $this->authorizeAdmin();

        $record = SystemSettingHistory::query()->find($history);

        if (! $record || ! $this->service->definitionExists((string) $record->setting_key)) {
            return $this->error('Restorable settings history record not found.', 404);
        }

        $this->service->restoreHistory($record, Auth::user());
        $payload = $this->historyPayload();

        return $this->success([
            'groups'  => $this->service->groups(maskSensitive: true),
            'values'  => $this->service->values(maskSensitive: true),
            'history' => $payload,
        ], 'Previous setting version restored.');
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

        $this->service->delete($setting, Auth::user());

        return $this->success(null, 'System setting reset.');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function historyPayload(array $filters = []): array
    {
        $history = $this->service->history($filters);

        return [
            'data'       => SystemSettingHistoryResource::collection($history['data'])->resolve(),
            'pagination' => $history['pagination'],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function downloadJson(array $payload, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(Auth::user()?->user_type === UserType::ADMIN, 403);
    }
}
