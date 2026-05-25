<?php

namespace Tests\Feature;

use App\Modules\Templates\Models\Template;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicTemplateRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_published_template_by_tenant_subdomain_and_slug(): void
    {
        $tenant   = $this->tenant('Onlyvo', 'onlyvo');
        $template = $this->template($tenant, ['slug' => 'my-business', 'status' => 'published']);

        $response = $this->getJson('http://onlyvo.localhost/api/public/sites/my-business');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.slug', 'my-business')
            ->assertJsonCount(1, 'data.sections')
            ->assertJsonPath('data.sections.0.section_type', 'hero');
    }

    public function test_public_endpoint_returns_404_for_draft_templates(): void
    {
        $tenant = $this->tenant('Onlyvo', 'onlyvo');
        $this->template($tenant, ['slug' => 'draft-site', 'status' => 'draft']);

        $this->getJson('http://onlyvo.localhost/api/public/sites/draft-site')
            ->assertNotFound();
    }

    public function test_public_endpoint_returns_404_for_another_tenants_slug(): void
    {
        $tenant = $this->tenant('Onlyvo', 'onlyvo');
        $other  = $this->tenant('Acme', 'acme');

        $this->template($tenant, ['slug' => 'shared-site', 'status' => 'published']);
        $this->template($other, ['slug' => 'other-site', 'status' => 'published']);

        $this->getJson('http://onlyvo.localhost/api/public/sites/other-site')
            ->assertNotFound();
    }

    public function test_default_endpoint_returns_only_tenant_default_published_template(): void
    {
        $tenant = $this->tenant('Onlyvo', 'onlyvo');
        $other  = $this->tenant('Acme', 'acme');

        $this->template($tenant, ['slug' => 'regular', 'status' => 'published']);
        $default = $this->template($tenant, [
            'slug'       => 'default-site',
            'status'     => 'published',
            'is_default' => true,
        ]);
        $this->template($other, ['slug' => 'default-site', 'status' => 'published', 'is_default' => true]);

        $response = $this->getJson('http://onlyvo.localhost/api/public/sites/default');

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $default->id)
            ->assertJsonPath('data.slug', 'default-site');
    }

    public function test_default_endpoint_returns_404_without_default_published_template(): void
    {
        $tenant = $this->tenant('Onlyvo', 'onlyvo');
        $this->template($tenant, ['slug' => 'draft-default', 'status' => 'draft', 'is_default' => true]);

        $this->getJson('http://onlyvo.localhost/api/public/sites/default')
            ->assertNotFound();
    }

    public function test_authenticated_published_preview_route_still_works(): void
    {
        $tenant   = $this->tenant('Onlyvo', 'onlyvo');
        $user     = $this->user($tenant);
        $template = $this->template($tenant, ['slug' => 'preview-site', 'status' => 'published']);

        $token = $user->createToken('preview-test')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', "Bearer {$token}")
            ->getJson("http://onlyvo.localhost/api/app/templates/{$template->id}/published");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.slug', 'preview-site');
    }

    private function tenant(string $name, string $subdomain): Tenant
    {
        return Tenant::query()->create([
            'name'      => $name,
            'subdomain' => $subdomain,
            'timezone'  => 'UTC',
            'status'    => 'active',
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function template(Tenant $tenant, array $attributes = []): Template
    {
        $template = Template::withoutTenantRestrictions(function () use ($tenant, $attributes): Template {
            return Template::query()->create(array_merge([
                'tenant_id'        => $tenant->id,
                'name'             => 'My Business',
                'slug'             => 'my-business',
                'business_name'    => 'My Business',
                'logo'             => 'https://example.com/logo.png',
                'contact_info'     => ['email' => 'hello@example.com', 'phone' => '555-0100', 'address' => ''],
                'social_links'     => ['website' => '', 'linkedin' => '', 'instagram' => '', 'facebook' => ''],
                'font_family'      => 'Inter',
                'primary_color'    => '#14b8a6',
                'secondary_color'  => '#0f766e',
                'background_color' => '#ffffff',
                'text_color'       => '#111827',
                'status'           => 'published',
                'is_default'       => false,
            ], $attributes));
        });

        $template->sections()->createMany([
            [
                'section_type' => 'contact',
                'design_key'   => 'contact-1',
                'sort_order'   => 2,
                'is_enabled'   => false,
                'content_json' => ['title' => 'Contact'],
            ],
            [
                'section_type' => 'hero',
                'design_key'   => 'hero-1',
                'sort_order'   => 1,
                'is_enabled'   => true,
                'content_json' => ['title' => 'Hero'],
            ],
        ]);

        return $template;
    }

    private function user(Tenant $tenant): User
    {
        return User::withoutTenantRestrictions(function () use ($tenant): User {
            return User::query()->create([
                'tenant_id'  => $tenant->id,
                'name'       => 'Tenant User',
                'first_name' => 'Tenant',
                'last_name'  => 'User',
                'email'      => 'user@example.com',
                'phone'      => '555-0100',
                'password'   => 'password',
                'is_active'  => true,
                'user_type'  => 'customer',
            ]);
        });
    }
}
