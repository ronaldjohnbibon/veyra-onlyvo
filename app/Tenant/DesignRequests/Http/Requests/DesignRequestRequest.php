<?php

namespace App\Tenant\DesignRequests\Http\Requests;

use App\Tenant\SystemSettings\Services\SystemSettingService;
use Illuminate\Foundation\Http\FormRequest;

class DesignRequestRequest extends FormRequest
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
            'title'             => ['required', 'string', 'max:180'],
            'description'       => ['required', 'string', 'max:10000'],
            'notes'             => ['nullable', 'string', 'max:10000'],
            'reference_links'   => ['nullable', 'array', 'max:10'],
            'reference_links.*' => ['required', 'url', 'max:2048'],
            'mockup_concept'    => ['nullable', 'string', 'max:20000'],
            'files'             => ['nullable', 'array', 'max:8'],
            'files.*'           => ['image', "mimes:{$mimes}", 'max:'.$settings->integer('storage.maximum_upload_size', 4096)],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'             => 'Enter a title for the design request.',
            'title.string'               => 'The design request title must be text.',
            'title.max'                  => 'The design request title may not exceed :max characters.',
            'description.required'       => 'Describe the design work you need.',
            'description.string'         => 'The design request description must be text.',
            'description.max'            => 'The design request description may not exceed :max characters.',
            'notes.string'               => 'The design request notes must be text.',
            'notes.max'                  => 'The design request notes may not exceed :max characters.',
            'reference_links.array'      => 'Reference links must be provided as a list.',
            'reference_links.max'        => 'You may add up to :max reference links.',
            'reference_links.*.required' => 'Each reference link must contain a URL.',
            'reference_links.*.url'      => 'Each reference link must be a valid URL.',
            'reference_links.*.max'      => 'Each reference link may not exceed :max characters.',
            'mockup_concept.string'      => 'The mockup concept must be text.',
            'mockup_concept.max'         => 'The mockup concept may not exceed :max characters.',
            'files.array'                => 'Design request files must be provided as a list.',
            'files.max'                  => 'You may upload up to :max design request files.',
            'files.*.image'              => 'Each design request file must be an image.',
            'files.*.mimes'              => 'Each design request image must be one of these file types: :values.',
            'files.*.max'                => 'Each design request image may not be larger than :max kilobytes.',
        ];
    }

    public function prepareForValidation(): void
    {
        $links = $this->input('reference_links', []);

        if (is_string($links)) {
            $links = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $links) ?: []));
        } elseif (is_array($links)) {
            $links = array_filter(array_map(
                fn (mixed $link): string => trim((string) $link),
                $links,
            ));
        } else {
            $links = [];
        }

        $this->merge([
            'title'           => trim((string) $this->input('title')),
            'description'     => trim((string) $this->input('description')),
            'notes'           => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
            'mockup_concept'  => $this->filled('mockup_concept') ? trim((string) $this->input('mockup_concept')) : null,
            'reference_links' => array_values($links),
        ]);
    }

    private function safeImageMimes(string $configured): string
    {
        return collect(explode(',', $configured))
            ->map(fn (string $mime): string => trim(strtolower($mime)))
            ->filter(fn (string $mime): bool => in_array($mime, self::SAFE_IMAGE_MIMES, true))
            ->implode(',') ?: implode(',', self::SAFE_IMAGE_MIMES);
    }
}
