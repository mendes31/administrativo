<?php

namespace App\adms\Controllers\Services;

// Carregar o autoload primeiro
require_once __DIR__ . '/../../../../vendor/autoload.php';

use App\adms\Helpers\EnvLoader;

// Carregar variáveis de ambiente usando o helper
if (!EnvLoader::loadWithTimezone()) {
    echo "Erro ao carregar configurações do .env. Verifique se o arquivo existe e está configurado.\n";
    exit(1);
}

// CLI para rodar notificações automáticas de treinamentos
if (php_sapi_name() !== 'cli') {
    echo "Este script deve ser executado via linha de comando.\n";
    exit(1);
}

echo "Iniciando processamento de notificações de treinamentos...\n";

try {
    $notificacaoService = new NotificacaoTreinamentosService();
    $notificacaoService->notificarTreinamentosPendentes();
    echo "Notificações de treinamentos processadas com sucesso.\n";
} catch (\Exception $e) {
    echo "Erro ao processar notificações: " . $e->getMessage() . "\n";
    exit(1);
} 