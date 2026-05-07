<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;

class DeleteTraining
{
    public function index(int|string $id): void
    {
        $repo = new TrainingsRepository();
        $training = $repo->getTraining($id);
        if (!$training) {
            $_SESSION['error'] = 'Treinamento não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }
        if (isset($training['is_current_version']) && (int)$training['is_current_version'] !== 1) {
            $_SESSION['error'] = 'Versões anteriores não podem ser excluídas.';
            header('Location: ' . $_ENV['URL_ADM'] . 'view-training/' . $id);
            exit;
        }
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