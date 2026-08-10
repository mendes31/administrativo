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

unset(
    $_SESSION['internal_chat_person_candidates'],
    $_SESSION['internal_chat_last_terminated'],
    $_SESSION['internal_chat_last_status']
);

$agent->handle('rafael está bloqueado?', ['user_id' => 1]);
$rStatus = $agent->handle('status do Rafael Mendes', ['user_id' => 1]);
echo "status do Rafael Mendes\n";
echo "tool=" . ($rStatus['tool'] ?? '') . "\n";
echo explode("\n", (string) $rStatus['resposta'])[0] . "\n\n";

$agent->handle('rafael está bloqueado?', ['user_id' => 1]);
$rBad = $agent->handle('status de rafael', ['user_id' => 1]);
echo "status de rafael (nova busca)\n";
echo "tool=" . ($rBad['tool'] ?? '') . "\n";
$hasNobody = str_contains((string) $rBad['resposta'], 'Ninguém na lista anterior');
echo $hasNobody ? "FAIL still stuck in refine\n\n" : "OK not stuck\n\n";

$agent->handle('rafael está bloqueado?', ['user_id' => 1]);
$rTi = $agent->handle('TI', ['user_id' => 1]);
echo "TI refine tool={$rTi['tool']}\n";
echo explode("\n", (string) $rTi['resposta'])[0] . "\n\n";

$rClear = $agent->handle('limpar', ['user_id' => 1]);
echo "limpar tool=" . ($rClear['tool'] ?? '') . "\n";
echo (string) $rClear['resposta'] . "\n";
