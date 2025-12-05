<?php

namespace App\adms\Controllers\performance;

use App\adms\Models\Repository\CompetenciesRepository;

/**
 * Controller para apagar competência
 */
class DeleteCompetency
{
    public function index(string|int $id): void
    {
        $repository = new CompetenciesRepository();
        
        try {
            $repository->delete((int)$id);
            
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Competência apagada com sucesso!</div>';
        } catch (\Exception $e) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Erro ao apagar competência: ' . $e->getMessage() . '</div>';
        }
        
        header('Location: ' . $_ENV['URL_ADM'] . 'list-competencies');
        exit;
    }
}

