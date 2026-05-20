<?php

declare(strict_types=1);

/**
 * Remove inscrições Web Push inválidas (HTTP 410/404) sem exibir notificação ao usuário.
 *
 * Ex.: php scripts/cron_push_subscriptions_cleanup.php
 * Cron sugerido (1x/dia, madrugada): 0 3 * * * cd /caminho/administrativo && php scripts/cron_push_subscriptions_cleanup.php
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\PushNotificationService;

$batch = 200;
if (isset($argv[1]) && is_numeric($argv[1])) {
    $batch = max(1, min(500, (int) $argv[1]));
}

$service = new PushNotificationService();
$totalChecked = 0;
$totalRemoved = 0;
$totalFailed = 0;
$errors = [];
$maxRounds = 10;

for ($round = 0; $round < $maxRounds; $round++) {
    $summary = $service->pruneExpiredSubscriptions($batch);
    $checked = (int) ($summary['checked'] ?? 0);
    $removed = (int) ($summary['removed'] ?? 0);
    $totalChecked += $checked;
    $totalRemoved += $removed;
    $totalFailed += (int) ($summary['failed'] ?? 0);
    if (!empty($summary['errors'])) {
        $errors = array_merge($errors, $summary['errors']);
    }
    if ($checked === 0) {
        break;
    }
    // Nada removido: o mesmo lote já foi verificado — não repetir 10x.
    if ($removed === 0) {
        break;
    }
    // Lote incompleto: não há mais linhas para verificar.
    if ($checked < $batch) {
        break;
    }
}

$line = sprintf(
    'cron_push_subscriptions_cleanup: checked=%d removed=%d failed=%d rounds<=%d at %s',
    $totalChecked,
    $totalRemoved,
    $totalFailed,
    $maxRounds,
    date('Y-m-d H:i:s')
);
fwrite(STDOUT, $line . PHP_EOL);

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_unique($errors)) . PHP_EOL);
}

exit($errors !== [] && $totalChecked === 0 ? 1 : 0);
