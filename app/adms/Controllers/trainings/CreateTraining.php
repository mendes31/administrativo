<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Helpers\ScreenResolutionHelper;

class CreateTraining
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_create_training', $this->data['form']['csrf_token'])
        ) {
            $this->addTraining();
        } else {
            $this->viewCreateTraining();
        }
    }

    private function viewCreateTraining(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $usersRepo = new UsersRepository();
        $departmentsRepo = new DepartmentsRepository();
        $this->data['listUsers'] = $usersRepo->getAllUsersSelect();
        $this->data['listDepartments'] = $departmentsRepo->getAllDepartmentsSelect();
        $pageElements = [
            'title_head' => 'Cadastrar Treinamento',
            'menu' => 'list-trainings',
            'buttonPermission' => ['ListTrainings'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar configurações responsivas
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;
        $loadView = new LoadViewService('adms/Views/trainings/create', $this->data);
        $loadView->loadView();
    }

    private function addTraining(): void
    {
        // Validação do prazo de treinamento
        $prazoTreinamento = (int)($this->data['form']['prazo_treinamento'] ?? 0);
        if ($prazoTreinamento <= 0) {
            $this->data['errors'][] = 'O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.';
            $this->viewCreateTraining();
            return;
        }
        
        // Determinar tipo de instrutor e ajustar campos
        if (!empty($this->data['form']['instructor_user_id'])) {
            // Instrutor interno - buscar nome e e-mail do usuário
            $usersRepo = new UsersRepository();
            $user = $usersRepo->getUser((int)$this->data['form']['instructor_user_id']);
            if ($user) {
                $this->data['form']['instructor_email'] = $user['email'];
                $this->data['form']['instructor_name'] = $user['name']; // Salva o nome do usuário
            }
            // Limpar campo de instrutor externo se existir
            $this->data['form']['instrutor'] = '';
        } elseif (!empty($this->data['form']['instructor_name'])) {
            // Instrutor externo - limpar campo de usuário interno
            $this->data['form']['instructor_user_id'] = null;
            $this->data['form']['instrutor'] = 'Instrutor Externo';
            // Salva o nome digitado
            // (já está em instructor_name)
        } else {
            // Nenhum instrutor definido - limpar campos
            $this->data['form']['instructor_user_id'] = null;
            $this->data['form']['instructor_email'] = null;
            $this->data['form']['instrutor'] = '';
            $this->data['form']['instructor_name'] = null;
        }
        
        // Depuração: ver o que está sendo enviado para o banco
        // var_dump($this->data['form']); exit;
        
        // Garantir que 'tipo' receba o valor de 'categoria'
        $this->data['form']['tipo'] = $this->data['form']['categoria'] ?? '';
        $this->data['form']['area_responsavel_id'] = $this->data['form']['area_responsavel_id'] ?? null;
        $this->data['form']['area_elaborador_id'] = $this->data['form']['area_elaborador_id'] ?? null;
        $this->data['form']['tipo_obrigatoriedade'] = $this->data['form']['tipo_obrigatoriedade'] ?? null;

        $repo = new TrainingsRepository();
        $result = $repo->createTraining($this->data['form']);
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            $matrixService->updateMatrixForAllUsers();
            $_SESSION['success'] = 'Treinamento cadastrado com sucesso!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        } else {
            $this->data['errors'][] = 'Erro ao cadastrar treinamento!';
            $this->viewCreateTraining();
        }
    }
} 