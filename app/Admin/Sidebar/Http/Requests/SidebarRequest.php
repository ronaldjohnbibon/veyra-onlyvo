<?php

namespace App\Admin\Sidebar\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'data.main_nav.*.items.*.icon'        => ['nullable', 'string', 'max:100'],
            'data.main_nav.*.items.*.description' => ['nullable', 'string'],
            'data.main_nav.*.items.*.is_active'   => ['sometimes', 'boolean'],

            'data.projects'        => ['sometimes', 'array'],
            'data.projects.*.name' => ['required_with:data.projects', 'string', 'max:255'],
            'data.projects.*.url'  => ['required_with:data.projects', 'string', 'max:255'],
            'data.projects.*.icon' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.uuid'                              => 'The tenant identifier is invalid.',
            'tenant_id.exists'                            => 'The selected tenant does not exist.',
            'name.required'                               => 'Enter a sidebar name.',
            'name.string'                                 => 'The sidebar name must be text.',
            'name.max'                                    => 'The sidebar name may not exceed :max characters.',
            'name.unique'                                 => 'A sidebar with this name already exists.',
            'description.string'                          => 'The sidebar description must be text.',
            'is_admin.boolean'                            => 'The admin sidebar setting must be true or false.',
            'data.required'                               => 'Sidebar navigation data is required.',
            'data.array'                                  => 'Sidebar navigation data must be a valid object.',
            'data.teams.array'                            => 'Sidebar teams must be provided as a list.',
            'data.teams.*.name.required_with'             => 'Enter a name for each sidebar team.',
            'data.teams.*.name.string'                    => 'Each sidebar team name must be text.',
            'data.teams.*.name.max'                       => 'Each sidebar team name may not exceed :max characters.',
            'data.teams.*.logo.string'                    => 'Each sidebar team logo must be text.',
            'data.teams.*.logo.max'                       => 'Each sidebar team logo may not exceed :max characters.',
            'data.teams.*.plan.string'                    => 'Each sidebar team plan must be text.',
            'data.teams.*.plan.max'                       => 'Each sidebar team plan may not exceed :max characters.',
            'data.main_nav.required'                      => 'Add at least one main navigation group.',
            'data.main_nav.array'                         => 'Main navigation must be provided as a list.',
            'data.main_nav.min'                           => 'Add at least :min main navigation group.',
            'data.main_nav.*.title.required'              => 'Enter a title for each main navigation group.',
            'data.main_nav.*.title.string'                => 'Each main navigation title must be text.',
            'data.main_nav.*.title.max'                   => 'Each main navigation title may not exceed :max characters.',
            'data.main_nav.*.url.required'                => 'Enter a URL for each main navigation group.',
            'data.main_nav.*.url.string'                  => 'Each main navigation URL must be text.',
            'data.main_nav.*.url.max'                     => 'Each main navigation URL may not exceed :max characters.',
            'data.main_nav.*.icon.string'                 => 'Each main navigation icon must be text.',
            'data.main_nav.*.icon.max'                    => 'Each main navigation icon may not exceed :max characters.',
            'data.main_nav.*.description.string'          => 'Each main navigation description must be text.',
            'data.main_nav.*.is_active.boolean'           => 'Each main navigation active setting must be true or false.',
            'data.main_nav.*.items.array'                 => 'Main navigation items must be provided as a list.',
            'data.main_nav.*.items.*.title.required_with' => 'Enter a title for each navigation item.',
            'data.main_nav.*.items.*.title.string'        => 'Each navigation item title must be text.',
            'data.main_nav.*.items.*.title.max'           => 'Each navigation item title may not exceed :max characters.',
            'data.main_nav.*.items.*.url.required_with'   => 'Enter a URL for each navigation item.',
            'data.main_nav.*.items.*.url.string'          => 'Each navigation item URL must be text.',
            'data.main_nav.*.items.*.url.max'             => 'Each navigation item URL may not exceed :max characters.',
            'data.main_nav.*.items.*.icon.string'         => 'Each navigation item icon must be text.',
            'data.main_nav.*.items.*.icon.max'            => 'Each navigation item icon may not exceed :max characters.',
            'data.main_nav.*.items.*.description.string'  => 'Each navigation item description must be text.',
            'data.main_nav.*.items.*.is_active.boolean'   => 'Each navigation item active setting must be true or false.',
            'data.projects.array'                         => 'Sidebar projects must be provided as a list.',
            'data.projects.*.name.required_with'          => 'Enter a name for each sidebar project.',
            'data.projects.*.name.string'                 => 'Each sidebar project name must be text.',
            'data.projects.*.name.max'                    => 'Each sidebar project name may not exceed :max characters.',
            'data.projects.*.url.required_with'           => 'Enter a URL for each sidebar project.',
            'data.projects.*.url.string'                  => 'Each sidebar project URL must be text.',
            'data.projects.*.url.max'                     => 'Each sidebar project URL may not exceed :max characters.',
            'data.projects.*.icon.string'                 => 'Each sidebar project icon must be text.',
            'data.projects.*.icon.max'                    => 'Each sidebar project icon may not exceed :max characters.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateNavigationUrls($validator);
            },
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

    private function validateNavigationUrls(Validator $validator): void
    {
        $groups = $this->input('data.main_nav', []);

        if (! is_array($groups)) {
            return;
        }

        foreach ($groups as $groupIndex => $group) {
            if (! is_array($group)) {
                continue;
            }

            $this->validateUrlValue($validator, "data.main_nav.{$groupIndex}.url", $group['url'] ?? null);

            foreach (($group['items'] ?? []) as $itemIndex => $item) {
                if (! is_array($item)) {
                    continue;
                }

                $this->validateUrlValue(
                    $validator,
                    "data.main_nav.{$groupIndex}.items.{$itemIndex}.url",
                    $item['url'] ?? null,
                );
            }
        }
    }

    private function validateUrlValue(Validator $validator, string $key, mixed $value): void
    {
        $url = trim((string) $value);

        if ($url === '#'
            || preg_match('/^(https?:|mailto:|tel:)/i', $url)
            || preg_match('/^\/?[a-z0-9][a-z0-9\-\/]*$/i', $url)
        ) {
            return;
        }

        $validator->errors()->add($key, 'Navigation URLs must be #, an internal path, or a valid http, mailto, or tel URL.');
    }
}
