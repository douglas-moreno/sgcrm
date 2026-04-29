<?php

declare(strict_types=1);

namespace App\Support;

final class Navigation
{
    public const ROLE_BUSINESS_OWNER = 'business_owner';

    public const ROLE_SALESPERSON = 'salesperson';

    /**
     * Build the sidebar items for a given role. Pass null for unauthenticated
     * users; the result is empty in that case.
     *
     * @return list<array{key: string, label: string, route: string, icon: string}>
     */
    public static function items(?string $role): array
    {
        if ($role === null) {
            return [];
        }

        $items = [
            [
                'key' => 'kanban',
                'label' => 'Pipeline',
                'route' => 'kanban',
                'icon' => 'view-columns',
            ],
            [
                'key' => 'leads',
                'label' => 'Leads',
                'route' => 'leads.index',
                'icon' => 'users',
            ],
        ];

        if ($role === self::ROLE_BUSINESS_OWNER) {
            $items[] = [
                'key' => 'reports',
                'label' => 'Reports',
                'route' => 'reports.index',
                'icon' => 'chart-bar',
            ];
            $items[] = [
                'key' => 'team',
                'label' => 'Team',
                'route' => 'team.index',
                'icon' => 'user-group',
            ];
        }

        $items[] = [
            'key' => 'settings',
            'label' => 'Settings',
            'route' => 'settings.index',
            'icon' => 'cog-6-tooth',
        ];

        return $items;
    }
}
