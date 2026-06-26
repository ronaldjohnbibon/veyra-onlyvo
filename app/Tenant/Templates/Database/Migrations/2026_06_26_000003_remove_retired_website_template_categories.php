<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return array<string, array{id: string, name: string}>
     */
    private function retiredWebsiteTypes(): array
    {
        return [
            'business-website' => [
                'id'   => '11111111-1111-4111-8111-111111111111',
                'name' => 'Business Website',
            ],
            'portfolio-website' => [
                'id'   => '22222222-2222-4222-8222-222222222222',
                'name' => 'Portfolio Website',
            ],
            'restaurant-website' => [
                'id'   => '66666666-6666-4666-8666-666666666666',
                'name' => 'Restaurant Website',
            ],
        ];
    }

    public function up(): void
    {
        $this->clearRetiredDefaultTemplateSettings();

        if (! Schema::hasTable('website_types')) {
            return;
        }

        $websiteTypeIds = DB::table('website_types')
            ->whereIn('slug', array_keys($this->retiredWebsiteTypes()))
            ->pluck('id')
            ->all();

        if ($websiteTypeIds === []) {
            return;
        }

        if (Schema::hasTable('templates')) {
            DB::table('templates')
                ->whereIn('website_type_id', $websiteTypeIds)
                ->delete();
        }

        if (Schema::hasTable('template_catalog_items')) {
            DB::table('template_catalog_items')
                ->whereIn('website_type_id', $websiteTypeIds)
                ->delete();
        }

        DB::table('website_types')
            ->whereIn('id', $websiteTypeIds)
            ->delete();
    }

    public function down(): void
    {
        if (! Schema::hasTable('website_types')) {
            return;
        }

        $now = now();

        foreach ($this->retiredWebsiteTypes() as $slug => $type) {
            DB::table('website_types')->updateOrInsert(
                ['id' => $type['id']],
                [
                    'name'        => $type['name'],
                    'slug'        => $slug,
                    'description' => null,
                    'is_active'   => true,
                    'updated_at'  => $now,
                    'created_at'  => $now,
                ],
            );
        }
    }

    private function clearRetiredDefaultTemplateSettings(): void
    {
        $retiredSlugs = array_keys($this->retiredWebsiteTypes());
        $now          = now();

        if (Schema::hasTable('system_settings')) {
            $settingIds = DB::table('system_settings')
                ->whereIn('key', [
                    'tenant_defaults.default_tenant_template_type',
                    'branding.default_tenant_template_type',
                ])
                ->get(['id', 'value'])
                ->filter(fn (object $setting): bool => in_array($this->jsonValue($setting->value), $retiredSlugs, true))
                ->pluck('id')
                ->all();

            if ($settingIds !== []) {
                DB::table('system_settings')
                    ->whereIn('id', $settingIds)
                    ->update([
                        'value'      => json_encode(''),
                        'updated_at' => $now,
                    ]);

                Cache::forget('system_settings.values');
            }
        }

        if (! Schema::hasTable('tenants')) {
            return;
        }

        DB::table('tenants')
            ->whereNotNull('settings')
            ->get(['id', 'settings'])
            ->each(function (object $tenant) use ($retiredSlugs, $now): void {
                $settings = $this->jsonValue($tenant->settings);

                if (! is_array($settings) || ! in_array($settings['default_template_type'] ?? null, $retiredSlugs, true)) {
                    return;
                }

                $settings['default_template_type'] = '';
                $settings['default_template_key']  = '';

                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update([
                        'settings'   => json_encode($settings),
                        'updated_at' => $now,
                    ]);
            });
    }

    private function jsonValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
};
