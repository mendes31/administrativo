<?php

namespace App\adms\Controllers\performance;

use App\adms\Models\Repository\PerformanceReviewsRepository;

/**
 * Controller para apagar avaliação de desempenho
 */
class DeletePerformanceReview
{
    public function index(?string $id = null): void
    {
        if (empty($id)) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: ID da avaliação não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        $repository = new PerformanceReviewsRepository();
        $review = $repository->getById((int)$id);

        if (!$review) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Avaliação não encontrada!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        // Verificar permissão
        $isSuperAdmin = isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1;
        $userId = $_SESSION['user_id'] ?? 0;
        
        if (!$isSuperAdmin && $review['created_by'] != $userId) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro: Você não tem permissão para apagar esta avaliação!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
            exit;
        }

        try {
            $success = $repository->delete((int)$id);
            
            if ($success) {
                $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Avaliação de desempenho apagada com sucesso!</div>';
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao apagar avaliação!</div>';
            }
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao apagar avaliação: ' . $e->getMessage() . '</div>';
        }

        header('Location: ' . $_ENV['URL_ADM'] . 'list-performance-reviews');
        exit;
    }
}

