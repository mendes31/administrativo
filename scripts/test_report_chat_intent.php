<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$agent = new LocalInternalChatAgent();
foreach (['quais relatorios no chat?', 'quais relatórios no chat?', 'listar relatórios'] as $q) {
    $r = $agent->handle($q, ['user_id' => 0]);
    echo $q . ' => ' . ($r['provider'] ?? '?') . ' / ' . ($r['tool'] ?? '?') . "\n";
}
