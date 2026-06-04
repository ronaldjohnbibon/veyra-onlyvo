<?php

use App\Tenant\Sidebar\Models\Sidebar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $knownUrls = [
        'dashboard',
        'templates',
        'template-builder',
        'posts',
        'navigation-builder',
        'sidebar',
        'leads',
        'analytics',
        'tracking-logs',
        'design-requests',
        'system-settings',
        'account',
        'team-management',
    ];

    public function up(): void
    {
        $this->syncNavigation(false);
    }

    public function down(): void
    {
        $this->syncNavigation(true);
    }

    private function syncNavigation(bool $legacy): void
    {
        Sidebar::withoutTenantRestrictions(function () use ($legacy): void {
            Sidebar::query()
                ->where('is_admin', false)
                ->get()
                ->each(function (Sidebar $sidebar) use ($legacy): void {
                    $data    = $sidebar->data    ?? [];
                    $mainNav = is_array($data['main_nav'] ?? null) ? $data['main_nav'] : [];

                    $data['main_nav'] = $legacy
                        ? $this->legacyNavigation($mainNav)
                        : $this->workspaceNavigation($mainNav);

                    $sidebar->update(['data' => $data]);
                });
        });
    }

    /**
     * @param  array<int, mixed>  $mainNav
     * @return array<int, array<string, mixed>>
     */
    private function workspaceNavigation(array $mainNav): array
    {
        $navigation = [
            $this->group('Home', 'LayoutDashboard', [
                $this->link($mainNav, 'Dashboard', 'dashboard'),
            ]),
            $this->group('Website', 'Frame', [
                $this->link($mainNav, 'Templates', 'templates'),
                $this->link($mainNav, 'Template Builder', 'template-builder'),
                $this->link($mainNav, 'Posts', 'posts'),
                $this->link($mainNav, 'Navigation Builder', 'navigation-builder', 'sidebar'),
            ]),
            $this->group('Growth', 'BarChart3', [
                $this->link($mainNav, 'Leads/Submissions', 'leads'),
                $this->link($mainNav, 'Analytics', 'analytics'),
                $this->link($mainNav, 'Tracking Activity', 'tracking-logs'),
            ]),
            $this->group('Collaboration', 'Palette', [
                $this->link($mainNav, 'Design Requests', 'design-requests'),
            ]),
            $this->group('Workspace', 'Settings', [
                $this->link($mainNav, 'System Settings', 'system-settings'),
                $this->link($mainNav, 'Account/Profile', 'account'),
                $this->link($mainNav, 'Team Management', 'team-management'),
            ]),
        ];

        $customLinks = $this->customLinks($mainNav);
        if ($customLinks !== []) {
            $navigation[] = $this->group('More', 'Circle', $customLinks);
        }

        return $navigation;
    }

    /**
     * @param  array<int, mixed>  $mainNav
     * @return array<int, array<string, mixed>>
     */
    private function legacyNavigation(array $mainNav): array
    {
        return [
            $this->link($mainNav, 'Dashboard', 'dashboard'),
            $this->group('Site Builder', 'Sparkles', [
                $this->link($mainNav, 'Templates', 'templates'),
                $this->link($mainNav, 'Posts', 'posts'),
            ]),
            $this->group('Creative', 'Palette', [
                $this->link($mainNav, 'Design Requests', 'design-requests'),
            ]),
            $this->group('Insights', 'BarChart3', [
                $this->link($mainNav, 'Analytics', 'analytics'),
                $this->link($mainNav, 'Leads', 'leads'),
                $this->link($mainNav, 'Tracking Logs', 'tracking-logs'),
            ]),
            $this->group('Maintenance', 'Wrench', [
                $this->link($mainNav, 'Sidebar', 'sidebar', 'navigation-builder'),
                $this->link($mainNav, 'System Settings', 'system-settings'),
            ]),
        ];
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<string, mixed>|null
     */
    private function findByUrl(array $items, string $url): ?array
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            if ($this->normalizeUrl((string) ($item['url'] ?? '')) === $url) {
                return $item;
            }

            if (is_array($item['items'] ?? null)) {
                $match = $this->findByUrl($item['items'], $url);

                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $mainNav
     * @return array<string, mixed>
     */
    private function link(array $mainNav, string $title, string $url, ?string $fallbackUrl = null): array
    {
        $existing = $this->findByUrl($mainNav, $url);

        if (! $existing && $fallbackUrl) {
            $existing = $this->findByUrl($mainNav, $fallbackUrl);
        }

        return [
            'title'       => $title,
            'url'         => $url,
            'icon'        => $existing['icon']        ?? null,
            'description' => $existing['description'] ?? '',
            'is_active'   => $existing['is_active']   ?? true,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, mixed>
     */
    private function group(string $title, string $icon, array $items): array
    {
        return [
            'title'       => $title,
            'url'         => '#',
            'icon'        => $icon,
            'description' => '',
            'is_active'   => collect($items)->contains(fn (array $item): bool => ($item['is_active'] ?? true) !== false),
            'items'       => $items,
        ];
    }

    /**
     * @param  array<int, mixed>  $mainNav
     * @return array<int, array<string, mixed>>
     */
    private function customLinks(array $mainNav): array
    {
        $links = [];

        foreach ($mainNav as $item) {
            if (! is_array($item)) {
                continue;
            }

            $links = array_merge($links, $this->customLinksFromItem($item));
        }

        return array_values($links);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customLinksFromItem(array $item): array
    {
        $links = [];
        $url   = $this->normalizeUrl((string) ($item['url'] ?? ''));

        if ($url !== '' && $url !== '#' && ! in_array($url, $this->knownUrls, true)) {
            $links[] = [
                'title'       => (string) ($item['title'] ?? 'Custom Link'),
                'url'         => (string) ($item['url'] ?? ''),
                'icon'        => $item['icon']        ?? null,
                'description' => $item['description'] ?? '',
                'is_active'   => $item['is_active']   ?? true,
            ];
        }

        if (is_array($item['items'] ?? null)) {
            foreach ($item['items'] as $child) {
                if (is_array($child)) {
                    $links = array_merge($links, $this->customLinksFromItem($child));
                }
            }
        }

        return $links;
    }

    private function normalizeUrl(string $url): string
    {
        return trim($url, '/');
    }
};
