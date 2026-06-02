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
}
