<?php

declare(strict_types=1);

/**
 * Carrega a árvore do menu administrativo (mesma estrutura de menu.php).
 *
 * @return array<int, array<string, mixed>>
 */
function loadAdmsMenuTree(): array
{
    $_ENV['URL_ADM'] = '';
    $admsMenuPasswordPolicyId = null;
    $policyId = null;

    $menuFile = dirname(__DIR__) . '/app/adms/Views/partials/menu.php';
    $lines = file($menuFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $chunk = implode("\n", array_slice($lines, 17, 1409));
    eval($chunk);

    if (!function_exists('sortAdmsMenuTree')) {
        require_once __DIR__ . '/adms_menu_sort.php';
    }

    /** @var array<int, array<string, mixed>> $menus */
    return sortAdmsMenuTree($menus);
}
