<?php

declare(strict_types=1);

namespace App\adms\Controllers\strategicPlans;

use App\adms\Models\Repository\StrategicPlansRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\DepartmentsRepository;

class EditStrategicPlan
{
    private $repository;
    private array $data = [];

    public function __construct()
    {
        $this->repository = new StrategicPlansRepository();
    }

    public function index(string|int|null $id = null): void
    {
        // Converter ID para int se necessário
        $id = $id ? (int)$id : null;
        
        // Verificar se o ID foi fornecido
        if (!$id) {
            $_SESSION['msg'] = "ID do plano não fornecido!";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "list-strategic-plans");
            exit;
        }

        $plan = $this->repository->getById($id);
        if (!$plan) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $this->repository->update($id, $data);
            header('Location: ' . $_ENV['URL_ADM'] . 'list-strategic-plans');
            exit;
        }

        $this->viewEdit($plan);
    }

    private function viewEdit($plan): void
    {
        // Verificar se usuário pode gerenciar outros departamentos
        $canManageOtherDepartments = $this->canManageOtherDepartments();

        // Obter dados do usuário atual
        $usersRepository = new UsersRepository();
        $currentUser = $usersRepository->getUser($_SESSION['user_id']);

        // Obter departamentos disponíveis
        $departmentsRepository = new DepartmentsRepository();
        $departments = $this->getAvailableDepartments($canManageOtherDepartments, $currentUser);

        // Obter usuários disponíveis
        $users = $this->getAvailableUsers($canManageOtherDepartments, $currentUser);

        // Definir o título da página e configurações
        $pageElements = [
            'title_head' => 'Editar Plano Estratégico',
            'menu' => 'list-strategic-plans',
            'buttonPermission' => ['ListStrategicPlans'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Adicionar dados específicos para o formulário
        $this->data['canManageOtherDepartments'] = $canManageOtherDepartments;
        $this->data['departments'] = $departments;
        $this->data['users'] = $users;
        $this->data['currentUser'] = $currentUser;
        $this->data['plan'] = $plan;

        // Carrega a view usando o padrão do projeto
        $loadView = new LoadViewService("adms/Views/strategicPlans/edit-strategic-plan", $this->data);
        $loadView->loadView();
    }

    /**
     * Verifica se o usuário pode gerenciar outros departamentos além do próprio
     */
    private function canManageOtherDepartments(): bool
    {
        // Super administrador pode gerenciar todos os departamentos
        if (isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1) {
            return true;
        }

        // Verificar se tem permissão para gerenciar outros departamentos
        // Baseado no sistema de escopo de departamentos implementado
        $sql = "SELECT COUNT(*) as count
                FROM adms_users_access_levels AS aual
                LEFT JOIN adms_access_levels_pages AS alp ON alp.adms_access_level_id = aual.adms_access_level_id
                LEFT JOIN adms_pages AS ap ON ap.id = alp.adms_page_id
                WHERE aual.adms_user_id = ?
                AND ap.controller = 'EditStrategicPlan'
                AND alp.permission = 1
                AND alp.department_scope = 'all'";

        $stmt = $this->repository->getConnection()->prepare($sql);
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result['count'] > 0;
    }

    /**
     * Obtém departamentos disponíveis baseado nas permissões do usuário
     */
    private function getAvailableDepartments(bool $canManageAll, array $currentUser): array
    {
        $departmentsRepository = new DepartmentsRepository();
        
        if ($canManageAll) {
            // Usuário pode ver todos os departamentos
            return $departmentsRepository->getAllDepartmentsSelect();
        } else {
            // Usuário só pode ver seu próprio departamento
            return [
                [
                    'id' => $currentUser['user_department_id'],
                    'name' => $currentUser['dep_name']
                ]
            ];
        }
    }

    /**
     * Obtém usuários disponíveis baseado nas permissões do usuário
     */
    private function getAvailableUsers(bool $canManageAll, array $currentUser): array
    {
        $usersRepository = new UsersRepository();
        
        if ($canManageAll) {
            // Usuário pode ver todos os usuários
            return $usersRepository->getAllUsersSelect();
        } else {
            // Usuário só pode ver usuários do seu departamento
            return $this->getUsersByDepartmentQuery($currentUser['user_department_id']);
        }
    }

    /**
     * Obtém usuários por departamento (método privado para uso interno)
     */
    private function getUsersByDepartmentQuery(int $departmentId): array
    {
        $sql = 'SELECT id, name, email 
                FROM adms_users 
                WHERE user_department_id = :department_id 
                AND status = 1
                ORDER BY name ASC';
        $stmt = $this->repository->getConnection()->prepare($sql);
        $stmt->bindValue(':department_id', $departmentId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
} 