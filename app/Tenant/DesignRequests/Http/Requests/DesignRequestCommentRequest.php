<?php

namespace App\Tenant\DesignRequests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DesignRequestCommentRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Enter a comment.',
            'message.string'   => 'The comment must be text.',
            'message.max'      => 'The comment may not exceed :max characters.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'message' => trim((string) $this->input('message')),
        ]);
    }
}
