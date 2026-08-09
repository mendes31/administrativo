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
foreach (['desligados 2025', 'desligados em 2025', 'inativos 2025', 'inativos por mês 2025', '2025'] as $q) {
    $r = $agent->handle($q);
    $one = preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) ?? '';
    echo "Q: {$q}\n";
    echo '  tool=' . ($r['tool'] ?? '—') . "\n";
    echo '  ' . mb_substr($one, 0, 160) . "\n\n";
}
