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
$questions = $argv[1] ?? null;
$queue = $questions !== null
    ? (str_contains($questions, '|') ? explode('|', $questions) : [$questions])
    : ['desligados 2025', 'inativos', 'por mes', 'financeiro', 'ativos', 'financeiro', 'ativos na TI'];

foreach ($queue as $q) {
    $r = $agent->handle($q);
    $oneLine = preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) ?? '';
    echo "Q: {$q}\n";
    echo '  tool=' . ($r['tool'] ?? '—') . ' provider=' . ($r['provider'] ?? '?') . "\n";
    echo "  {$oneLine}\n\n";
}
