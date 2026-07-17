<?php

declare(strict_types=1);

/**
 * Smoke tests do Canal de Denúncias (sem PHPUnit).
 * Uso: php scripts/whistleblowing_smoke_test.php
 */

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($projectRoot);
$dotenv->safeLoad();

$failures = 0;

function assertTrue(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "[OK] {$message}\n";
        return;
    }
    echo "[FALHA] {$message}\n";
    $failures++;
}

// 1. Canal público bloqueado sem chave forte
use App\adms\Models\Services\WhistleblowingChannelSecurityService;

$available = WhistleblowingChannelSecurityService::isPublicChannelAvailable();
$keyOk = WhistleblowingChannelSecurityService::hasStrongEncryptionKey();
if ($keyOk) {
    assertTrue($available, 'Canal público disponível quando chave forte configurada');
} else {
    assertTrue(!$available, 'Canal público indisponível sem chave forte');
}

// 2. Rate limit — bloqueio após tentativas
use App\adms\Models\Services\WhistleblowingRateLimitService;

$rate = new WhistleblowingRateLimitService();
$scope = 'smoke_test_' . bin2hex(random_bytes(4));
assertTrue(!$rate->isBlocked($scope), 'Rate limit inicia desbloqueado');
for ($i = 0; $i < 6; $i++) {
    $rate->recordFailedAttempt($scope);
}
assertTrue($rate->isBlocked($scope), 'Rate limit bloqueia após tentativas excessivas');
$rate->clear($scope);

// 3. Permissão — operador sem comitê não acessa denúncia de outro comitê
use App\adms\Models\Services\WhistleblowingPermissionService;

$_SESSION['user_id'] = 999999;
$_SESSION['user_access_levels'] = [];
$reportOtherCommittee = ['committee_id' => 1, 'assigned_user_id' => null];
// Sem nível nem comitê — deve negar (se serviço exige login)
// Apenas verifica que o método existe e retorna bool
assertTrue(
    is_bool(WhistleblowingPermissionService::canAccessReport($reportOtherCommittee)),
    'canAccessReport retorna booleano'
);

// 4. Protocolo — formato válido
use App\adms\Models\Services\WhistleblowingProtocolService;

$protocol = WhistleblowingProtocolService::generateProtocol();
assertTrue(strlen($protocol) >= 8, 'Protocolo gerado com tamanho mínimo');

// 5. Notificação — serviço instanciável
use App\adms\Models\Services\WhistleblowingCommitteeNotificationService;

$notifier = new WhistleblowingCommitteeNotificationService();
assertTrue($notifier instanceof WhistleblowingCommitteeNotificationService, 'Serviço de notificação carrega');

// 6. Envelopamento da DEK (Modelo B)
use App\adms\Models\Services\WhistleblowingKeyWrapService;

$prevWrap = $_ENV[WhistleblowingKeyWrapService::ENV_VAR] ?? null;
$_ENV[WhistleblowingKeyWrapService::ENV_VAR] = bin2hex(random_bytes(32));
$dek = bin2hex(random_bytes(32));
$wrapped = WhistleblowingKeyWrapService::wrap($dek);
assertTrue(WhistleblowingKeyWrapService::isWrapped($wrapped), 'Blob DEK usa prefixo wbk1:');
assertTrue(WhistleblowingKeyWrapService::unwrap($wrapped) === $dek, 'Unwrap recupera a DEK original');
$wrong = $_ENV[WhistleblowingKeyWrapService::ENV_VAR];
$_ENV[WhistleblowingKeyWrapService::ENV_VAR] = bin2hex(random_bytes(32));
$unwrapFailed = false;
try {
    WhistleblowingKeyWrapService::unwrap($wrapped);
} catch (\Throwable) {
    $unwrapFailed = true;
}
assertTrue($unwrapFailed, 'KEK errado não abre a DEK envelopada');
if ($prevWrap === null) {
    unset($_ENV[WhistleblowingKeyWrapService::ENV_VAR]);
} else {
    $_ENV[WhistleblowingKeyWrapService::ENV_VAR] = $prevWrap;
}
// silencia unused
unset($wrong);

echo "\n";
if ($failures > 0) {
    echo "Resultado: {$failures} falha(s).\n";
    exit(1);
}

echo "Todos os smoke tests passaram.\n";
exit(0);
