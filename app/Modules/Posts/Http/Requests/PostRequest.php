<?php

namespace App\Modules\Posts\Http\Requests;

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
            'content'        => ['required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:2048', 'not_regex:/^data:/i'],
            'status'         => ['nullable', Rule::in(['draft', 'published'])],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'title'          => trim((string) $this->input('title')),
            'content'        => trim((string) $this->input('content')),
            'featured_image' => $this->filled('featured_image') ? trim((string) $this->input('featured_image')) : null,
        ]);
    }
}
