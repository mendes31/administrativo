<?php

declare(strict_types=1);

/**
 * Instala o gateway público em www/canaldenuncia (ao lado de administrativo).
 *
 * Uso: php scripts/install-canaldenuncia-gateway.php
 */

$projectRoot = dirname(__DIR__);
$source = $projectRoot . DIRECTORY_SEPARATOR . 'deploy' . DIRECTORY_SEPARATOR . 'canaldenuncia';
$target = dirname($projectRoot) . DIRECTORY_SEPARATOR . 'canaldenuncia';

if (!is_dir($source)) {
    fwrite(STDERR, "Origem não encontrada: {$source}\n");
    exit(1);
}

if (!is_dir($target)) {
    if (!mkdir($target, 0755, true)) {
        fwrite(STDERR, "Não foi possível criar: {$target}\n");
        exit(1);
    }
    echo "Pasta criada: {$target}\n";
}

foreach (['index.php', '.htaccess'] as $file) {
    $from = $source . DIRECTORY_SEPARATOR . $file;
    $to = $target . DIRECTORY_SEPARATOR . $file;
    if (!copy($from, $to)) {
        fwrite(STDERR, "Falha ao copiar {$file}\n");
        exit(1);
    }
    echo "Copiado: {$to}\n";
}

echo "\nGateway instalado. Acesse: http://localhost/canaldenuncia\n";
echo "Configure no .env: URL_CANAL_DENUNCIA=http://localhost/canaldenuncia/\n";
