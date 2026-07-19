<?php

declare(strict_types=1);

/**
 * Preflight CLI de comunicações de entrevista (sem SMTP).
 *
 * Uso:
 *   php scripts/rh_entrevista_comunicacoes_preflight.php
 *   php scripts/rh_entrevista_comunicacoes_preflight.php --limit=20
 *   php scripts/rh_entrevista_comunicacoes_preflight.php --apply
 *
 * --apply só funciona se RH_ENTREVISTA_PREFLIGHT_APPLY=true no .env.
 * Nunca envia e-mail; apenas recorded → ready|blocked.
 */

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\RhEntrevistaComunicacaoPreflightService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script só pode rodar em CLI.\n");
    exit(1);
}

$limit = 20;
$apply = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, min(100, (int) substr($arg, 8)));
    }
    if ($arg === '--apply') {
        $apply = true;
    }
}

if ($apply && !RhEntrevistaComunicacaoPreflightService::isApplyEnabled()) {
    fwrite(STDERR, "Apply bloqueado: defina RH_ENTREVISTA_PREFLIGHT_APPLY=true no .env para permitir.\n");
    exit(2);
}

$service = new RhEntrevistaComunicacaoPreflightService();
$report = $service->run($limit, $apply);

$mode = $report['dry_run'] ? 'DRY-RUN' : 'APPLY';
echo "=== Preflight comunicações entrevista ({$mode}) ===\n";
echo 'Scanned: ' . $report['totals']['scanned']
    . ' | ready: ' . $report['totals']['ready']
    . ' | blocked: ' . $report['totals']['blocked']
    . ' | applied: ' . $report['totals']['applied']
    . ' | skipped: ' . $report['totals']['skipped']
    . "\n";

foreach ($report['items'] as $item) {
    $reasons = implode(', ', $item['reasons']);
    $flag = !empty($item['applied']) ? 'APPLIED' : 'PLAN';
    echo sprintf(
        "[%s] com#%d entrevista#%d → %s (%s)\n",
        $flag,
        $item['id'],
        $item['entrevista_id'],
        $item['decision'],
        $reasons
    );
}

if ($report['dry_run']) {
    echo "Nenhuma alteração gravada. Use --apply com RH_ENTREVISTA_PREFLIGHT_APPLY=true para promover ready/blocked.\n";
}

exit(0);
