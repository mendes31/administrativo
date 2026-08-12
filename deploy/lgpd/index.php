<?php

declare(strict_types=1);

/**
 * Gateway público do portal LGPD na raiz do site.
 * Instale em: www/lgpd/ (irmão de www/administrativo/)
 *
 * Ex.: http://localhost/lgpd
 *      https://www.tiaraju.com.br/lgpd
 */

$gatewayDir = str_replace('\\', '/', __DIR__);
$adminRoot = realpath($gatewayDir . '/../administrativo');

if ($adminRoot === false) {
    $adminRoot = realpath($gatewayDir . '/../../administrativo');
}

if ($adminRoot === false || !is_file($adminRoot . '/index.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Portal LGPD: não foi possível localizar o sistema administrativo.\n";
    echo "Copie esta pasta para a raiz do site (www/lgpd) ao lado de administrativo/.\n";
    exit;
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('\\', '/', $path);

$mount = '/lgpd';
if (stripos($path, $mount) === 0) {
    $suffix = substr($path, strlen($mount));
} else {
    $suffix = $path;
}
$suffix = trim($suffix, '/');

$_GET['url'] = 'lgpd' . ($suffix !== '' ? '/' . $suffix : '');

chdir($adminRoot);
require $adminRoot . DIRECTORY_SEPARATOR . 'index.php';
