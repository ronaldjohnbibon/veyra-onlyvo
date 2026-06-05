<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('design_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('design_requests', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('user_id');
                $table->foreign('assigned_to', 'dr_assigned_to_fk')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('design_requests', 'priority')) {
                $table->string('priority', 20)->default('normal')->after('status');
            }

            if (! Schema::hasColumn('design_requests', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('priority');
            }

            if (! Schema::hasColumn('design_requests', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->after('due_at');
            }

            if (! Schema::hasColumn('design_requests', 'internal_notes')) {
                $table->longText('internal_notes')->nullable()->after('admin_remarks');
            }

            if (! Schema::hasColumn('design_requests', 'notification_requested')) {
                $table->boolean('notification_requested')->default(false)->after('internal_notes');
            }

            if (! Schema::hasColumn('design_requests', 'notification_sent_at')) {
                $table->timestamp('notification_sent_at')->nullable()->after('notification_requested');
            }

            if (! Schema::hasColumn('design_requests', 'conversion_type')) {
                $table->string('conversion_type', 40)->nullable()->after('notification_sent_at');
            }

            if (! Schema::hasColumn('design_requests', 'conversion_payload')) {
                $table->json('conversion_payload')->nullable()->after('conversion_type');
            }

            if (! Schema::hasColumn('design_requests', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('conversion_payload');
            }

            if (! Schema::hasColumn('design_requests', 'converted_by')) {
                $table->foreignId('converted_by')->nullable()->after('converted_at');
                $table->foreign('converted_by', 'dr_converted_by_fk')->references('id')->on('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('design_requests', 'linked_template_id')) {
                $table->foreignUuid('linked_template_id')->nullable()->after('converted_by');
                $table->foreign('linked_template_id', 'dr_template_fk')->references('id')->on('templates')->nullOnDelete();
            }

            if (! Schema::hasColumn('design_requests', 'linked_site_url')) {
                $table->text('linked_site_url')->nullable()->after('linked_template_id');
            }
        });

        Schema::table('design_requests', function (Blueprint $table): void {
            if (! Schema::hasIndex('design_requests', 'dr_assigned_status_idx')) {
                $table->index(['assigned_to', 'status'], 'dr_assigned_status_idx');
            }

            if (! Schema::hasIndex('design_requests', 'dr_priority_due_idx')) {
                $table->index(['priority', 'due_at'], 'dr_priority_due_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('design_requests', function (Blueprint $table): void {
            if (Schema::hasIndex('design_requests', 'dr_assigned_status_idx')) {
                $table->dropIndex('dr_assigned_status_idx');
            }

            if (Schema::hasIndex('design_requests', 'dr_priority_due_idx')) {
                $table->dropIndex('dr_priority_due_idx');
            }
        });

        Schema::table('design_requests', function (Blueprint $table): void {
            foreach (['dr_assigned_to_fk', 'dr_converted_by_fk', 'dr_template_fk'] as $foreign) {
                try {
                    $table->dropForeign($foreign);
                } catch (Throwable) {
                    //
                }
            }

            foreach ([
                'assigned_to',
                'priority',
                'due_at',
                'sla_due_at',
                'internal_notes',
                'notification_requested',
                'notification_sent_at',
                'conversion_type',
                'conversion_payload',
                'converted_at',
                'converted_by',
                'linked_template_id',
                'linked_site_url',
            ] as $column) {
                if (Schema::hasColumn('design_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
