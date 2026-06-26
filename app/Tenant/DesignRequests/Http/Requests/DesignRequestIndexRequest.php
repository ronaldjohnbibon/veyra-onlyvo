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

    public function messages(): array
    {
        return [
            'search.string'    => 'The design request search must be text.',
            'search.max'       => 'The design request search may not exceed :max characters.',
            'status.in'        => 'Select a valid design request status.',
            'sort.in'          => 'Select a valid design request sort field.',
            'direction.in'     => 'Select a valid sort direction.',
            'page.integer'     => 'The page must be a whole number.',
            'page.min'         => 'The page must be at least :min.',
            'pageSize.integer' => 'The page size must be a whole number.',
            'pageSize.min'     => 'The page size must be at least :min.',
            'pageSize.max'     => 'The page size may not exceed :max.',
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
