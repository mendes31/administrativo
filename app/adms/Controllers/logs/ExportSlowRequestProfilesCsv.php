<?php

namespace App\adms\Controllers\logs;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\AdmsSlowRequestProfileRepository;

class ExportSlowRequestProfilesCsv
{
    public function index(): void
    {
        if (!CSRFHelper::validateCSRFToken('download_slow_profiles', (string)($_GET['token'] ?? ''), false)) {
            $_SESSION['msg'] = 'Token inválido para exportação do micro-profiler.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'log-settings');
            exit;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20000;
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 0;
        $from = $_GET['from'] ?? '';
        $to = $_GET['to'] ?? '';

        if ($days > 0) {
            $to = date('Y-m-d');
            $from = date('Y-m-d', strtotime("-{$days} days"));
        }

        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : '';
        $to = preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) ? $to : '';

        $repo = new AdmsSlowRequestProfileRepository();
        $rows = $repo->listForExport($limit, $from ?: null, $to ?: null);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="slow_request_profiles_' . date('Y-m-d_H-i-s') . '.csv"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, [
            'ID',
            'Data/Hora',
            'Metodo',
            'Rota',
            'URI',
            'Duracao (ms)',
            'Memoria (MB)',
            'Usuario ID',
        ], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                (int)($row['id'] ?? 0),
                (string)($row['created_at'] ?? ''),
                (string)($row['request_method'] ?? ''),
                (string)($row['route_label'] ?? ''),
                (string)($row['request_uri'] ?? ''),
                (int)($row['duration_ms'] ?? 0),
                number_format((float)($row['memory_mb'] ?? 0), 2, '.', ''),
                isset($row['user_id']) ? (int)$row['user_id'] : '',
            ], ';');
        }

        fclose($output);
        exit;
    }
}
