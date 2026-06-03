<?php

namespace App\Tenant\DesignRequests\Http\Requests;

use App\Shared\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class DesignRequestRequest extends FormRequest
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
        $mimes    = collect(explode(',', $settings->string('storage.allowed_file_types', 'jpg,jpeg,png,webp,gif')))
            ->map(fn (string $mime): string => trim(strtolower($mime)))
            ->filter()
            ->implode(',');

        return [
            'title'             => ['required', 'string', 'max:180'],
            'description'       => ['required', 'string', 'max:10000'],
            'notes'             => ['nullable', 'string', 'max:10000'],
            'reference_links'   => ['nullable', 'array', 'max:10'],
            'reference_links.*' => ['required', 'url', 'max:2048'],
            'mockup_concept'    => ['nullable', 'string', 'max:20000'],
            'files'             => ['nullable', 'array', 'max:8'],
            'files.*'           => ['file', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }

    public function prepareForValidation(): void
    {
        $links = $this->input('reference_links', []);

        if (is_string($links)) {
            $links = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $links) ?: []));
        }

        $this->merge([
            'title'           => trim((string) $this->input('title')),
            'description'     => trim((string) $this->input('description')),
            'notes'           => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'mockup_concept'  => $this->filled('mockup_concept') ? trim((string) $this->input('mockup_concept')) : null,
            'reference_links' => array_values($links),
        ]);
    }
}
