<?php

declare(strict_types=1);

/**
 * Gera e envia .ftp-deploy-sync-state.json para o servidor FTP.
 *
 * Uso:
 *   FTP_SERVER=host FTP_USER=user FTP_PASS=pass php scripts/upload_ftp_sync_state.php
 */

require __DIR__ . '/deploy_config.php';
require __DIR__ . '/deploy_changed_files_lib.php';

$generateScript = __DIR__ . '/generate_ftp_deploy_state.php';
echo "A gerar .ftp-deploy-sync-state.json (pode demorar ~1 min)...\n";
passthru('php ' . escapeshellarg($generateScript), $genCode);
if ($genCode !== 0) {
    fwrite(STDERR, "Falha ao gerar .ftp-deploy-sync-state.json\n");
    exit(1);
}

$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$port = (int)(getenv('FTP_PORT') ?: 21);
$maxRetries = max(1, (int)(getenv('DEPLOY_FTP_RETRIES') ?: 4));

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

$size = (int)filesize($stateFile);
echo "A enviar estado FTP ({$size} bytes) com reconexão por tentativa...\n";

$rel = '.ftp-deploy-sync-state.json';
$ok = deployFtpUploadFile($server, $port, $user, $pass, $root, $rel, $maxRetries);

if (!$ok) {
    fwrite(STDERR, "Falha ao enviar {$rel} após {$maxRetries} tentativa(s).\n");
    exit(1);
}

echo 'Estado FTP enviado: ' . deployFtpRemotePath($rel) . ' (' . number_format($size) . " bytes)\n";
exit(0);
