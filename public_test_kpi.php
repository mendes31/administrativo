<?php

// Teste simples para verificar se o ambiente web está usando o mesmo código
// que o CLI e se os métodos de KPI estão retornando os valores corretos.
//
// Acesse no navegador (ajuste o caminho conforme a raiz do site):
//   https://seu-dominio/administrativo/public_test_kpi.php

require __DIR__ . '/vendor/autoload.php';

use App\adms\Models\Repository\TrainingUsersRepository;

header('Content-Type: text/html; charset=utf-8');

echo '<h1>DEBUG – KPI Treinamentos (public_test_kpi.php)</h1>';

try {
    $repo = new TrainingUsersRepository();

    $summary = $repo->getSummaryAll();
    $status  = $repo->getStatusCounts();

    echo '<h2>getSummaryAll()</h2>';
    echo '<pre>';
    var_export($summary);
    echo '</pre>';

    echo '<h2>getStatusCounts()</h2>';
    echo '<pre>';
    var_export($status);
    echo '</pre>';

    echo '<p><strong>Data/Hora servidor:</strong> ' . date('Y-m-d H:i:s') . '</p>';
} catch (\Throwable $e) {
    echo '<h2>ERRO ao executar teste</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</pre>";
}


