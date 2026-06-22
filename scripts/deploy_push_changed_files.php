<?php

declare(strict_types=1);

/**
 * Envia via FTP APENAS os ficheiros alterados no push (segundos, não minutos).
 *
 * Variáveis:
 *   FTP_SERVER / FTP_HOST, FTP_USER, FTP_PASS
 *   GIT_BEFORE — commit anterior (github.event.before)
 *   GIT_AFTER  — commit actual (github.sha); default HEAD
 *   DEPLOY_MAX_CHANGED — limite para caminho rápido (default 150)
 *
 * Exit 0 = upload OK (ou nada a enviar)
 * Exit 1 = falha FTP
 * Exit 3 = demasiados ficheiros alterados → usar FTP-Deploy-Action
 */

require __DIR__ . '/deploy_excludes.php';
require __DIR__ . '/deploy_config.php';

$root = dirname(__DIR__);
$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$gitBefore = trim((string)(getenv('GIT_BEFORE') ?: ''));
$gitAfter = trim((string)(getenv('GIT_AFTER') ?: 'HEAD'));
$maxChanged = (int)(getenv('DEPLOY_MAX_CHANGED') ?: 150);
$port = (int)(getenv('FTP_PORT') ?: 21);

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "❌ Defina FTP_SERVER, FTP_USER e FTP_PASS.\n");
    exit(1);
}

/**
 * @return list<string> caminhos relativos com /
 */
function collectChangedFiles(string $root, string $gitBefore, string $gitAfter): array
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
 * @param resource $conn
 */
function ftpEnsureDir($conn, string $remoteDir): void
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

$changed = collectChangedFiles($root, $gitBefore, $gitAfter);

echo '=== Deploy rápido (ficheiros do push) — ' . date('Y-m-d H:i:s') . " ===\n";
echo 'Git: ' . ($gitBefore !== '' ? substr($gitBefore, 0, 7) : 'HEAD~1') . ' → ' . substr($gitAfter, 0, 7) . "\n";
echo 'Ficheiros a enviar (após exclude): ' . count($changed) . "\n";

if ($changed === []) {
    echo "✅ Nenhum ficheiro deployável alterado neste push.\n";
    exit(0);
}

if (count($changed) > $maxChanged) {
    echo "⚠️ Mais de {$maxChanged} ficheiros — usar FTP-Deploy-Action (incremental completo).\n";
    exit(3);
}

$conn = @ftp_connect($server, $port, 120);
if ($conn === false) {
    fwrite(STDERR, "❌ Falha ao conectar FTP.\n");
    exit(1);
}

if (!@ftp_login($conn, $user, $pass)) {
    fwrite(STDERR, "❌ Falha na autenticação FTP.\n");
    ftp_close($conn);
    exit(1);
}

@ftp_pasv($conn, true);
@ftp_set_option($conn, FTP_TIMEOUT_SEC, 120);

$uploaded = 0;
$errors = [];

foreach ($changed as $rel) {
    $local = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
    $remote = deployFtpRemotePath($rel);
    $remoteDir = dirname(str_replace('\\', '/', $remote));

    if ($remoteDir !== '.' && $remoteDir !== '') {
        ftpEnsureDir($conn, $remoteDir);
    }

    if (@ftp_put($conn, $remote, $local, FTP_BINARY)) {
        echo "  ↑ {$rel}\n";
        $uploaded++;
    } else {
        $errors[] = $rel;
        fwrite(STDERR, "  ✗ falha: {$rel}\n");
    }
}

ftp_close($conn);

echo "\nEnviados: {$uploaded}/" . count($changed) . "\n";

if ($errors !== []) {
    fwrite(STDERR, '❌ Falha ao enviar ' . count($errors) . " ficheiro(s).\n");
    exit(1);
}

echo "✅ Deploy rápido concluído.\n";
exit(0);
