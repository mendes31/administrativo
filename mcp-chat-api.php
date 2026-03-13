<?php

// Endpoint direto para o chat MCP, evitando passar pelo roteador LoadPageAdm

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Garantir que variáveis de ambiente estejam carregadas
if (!isset($_ENV['DB_HOST'])) {
    require_once __DIR__ . '/app/adms/Helpers/EnvLoader.php';
    \App\adms\Helpers\EnvLoader::load();
}

// Delegar para o controller dedicado
$controller = new \App\adms\Controllers\settings\McpChatApi();
$controller->index();

