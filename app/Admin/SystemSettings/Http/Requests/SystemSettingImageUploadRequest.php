<?php

namespace App\Admin\SystemSettings\Http\Requests;

use App\Shared\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingImageUploadRequest extends FormRequest
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
        $settings  = app(SystemSettingService::class);
        $imageKeys = collect($settings->definitions())
            ->filter(fn (array $definition): bool => $definition['type'] === 'image')
            ->keys()
            ->all();
        $mimes = collect(explode(',', $settings->string('storage.allowed_file_types', 'jpg,jpeg,png,webp,gif')))
            ->map(fn (string $mime): string => trim(strtolower($mime)))
            ->filter(fn (string $mime): bool => in_array($mime, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'], true))
            ->implode(',') ?: 'jpg,jpeg,png,webp,gif';

        return [
            'key'   => ['required', 'string', Rule::in($imageKeys)],
            'image' => ['required', 'file', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }
}
