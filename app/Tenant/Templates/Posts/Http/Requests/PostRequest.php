<?php

namespace App\Tenant\Templates\Posts\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostRequest extends FormRequest
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
        return [
            'title'          => ['required', 'string', 'max:180'],
            'slug'           => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'content'        => ['required', 'string'],
            'excerpt'        => ['nullable', 'string', 'max:320'],
            'featured_image' => ['nullable', 'string', 'max:2048', 'not_regex:/^data:/i'],
            'seo_title'      => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'tags'           => ['nullable', 'array', 'max:12'],
            'tags.*'         => ['string', 'max:40'],
            'status'         => ['nullable', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at'   => ['nullable', 'date', 'required_if:status,scheduled'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'title'          => trim((string) $this->input('title')),
            'slug'           => $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null,
            'content'        => trim((string) $this->input('content')),
            'excerpt'        => $this->filled('excerpt') ? trim((string) $this->input('excerpt')) : null,
            'featured_image' => $this->filled('featured_image') ? trim((string) $this->input('featured_image')) : null,
            'seo_title'      => $this->filled('seo_title') ? trim((string) $this->input('seo_title')) : null,
            'meta_description' => $this->filled('meta_description') ? trim((string) $this->input('meta_description')) : null,
            'tags'           => $this->normalizedTags(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function normalizedTags(): array
    {
        $tags = $this->input('tags', []);

        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        if (! is_array($tags)) {
            return [];
        }

        return collect($tags)
            ->map(fn (mixed $tag): string => trim((string) $tag))
            ->filter()
            ->unique(fn (string $tag): string => mb_strtolower($tag))
            ->take(12)
            ->values()
            ->all();
    }
}
