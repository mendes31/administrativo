<?php

declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['tab'] = 'despesas';

require __DIR__ . '/bootstrap_app.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;

try {
    ob_start();
    $ctrl = new App\adms\Controllers\inventory\ViewInvCostPeriod();
    $ctrl->index(4);
    $html = ob_get_clean();
    echo 'Rendered bytes: ' . strlen($html) . "\n";
    if (str_contains($html, 'Erro 004')) {
        echo "CONTAINS ERRO 004\n";
        exit(2);
    }
    echo "OK\n";
} catch (Throwable $e) {
    ob_end_clean();
    fwrite(STDERR, $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n");
    exit(2);
}
