<?php

declare(strict_types=1);

/**
 * Backfill do ledger de gamificação a partir da timeline, com created_at = data real de cada ação.
 *
 * Uso:
 *   php scripts/gamification_backfill_timeline_ledger.php --dry-run
 *   php scripts/gamification_backfill_timeline_ledger.php
 *   php scripts/gamification_backfill_timeline_ledger.php --since=2024-01-01
 *   php scripts/gamification_backfill_timeline_ledger.php --include-inactive-rules
 *
 * Opções:
 *   --dry-run                  Simula contagens sem INSERT.
 *   --since=YYYY-MM-DD        Só eventos com created_at >= (início do dia).
 *   --include-inactive-rules  Usa também regras com is_active=0 (pontos da linha na BD).
 *   --mission-snapshots       Após o ledger (e se não for --dry-run), preenche snapshots NULL nas missões mensais.
 *   --mission-snapshots-only  Só executa o preenchimento de snapshots (copia meta/recompensa da definição actual).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require APP_ROOT . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(APP_ROOT);
$dotenv->load();
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

use App\adms\Models\Repository\GamificationProgramRepository;
use App\adms\Models\Services\GamificationTimelineLedgerBackfillService;

$dry = in_array('--dry-run', $argv, true);
$includeInactive = in_array('--include-inactive-rules', $argv, true);
$snapshotsOnly = in_array('--mission-snapshots-only', $argv, true);
$snapshotsAlso = in_array('--mission-snapshots', $argv, true);
$since = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--since=')) {
        $since = substr($arg, 8);
        break;
    }
}

if ($snapshotsOnly) {
    $n = (new GamificationProgramRepository())->backfillNullMissionSnapshots();
    fwrite(STDOUT, json_encode(['mission_snapshots_rows_updated' => $n], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
    exit(0);
}

$svc = new GamificationTimelineLedgerBackfillService();
$stats = $svc->run($dry, $includeInactive, $since);

if ($snapshotsAlso && !$dry) {
    $stats['mission_snapshots_rows_updated'] = (new GamificationProgramRepository())->backfillNullMissionSnapshots();
}

fwrite(STDOUT, json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL);
