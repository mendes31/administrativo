<?php

declare(strict_types=1);

/**
 * Escala solicitações com SLA vencido na etapa do gestor.
 *
 * Uso:
 *   php scripts/employee_request_escalate.php
 *   php scripts/employee_request_escalate.php --dry-run
 */

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$dryRun = in_array('--dry-run', $argv ?? [], true);

$workflow = new App\adms\Models\Services\EmployeeRequestWorkflowService();

if ($dryRun) {
    $repo = new App\adms\Models\Repository\EmployeeRequestsRepository();
    $due = $repo->listDueForEscalation(date('Y-m-d H:i:s'));
    echo '[dry-run] ' . count($due) . " solicitação(ões) elegíveis para escalação\n";
    foreach ($due as $row) {
        echo ' - #' . $row['id'] . ' status=' . $row['status']
            . ' approver=' . ($row['current_approver_user_id'] ?? '-')
            . ' started=' . ($row['stage_started_at'] ?? '-')
            . ' sla_h=' . ($row['escalate_after_hours'] ?? '-')
            . PHP_EOL;
    }
    exit(0);
}

$result = $workflow->escalateDue();
echo 'Escaladas para nível acima: ' . $result['escalated'] . PHP_EOL;
echo 'Movidas para RH (fallback): ' . $result['moved_to_hr'] . PHP_EOL;
exit(0);
