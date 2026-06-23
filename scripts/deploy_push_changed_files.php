<?php

declare(strict_types=1);

/**
 * Envia via FTP APENAS os ficheiros alterados no push (segundos, não minutos).
 *
 * Cada ficheiro usa conexão FTP própria + retries — evita queda da sessão Kinghost
 * após ~6 uploads na mesma ligação.
 *
 * Variáveis:
 *   FTP_SERVER / FTP_HOST, FTP_USER, FTP_PASS
 *   GIT_BEFORE — commit anterior (github.event.before)
 *   GIT_AFTER  — commit actual (github.sha); default HEAD
 *   DEPLOY_MAX_CHANGED — limite para caminho rápido (default 150)
 *   DEPLOY_FTP_RETRIES — tentativas por ficheiro (default 4)
 *
 * Exit 0 = upload OK (ou nada a enviar)
 * Exit 1 = falha FTP
 * Exit 3 = demasiados ficheiros alterados → usar FTP-Deploy-Action
 */

require __DIR__ . '/deploy_changed_files_lib.php';

$root = dirname(__DIR__);
$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$gitBefore = trim((string)(getenv('GIT_BEFORE') ?: ''));
$gitAfter = trim((string)(getenv('GIT_AFTER') ?: 'HEAD'));
$maxChanged = (int)(getenv('DEPLOY_MAX_CHANGED') ?: 150);
$maxRetries = max(1, (int)(getenv('DEPLOY_FTP_RETRIES') ?: 4));
$port = (int)(getenv('FTP_PORT') ?: 21);

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "❌ Defina FTP_SERVER, FTP_USER e FTP_PASS.\n");
    exit(1);
}

$changed = deployResolveFilesToUpload($root, $gitBefore, $gitAfter);
$manifestName = trim((string)(getenv('DEPLOY_FEATURE_MANIFEST') ?: ''));

echo '=== Deploy rápido (ficheiros do push) — ' . date('Y-m-d H:i:s') . " ===\n";
if ($manifestName !== '') {
    echo "Manifesto: {$manifestName}\n";
} else {
    echo 'Git: ' . ($gitBefore !== '' ? substr($gitBefore, 0, 7) : 'HEAD~1') . ' → ' . substr($gitAfter, 0, 7) . "\n";
}
echo 'Ficheiros a enviar (após exclude): ' . count($changed) . "\n";
echo "Modo: conexão FTP por ficheiro, até {$maxRetries} tentativa(s) cada.\n";

if ($changed === []) {
    echo "✅ Nenhum ficheiro deployável alterado neste push.\n";
    exit(0);
}

if (count($changed) > $maxChanged) {
    echo "⚠️ Mais de {$maxChanged} ficheiros — usar FTP-Deploy-Action (incremental completo).\n";
    exit(3);
}

$uploaded = 0;
$errors = deployUploadFilesList($server, $port, $user, $pass, $root, $changed, $maxRetries);
$uploaded = count($changed) - count($errors);

echo "\nEnviados: {$uploaded}/" . count($changed) . "\n";

if ($errors !== []) {
    fwrite(STDERR, '❌ Falha ao enviar ' . count($errors) . " ficheiro(s).\n");
    fwrite(STDERR, "Use deploy_push_changed_lftp.php como fallback ou reexecute o workflow.\n");
    exit(1);
}

echo "✅ Deploy rápido concluído.\n";
exit(0);
