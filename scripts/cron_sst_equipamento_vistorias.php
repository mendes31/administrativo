<?php

declare(strict_types=1);

/**
 * Cron: gera vistorias de equipamentos de segurança (dia 01) e marca vencidas.
 *
 * Ex.: php scripts/cron_sst_equipamento_vistorias.php
 * Ex. simular data: php scripts/cron_sst_equipamento_vistorias.php 2026-07-01
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\SstEquipamentoVistoriaGeneratorService;

$refDate = null;
if (!empty($argv[1])) {
    $refDate = DateTimeImmutable::createFromFormat('Y-m-d', $argv[1]) ?: null;
}

$result = (new SstEquipamentoVistoriaGeneratorService())->run($refDate);

fwrite(STDOUT, sprintf(
    "cron_sst_equipamento_vistorias: competencia=%s criadas=%d ignoradas=%d vencidas_marcadas=%d em %s\n",
    $result['competencia'],
    $result['created'],
    $result['skipped'],
    $result['overdue_marked'],
    date('Y-m-d H:i:s')
));

foreach ($result['errors'] as $err) {
    fwrite(STDERR, $err . PHP_EOL);
}

exit(empty($result['errors']) ? 0 : 1);
