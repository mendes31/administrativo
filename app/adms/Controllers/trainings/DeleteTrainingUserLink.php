<?php
namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;

class DeleteTrainingUserLink
{
    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }
        $trainingId = isset($_POST['training_id']) ? (int)$_POST['training_id'] : null;
        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        if ($trainingId && $userId) {
            $repo = new TrainingUsersRepository();
            $repo->deleteIndividualVinculo($trainingId, $userId);
            $_SESSION['msg'] = '<div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle"></i> Vínculo removido com sucesso!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>';
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> Erro: Parâmetros inválidos!
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>';
        }
        header('Location: ' . $_ENV['URL_ADM'] . 'link-training-users/' . $trainingId);
        exit;
    }
} 