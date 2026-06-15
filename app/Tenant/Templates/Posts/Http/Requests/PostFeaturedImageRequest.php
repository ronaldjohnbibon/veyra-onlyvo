<?php

namespace App\Tenant\Templates\Posts\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class PostFeaturedImageRequest extends FormRequest
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
        $settings = app(SystemSettingService::class);
        $mimes    = $this->safeImageMimes($settings->string('storage.allowed_file_types', implode(',', self::SAFE_IMAGE_MIMES)));

        return [
            'image' => ['required', 'image', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
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
