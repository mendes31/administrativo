<?php

declare(strict_types=1);

/**
 * Compara ficheiros críticos no servidor FTP com o checkout Git (SHA-256).
 *
 * Uso (GitHub Actions ou local):
 *   FTP_SERVER=host FTP_USER=user FTP_PASS=pass php scripts/verify_ftp_deploy_hashes.php
 *
 * Variáveis opcionais:
 *   FTP_REMOTE_BASE=administrativo  (pasta dentro da raiz FTP)
 *   FTP_PORT=21
 */

require __DIR__ . '/deploy_critical_manifest.php';
require __DIR__ . '/deploy_config.php';

$root = dirname(__DIR__);
$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$remoteBase = deployFtpRemoteBase();
$port = (int)(getenv('FTP_PORT') ?: 21);

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "Defina FTP_SERVER (ou FTP_HOST), FTP_USER e FTP_PASS.\n");
    exit(2);
}

$conn = @ftp_connect($server, $port, 120);
if ($conn === false) {
    fwrite(STDERR, "Falha ao conectar em {$server}:{$port}\n");
    exit(2);
}

if (!@ftp_login($conn, $user, $pass)) {
    fwrite(STDERR, "Falha na autenticação FTP.\n");
    ftp_close($conn);
    exit(2);
}

@ftp_pasv($conn, true);
@ftp_set_option($conn, FTP_TIMEOUT_SEC, 120);

$errors = [];
$checked = 0;

foreach (deployCriticalManifest() as $entry) {
    $rel = $entry['path'];
    $localPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);

    if (!is_file($localPath)) {
        $errors[] = "Local ausente (Git): {$rel}";
        continue;
    }

    $localHash = hash_file('sha256', $localPath);
    if ($localHash === false) {
        $errors[] = "Não foi possível ler hash local: {$rel}";
        continue;
    }

    $remotePath = deployFtpRemotePath($rel);
    $tmp = tempnam(sys_get_temp_dir(), 'ftpv_');
    if ($tmp === false) {
        $errors[] = "Falha ao criar ficheiro temporário para {$rel}";
        continue;
    }

    $downloaded = @ftp_get($conn, $tmp, $remotePath, FTP_BINARY);
    if (!$downloaded) {
        @unlink($tmp);
        $errors[] = "Remoto ausente ou inacessível: {$remotePath}";
        continue;
    }

    $remoteHash = hash_file('sha256', $tmp);
    $remoteContent = (string)file_get_contents($tmp);
    @unlink($tmp);
    $checked++;

    if ($remoteHash !== $localHash) {
        $errors[] = "Hash diverge (servidor ≠ Git): {$rel}";
        continue;
    }

    foreach ($entry['must_contain'] ?? [] as $needle) {
        if (!str_contains($remoteContent, $needle)) {
            $errors[] = "Conteúdo remoto sem marcador \"{$needle}\": {$rel}";
        }
    }
}

ftp_close($conn);

echo '=== Verificação FTP pós-deploy — ' . date('Y-m-d H:i:s') . " ===\n";
echo "Servidor: {$server}, base remota: " . ($remoteBase === '' ? '(raiz da sessão FTP)' : $remoteBase . '/') . "\n";
echo "Ficheiros verificados: {$checked}\n\n";

if ($errors !== []) {
    echo "FALHA — " . count($errors) . " problema(s):\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\nO deploy FTP marcou sucesso mas o servidor não reflete o Git.\n";
    echo "Corrija com: workflow_dispatch + force_full_resync, ou scp manual + php scripts/generate_ftp_deploy_state.php no servidor.\n";
    exit(1);
}

echo "OK: ficheiros críticos no servidor coincidem com o Git.\n";
exit(0);
