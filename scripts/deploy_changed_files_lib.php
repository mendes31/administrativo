<?php

declare(strict_types=1);

require __DIR__ . '/deploy_excludes.php';
require __DIR__ . '/deploy_config.php';

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
