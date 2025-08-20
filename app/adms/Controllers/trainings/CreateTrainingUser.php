<?php
namespace App\adms\Controllers\trainings;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\TrainingUsersRepository;
use App\adms\Views\Services\LoadViewService;

class CreateTrainingUser
{
    private array|string|null $data = null;

    public function index(int $trainingId)
    {
        // Buscar usuários disponíveis
        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        $this->data['training_id'] = $trainingId;

        // Configurar elementos da página
        $pageElements = [
            'title_head' => 'Vincular Colaboradores ao Treinamento',
            'menu' => 'list-trainings',
            'buttonPermission' => ['CreateTrainingUser'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Carregar a view
        $loadView = new LoadViewService("adms/Views/trainings/createTrainingUser", $this->data);
        $loadView->loadView();
    }

    public function store()
    {
        $trainingId = $_POST['training_id'] ?? null;
        $userIds = $_POST['user_ids'] ?? [];
        if ($trainingId && !empty($userIds)) {
            $repo = new TrainingUsersRepository();
            $repo->vincularUsuariosTreinamento($trainingId, $userIds);
            $_SESSION['msg'] = '<div class="alert alert-success">Colaboradores vinculados com sucesso!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        } else {
            $_SESSION['msg'] = '<div class="alert alert-danger">Selecione pelo menos um colaborador.</div>';
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
} 