<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = 1;

use App\adms\Models\Services\InternalChat\ChatRoomsService;

$svc = new ChatRoomsService();
$day = '2026-08-10';
$slots = $svc->listDayHalfHourSlots(1, $day);
$free = 0;
$busy = 0;
$past = 0;
$sampleBusy = null;
foreach ($slots as $s) {
    $st = $s['status'] ?? '';
    if ($st === 'free') {
        $free++;
    } elseif ($st === 'busy') {
        $busy++;
        $sampleBusy ??= $s;
    } else {
        $past++;
    }
}
echo "total=" . count($slots) . " free={$free} busy={$busy} past={$past}\n";
if ($sampleBusy) {
    echo 'busy_sample=' . ($sampleBusy['start'] ?? '') . ' by=' . ($sampleBusy['reserved_by'] ?? '')
        . ' dept=' . ($sampleBusy['reserved_department'] ?? '-') . "\n";
}
echo count($slots) >= 20 ? "OK day grid\n" : "WARN unexpected count\n";
