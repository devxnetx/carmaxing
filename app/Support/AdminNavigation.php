<?php

namespace App\Support;

class AdminNavigation
{
    /**
     * @return array<int, array{route?: string, url?: string, label: string, icon: string, external?: bool}>
     */
    public static function items(): array
    {
        $items = [
            ['route' => 'admin.dashboard', 'label' => __('admin.nav_dashboard'), 'icon' => 'dashboard'],
            ['route' => 'admin.users.index', 'label' => __('admin.nav_users'), 'icon' => 'user'],
            ['route' => 'admin.companies.index', 'label' => __('admin.nav_companies'), 'icon' => 'building'],
            ['route' => 'admin.leads.index', 'label' => __('admin.nav_leads'), 'icon' => 'user'],
            ['route' => 'admin.listings.index', 'label' => __('admin.nav_listings'), 'icon' => 'list'],
            ['route' => 'admin.reports.index', 'label' => __('admin.nav_reports'), 'icon' => 'flag'],
            ['route' => 'admin.api-keys.index', 'label' => __('admin.nav_api_keys'), 'icon' => 'link'],
            ['route' => 'admin.imports.index', 'label' => __('admin.nav_imports'), 'icon' => 'share'],
            ['route' => 'admin.settings.index', 'label' => __('admin.nav_settings'), 'icon' => 'cog'],
        ];

        if (class_exists(\Laravel\Horizon\Horizon::class)) {
            $items[] = [
                'url' => url('/'.trim(config('horizon.path', 'horizon'), '/')),
                'label' => __('admin.nav_horizon'),
                'icon' => 'cog',
                'external' => true,
            ];
        }

        return $items;
    }

    public static function isActive(array $link): bool
    {
        if (! isset($link['route'])) {
            return request()->is(trim(config('horizon.path', 'horizon'), '/').'*');
        }

        $route = $link['route'];
        $pattern = str_ends_with($route, '.index')
            ? str_replace('.index', '.*', $route)
            : $route;

        return request()->routeIs($pattern) || request()->routeIs($route);
    }
}