<?php

namespace App\Tenant\Dashboard\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsDashboardRequest extends FormRequest
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
            'period'         => ['nullable', Rule::in(['today', 'last_7_days', 'last_30_days', 'custom'])],
            'from'           => ['nullable', 'required_if:period,custom', 'date'],
            'to'             => ['nullable', 'required_if:period,custom', 'date'],
            'pageSize'       => ['nullable', 'integer', 'min:5', 'max:50'],
            'top_pages_page' => ['nullable', 'integer', 'min:1'],
            'referrers_page' => ['nullable', 'integer', 'min:1'],
            'top_ctas_page'  => ['nullable', 'integer', 'min:1'],
        ];
    }
}
