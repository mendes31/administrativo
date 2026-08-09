<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/adms/Helpers/EnvLoader.php';
\App\adms\Helpers\EnvLoader::load();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = (int) ($argv[1] ?? 1);

use App\adms\Models\Services\InternalChat\LocalInternalChatAgent;

$agent = new LocalInternalChatAgent();
$uid = (int) $_SESSION['user_id'];
foreach (['agendar', '1', 'amanhã'] as $q) {
    $r = $agent->handle($q, ['user_id' => $uid]);
    $step = $r['data']['ui']['step'] ?? '-';
    $opts = isset($r['data']['ui']['options']) ? count($r['data']['ui']['options']) : 0;
    $hasImg = false;
    foreach (($r['data']['ui']['options'] ?? []) as $o) {
        if (!empty($o['image_url'])) {
            $hasImg = true;
            break;
        }
    }
    $one = preg_replace('/\s+/u', ' ', (string) ($r['resposta'] ?? '')) ?? '';
    echo "Q: {$q}\n";
    echo '  tool=' . ($r['tool'] ?? '—') . " step={$step} options={$opts} img=" . ($hasImg ? 'yes' : 'no') . "\n";
    echo '  ' . mb_substr($one, 0, 140) . "\n\n";
}
