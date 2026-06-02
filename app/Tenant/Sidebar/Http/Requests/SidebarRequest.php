<?php

namespace App\Tenant\Sidebar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class SidebarRequest extends FormRequest
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
        $routeParam = $this->route('sidebar');
        $sidebarId  = is_object($routeParam) ? $routeParam->id : $routeParam;
        $tenantId   = $this->input('tenant_id');
        $isAdmin    = $this->boolean('is_admin', $this->isAdminRoute());

        return [
            'tenant_id' => ['nullable', 'uuid', Rule::exists('tenants', 'id')],
            'name'      => [
                'required',
                'string',
                'max:100',
                Rule::unique('sidebars', 'name')
                    ->ignore($sidebarId)
                    ->where(fn ($query) => $query
                        ->where('is_admin', $isAdmin)
                        ->where('tenant_id', $tenantId)
                        ->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string'],
            'is_admin'    => ['sometimes', 'boolean'],
            'data'        => ['required', 'array'],

            'data.teams'        => ['sometimes', 'array'],
            'data.teams.*.name' => ['required_with:data.teams', 'string', 'max:255'],
            'data.teams.*.logo' => ['nullable', 'string', 'max:100'],
            'data.teams.*.plan' => ['nullable', 'string', 'max:100'],

            'data.main_nav'                       => ['required', 'array', 'min:1'],
            'data.main_nav.*.title'               => ['required', 'string', 'max:255'],
            'data.main_nav.*.url'                 => ['required', 'string', 'max:255'],
            'data.main_nav.*.icon'                => ['nullable', 'string', 'max:100'],
            'data.main_nav.*.description'         => ['nullable', 'string'],
            'data.main_nav.*.is_active'           => ['sometimes', 'boolean'],
            'data.main_nav.*.items'               => ['sometimes', 'array'],
            'data.main_nav.*.items.*.title'       => ['required_with:data.main_nav.*.items', 'string', 'max:255'],
            'data.main_nav.*.items.*.url'         => ['required_with:data.main_nav.*.items', 'string', 'max:255'],
            'data.main_nav.*.items.*.description' => ['nullable', 'string'],
            'data.main_nav.*.items.*.is_active'   => ['sometimes', 'boolean'],

            'data.projects'        => ['sometimes', 'array'],
            'data.projects.*.name' => ['required_with:data.projects', 'string', 'max:255'],
            'data.projects.*.url'  => ['required_with:data.projects', 'string', 'max:255'],
            'data.projects.*.icon' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function prepareForValidation(): void
    {
        $tenantId = Auth::user()?->tenant_id;

        if (! $this->has('is_admin')) {
            $this->merge(['is_admin' => $this->isAdminRoute()]);
        }

        // Tenant sidebar requests are always scoped to the authenticated tenant.
        if (! $this->isAdminRoute() && $tenantId) {
            $this->merge(['tenant_id' => $tenantId]);
        }

        if ($this->has('name')) {
            $this->merge(['name' => trim((string) $this->input('name'))]);
        }
    }

    private function isAdminRoute(): bool
    {
        return str_starts_with((string) $this->route()?->getName(), 'admin.');
    }
}
