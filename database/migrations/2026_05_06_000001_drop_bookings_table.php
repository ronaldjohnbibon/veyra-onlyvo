<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('bookings');

        if (! Schema::hasTable('sidebars')) {
            return;
        }

        $removeBookingLinks = function (array $items) use (&$removeBookingLinks): array {
            return array_values(array_filter(array_map(function (array $item) use (&$removeBookingLinks): ?array {
                $url = trim((string) ($item['url'] ?? ''), '/');

                if ($url === 'bookings') {
                    return null;
                }

                if (isset($item['items']) && is_array($item['items'])) {
                    $item['items'] = $removeBookingLinks($item['items']);
                }

                return $item;
            }, $items)));
        };

        DB::table('sidebars')->select(['id', 'data'])->orderBy('id')->each(function (object $sidebar) use ($removeBookingLinks): void {
            $data = json_decode((string) $sidebar->data, true);

            if (! is_array($data)) {
                return;
            }

            if (isset($data['main_nav']) && is_array($data['main_nav'])) {
                $data['main_nav'] = $removeBookingLinks($data['main_nav']);
            }

            if (isset($data['projects']) && is_array($data['projects'])) {
                $data['projects'] = $removeBookingLinks($data['projects']);
            }

            DB::table('sidebars')
                ->where('id', $sidebar->id)
                ->update(['data' => json_encode($data, JSON_UNESCAPED_SLASHES)]);
        });
    }

    public function down(): void
    {
        Schema::create('bookings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('service');
            $table->date('booking_date');
            $table->time('booking_time');
            $table->text('notes')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }
};
