<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = 1;

use App\adms\Models\Services\InternalChat\ChatRoomsBookingWizard;
use App\adms\Models\Services\InternalChat\ChatRoomsService;

$svc = new ChatRoomsService();
$slots = $svc->listDayHalfHourSlots(1, '2026-08-09');
$ref = new ReflectionClass(ChatRoomsBookingWizard::class);
$m = $ref->getMethod('buildSlotMapAndOptions');
$m->setAccessible(true);
$built = $m->invoke(new ChatRoomsBookingWizard(), $slots);
foreach ($built['options'] as $o) {
    if (($o['status'] ?? '') === 'busy') {
        echo json_encode($o, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
}
