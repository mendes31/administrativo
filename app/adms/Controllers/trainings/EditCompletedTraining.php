<?php

namespace App\adms\Controllers\trainings;

/**
 * Controller para permissão de edição de treinamentos realizados
 * 
 * Esta controller existe apenas para o sistema de permissões.
 * Os métodos reais de edição estão em CompletedTrainingsMatrix (getApplication e updateApplication).
 */
class EditCompletedTraining
{
    /**
     * Este método nunca deve ser chamado diretamente.
     * A edição é feita via AJAX através dos métodos getApplication e updateApplication
     * da controller CompletedTrainingsMatrix.
     */
    public function index(): void
    {
        // Redirecionar para a matriz de treinamentos
        header('Location: ' . $_ENV['URL_ADM'] . 'completed-trainings-matrix');
        exit;
    }
}


