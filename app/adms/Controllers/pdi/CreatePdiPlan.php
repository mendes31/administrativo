<?php

declare(strict_types=1);

namespace App\adms\Controllers\pdi;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PerformanceCyclesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\PdiPlanService;
use App\adms\Views\Services\LoadViewService;

class CreatePdiPlan
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $this->loadFormData();

        $pageElements = [
            'title_head' => 'Criar PDI',
            'menu' => 'list-pdi-plans',
            'buttonPermission' => ['ListPdiPlans'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/pdi/create', $this->data);
        $loadView->loadView();
    }

    private function loadFormData(): void
    {
        $usersRepo = new UsersRepository();
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();

        if ($isSuperAdmin) {
            $this->data['employees'] = $usersRepo->getAllUsers(1, 1000);
        } else {
            $userId = $_SESSION['user_id'] ?? 0;
            $allUsers = $usersRepo->getAllUsers(1, 1000, []);
            $this->data['employees'] = array_filter($allUsers, static function ($user) use ($userId) {
                return isset($user['immediate_supervisor']) && (int) $user['immediate_supervisor'] === (int) $userId;
            });
        }

        $this->data['cycles'] = (new PerformanceCyclesRepository())->listLinkable();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_pdi_plan', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-pdi-plan');
            exit;
        }

        $result = (new PdiPlanService())->create($_POST, (int) ($_SESSION['user_id'] ?? 0));
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao criar PDI.';

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">PDI criado com sucesso!</div>';
        GenerateLog::generateLog('info', 'PDI criado.', ['id' => $result['id']]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-pdi-plan/' . $result['id']);
        exit;
    }
}
