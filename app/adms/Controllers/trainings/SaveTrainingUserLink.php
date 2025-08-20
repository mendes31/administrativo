<?php
namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingUsersRepository;

class SaveTrainingUserLink
{
    public function index()
    {
        // Debug temporário - remover depois
        // die('CONTROLLER SaveTrainingUserLink CHAMADO! POST: ' . print_r($_POST, true));
        
        error_log("=== SaveTrainingUserLink chamado ===");
        error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
        error_log("POST data: " . print_r($_POST, true));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            error_log("Método não é POST, redirecionando");
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        }
        
        // Log de debug
        \App\adms\Helpers\GenerateLog::generateLog(
            "debug", 
            "SaveTrainingUserLink - Dados recebidos", 
            [
                'POST' => $_POST,
                'training_id' => $_POST['training_id'] ?? null,
                'user_ids' => $_POST['user_ids'] ?? [],
                'count_user_ids' => count($_POST['user_ids'] ?? [])
            ]
        );
        
        $trainingId = isset($_POST['training_id']) ? (int)$_POST['training_id'] : null;
        $userIds = $_POST['user_ids'] ?? [];
        
        // Log de debug após processamento
        \App\adms\Helpers\GenerateLog::generateLog(
            "debug", 
            "SaveTrainingUserLink - Dados processados", 
            [
                'trainingId' => $trainingId,
                'userIds' => $userIds,
                'count_userIds' => count($userIds)
            ]
        );
        
        if ($trainingId && !empty($userIds)) {
            $repo = new TrainingUsersRepository();
            
            // Log antes de chamar o método
            \App\adms\Helpers\GenerateLog::generateLog(
                "debug", 
                "SaveTrainingUserLink - Chamando vincularUsuariosTreinamento", 
                [
                    'trainingId' => $trainingId,
                    'userIds' => $userIds
                ]
            );
            
            $repo->vincularUsuariosTreinamento($trainingId, $userIds);
            
            // Log após chamar o método
            \App\adms\Helpers\GenerateLog::generateLog(
                "info", 
                "SaveTrainingUserLink - Vínculos criados com sucesso", 
                [
                    'trainingId' => $trainingId,
                    'userIds' => $userIds
                ]
            );
            
            $_SESSION['msg'] = '<div class="alert alert-success">Colaboradores vinculados com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        } else {
            // Log de erro
            \App\adms\Helpers\GenerateLog::generateLog(
                "error", 
                "SaveTrainingUserLink - Dados inválidos", 
                [
                    'trainingId' => $trainingId,
                    'userIds' => $userIds,
                    'trainingId_valid' => !empty($trainingId),
                    'userIds_valid' => !empty($userIds)
                ]
            );
            
            $_SESSION['msg'] = '<div class="alert alert-danger">Selecione pelo menos um colaborador.</div>';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
} 