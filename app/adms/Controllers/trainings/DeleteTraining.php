<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;

class DeleteTraining
{
    public function index(int|string $id): void
    {
        $repo = new TrainingsRepository();
        $result = $repo->deleteTraining($id);
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForAllUsers();
            $_SESSION['success'] = 'Treinamento excluído com sucesso!';
        } else {
            $_SESSION['error'] = 'Erro ao excluir treinamento!';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
        exit;
    }
} 