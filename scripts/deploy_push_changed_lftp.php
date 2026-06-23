<?php

declare(strict_types=1);

/**
 * Fallback: envia via lftp APENAS os ficheiros alterados no push.
 * Mais resiliente que ftp_put em lote na Kinghost (reconnect automático).
 *
 * Requer lftp instalado (apt install lftp).
 *
 * Variáveis: FTP_SERVER/FTP_HOST, FTP_USER, FTP_PASS, GIT_BEFORE, GIT_AFTER
 * Exit 0 = OK | 1 = falha | 3 = demasiados ficheiros
 */

require __DIR__ . '/deploy_changed_files_lib.php';

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

$lftpBin = trim((string)shell_exec('command -v lftp 2>/dev/null') ?: '');
if ($lftpBin === '') {
    fwrite(STDERR, "❌ lftp não encontrado. Instale com: apt install lftp\n");
    exit(1);
}

$changed = deployCollectChangedFiles($root, $gitBefore, $gitAfter);

echo '=== Deploy lftp (só ficheiros do push) — ' . date('Y-m-d H:i:s') . " ===\n";
echo 'Ficheiros: ' . count($changed) . "\n";

if ($changed === []) {
    echo "✅ Nenhum ficheiro deployável alterado.\n";
    exit(0);
}

if (count($changed) > $maxChanged) {
    echo "⚠️ Mais de {$maxChanged} ficheiros — usar deploy incremental completo.\n";
    exit(3);
}

$scriptFile = tempnam(sys_get_temp_dir(), 'lftp_push_');
if ($scriptFile === false) {
    fwrite(STDERR, "❌ Falha ao criar script temporário lftp.\n");
    exit(1);
}

$remoteBase = deployFtpRemoteBase();
$lines = [
    'set cmd:fail-exit yes',
    'set ftp:passive-mode true',
    'set ftp:ssl-allow no',
    'set net:timeout 120',
    'set net:max-retries 20',
    'set net:reconnect-interval-base 2',
    'set net:reconnect-interval-max 10',
    'set xfer:clobber on',
];

$rootEscaped = str_replace("'", "\\'", $root);
$lines[] = "lcd '{$rootEscaped}'";

if ($remoteBase !== '') {
    $baseEscaped = str_replace("'", "\\'", $remoteBase);
    $lines[] = "cd '{$baseEscaped}'";
}

foreach ($changed as $rel) {
    $remote = deployFtpRemotePath($rel);
    $relEscaped = str_replace("'", "\\'", $rel);
    $remoteEscaped = str_replace("'", "\\'", $remote);
    $lines[] = "put -o '{$remoteEscaped}' '{$relEscaped}'";
}

$lines[] = 'quit';
file_put_contents($scriptFile, implode("\n", $lines) . "\n");

$hostArg = $port === 21 ? escapeshellarg($server) : escapeshellarg("{$server}:{$port}");
$userEscaped = str_replace("'", "\\'", $user);
$passEscaped = str_replace("'", "\\'", $pass);

$cmd = sprintf(
    "%s -u '%s','%s' %s -f %s 2>&1",
    escapeshellarg($lftpBin),
    $userEscaped,
    $passEscaped,
    $hostArg,
    escapeshellarg($scriptFile)
);

echo "A enviar " . count($changed) . " ficheiro(s) via lftp...\n";

$output = [];
$code = 0;
exec($cmd, $output, $code);
@unlink($scriptFile);

foreach ($output as $line) {
    echo $line . "\n";
}

if ($code !== 0) {
    fwrite(STDERR, "❌ lftp terminou com código {$code}.\n");
    exit(1);
}

echo "✅ Deploy lftp (ficheiros do push) concluído.\n";
exit(0);
