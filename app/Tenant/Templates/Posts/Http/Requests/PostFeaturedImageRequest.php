<?php

namespace App\Tenant\Templates\Posts\Http\Requests;

use App\Shared\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class PostFeaturedImageRequest extends FormRequest
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
        $settings = app(SystemSettingService::class);
        $mimes = collect(explode(',', $settings->string('storage.allowed_file_types', 'jpg,jpeg,png,webp,gif')))
            ->map(fn (string $mime): string => trim(strtolower($mime)))
            ->filter()
            ->implode(',');

        return [
            'image' => ['required', 'image', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }
}
