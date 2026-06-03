<?php

namespace App\Tenant\SystemSettings\Http\Requests;

use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantSystemSettingImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantSettings = app(TenantSystemSettingService::class);
        $imageKeys      = collect($tenantSettings->definitions())
            ->filter(fn (array $definition): bool => ($definition['type'] ?? null) === 'image')
            ->keys()
            ->all();

        $platformSettings = app(SystemSettingService::class);
        $mimes            = collect(explode(',', $platformSettings->string('storage.allowed_file_types', 'jpg,jpeg,png,webp,gif')))
            ->map(fn (string $mime): string => trim($mime))
            ->filter()
            ->implode(',');

        return [
            'key'   => ['required', 'string', Rule::in($imageKeys)],
            'image' => ['required', 'file', "mimes:{$mimes}", 'max:'.$platformSettings->integer('storage.maximum_upload_size', 4096)],
        ];
    }
}
