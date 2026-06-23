<?php

declare(strict_types=1);

require_once __DIR__ . '/deploy_excludes.php';
require_once __DIR__ . '/deploy_config.php';

/**
 * Hash SHA-256 normalizado (LF) — evita falso negativo CRLF no servidor.
 */
function deployContentHash(string $absolutePath): string|false
{
    $content = file_get_contents($absolutePath);
    if ($content === false) {
        return false;
    }

    $content = str_replace("\r\n", "\n", str_replace("\r", "\n", $content));

    return hash('sha256', $content);
}

/**
 * @return list<string> caminhos relativos com /
 */
function deployCollectChangedFiles(string $root, string $gitBefore, string $gitAfter): array
{
    $invalidBefore = $gitBefore === ''
        || preg_match('/^0+$/', $gitBefore)
        || strlen($gitBefore) < 7;

    if ($invalidBefore) {
        $cmd = 'git diff --name-only --diff-filter=ACMRT HEAD~1 HEAD 2>/dev/null';
    } else {
        $cmd = sprintf(
            'git diff --name-only --diff-filter=ACMRT %s %s',
            escapeshellarg($gitBefore),
            escapeshellarg($gitAfter)
        );
    }

    $output = [];
    exec($cmd, $output, $code);

    if ($code !== 0 || $output === []) {
        return [];
    }

    $files = [];
    foreach ($output as $line) {
        $line = trim(str_replace('\\', '/', $line));
        if ($line === '' || deployPathExcluded($line)) {
            continue;
        }
        $local = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $line);
        if (!is_file($local)) {
            continue;
        }
        $files[] = $line;
    }

    return array_values(array_unique($files));
}

/**
 * Lista final de ficheiros a enviar/verificar neste deploy.
 *
 * Prioridade:
 *   1) DEPLOY_FEATURE_MANIFEST (ex. institutional-user)
 *   2) git diff GIT_BEFORE..GIT_AFTER (exclui .github, vendor, etc.)
 *
 * @return list<string>
 */
function deployResolveFilesToUpload(string $root, string $gitBefore, string $gitAfter): array
{
    $manifestName = trim((string)(getenv('DEPLOY_FEATURE_MANIFEST') ?: ''));
    if ($manifestName !== '') {
        require_once __DIR__ . '/deploy_feature_manifests.php';
        $listed = deployFeatureManifestFiles($manifestName);
        if ($listed === null) {
            fwrite(STDERR, "❌ Manifesto desconhecido: {$manifestName}\n");
            exit(1);
        }

        $files = [];
        foreach ($listed as $rel) {
            $rel = trim(str_replace('\\', '/', $rel));
            if ($rel === '' || deployPathExcluded($rel)) {
                continue;
            }
            $local = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
            if (!is_file($local)) {
                fwrite(STDERR, "❌ Ficheiro do manifesto ausente no checkout: {$rel}\n");
                exit(1);
            }
            $files[] = $rel;
        }

        return array_values(array_unique($files));
    }

    return deployCollectChangedFiles($root, $gitBefore, $gitAfter);
}

/**
 * @param list<string> $paths
 */
function deployIsScriptsOnlyPaths(array $paths): bool
{
    if ($paths === []) {
        return false;
    }

    foreach ($paths as $path) {
        if (!str_starts_with(str_replace('\\', '/', $path), 'scripts/')) {
            return false;
        }
    }

    return true;
}

/**
 * Ficheiros mínimos que indicam se o manifesto institutional-user está em produção.
 *
 * @return list<string>
 */
function deployProductionGatePaths(): array
{
    return [
        'app/adms/Helpers/InstitutionalSystemUserHelper.php',
        'app/adms/Models/Repository/EmployeePayrollDocumentsRepository.php',
        'app/adms/Controllers/dashboard/Dashboard.php',
    ];
}

function deployRemoteMatchesLocal(
    string $server,
    int $port,
    string $user,
    string $pass,
    string $root,
    string $rel,
    int $maxAttempts = 4
): bool {
    $localPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    if (!is_file($localPath)) {
        return false;
    }

    $localHash = deployContentHash($localPath);
    if ($localHash === false) {
        return false;
    }

    $tmp = deployFtpDownloadFile($server, $port, $user, $pass, $rel, $maxAttempts);
    if ($tmp === null) {
        return false;
    }

    $remoteHash = deployContentHash($tmp);
    @unlink($tmp);

    return $remoteHash === $localHash;
}

/**
 * @param list<string> $files
 * @return list<string> ficheiros que falharam
 */
function deployUploadFilesList(
    string $server,
    int $port,
    string $user,
    string $pass,
    string $root,
    array $files,
    int $maxRetries = 4
): array {
    $errors = [];

    foreach ($files as $rel) {
        if (deployFtpUploadFile($server, $port, $user, $pass, $root, $rel, $maxRetries)) {
            echo "  ↑ {$rel}\n";
        } else {
            $errors[] = $rel;
            fwrite(STDERR, "  ✗ falha: {$rel}\n");
        }
    }

    if ($errors !== []) {
        echo "\n↻ Retry final para " . count($errors) . " ficheiro(s)...\n";
        $retryErrors = [];
        foreach ($errors as $rel) {
            if (deployFtpUploadFile($server, $port, $user, $pass, $root, $rel, $maxRetries)) {
                echo "  ↑ {$rel} (retry)\n";
            } else {
                $retryErrors[] = $rel;
                fwrite(STDERR, "  ✗ falha (retry): {$rel}\n");
            }
        }
        $errors = $retryErrors;
    }

    return $errors;
}

/**
 * @return \FTP\Connection|false
 */
function deployFtpConnect(string $server, int $port, string $user, string $pass)
{
    $conn = @ftp_connect($server, $port, 120);
    if ($conn === false) {
        return false;
    }

    if (!@ftp_login($conn, $user, $pass)) {
        ftp_close($conn);

        return false;
    }

    @ftp_pasv($conn, true);
    @ftp_set_option($conn, FTP_TIMEOUT_SEC, 180);

    return $conn;
}

/**
 * @param \FTP\Connection $conn
 */
function deployFtpEnsureDir($conn, string $remoteDir): void
{
    $remoteDir = str_replace('\\', '/', $remoteDir);
    $remoteDir = trim($remoteDir, '/');
    if ($remoteDir === '') {
        return;
    }

    $base = deployFtpRemoteBase();
    $parts = explode('/', $remoteDir);
    $acc = $base === '' ? '' : $base;

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $acc = $acc === '' ? $part : $acc . '/' . $part;
        @ftp_mkdir($conn, $acc);
    }
}

function deployFtpUploadFile(
    string $server,
    int $port,
    string $user,
    string $pass,
    string $root,
    string $rel,
    int $maxAttempts = 4
): bool {
    $local = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $remote = deployFtpRemotePath($rel);
    $remoteDir = dirname(str_replace('\\', '/', $remote));

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $conn = deployFtpConnect($server, $port, $user, $pass);
        if ($conn === false) {
            usleep(500_000);
            continue;
        }

        if ($remoteDir !== '.' && $remoteDir !== '') {
            deployFtpEnsureDir($conn, $remoteDir);
        }

        $ok = @ftp_put($conn, $remote, $local, FTP_BINARY);
        ftp_close($conn);

        if ($ok) {
            return true;
        }

        usleep(500_000);
    }

    return false;
}

/**
 * @return string|null caminho temporário com conteúdo remoto, ou null em falha
 */
function deployFtpDownloadFile(
    string $server,
    int $port,
    string $user,
    string $pass,
    string $rel,
    int $maxAttempts = 4
): ?string {
    $remote = deployFtpRemotePath($rel);

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $conn = deployFtpConnect($server, $port, $user, $pass);
        if ($conn === false) {
            usleep(500_000);
            continue;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'ftpv_');
        if ($tmp === false) {
            ftp_close($conn);
            return null;
        }

        $ok = @ftp_get($conn, $tmp, $remote, FTP_BINARY);
        ftp_close($conn);

        if ($ok) {
            return $tmp;
        }

        @unlink($tmp);
        usleep(500_000);
    }

    return null;
}
