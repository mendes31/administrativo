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
$agent->handle('lista de desligados', ['user_id' => 1]);
$r = $agent->handle('2026', ['user_id' => 1]);
echo "tool={$r['tool']}\n";
echo "year=" . var_export($r['data']['year'] ?? null, true) . "\n";
echo "total=" . ($r['data']['total'] ?? '?') . "\n";
echo "label_line=" . explode("\n", (string) $r['resposta'])[0] . "\n";
echo "has_bullets=" . (str_contains((string) $r['resposta'], '•') ? 'yes' : 'no') . "\n";
echo "cols=" . implode(',', array_keys($r['data']['rows'][0] ?? [])) . "\n";

$r2 = $agent->handle('lista de desligados 2026', ['user_id' => 1]);
echo "direct tool={$r2['tool']} year=" . var_export($r2['data']['year'] ?? null, true) . " total=" . ($r2['data']['total'] ?? '') . "\n";
