<?php

namespace App\Admin\Sidebar\Services;

use App\Admin\Sidebar\Models\Sidebar;

class SidebarService
{
    public function create(array $data): Sidebar
    {
        return Sidebar::create($this->normalizePayload($data));
    }

    public function update(Sidebar $sidebar, array $data): Sidebar
    {
        $sidebar->update($this->normalizePayload($data));

        return $sidebar->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizePayload(array $data): array
    {
        // Match validated request values to the model casts before saving.
        if (array_key_exists('is_admin', $data)) {
            $data['is_admin'] = (bool) $data['is_admin'];
        }

        if (isset($data['name'])) {
            $data['name'] = trim((string) $data['name']);
        }

        if (isset($data['data']) && is_array($data['data'])) {
            $data['data'] = $this->normalizeStructure($data['data']);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $structure
     * @return array<string, mixed>
     */
    private function normalizeStructure(array $structure): array
    {
        if (! isset($structure['main_nav']) || ! is_array($structure['main_nav'])) {
            return $structure;
        }

        // Store nested active flags as booleans for consistent JSON output.
        $structure['main_nav'] = array_map(function (array $item): array {
            if (array_key_exists('is_active', $item)) {
                $item['is_active'] = (bool) $item['is_active'];
            }

            if (isset($item['items']) && is_array($item['items'])) {
                $item['items'] = array_map(function (array $child): array {
                    if (array_key_exists('is_active', $child)) {
                        $child['is_active'] = (bool) $child['is_active'];
                    }

                    return $child;
                }, $item['items']);
            }

            return $item;
        }, $structure['main_nav']);

        return $structure;
    }
}
