<?php

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Repository\ApprovalDelegationsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Views\Services\LoadViewService;

class CreateApprovalDelegation
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersForSelect();
        $this->data['current_user_id'] = (int) ($_SESSION['user_id'] ?? 0);

        $pageElements = [
            'title_head' => 'Nova Delegação de Aprovação',
            'menu' => 'list-approval-delegations',
            'buttonPermission' => [
                'ListApprovalDelegations',
                'CreateApprovalDelegation',
            ],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/portal/create_approval_delegation', $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_approval_delegation', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-approval-delegation');
            exit;
        }

        $actorId = (int) ($_SESSION['user_id'] ?? 0);
        $isFull = UserAccessHelper::hasFullSystemAccess();
        $delegatorId = (int) ($_POST['delegator_user_id'] ?? 0);
        $delegateId = (int) ($_POST['delegate_user_id'] ?? 0);

        if (!$isFull) {
            $delegatorId = $actorId;
        }

        $startsAt = trim((string) ($_POST['starts_at'] ?? ''));
        $endsAt = trim((string) ($_POST['ends_at'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if ($delegatorId <= 0 || $delegateId <= 0 || $startsAt === '' || $endsAt === '') {
            $_SESSION['error'] = 'Preencha gestor, substituto e período.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-approval-delegation');
            exit;
        }

        if ($delegatorId === $delegateId) {
            $_SESSION['error'] = 'O substituto deve ser diferente do gestor.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-approval-delegation');
            exit;
        }

        if (strtotime($endsAt) < strtotime($startsAt)) {
            $_SESSION['error'] = 'A data final deve ser posterior à inicial.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-approval-delegation');
            exit;
        }

        $repo = new ApprovalDelegationsRepository();
        $id = $repo->create([
            'delegator_user_id' => $delegatorId,
            'delegate_user_id' => $delegateId,
            'starts_at' => date('Y-m-d H:i:s', strtotime($startsAt)),
            'ends_at' => date('Y-m-d H:i:s', strtotime($endsAt)),
            'notes' => $notes !== '' ? $notes : null,
            'created_by' => $actorId,
        ]);

        if ($id > 0) {
            $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Delegação criada com sucesso.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-approval-delegations');
            exit;
        }

        $_SESSION['error'] = 'Não foi possível criar a delegação.';
        header('Location: ' . $_ENV['URL_ADM'] . 'create-approval-delegation');
        exit;
    }
}
