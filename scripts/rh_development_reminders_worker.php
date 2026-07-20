<?php

declare(strict_types=1);

/**
 * Digest de lembretes de desenvolvimento (PDI atrasado, avaliações draft).
 *
 * Uso:
 *   php scripts/rh_development_reminders_worker.php           # dry-run
 *   php scripts/rh_development_reminders_worker.php --send    # envia se toggle ligado
 *   php scripts/rh_development_reminders_worker.php --send --limit=50
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\RhDevelopmentRemindersService;

$send = in_array('--send', $argv ?? [], true);
$limit = 100;
foreach ($argv ?? [] as $arg) {
    if (str_starts_with($arg, '--limit=')) {
        $limit = max(1, (int) substr($arg, 8));
    }
}

$result = (new RhDevelopmentRemindersService())->run($send, $limit);
$summary = $result['summary'] ?? [];

fwrite(STDOUT, 'rh_development_reminders: mode=' . ($result['dry_run'] ? 'dry-run' : 'send')
    . ' em ' . date('Y-m-d H:i:s') . PHP_EOL);
fwrite(STDOUT, sprintf(
    "  pulse_open=%d overdue_pdi_actions=%d draft_reviews=%d recipients=%d\n",
    (int) ($summary['open_pulse_campaigns'] ?? 0),
    (int) ($summary['overdue_pdi_actions'] ?? 0),
    (int) ($summary['draft_reviews_open_cycle'] ?? 0),
    count($summary['recipients'] ?? [])
));

if (!empty($result['disabled'])) {
    fwrite(STDOUT, "  envio desabilitado (ative rh_dev_reminders_email / rh_dev_reminders_inapp nas Configurações de Notificações)\n");
    exit(0);
}

if (!$result['dry_run']) {
    fwrite(STDOUT, sprintf(
        "  enviados=%d ignorados=%d falhas=%d\n",
        (int) $result['sent'],
        (int) $result['skipped'],
        (int) $result['failed']
    ));
}

if (!empty($result['errors'])) {
    foreach ($result['errors'] as $err) {
        fwrite(STDERR, $err . PHP_EOL);
    }
}

exit(((int) ($result['failed'] ?? 0)) > 0 ? 1 : 0);
