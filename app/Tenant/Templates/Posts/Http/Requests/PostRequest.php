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
            'title'            => ['required', 'string', 'max:180'],
            'slug'             => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'content'          => ['required', 'string'],
            'excerpt'          => ['nullable', 'string', 'max:320'],
            'featured_image'   => ['nullable', 'string', 'max:2048', 'not_regex:/^data:/i'],
            'seo_title'        => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:320'],
            'tags'             => ['nullable', 'array', 'max:12'],
            'tags.*'           => ['string', 'max:40'],
            'status'           => ['nullable', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at'     => ['nullable', 'date', 'required_if:status,scheduled'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'           => 'Enter the post title.',
            'title.string'             => 'The post title must be text.',
            'title.max'                => 'The post title may not exceed :max characters.',
            'slug.string'              => 'The post slug must be text.',
            'slug.max'                 => 'The post slug may not exceed :max characters.',
            'slug.regex'               => 'The post slug may contain only lowercase letters, numbers, and single hyphens.',
            'content.required'         => 'Enter the post content.',
            'content.string'           => 'The post content must be text.',
            'excerpt.string'           => 'The post excerpt must be text.',
            'excerpt.max'              => 'The post excerpt may not exceed :max characters.',
            'featured_image.string'    => 'The featured image path must be text.',
            'featured_image.max'       => 'The featured image path may not exceed :max characters.',
            'featured_image.not_regex' => 'Upload the featured image instead of using embedded image data.',
            'seo_title.string'         => 'The SEO title must be text.',
            'seo_title.max'            => 'The SEO title may not exceed :max characters.',
            'meta_description.string'  => 'The meta description must be text.',
            'meta_description.max'     => 'The meta description may not exceed :max characters.',
            'tags.array'               => 'Post tags must be provided as a list.',
            'tags.max'                 => 'You may add up to :max post tags.',
            'tags.*.string'            => 'Each post tag must be text.',
            'tags.*.max'               => 'Each post tag may not exceed :max characters.',
            'status.in'                => 'Select a valid post status.',
            'published_at.required_if' => 'Choose a publication date and time for a scheduled post.',
            'published_at.date'        => 'Enter a valid publication date and time.',
        ];
    }

    public function prepareForValidation(): void
    {
        $slug = $this->filled('slug') ? str($this->input('slug'))->slug()->toString() : null;

        $this->merge([
            'title'            => trim((string) $this->input('title')),
            'slug'             => $slug ?: null,
            'content'          => trim((string) $this->input('content')),
            'excerpt'          => $this->filled('excerpt') ? trim((string) $this->input('excerpt')) : null,
            'featured_image'   => $this->filled('featured_image') ? trim((string) $this->input('featured_image')) : null,
            'seo_title'        => $this->filled('seo_title') ? trim((string) $this->input('seo_title')) : null,
            'meta_description' => $this->filled('meta_description') ? trim((string) $this->input('meta_description')) : null,
            'tags'             => $this->normalizedTags(),
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
