<?php

namespace App\adms\Controllers\trainings;

class UpdateTrainingMatrix
{
    public function index(): void
    {
        $service = new TrainingMatrixService();
        $service->updateMatrixForAllUsers();
        $_SESSION['msg'] = '<div class="alert alert-success">Matriz de treinamentos obrigatórios atualizada com sucesso para todos os colaboradores!</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
        exit;
    }
} 