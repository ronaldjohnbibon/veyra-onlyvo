<?php

namespace App\Tenant\DesignRequests\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignRequestActionRequest extends FormRequest
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
            'action'  => ['required', Rule::in(['approve', 'request_changes'])],
            'message' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Select an action for this design request.',
            'action.in'       => 'Select a valid design request action.',
            'message.string'  => 'The action message must be text.',
            'message.max'     => 'The action message may not exceed :max characters.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'message' => $this->filled('message') ? trim((string) $this->input('message')) : null,
        ]);
    }
}
