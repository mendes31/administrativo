<?php

declare(strict_types=1);

/**
 * Compara ficheiros no servidor FTP com o checkout Git (SHA-256).
 *
 * Modos (DEPLOY_VERIFY_MODE):
 *   changed — só ficheiros alterados no push (deploy rápido / lftp push)
 *   full    — manifesto completo deployCriticalManifest() (deploy incremental)
 *
 * Uso:
 *   FTP_SERVER=host FTP_USER=user FTP_PASS=pass php scripts/verify_ftp_deploy_hashes.php
 *
 * Variáveis opcionais:
 *   GIT_BEFORE, GIT_AFTER — intervalo Git (modo changed)
 *   FTP_REMOTE_BASE, FTP_PORT, DEPLOY_FTP_RETRIES
 */

require __DIR__ . '/deploy_critical_manifest.php';
require __DIR__ . '/deploy_config.php';
require __DIR__ . '/deploy_changed_files_lib.php';

$root = dirname(__DIR__);
$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$remoteBase = deployFtpRemoteBase();
$port = (int)(getenv('FTP_PORT') ?: 21);
$maxRetries = max(1, (int)(getenv('DEPLOY_FTP_RETRIES') ?: 4));
$mode = strtolower(trim((string)(getenv('DEPLOY_VERIFY_MODE') ?: 'changed')));
$gitBefore = trim((string)(getenv('GIT_BEFORE') ?: ''));
$gitAfter = trim((string)(getenv('GIT_AFTER') ?: 'HEAD'));

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "Defina FTP_SERVER (ou FTP_HOST), FTP_USER e FTP_PASS.\n");
    exit(2);
}

if ($mode !== 'full' && $mode !== 'changed') {
    fwrite(STDERR, "DEPLOY_VERIFY_MODE inválido: {$mode} (use changed ou full).\n");
    exit(2);
}

/**
 * @return list<array{path: string, must_contain?: list<string>}>
 */
function deployVerifyEntries(string $root, string $mode, string $gitBefore, string $gitAfter): array
{
    if ($mode === 'full') {
        return deployCriticalManifest();
    }

    $changed = deployCollectChangedFiles($root, $gitBefore, $gitAfter);
    if ($changed === []) {
        return [];
    }

    $manifestByPath = [];
    foreach (deployCriticalManifest() as $entry) {
        $manifestByPath[$entry['path']] = $entry;
    }

    $entries = [];
    foreach ($changed as $path) {
        $entries[] = $manifestByPath[$path] ?? ['path' => $path];
    }

    return $entries;
}

$entries = deployVerifyEntries($root, $mode, $gitBefore, $gitAfter);
$errors = [];
$checked = 0;

echo '=== Verificação FTP pós-deploy — ' . date('Y-m-d H:i:s') . " ===\n";
echo "Modo: {$mode}\n";
echo "Servidor: {$server}, base remota: " . ($remoteBase === '' ? '(raiz da sessão FTP)' : $remoteBase . '/') . "\n";
echo 'Ficheiros a verificar: ' . count($entries) . "\n\n";

if ($entries === []) {
    echo "OK: nenhum ficheiro deployável a verificar.\n";
    exit(0);
}

foreach ($entries as $entry) {
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

    $tmp = deployFtpDownloadFile($server, $port, $user, $pass, $rel, $maxRetries);
    if ($tmp === null) {
        $errors[] = 'Remoto ausente ou inacessível: ' . deployFtpRemotePath($rel);
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

echo "Ficheiros verificados com sucesso: {$checked}\n\n";

if ($errors !== []) {
    echo 'FALHA — ' . count($errors) . " problema(s):\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\nO deploy FTP marcou sucesso mas o servidor não reflete o Git.\n";
    if ($mode === 'changed') {
        echo "Reexecute com workflow_dispatch e git_before/git_after do intervalo em falta.\n";
    }
    exit(1);
}

echo "OK: ficheiros verificados coincidem com o Git.\n";
exit(0);
