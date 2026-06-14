<?php

namespace App\Tenant\DesignRequests\Http\Requests;

use App\Tenant\DesignRequests\Models\DesignRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DesignRequestIndexRequest extends FormRequest
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
            'search'    => ['nullable', 'string', 'max:255'],
            'status'    => ['nullable', Rule::in(DesignRequest::STATUSES)],
            'sort'      => ['nullable', Rule::in(['created_at', 'status', 'title'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'page'      => ['nullable', 'integer', 'min:1'],
            'pageSize'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function prepareForValidation(): void
    {
        $search = trim((string) $this->input('search'));

        $this->merge([
            'search' => $search !== '' ? $search : null,
            'status' => $this->filled('status') ? $this->input('status') : null,
        ]);
    }
}
