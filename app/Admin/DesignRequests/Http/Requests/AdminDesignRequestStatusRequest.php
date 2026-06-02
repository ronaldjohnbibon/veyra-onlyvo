<?php

namespace App\Admin\DesignRequests\Http\Requests;

use App\Admin\DesignRequests\Models\DesignRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminDesignRequestStatusRequest extends FormRequest
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
            'status'        => ['required', Rule::in(DesignRequest::STATUSES)],
            'admin_remarks' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'admin_remarks' => $this->filled('admin_remarks') ? trim((string) $this->input('admin_remarks')) : null,
        ]);
    }
}
