<?php

/**
 * Script de diagnóstico para o Dashboard de KPIs de Treinamentos.
 *
 * Uso (no servidor, na pasta do projeto):
 *   php scripts/debug_kpi_summary.php
 *
 * Ele imprime na saída:
 *   - Resultado de TrainingUsersRepository::getSummaryAll()
 *   - Resultado de TrainingUsersRepository::getStatusCounts()
 */

require __DIR__ . '/../vendor/autoload.php';

use App\adms\Models\Repository\TrainingUsersRepository;

echo "=== DEBUG KPI SUMMARY / STATUS COUNTS ===\n\n";

try {
    $repo = new TrainingUsersRepository();

    echo "-> getSummaryAll():\n";
    $summary = $repo->getSummaryAll();
    var_export($summary);
    echo "\n\n";

    echo "-> getStatusCounts():\n";
    $status = $repo->getStatusCounts();
    var_export($status);
    echo "\n\n";

    echo "Concluído em: " . date('Y-m-d H:i:s') . "\n";
} catch (\Throwable $e) {
    echo "ERRO ao executar diagnóstico: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}


