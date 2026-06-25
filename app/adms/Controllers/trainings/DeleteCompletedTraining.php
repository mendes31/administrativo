<?php

namespace App\adms\Controllers\trainings;

/**
 * Controller para permissão de exclusão de treinamentos realizados.
 *
 * A exclusão é feita via AJAX pelo método deleteApplication da CompletedTrainingsMatrix.
 */
class DeleteCompletedTraining
{
    public function index(): void
    {
        header('Location: ' . $_ENV['URL_ADM'] . 'completed-trainings-matrix');
        exit;
    }
}
