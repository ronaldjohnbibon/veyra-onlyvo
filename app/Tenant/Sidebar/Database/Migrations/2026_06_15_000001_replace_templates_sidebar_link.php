<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->updateTenantSidebars('template-builder', 'Template Builder', 'templates');
    }

    public function down(): void
    {
        $this->updateTenantSidebars('templates', 'Templates', 'template-builder');
    }

    private function updateTenantSidebars(string $newUrl, string $newTitle, string $oldUrl): void
    {
        if (! Schema::hasTable('sidebars')) {
            return;
        }

        DB::table('sidebars')
            ->where('is_admin', false)
            ->orderBy('id')
            ->each(function (object $sidebar) use ($newUrl, $newTitle, $oldUrl): void {
                $data = json_decode((string) $sidebar->data, true);

                if (! is_array($data) || ! is_array($data['main_nav'] ?? null)) {
                    return;
                }

                foreach ($data['main_nav'] as &$group) {
                    if (! is_array($group['items'] ?? null)) {
                        continue;
                    }

                    $hasNewLink = collect($group['items'])->contains(
                        fn ($item): bool => is_array($item) && ($item['url'] ?? null) === $newUrl
                    );

                    $items = [];

                    foreach ($group['items'] as $item) {
                        if (! is_array($item)) {
                            $items[] = $item;

                            continue;
                        }

                        if (($item['url'] ?? null) === $oldUrl) {
                            if ($hasNewLink) {
                                continue;
                            }

                            $item['title'] = $newTitle;
                            $item['url']   = $newUrl;
                            $hasNewLink    = true;
                        }

                        if (($item['url'] ?? null) === $newUrl && collect($items)->contains(
                            fn ($existing): bool => is_array($existing) && ($existing['url'] ?? null) === $newUrl
                        )) {
                            continue;
                        }

                        $items[] = $item;
                    }

                    $group['items'] = array_values($items);
                }

                DB::table('sidebars')
                    ->where('id', $sidebar->id)
                    ->update(['data' => json_encode($data)]);
            });
    }
};
