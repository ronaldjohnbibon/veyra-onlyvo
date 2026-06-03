<?php

namespace App\Tenant\Auth\Services;

use App\Shared\Enums\UserType;
use App\Shared\SystemSettings\Services\SystemSettingService;
use App\Tenant\Templates\Models\Template;
use App\Tenant\Templates\Models\TemplateCatalogItem;
use App\Tenant\Templates\Models\WebsiteType;
use App\Tenant\Tenants\Models\Tenant;
use App\Tenant\Users\Models\User;
use Database\Seeders\SidebarSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        private readonly SystemSettingService $settings,
    ) {}

    public function register(array $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::create([
                'name'      => $data['name'],
                'subdomain' => $this->generateSubdomain($data['name']),
                'timezone'  => $this->settings->string('tenant_defaults.default_tenant_timezone', 'UTC'),
                'status'    => $this->settings->string('tenant_defaults.default_tenant_status', 'active'),
                'settings'  => [
                    'trial_days'            => $this->settings->integer('tenant_defaults.default_tenant_trial_days', 14),
                    'default_template_type' => $this->settings->string('tenant_defaults.default_tenant_template_type'),
                    'default_template_key'  => $this->settings->string('tenant_defaults.default_tenant_template_key'),
                ],
            ]);

            User::create([
                'name'              => $data['name'],
                'tenant_id'         => $tenant->id,
                'email'             => $data['email'],
                'phone'             => $data['phone'],
                'password'          => Hash::make($data['password']),
                'email_verified_at' => $this->settings->boolean('authentication.require_email_verification') ? null : now(),
                'user_type'         => UserType::TENANT,
            ]);

            app(SidebarSeeder::class)->runForTenant($tenant);
            $this->createDefaultTemplate($tenant);

            return $tenant;
        });
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

    private function generateSubdomain(string $companyName): string
    {
        $subdomain = strtolower($companyName);
        $subdomain = preg_replace('/[^a-z0-9]+/i', '-', $subdomain) ?? '';
        $subdomain = preg_replace('/-+/', '-', $subdomain)          ?? '';

        return trim($subdomain, '-');
    }
}
