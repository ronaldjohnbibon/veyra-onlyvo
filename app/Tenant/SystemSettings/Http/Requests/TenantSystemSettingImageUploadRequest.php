<?php

namespace App\Tenant\SystemSettings\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use App\Tenant\SystemSettings\Services\TenantSystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TenantSystemSettingImageUploadRequest extends FormRequest
{
    /**
     * @var array<int, string>
     */
    private const SAFE_IMAGE_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

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
        $mimes            = $this->safeImageMimes($platformSettings->string('storage.allowed_file_types', implode(',', self::SAFE_IMAGE_MIMES)));

        return [
            'key'   => ['required', 'string', Rule::in($imageKeys)],
            'image' => ['required', 'image', "mimes:{$mimes}", 'max:'.$platformSettings->integer('storage.maximum_upload_size', 4096)],
        ];
    }

    private function safeImageMimes(string $configured): string
    {
        return collect(explode(',', $configured))
            ->map(fn (string $mime): string => trim(strtolower($mime)))
            ->filter(fn (string $mime): bool => in_array($mime, self::SAFE_IMAGE_MIMES, true))
            ->implode(',') ?: implode(',', self::SAFE_IMAGE_MIMES);
    }
}
