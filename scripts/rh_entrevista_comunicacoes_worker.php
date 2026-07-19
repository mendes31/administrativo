<?php

declare(strict_types=1);

/**
 * Worker CLI de comunicações de entrevista.
 *
 * Uso seguro:
 *   php scripts/rh_entrevista_comunicacoes_worker.php
 *   php scripts/rh_entrevista_comunicacoes_worker.php --limit=20
 *
 * Envio real exige os dois controles:
 *   interruptor "Envio automático de comunicações de entrevista" ligado na
 *   tela Configuração de E-mail (adms_email_config.rh_entrevista_send_enabled)
 *   php scripts/rh_entrevista_comunicacoes_worker.php --send
 */

require __DIR__ . '/bootstrap_app.php';

use App\adms\Models\Services\RhEntrevistaComunicacaoWorkerService;

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Este script só pode rodar em CLI.\n");
    exit(1);
}

$limit = 20;
$send = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, min(100, (int) substr($arg, 8)));
    }
    if ($arg === '--send') {
        $send = true;
    }
}

if ($send && !RhEntrevistaComunicacaoWorkerService::isSendEnabled()) {
    fwrite(
        STDERR,
        "Envio bloqueado: ative \"Envio automático de comunicações de entrevista\" "
        . "na tela Configuração de E-mail e use --send.\n"
    );
    exit(2);
}

try {
    $report = (new RhEntrevistaComunicacaoWorkerService())->run($limit, $send);
} catch (Throwable $e) {
    fwrite(STDERR, 'Worker abortado: ' . $e->getMessage() . "\n");
    exit(3);
}

$mode = $report['dry_run'] ? 'DRY-RUN' : 'SEND';
$environment = $report['non_production'] ? 'NÃO PRODUÇÃO (destinatário de teste)' : 'PRODUÇÃO';
echo "=== Worker comunicações entrevista ({$mode}) ===\n";
echo "Ambiente: {$environment}\n";
echo 'Scanned: ' . $report['totals']['scanned']
    . ' | eligible: ' . $report['totals']['eligible']
    . ' | sent: ' . $report['totals']['sent']
    . ' | failed: ' . $report['totals']['failed']
    . ' | skipped: ' . $report['totals']['skipped']
    . ' | uncertain: ' . $report['totals']['uncertain']
    . "\n";

if ($report['config_errors'] !== []) {
    echo 'Configuração: ' . implode('; ', $report['config_errors']) . "\n";
}

foreach ($report['items'] as $item) {
    echo sprintf(
        "[%s] com#%d entrevista#%d — %s\n",
        strtoupper($item['decision']),
        $item['id'],
        $item['entrevista_id'],
        $item['detail']
    );
}

if ($report['dry_run']) {
    echo "Nenhum e-mail enviado ou status alterado. Para envio: ligue o interruptor na tela "
        . "Configuração de E-mail e use --send.\n";
}

exit($report['totals']['failed'] > 0 || $report['totals']['uncertain'] > 0 ? 4 : 0);
