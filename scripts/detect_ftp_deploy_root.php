<?php

declare(strict_types=1);

/**
 * Detecta se o deploy deve usar raiz FTP (./) ou subpasta administrativo/.
 *
 * Execute após login FTP (GitHub secrets ou .env):
 *   FTP_SERVER=... FTP_USER=... FTP_PASS=... php scripts/detect_ftp_deploy_root.php
 */

require __DIR__ . '/deploy_config.php';

$server = trim((string)(getenv('FTP_SERVER') ?: getenv('FTP_HOST') ?: ''));
$user = trim((string)(getenv('FTP_USER') ?: ''));
$pass = (string)(getenv('FTP_PASS') ?: '');
$port = (int)(getenv('FTP_PORT') ?: 21);

if ($server === '' || $user === '' || $pass === '') {
    fwrite(STDERR, "Defina FTP_SERVER, FTP_USER e FTP_PASS.\n");
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

$listRoot = @ftp_nlist($conn, '.') ?: [];
$listRootLower = array_map(static fn(string $n): string => strtolower(basename($n)), $listRoot);

$hasIndexAtRoot = in_array('index.php', $listRootLower, true);
$hasAppAtRoot = in_array('app', $listRootLower, true);
$hasNestedAdministrativo = in_array('administrativo', $listRootLower, true);

$nestedHasIndex = false;
if ($hasNestedAdministrativo) {
    $nestedList = @ftp_nlist($conn, 'administrativo') ?: [];
    $nestedLower = array_map(static fn(string $n): string => strtolower(basename($n)), $nestedList);
    $nestedHasIndex = in_array('index.php', $nestedLower, true);
}

ftp_close($conn);

echo "=== Detecção de raiz FTP — " . date('Y-m-d H:i:s') . " ===\n";
echo "Itens na raiz da sessão: " . count($listRoot) . "\n";
echo "index.php na raiz: " . ($hasIndexAtRoot ? 'sim' : 'não') . "\n";
echo "app/ na raiz: " . ($hasAppAtRoot ? 'sim' : 'não') . "\n";
echo "subpasta administrativo/: " . ($hasNestedAdministrativo ? 'sim' : 'não');
if ($hasNestedAdministrativo) {
    echo ' (index.php dentro: ' . ($nestedHasIndex ? 'sim — provável lixo de deploy' : 'não') . ')';
}
echo "\n\n";

if ($hasIndexAtRoot && $hasAppAtRoot) {
    echo "RECOMENDADO: server-dir: ./  e  FTP_REMOTE_BASE vazio\n";
    echo "O login FTP já está em ~/www/administrativo/.\n";
    if ($hasNestedAdministrativo) {
        echo "\nAÇÃO: apague a subpasta duplicada no servidor (PuTTY ou WebFTP):\n";
        echo "  rm -rf /home/tiaraju/www/administrativo/administrativo\n";
        echo "  rm -rf /home/tiaraju/www/administrativo/Administrativo\n";
    }
    exit(0);
}

if (!$hasIndexAtRoot && $hasNestedAdministrativo && $nestedHasIndex) {
    echo "RECOMENDADO: server-dir: administrativo/  e  FTP_REMOTE_BASE=administrativo\n";
    echo "O login FTP abre em ~/www/; o site está em administrativo/.\n";
    exit(0);
}

echo "AVISO: estrutura ambígua — confira manualmente no WebFTP.\n";
echo "Procure index.php + pasta app/ no mesmo nível.\n";
exit(1);
