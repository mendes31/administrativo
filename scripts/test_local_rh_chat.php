<?php
/**
 * Teste CLI do chat RH local (sem SAP, sem API paga).
 *
 * Uso:
 *   php scripts/test_local_rh_chat.php
 *   php scripts/test_local_rh_chat.php "quantos bloqueados?"
 *   php scripts/test_local_rh_chat.php "ativos em Produção"
 */
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$question = $argv[1] ?? 'quantos colaboradores ativos?';
$agent = new LocalInternalChatAgent();
$result = $agent->handle($question, ['user_id' => 0]);

echo "Pergunta: {$question}\n";
echo "Provider: " . ($result['provider'] ?? '?') . "\n";
echo "Tool: " . ($result['tool'] ?? '—') . "\n";
echo "Resposta:\n" . ($result['resposta'] ?? '') . "\n";
if (!empty($result['data'])) {
    echo "Dados: " . json_encode($result['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
}
