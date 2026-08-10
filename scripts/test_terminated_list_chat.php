<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$agent = new LocalInternalChatAgent();

$r1 = $agent->handle('lista de desligados', ['user_id' => 1]);
echo "T1 tool={$r1['tool']} rows=" . count($r1['data']['rows'] ?? []) . " total=" . ($r1['data']['total'] ?? '?') . "\n";
echo substr((string) $r1['resposta'], 0, 180) . "\n\n";

$r2 = $agent->handle('usuários desligados', ['user_id' => 1]);
echo "T2 tool={$r2['tool']}\n";
$r3 = $agent->handle('lista', ['user_id' => 1]);
echo "T3 tool={$r3['tool']} rows=" . count($r3['data']['rows'] ?? []) . "\n";

$r4 = $agent->handle('lista desligados Produção', ['user_id' => 1]);
echo "T4 tool={$r4['tool']} dept=" . ($r4['data']['department'] ?? '?') . " total=" . ($r4['data']['total'] ?? '?') . "\n";
