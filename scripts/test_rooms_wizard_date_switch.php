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

$w = new ChatRoomsBookingWizard();
$_SESSION['internal_chat_rooms_wizard'] = [
    'step' => 'pick_slots',
    'user_id' => 1,
    'room_id' => 1,
    'room_name' => 'Sala Teste',
    'date' => '2026-08-09',
    'slot_map' => [
        1 => ['start' => '08:00', 'end' => '08:30'],
        2 => ['start' => '08:30', 'end' => '09:00'],
    ],
    'room_map' => [],
];

$r = $w->continue(1, '10/08/2026');
$one = preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) ?? '';
echo 'step=' . ($r['data']['ui']['step'] ?? '-') . PHP_EOL;
echo 'layout=' . ($r['data']['ui']['layout'] ?? '-') . PHP_EOL;
echo mb_substr($one, 0, 200) . PHP_EOL;
echo (str_contains((string) ($r['resposta'] ?? ''), '2026 inválido') ? "FAIL\n" : "OK\n");
