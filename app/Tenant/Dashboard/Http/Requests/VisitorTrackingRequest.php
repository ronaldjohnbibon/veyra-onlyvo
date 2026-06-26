<?php

namespace App\Tenant\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VisitorTrackingRequest extends FormRequest
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
            'template_id' => ['required', 'uuid'],
            'url'         => ['required', 'string', 'max:2000'],
            'referrer'    => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'template_id.required' => 'The template identifier is required to record this visit.',
            'template_id.uuid'     => 'The template identifier is invalid.',
            'url.required'         => 'The visited URL is required.',
            'url.string'           => 'The visited URL must be text.',
            'url.max'              => 'The visited URL may not exceed :max characters.',
            'referrer.string'      => 'The referring URL must be text.',
            'referrer.max'         => 'The referring URL may not exceed :max characters.',
        ];
    }
}
