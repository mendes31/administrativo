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
$agent->handle('status do Rafael', ['user_id' => 1]);
$rTi = $agent->handle('TI', ['user_id' => 1]);
echo "TI tool={$rTi['tool']}\n";
echo explode("\n", (string) $rTi['resposta'])[0] . "\n\n";

$rCom = $agent->handle('Comercial', ['user_id' => 1]);
echo "Comercial tool={$rCom['tool']}\n";
echo (string) $rCom['resposta'] . "\n";
