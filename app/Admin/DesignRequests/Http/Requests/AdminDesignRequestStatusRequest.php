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
            'status'         => ['required', Rule::in(DesignRequest::STATUSES)],
            'assigned_to'    => ['nullable', Rule::exists('users', 'id')->where(fn ($query) => $query->where('user_type', 'admin'))],
            'priority'       => ['required', Rule::in(DesignRequest::PRIORITIES)],
            'due_at'         => ['nullable', 'date'],
            'sla_due_at'     => ['nullable', 'date'],
            'admin_remarks'  => ['nullable', 'string', 'max:10000'],
            'internal_notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'admin_remarks'  => $this->filled('admin_remarks') ? trim((string) $this->input('admin_remarks')) : null,
            'internal_notes' => $this->filled('internal_notes') ? trim((string) $this->input('internal_notes')) : null,
            'priority'       => $this->input('priority', 'normal'),
        ]);
    }
}
