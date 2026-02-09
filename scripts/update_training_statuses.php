<?php
/**
 * Script CLI para atualizar os STATUS dinâmicos de treinamentos no banco.
 *
 * Uso:
 *   php scripts/update_training_statuses.php
 *
 * Sugestão de agendamento (cron no servidor):
 *   0 3 * * * php /caminho/para/administrativo/scripts/update_training_statuses.php >> /caminho/para/logs/update_training_statuses.log 2>&1
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Services\TrainingStatusUpdaterService;

echo "=== Atualização de Status Dinâmicos de Treinamentos ===\n";
echo "Iniciando em: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Forçar atualização ignorando intervalo mínimo
    TrainingStatusUpdaterService::ensureUpdated(true);

    echo "Status dinâmicos atualizados com sucesso.\n\n";
    echo "Concluído em: " . date('Y-m-d H:i:s') . "\n";
    exit(0);
} catch (\Throwable $e) {
    echo "ERRO ao atualizar status dinâmicos: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


