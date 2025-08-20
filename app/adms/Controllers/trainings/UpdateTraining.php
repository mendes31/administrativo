<?php

namespace App\adms\Controllers\trainings;

use App\adms\Models\Repository\TrainingsRepository;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Views\Services\LoadViewService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Helpers\ScreenResolutionHelper;

class UpdateTraining
{
    private array|string|null $data = null;
    private int|string $id;

    public function index(int|string $id): void
    {
        $this->id = $id;
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (
            isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_training', $this->data['form']['csrf_token'])
        ) {
            $this->editTraining();
        } else {
            $this->viewUpdateTraining();
        }
    }

    private function viewUpdateTraining(): void
    {
        // Obter configurações responsivas
        $resolution = ScreenResolutionHelper::getScreenResolution();
        $responsiveClasses = ScreenResolutionHelper::getResponsiveClasses($resolution['category']);
        $paginationSettings = ScreenResolutionHelper::getPaginationSettings($resolution['category']);
        
        $repo = new TrainingsRepository();
        $departmentsRepo = new DepartmentsRepository();
        $this->data['training'] = $repo->getTraining($this->id);
        $usersRepo = new UsersRepository();
        $this->data['listUsers'] = $usersRepo->getAllUsersSelect();
        $this->data['listDepartments'] = $departmentsRepo->getAllDepartmentsSelect();
        $pageElements = [
            'title_head' => 'Editar Treinamento',
            'menu' => 'list-trainings',
            'buttonPermission' => ['ListTrainings'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));
        
        // Adicionar configurações responsivas
        $this->data['responsiveClasses'] = $responsiveClasses;
        $this->data['paginationSettings'] = $paginationSettings;
        $loadView = new LoadViewService('adms/Views/trainings/update', $this->data);
        $loadView->loadView();
    }

    private function editTraining(): void
    {
        // Validação do prazo de treinamento
        $prazoTreinamento = (int)($this->data['form']['prazo_treinamento'] ?? 0);
        if ($prazoTreinamento <= 0) {
            $this->data['errors'][] = 'O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.';
            $this->viewUpdateTraining();
            return;
        }
        
        // Capturar dados antigos para verificar mudança de status
        $repo = new TrainingsRepository();
        $trainingAntigo = $repo->getTraining($this->id);
        $statusAnterior = $trainingAntigo['ativo'] ?? 1;
        
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
        
        $repo = new TrainingsRepository();
        $this->data['form']['area_responsavel_id'] = $this->data['form']['area_responsavel_id'] ?? null;
        $this->data['form']['area_elaborador_id'] = $this->data['form']['area_elaborador_id'] ?? null;
        $this->data['form']['tipo_obrigatoriedade'] = $this->data['form']['tipo_obrigatoriedade'] ?? null;
        $result = $repo->updateTraining($this->id, $this->data['form']);
        if ($result) {
            $matrixService = new \App\adms\Controllers\trainings\TrainingMatrixService();
            
            // Verificar mudanças de status do treinamento
            $statusNovo = $this->data['form']['ativo'] ?? 1;
            
            if ($statusAnterior == 1 && $statusNovo == 0) {
                // Treinamento foi inativado - remover vínculos ativos
                $trainingUsersRepo = new \App\adms\Models\Repository\TrainingUsersRepository();
                $trainingUsersRepo->removeActiveLinksByTraining($this->id);
                
                // Log da ação
                \App\adms\Helpers\GenerateLog::generateLog(
                    "info", 
                    "Treinamento inativado - vínculos removidos", 
                    [
                        'training_id' => $this->id,
                        'training_name' => $trainingAntigo['nome'] ?? '',
                        'user_id' => $_SESSION['user_id'] ?? 0
                    ]
                );
            } elseif ($statusAnterior == 0 && $statusNovo == 1) {
                // Treinamento foi reativado - recriar vínculos necessários
                $results = $matrixService->recreateLinksForReactivatedTraining($this->id);
                
                // Log da ação
                \App\adms\Helpers\GenerateLog::generateLog(
                    "info", 
                    "Treinamento reativado - vínculos recriados", 
                    [
                        'training_id' => $this->id,
                        'training_name' => $trainingAntigo['nome'] ?? '',
                        'user_id' => $_SESSION['user_id'] ?? 0,
                        'users_added' => $results['users_added'],
                        'users_skipped' => $results['users_skipped'],
                        'reciclagem_added' => $results['reciclagem_added']
                    ]
                );
            } else {
                // Atualização normal - atualizar matriz
                $matrixService->updateMatrixForAllUsers();
            }
            
            $_SESSION['success'] = 'Treinamento atualizado com sucesso!';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-trainings');
            exit;
        } else {
            $this->data['errors'][] = 'Erro ao atualizar treinamento!';
            $this->viewUpdateTraining();
        }
    }
} 