<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('audit_logs', 'scope')) {
                $table->string('scope', 20)->default('admin')->after('id')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'tenant_id')) {
                $table->uuid('tenant_id')->nullable()->after('scope')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'category')) {
                $table->string('category', 40)->default('audit')->after('tenant_id')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'severity')) {
                $table->string('severity', 20)->default('info')->after('category')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('ip_address');
            }
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            if (! $this->hasIndex('audit_logs_scope_category_severity_index')) {
                $table->index(['scope', 'category', 'severity'], 'audit_logs_scope_category_severity_index');
            }

            if (! $this->hasIndex('audit_logs_tenant_occurred_index')) {
                $table->index(['tenant_id', 'occurred_at'], 'audit_logs_tenant_occurred_index');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            if ($this->hasIndex('audit_logs_scope_category_severity_index')) {
                $table->dropIndex('audit_logs_scope_category_severity_index');
            }

            if ($this->hasIndex('audit_logs_tenant_occurred_index')) {
                $table->dropIndex('audit_logs_tenant_occurred_index');
            }
        });
    }

    private function hasIndex(string $name): bool
    {
        return collect(Schema::getIndexes('audit_logs'))
            ->contains(fn (array $index): bool => ($index['name'] ?? null) === $name);
    }
};
