<?php

namespace App\Modules\Templates\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class TemplateCatalogMigration
{
    /**
     * Upsert one template catalog item for a website type.
     *
     * @param  array<string, mixed>  $item
     */
    public static function upsert(string $websiteTypeSlug, array $item): void
    {
        $websiteTypeId = self::websiteTypeId($websiteTypeSlug);
        $now           = now();
        $key           = (string) $item['key'];

        $payload = [
            'name'            => $item['name'],
            'description'     => $item['description']   ?? null,
            'preview_image'   => $item['preview_image'] ?? null,
            'field_schema'    => json_encode($item['field_schema'] ?? []),
            'default_content' => json_encode($item['default_content'] ?? []),
            'is_active'       => $item['is_active'] ?? true,
            'updated_at'      => $now,
        ];

        $existingId = DB::table('template_catalog_items')
            ->where('website_type_id', $websiteTypeId)
            ->where('key', $key)
            ->value('id');

        if ($existingId) {
            DB::table('template_catalog_items')->where('id', $existingId)->update($payload);

            return;
        }

        DB::table('template_catalog_items')->insert(array_merge($payload, [
            'id'              => (string) Str::uuid(),
            'website_type_id' => $websiteTypeId,
            'key'             => $key,
            'created_at'      => $now,
        ]));
    }

    public static function delete(string $websiteTypeSlug, string $key): void
    {
        DB::table('template_catalog_items')
            ->where('website_type_id', self::websiteTypeId($websiteTypeSlug))
            ->where('key', $key)
            ->delete();
    }

    private static function websiteTypeId(string $websiteTypeSlug): string
    {
        $websiteTypeId = DB::table('website_types')->where('slug', $websiteTypeSlug)->value('id');

        if (! $websiteTypeId) {
            throw new RuntimeException("Website type [{$websiteTypeSlug}] is not available.");
        }

        return (string) $websiteTypeId;
    }
}
