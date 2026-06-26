<?php

namespace App\Admin\Templates\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class TemplateCatalogPreviewImageUploadRequest extends FormRequest
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

    public function messages(): array
    {
        return [
            'image.required' => 'Choose a template preview image to upload.',
            'image.image'    => 'The template preview file must be an image.',
            'image.mimes'    => 'The template preview image must be one of these file types: :values.',
            'image.max'      => 'The template preview image may not be larger than :max kilobytes.',
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
