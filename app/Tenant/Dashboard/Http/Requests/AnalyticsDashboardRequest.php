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

    public function messages(): array
    {
        return [
            'period.in'              => 'Select a valid analytics period.',
            'from.required_if'       => 'Choose a start date for the custom analytics period.',
            'from.date'              => 'Enter a valid analytics start date.',
            'to.required_if'         => 'Choose an end date for the custom analytics period.',
            'to.date'                => 'Enter a valid analytics end date.',
            'pageSize.integer'       => 'The analytics page size must be a whole number.',
            'pageSize.min'           => 'The analytics page size must be at least :min.',
            'pageSize.max'           => 'The analytics page size may not exceed :max.',
            'top_pages_page.integer' => 'The top pages page must be a whole number.',
            'top_pages_page.min'     => 'The top pages page must be at least :min.',
            'referrers_page.integer' => 'The referrers page must be a whole number.',
            'referrers_page.min'     => 'The referrers page must be at least :min.',
            'top_ctas_page.integer'  => 'The top CTAs page must be a whole number.',
            'top_ctas_page.min'      => 'The top CTAs page must be at least :min.',
        ];
    }
}
