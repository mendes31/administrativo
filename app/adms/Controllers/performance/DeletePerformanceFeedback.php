<?php

namespace App\adms\Controllers\performance;

use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceFeedbacksRepository;

/**
 * Controller para deletar feedback de desempenho
 */
class DeletePerformanceFeedback
{
    public function index(int|string $id): void
    {
        if (!(int)$id) {
            $_SESSION['error'] = 'Feedback não encontrado.';
            header("Location: {$_ENV['URL_ADM']}list-performance-feedbacks");
            return;
        }

        // Validar CSRF
        if (!CSRFHelper::validateCSRFToken('form_delete_performance_feedback', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header("Location: {$_ENV['URL_ADM']}list-performance-feedbacks");
            return;
        }

        $repository = new PerformanceFeedbacksRepository();
        
        if ($repository->delete((int)$id)) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Feedback excluído com sucesso!</div>';
            GenerateLog::generateLog("info", "Feedback de desempenho excluído.", ['id' => $id]);
        } else {
            $_SESSION['error'] = 'Erro ao excluir feedback. Tente novamente.';
        }
        
        header("Location: {$_ENV['URL_ADM']}list-performance-feedbacks");
    }
}

