<?php

declare(strict_types=1);

if (!function_exists('admsMenuIsDashboardEntry')) {
    function admsMenuIsDashboardEntry(array $item): bool
    {
        if (($item['id'] ?? '') === 'dashboard') {
            return true;
        }
        $label = (string) ($item['label'] ?? '');
        if ($label !== '' && stripos($label, 'dashboard') !== false) {
            return true;
        }
        $permission = (string) ($item['permission'] ?? '');
        if ($permission !== '' && stripos($permission, 'Dashboard') !== false) {
            return true;
        }

        return false;
    }
}

if (!function_exists('admsMenuCompareEntries')) {
    function admsMenuCompareEntries(array $a, array $b): int
    {
        $aLogout = ($a['id'] ?? '') === 'logout';
        $bLogout = ($b['id'] ?? '') === 'logout';
        if ($aLogout !== $bLogout) {
            return $aLogout ? 1 : -1;
        }

        $aDash = admsMenuIsDashboardEntry($a);
        $bDash = admsMenuIsDashboardEntry($b);
        if ($aDash !== $bDash) {
            return $aDash ? -1 : 1;
        }

        $labelA = trim((string) ($a['label'] ?? ''));
        $labelB = trim((string) ($b['label'] ?? ''));

        return strcasecmp($labelA, $labelB);
    }
}

if (!function_exists('sortAdmsMenuTree')) {
    /**
     * @param array<int, array<string, mixed>> $menus
     * @return array<int, array<string, mixed>>
     */
    function sortAdmsMenuTree(array $menus): array
    {
        foreach ($menus as $index => $menu) {
            if (!empty($menu['submenu']) && is_array($menu['submenu'])) {
                $menus[$index]['submenu'] = sortAdmsMenuTree($menu['submenu']);
            }
        }

        usort($menus, 'admsMenuCompareEntries');

        return array_values($menus);
    }
}
