<?php

declare(strict_types=1);

/**
 * Gera e envia .ftp-deploy-sync-state.json para o servidor FTP.
 *
 * Uso:
 *   FTP_SERVER=host FTP_USER=user FTP_PASS=pass php scripts/upload_ftp_sync_state.php
 */

require __DIR__ . '/deploy_config.php';

$generateScript = __DIR__ . '/generate_ftp_deploy_state.php';
passthru('php ' . escapeshellarg($generateScript), $genCode);
if ($genCode !== 0) {
    fwrite(STDERR, "Falha ao gerar .ftp-deploy-sync-state.json\n");
    exit(1);
}

$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$remoteBase = deployFtpRemoteBase();
$port = (int)(getenv('FTP_PORT') ?: 21);

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "Defina FTP_SERVER, FTP_USER e FTP_PASS.\n");
    exit(2);
}

$root = dirname(__DIR__);
$stateFile = $root . DIRECTORY_SEPARATOR . '.ftp-deploy-sync-state.json';

if (!is_file($stateFile)) {
    fwrite(STDERR, "Estado não gerado: {$stateFile}\n");
    exit(2);
}

$conn = @ftp_connect($server, $port, 120);
if ($conn === false) {
    fwrite(STDERR, "Falha ao conectar FTP.\n");
    exit(2);
}

if (!@ftp_login($conn, $user, $pass)) {
    fwrite(STDERR, "Falha na autenticação FTP.\n");
    ftp_close($conn);
    exit(2);
}

@ftp_pasv($conn, true);
@ftp_set_option($conn, FTP_TIMEOUT_SEC, 180);

$remotePath = deployFtpRemotePath('.ftp-deploy-sync-state.json');

if (!@ftp_put($conn, $remotePath, $stateFile, FTP_BINARY)) {
    fwrite(STDERR, "Falha ao enviar {$remotePath}\n");
    ftp_close($conn);
    exit(1);
}

ftp_close($conn);

$size = filesize($stateFile);
echo "Estado FTP enviado: {$remotePath} (" . number_format((int)$size) . " bytes)\n";
exit(0);
