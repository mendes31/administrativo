<?php

declare(strict_types=1);

/**
 * Gateway público do Canal de Denúncias na raiz do site.
 * Instale em: www/canaldenuncia/ (irmão de www/administrativo/)
 *
 * Ex.: http://localhost/canaldenuncia
 *      https://www.tiaraju.com.br/canaldenuncia
 */

$gatewayDir = str_replace('\\', '/', __DIR__);
$adminRoot = realpath($gatewayDir . '/../administrativo');

if ($adminRoot === false) {
    $adminRoot = realpath($gatewayDir . '/../../administrativo');
}

if ($adminRoot === false || !is_file($adminRoot . '/index.php')) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Canal de Denúncias: não foi possível localizar o sistema administrativo.\n";
    echo "Copie esta pasta para a raiz do site (www/canaldenuncia) ao lado de administrativo/.\n";
    exit;
}

$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) parse_url($requestUri, PHP_URL_PATH);
$path = str_replace('\\', '/', $path);

$mount = '/canaldenuncia';
if (stripos($path, $mount) === 0) {
    $suffix = substr($path, strlen($mount));
} else {
    $suffix = $path;
}
$suffix = trim($suffix, '/');

$_GET['url'] = 'canaldenuncia' . ($suffix !== '' ? '/' . $suffix : '');

chdir($adminRoot);
require $adminRoot . DIRECTORY_SEPARATOR . 'index.php';
