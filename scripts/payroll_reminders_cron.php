<?php

declare(strict_types=1);

/**
 * Execução forçada dos lembretes de ciência (folha RH). Ignora o intervalo de 24 h.
 * O uso normal é automático no login/dashboard (PayrollDocumentRemindersService).
 *
 * Ex.: php scripts/payroll_reminders_cron.php
 *
 * HTTP (opcional): token em RH → payroll-cron-config → payroll-reminders-cron?token=...
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Services\PayrollDocumentRemindersService;

PayrollDocumentRemindersService::ensureUpdated(true);
$cacheFile = APP_ROOT . '/storage/cache/system/payroll_reminders_last_run.json';
$sent = 0;
if (is_readable($cacheFile)) {
    $j = json_decode((string)file_get_contents($cacheFile), true);
    if (is_array($j) && isset($j['reminders_sent'])) {
        $sent = (int) $j['reminders_sent'];
    }
}
fwrite(STDOUT, 'payroll_reminders_cron: force run, reminders_sent=' . $sent . PHP_EOL);
