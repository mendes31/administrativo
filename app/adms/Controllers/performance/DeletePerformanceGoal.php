<?php

namespace App\adms\Controllers\performance;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceGoalsRepository;

/**
 * Controller para deletar meta de desempenho
 */
class DeletePerformanceGoal
{
    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Meta não encontrada.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_delete_performance_goal', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header("Location: {$_ENV['URL_ADM']}list-performance-goals");
            return;
        }

        $repository = new PerformanceGoalsRepository();
        
        if ($repository->delete((int)$id)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Meta excluída com sucesso!</div>';
            GenerateLog::generateLog("info", "Meta de desempenho excluída.", ['id' => $id]);
        } else {
            $_SESSION['error'] = 'Erro ao excluir meta. Tente novamente.';
        }
        
        header("Location: {$_ENV['URL_ADM']}list-performance-goals");
    }
}

