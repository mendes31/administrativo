<?php

declare(strict_types=1);

/**
 * Verifica ficheiros críticos de produção no FTP e envia manifesto institutional-user se em falta.
 *
 * Uso (GitHub Actions após deploy rápido):
 *   FTP_SERVER=... FTP_USER=... FTP_PASS=... php scripts/deploy_ensure_gate_files.php
 */

require_once __DIR__ . '/deploy_changed_files_lib.php';

$root = dirname(__DIR__);
$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$port = (int)(getenv('FTP_PORT') ?: 21);
$maxRetries = max(1, (int)(getenv('DEPLOY_FTP_RETRIES') ?: 4));
$manifestName = trim((string)(getenv('DEPLOY_FEATURE_MANIFEST') ?: ''));

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "❌ Defina FTP_SERVER, FTP_USER e FTP_PASS.\n");
    exit(1);
}

if ($manifestName === 'institutional-user') {
    echo "Manifesto institutional-user já aplicado neste run — skip reparo.\n";
    exit(0);
}

echo "=== Verificação gate produção — " . date('Y-m-d H:i:s') . " ===\n";

$gatePaths = deployProductionGatePaths();
$needsRepair = false;

foreach ($gatePaths as $rel) {
    if (deployRemoteMatchesLocal($server, $port, $user, $pass, $root, $rel, $maxRetries)) {
        echo "  ✓ {$rel}\n";
        continue;
    }

    echo "  ✗ em falta ou desatualizado: {$rel}\n";
    $needsRepair = true;
}

if (!$needsRepair) {
    echo "✅ Gate produção OK — sem reparo.\n";
    exit(0);
}

putenv('DEPLOY_FEATURE_MANIFEST=institutional-user');
$files = deployResolveFilesToUpload($root, '', 'HEAD');

echo "\n=== Reparo: manifesto institutional-user (" . count($files) . " ficheiros) ===\n";

$errors = deployUploadFilesList($server, $port, $user, $pass, $root, $files, $maxRetries);

echo "\nEnviados: " . (count($files) - count($errors)) . '/' . count($files) . "\n";

if ($errors !== []) {
    fwrite(STDERR, '❌ Falha ao reparar ' . count($errors) . " ficheiro(s).\n");
    exit(1);
}

echo "\n=== Revalidação gate ===\n";
foreach ($gatePaths as $rel) {
    if (!deployRemoteMatchesLocal($server, $port, $user, $pass, $root, $rel, $maxRetries)) {
        fwrite(STDERR, "❌ Gate ainda inválido após reparo: {$rel}\n");
        exit(1);
    }
    echo "  ✓ {$rel}\n";
}

echo "✅ Reparo institutional-user concluído.\n";
exit(0);
