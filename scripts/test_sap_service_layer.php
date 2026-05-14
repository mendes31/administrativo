<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__ . '/..');
$dotenv->load();

use App\adms\Models\Repository\AdmsSapServiceLayerConnectionRepository;
use App\adms\Models\Services\SapGatewayHttpClient;

echo "\n🧪 TESTE — API SAP (gateway)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$repo = new AdmsSapServiceLayerConnectionRepository();
$row = $repo->getDefaultOrFirstActive();
if (!$row) {
    echo "❌ Nenhuma conexão activa em adms_sap_service_layer_connections.\n";
    echo "  Cadastre em: Administração → Configurações → API SAP (integração)\n\n";
    exit(1);
}

$connId = (int) ($row['id'] ?? 0);
echo "Conexão: #{$connId} — " . ($row['name'] ?? '') . "\n\n";

echo "📊 GET health (URL base + health_path)\n";
echo "───────────────────────────────────────────────────────────────\n";
$r = SapGatewayHttpClient::ping($connId);
echo ($r['success'] ? '✅ ' : '❌ ') . $r['message'] . "\n\n";

exit($r['success'] ? 0 : 1);
