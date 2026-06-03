<?php

namespace App\Admin\Tenants\Services;

use App\Admin\Sidebar\Models\Sidebar;
use App\Admin\SystemSettings\Services\SystemSettingService;
use App\Admin\Templates\Models\Template;
use App\Admin\Templates\Models\TemplateCatalogItem;
use App\Admin\Templates\Models\WebsiteType;
use App\Admin\Tenants\Models\Tenant;
use App\Admin\Users\Models\User;
use App\Shared\Enums\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::query()->create($this->payload($data));

            $this->createOwner($tenant, $data);

            $this->createDefaultSidebar($tenant);
            $this->createDefaultTemplate($tenant);

            return $tenant;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Tenant $tenant, array $data): Tenant
    {
        return DB::transaction(function () use ($tenant, $data): Tenant {
            $tenant->update($this->payload($data));

            $this->updateOwner($tenant, $data);

            return $tenant->fresh();
        });
    }

    public function setStatus(Tenant $tenant, string $status): Tenant
    {
        $tenant->update(['status' => $status]);

        return $tenant->fresh();
    }

    public function delete(Tenant $tenant): void
    {
        // Delete through the Eloquent builder to keep soft deletes consistent.
        Tenant::query()->whereKey($tenant->getKey())->delete();
    }

    /**
     * Keep tenant persistence limited to fields admins can manage.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function payload(array $data): array
    {
        return [
            'name'      => $data['name'],
            'subdomain' => $data['subdomain'],
            'timezone'  => $data['timezone'] ?? $this->settings->string('tenant_defaults.default_tenant_timezone', 'UTC'),
            'status'    => $data['status']   ?? $this->settings->string('tenant_defaults.default_tenant_status', 'active'),
            'settings'  => array_merge([
                'trial_days'            => $this->settings->integer('tenant_defaults.default_tenant_trial_days', 14),
                'default_template_type' => $this->settings->string('tenant_defaults.default_tenant_template_type'),
                'default_template_key'  => $this->settings->string('tenant_defaults.default_tenant_template_key'),
            ], $data['settings'] ?? []),
        ];
    }

    /**
     * Create the first tenant user so the tenant can sign in.
     *
     * @param  array<string, mixed>  $data
     */
    private function createOwner(Tenant $tenant, array $data): void
    {
        User::query()->create([
            'tenant_id'         => $tenant->id,
            'name'              => $data['owner_name'],
            'first_name'        => $data['owner_first_name'] ?? null,
            'last_name'         => $data['owner_last_name']  ?? null,
            'email'             => $data['owner_email'],
            'phone'             => $data['owner_phone'] ?? null,
            'password'          => Hash::make($data['owner_password']),
            'email_verified_at' => $this->settings->boolean('authentication.require_email_verification') ? null : now(),
            'is_active'         => true,
            'user_type'         => UserType::TENANT,
        ]);
    }

    private function createDefaultSidebar(Tenant $tenant): void
    {
        Sidebar::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name'      => 'default',
                'is_admin'  => false,
            ],
            [
                'description' => 'Default tenant sidebar.',
                'data'        => [
                    'teams' => [
                        [
                            'name' => $tenant->name,
                            'logo' => 'Sparkles',
                            'plan' => 'Workspace',
                        ],
                    ],
                    'main_nav' => [
                        [
                            'title'       => 'Site Builder',
                            'url'         => '#',
                            'icon'        => 'Sparkles',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Templates', 'url' => 'templates', 'is_active' => true],
                                ['title' => 'Posts', 'url' => 'posts', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Creative',
                            'url'         => '#',
                            'icon'        => 'Palette',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Design Requests', 'url' => 'design-requests', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Insights',
                            'url'         => '#',
                            'icon'        => 'BarChart3',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Analytics', 'url' => 'analytics', 'is_active' => true],
                                ['title' => 'Tracking Logs', 'url' => 'tracking-logs', 'is_active' => true],
                            ],
                        ],
                        [
                            'title'       => 'Maintenance',
                            'url'         => '#',
                            'icon'        => 'Wrench',
                            'description' => '',
                            'is_active'   => true,
                            'items'       => [
                                ['title' => 'Sidebar', 'url' => 'sidebar', 'is_active' => true],
                            ],
                        ],
                    ],
                    'projects' => [],
                ],
            ],
        );
    }

    private function createDefaultTemplate(Tenant $tenant): void
    {
        $websiteTypeKey = $this->settings->string('tenant_defaults.default_tenant_template_type');
        $templateKey    = $this->settings->string('tenant_defaults.default_tenant_template_key');

        if ($websiteTypeKey === '' || $templateKey === '') {
            return;
        }

        $websiteType = WebsiteType::query()
            ->where(function ($query) use ($websiteTypeKey): void {
                $query->where('slug', $websiteTypeKey)
                    ->orWhereKey($websiteTypeKey);
            })
            ->where('is_active', true)
            ->first();

        $catalogItem = $websiteType
            ? TemplateCatalogItem::query()
                ->where('website_type_id', $websiteType->id)
                ->where('key', $templateKey)
                ->where('is_active', true)
                ->first()
            : null;

        if (! $websiteType || ! $catalogItem) {
            return;
        }

        Template::query()->create([
            'tenant_id'       => $tenant->id,
            'website_type_id' => $websiteType->id,
            'name'            => $catalogItem->name,
            'slug'            => Str::slug($catalogItem->name) ?: 'site',
            'template_key'    => $catalogItem->key,
            'business_name'   => $tenant->name,
            'logo'            => $this->settings->string('general.logo'),
            'contact_info'    => [
                'email'   => $this->settings->string('general.support_email'),
                'phone'   => $this->settings->string('general.support_phone'),
                'address' => $this->settings->string('general.company_address'),
            ],
            'social_links' => [
                'facebook'  => $this->settings->string('social.facebook_url'),
                'instagram' => $this->settings->string('social.instagram_url'),
                'linkedin'  => $this->settings->string('social.linkedin_url'),
                'twitter'   => $this->settings->string('social.twitter_url'),
                'youtube'   => $this->settings->string('social.youtube_url'),
            ],
            'content'          => $catalogItem->default_content ?? [],
            'font_family'      => 'Inter',
            'primary_color'    => '#14b8a6',
            'secondary_color'  => '#0f766e',
            'background_color' => '#ffffff',
            'text_color'       => '#111827',
            'status'           => 'published',
            'is_default'       => true,
        ]);
    }

    /**
     * Update the linked tenant user used for tenant login.
     *
     * @param  array<string, mixed>  $data
     */
    private function updateOwner(Tenant $tenant, array $data): void
    {
        $owner = $tenant->owner()->first();

        if (! $owner) {
            $this->createOwner($tenant, $data);

            return;
        }

        $payload = [
            'name'       => $data['owner_name'],
            'first_name' => $data['owner_first_name'] ?? null,
            'last_name'  => $data['owner_last_name']  ?? null,
            'email'      => $data['owner_email'],
            'phone'      => $data['owner_phone'] ?? null,
            'is_active'  => true,
            'user_type'  => UserType::TENANT,
        ];

        if (! empty($data['owner_password'])) {
            $payload['password'] = Hash::make($data['owner_password']);
        }

        $owner->update($payload);
    }
}
