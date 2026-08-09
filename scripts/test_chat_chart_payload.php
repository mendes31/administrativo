<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$r = (new LocalInternalChatAgent())->handle('headcount por departamento', ['user_id' => 0]);
$chart = $r['data']['chart'] ?? null;
if (!is_array($chart)) {
    echo "NO_CHART\n";
    exit(1);
}
echo 'chart=' . ($chart['type'] ?? '?')
    . ' labels=' . count($chart['labels'] ?? [])
    . ' values=' . count($chart['values'] ?? [])
    . "\n";
