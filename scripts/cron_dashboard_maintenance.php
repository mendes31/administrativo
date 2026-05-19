<?php

declare(strict_types=1);

/**
 * Execução forçada das tarefas de manutenção (mesma lógica do dashboard/login).
 * Opcional no cron do servidor — não substitui o fluxo normal em Dashboard::index().
 *
 * Ex.: php scripts/cron_dashboard_maintenance.php
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\CandidateRetentionService;
use App\adms\Models\Services\PayrollDocumentRemindersService;

CandidateRetentionService::ensureUpdated(true);
PayrollDocumentRemindersService::ensureUpdated(true);
fwrite(STDOUT, 'cron_dashboard_maintenance: concluído em ' . date('Y-m-d H:i:s') . PHP_EOL);
