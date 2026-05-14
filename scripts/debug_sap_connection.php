<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;
use App\adms\Models\Services\SapGatewayHttpClient;

echo "\n🔍 DEBUG — API SAP (gateway)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$repo = new AdmsSapServiceLayerConnectionRepository();
$row = $repo->getDefaultOrFirstActive();
if (!$row) {
    echo "❌ Nenhuma conexão activa.\n\n";
    exit(1);
}

$cid = (int) ($row['id'] ?? 0);
echo "Conexão #{$cid}: " . ($row['name'] ?? '') . "\n";
echo "  URL: " . ($row['base_url'] ?? '') . "\n";
echo "  Health: " . ($row['health_path'] ?? '/health') . "\n\n";

$r = SapGatewayHttpClient::ping($cid);
echo ($r['success'] ? '✅ ' : '❌ ') . $r['message'] . "\n";
echo "\n═══════════════════════════════════════════════════════════════\n";
