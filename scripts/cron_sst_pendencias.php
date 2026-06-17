<?php

declare(strict_types=1);

/**
 * Cron: alertas de pendências SST (e-mail + notificação in-app).
 *
 * Ex.: php scripts/cron_sst_pendencias.php
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\SstNotificationService;

$results = (new SstNotificationService())->sendPendenciasDigest();

if (!empty($results['disabled'])) {
    fwrite(STDOUT, 'cron_sst_pendencias: desabilitado nas Configurações de Notificações em ' . date('Y-m-d H:i:s') . PHP_EOL);
    exit(0);
}

fwrite(STDOUT, sprintf(
    "cron_sst_pendencias: enviados=%d ignorados=%d falhas=%d em %s\n",
    $results['sent'],
    $results['skipped'],
    $results['failed'],
    date('Y-m-d H:i:s')
));

if (!empty($results['errors'])) {
    foreach ($results['errors'] as $err) {
        fwrite(STDERR, $err . PHP_EOL);
    }
}

exit($results['failed'] > 0 ? 1 : 0);
