<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;
use App\adms\Models\Services\WhistleblowingProtocolService;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingCreateCommittee
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }

        $usersRepo = new UsersRepository();
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        $this->data['categories'] = WhistleblowingProtocolService::CATEGORIES;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_committee');

        $pageElements = [
            'title_head' => 'Novo Comitê — Canal de Denúncias',
            'menu' => 'list-whistleblowing-committees',
            'buttonPermission' => ['WhistleblowingCreateCommittee'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/committees/form', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_committee', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-whistleblowing-committee');
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['msg'] = 'O nome do comitê é obrigatório.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-whistleblowing-committee');
            exit;
        }

        $memberIds = array_map('intval', $_POST['member_ids'] ?? []);
        $categories = array_map('strval', $_POST['categories'] ?? []);

        $repo = new WhistleblowingCommitteesRepository();
        $id = $repo->createCommittee($name, trim((string) ($_POST['description'] ?? '')) ?: null, $memberIds, $categories);

        $_SESSION['msg'] = $id ? 'Comitê cadastrado com sucesso.' : 'Erro ao cadastrar comitê.';
        $_SESSION['msg_type'] = $id ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-whistleblowing-committees');
        exit;
    }
}
