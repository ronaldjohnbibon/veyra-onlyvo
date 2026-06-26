<?php

namespace App\Admin\SystemSettings\Http\Requests;

use App\Admin\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingImageUploadRequest extends FormRequest
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
        $settings  = app(SystemSettingService::class);
        $imageKeys = collect($settings->definitions())
            ->filter(fn (array $definition): bool => $definition['type'] === 'image')
            ->keys()
            ->all();
        $mimes = $this->safeImageMimes($settings->string('storage.allowed_file_types', implode(',', self::SAFE_IMAGE_MIMES)));

        return [
            'key'   => ['required', 'string', Rule::in($imageKeys)],
            'image' => ['required', 'image', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }

    public function messages(): array
    {
        return [
            'key.required'   => 'Select the system setting image to update.',
            'key.string'     => 'The system setting image key must be text.',
            'key.in'         => 'Select a valid system setting image.',
            'image.required' => 'Choose an image to upload.',
            'image.image'    => 'The uploaded file must be an image.',
            'image.mimes'    => 'The image must be one of these file types: :values.',
            'image.max'      => 'The image may not be larger than :max kilobytes.',
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
