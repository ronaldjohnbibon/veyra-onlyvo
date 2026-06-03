<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_system_settings')) {
            return;
        }

        Schema::create('tenant_system_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('group', 100)->index();
            $table->string('key', 150);
            $table->string('label', 150);
            $table->string('type', 50)->default('string');
            $table->json('value')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();

            $table->unique(['tenant_id', 'key']);
        });

        $this->backfillFromTenantJson();
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_system_settings');
    }

    private function backfillFromTenantJson(): void
    {
        DB::table('tenants')
            ->whereNotNull('settings')
            ->orderBy('id')
            ->select(['id', 'settings'])
            ->each(function (object $tenant): void {
                $settings = json_decode((string) $tenant->settings, true);

                if (! is_array($settings)) {
                    return;
                }

                foreach ($settings as $key => $value) {
                    if (! is_string($key) || ! str_contains($key, '.')) {
                        continue;
                    }

                    $group = Str::before($key, '.');

                    DB::table('tenant_system_settings')->insertOrIgnore([
                        'id'         => (string) Str::uuid(),
                        'tenant_id'  => $tenant->id,
                        'group'      => $group,
                        'key'        => $key,
                        'label'      => Str::headline(Str::after($key, '.')),
                        'type'       => $this->typeFor($value),
                        'value'      => json_encode($value),
                        'is_public'  => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    private function typeFor(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value)  => 'integer',
            default         => 'string',
        };
    }
};
