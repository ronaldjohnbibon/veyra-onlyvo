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
        $settings = app(SystemSettingService::class);

        return [
            'key'   => ['required', 'string', Rule::in(['general.logo', 'general.favicon', 'seo.open_graph_image'])],
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,svg,ico', 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }
}
