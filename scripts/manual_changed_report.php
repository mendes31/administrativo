<?php

declare(strict_types=1);

/**
 * Relatório de alterações git → tópicos do manual (para Cursor, PR e pré-commit).
 *
 * Uso:
 *   php scripts/manual_changed_report.php
 *   php scripts/manual_changed_report.php --staged
 *   php scripts/manual_changed_report.php --base=origin/dev-master
 *   php scripts/manual_changed_report.php --base=auto
 *   php scripts/manual_changed_report.php --json
 *   php scripts/manual_changed_report.php --strict   # exit 1 se houver pendências relevantes
 */

require_once __DIR__ . '/manual_coverage_lib.php';

$json = in_array('--json', $argv ?? [], true);
$strict = in_array('--strict', $argv ?? [], true);
$stagedOnly = in_array('--staged', $argv ?? [], true);
$baseRef = null;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--base=')) {
        $baseRef = substr($arg, 7);
    }
}

$report = manual_build_change_report($baseRef, $stagedOnly);

if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
} else {
    echo manual_format_change_report_markdown($report);
}

$exit = 0;
if ($strict && ($report['has_relevant_changes'] ?? false)) {
    $needsWork = ($report['issue_count'] ?? 0) > 0
        || ($report['needs_manual_update'] ?? []) !== [];
    if ($needsWork) {
        $exit = 1;
    }
}

exit($exit);
