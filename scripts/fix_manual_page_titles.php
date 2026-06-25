<?php

declare(strict_types=1);

/**
 * Corrige títulos do manual que vieram do slug (URL) em vez do nome da página no seed.
 *
 * Uso: php scripts/fix_manual_page_titles.php
 *      php scripts/fix_manual_page_titles.php --dry-run
 */

require_once __DIR__ . '/manual_doc_lib.php';
require_once __DIR__ . '/manual_coverage_lib.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$contentRoot = dirname(__DIR__) . '/docs/manual/content';
$pageTitles = manual_page_titles_by_slug();
$updated = 0;

foreach (glob($contentRoot . '/*/*.html') ?: [] as $absPath) {
    $slug = pathinfo($absPath, PATHINFO_FILENAME);
    if (!isset($pageTitles[$slug])) {
        continue;
    }

    $correct = $pageTitles[$slug];
    $legacy = manual_legacy_title_from_slug($slug);
    $humanized = manual_humanize_slug($slug);
    $wrongTitles = array_unique(array_filter([
        $legacy,
        $humanized,
        ucwords(str_replace('-', ' ', $slug)),
    ], static fn (string $t): bool => $t !== '' && $t !== $correct));

    $html = (string) file_get_contents($absPath);
    $original = $html;

    if (preg_match('/<h1>.*?<\/h1>/s', $html)) {
        $html = preg_replace('/<h1>.*?<\/h1>/s', '<h1>' . $correct . '</h1>', $html, 1) ?? $html;
    }

    foreach ($wrongTitles as $wrong) {
        $html = str_replace($wrong, $correct, $html);
    }

    if ($html !== $original) {
        $rel = str_replace(dirname(__DIR__) . DIRECTORY_SEPARATOR, '', $absPath);
        echo ($dryRun ? '[dry-run] ' : '') . "Atualizado: {$rel} → {$correct}\n";
        if (!$dryRun) {
            file_put_contents($absPath, $html);
        }
        $updated++;
    }
}

echo "\n{$updated} arquivo(s) " . ($dryRun ? 'seriam atualizados' : 'atualizados') . ".\n";
if (!$dryRun && $updated > 0) {
    echo "Execute: php scripts/generate_manual_manifest.php && php scripts/generate_help_menu.php\n";
}
