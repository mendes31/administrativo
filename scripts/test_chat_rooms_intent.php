<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$userId = (int) ($_SESSION['user_id'] ?? ($argv[1] ?? 1));
$_SESSION['user_id'] = $userId;

$agent = new LocalInternalChatAgent();
$queue = [
    'salas',
    'agenda da sala hoje',
    'agenda da sala Reuniões Grande hoje',
    'minhas reservas',
    'cancelar reserva #0',
];

foreach ($queue as $q) {
    $r = $agent->handle($q, ['user_id' => $userId]);
    $one = preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) ?? '';
    echo "Q: {$q}\n";
    echo '  tool=' . ($r['tool'] ?? '—') . "\n";
    echo '  ' . mb_substr($one, 0, 180) . "\n\n";
}
