<?php

namespace App\adms\Controllers\reports;

use App\adms\Models\Repository\DynamicReportsRepository;

class DeleteDynamicReport
{
    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = 'ID do relatório não fornecido';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $repo = new DynamicReportsRepository();
        $report = $repo->getById((int)$id);
        
        if (!$report) {
            $_SESSION['error'] = 'Relatório não encontrado';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        // Verificar se o usuário é o criador ou super admin
        $userId = $_SESSION['user_id'] ?? 0;
        if ($report['created_by'] != $userId && !in_array(1, $_SESSION['user_access_levels'] ?? [])) {
            $_SESSION['error'] = 'Você não tem permissão para deletar este relatório';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
            exit;
        }
        
        $success = $repo->delete((int)$id);
        
        if ($success) {
            $_SESSION['success'] = 'Relatório excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir relatório';
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'list-dynamic-reports');
        exit;
    }
}

