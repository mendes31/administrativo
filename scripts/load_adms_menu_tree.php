<?php

declare(strict_types=1);

/**
 * Carrega a árvore do menu administrativo (mesma estrutura de menu.php).
 *
 * @return array<int, array<string, mixed>>
 */
function loadAdmsMenuTree(): array
{
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    }

    $_ENV['URL_ADM'] = '';
    $admsMenuPasswordPolicyId = null;
    $policyId = null;
    if (!class_exists('WhistleblowingPublicUrlHelper', false)) {
        class_alias(\App\adms\Helpers\WhistleblowingPublicUrlHelper::class, 'WhistleblowingPublicUrlHelper');
    }

    $menuFile = dirname(__DIR__) . '/app/adms/Views/partials/menu.php';
    $lines = file($menuFile, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return [];
    }

    $start = null;
    $end = null;
    foreach ($lines as $i => $line) {
        if ($start === null && preg_match('/^\$menus\s*=\s*\[/', ltrim((string) $line))) {
            $start = $i;
            continue;
        }
        if ($start !== null && str_contains((string) $line, 'Ordenação alfabética do menu')) {
            $end = $i - 1;
            while ($end > $start && trim((string) $lines[$end]) === '') {
                $end--;
            }
            break;
        }
    }
    if ($start === null || $end === null || $end <= $start) {
        return [];
    }

    $chunk = implode("\n", array_slice($lines, $start, $end - $start + 1));
    eval($chunk);

    if (!function_exists('sortAdmsMenuTree')) {
        require_once __DIR__ . '/adms_menu_sort.php';
    }

    /** @var array<int, array<string, mixed>> $menus */
    return sortAdmsMenuTree($menus);
}
