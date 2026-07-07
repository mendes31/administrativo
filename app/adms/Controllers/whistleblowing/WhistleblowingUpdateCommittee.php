<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Repository\WhistleblowingCommitteesRepository;
use App\adms\Models\Services\WhistleblowingProtocolService;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingUpdateCommittee
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-whistleblowing-committees');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save((int) $id);
            return;
        }

        $repo = new WhistleblowingCommitteesRepository();
        $committee = $repo->getCommitteeById((int) $id);
        if (!$committee) {
            $_SESSION['msg'] = 'Comitê não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-whistleblowing-committees');
            exit;
        }

        $usersRepo = new UsersRepository();
        $this->data['committee'] = $committee;
        $this->data['member_ids'] = $repo->getMemberIds((int) $id);
        $this->data['selected_categories'] = $repo->getCategories((int) $id);
        $this->data['users'] = $usersRepo->getAllUsersSelect();
        $this->data['categories'] = WhistleblowingProtocolService::CATEGORIES;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_committee');

        $pageElements = [
            'title_head' => 'Editar Comitê — Canal de Denúncias',
            'menu' => 'list-whistleblowing-committees',
            'buttonPermission' => ['WhistleblowingUpdateCommittee'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/committees/form', $this->data);
        $loadView->loadView();
    }

    private function save(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_committee', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token CSRF inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-whistleblowing-committee/' . $id);
            exit;
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['msg'] = 'O nome do comitê é obrigatório.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'update-whistleblowing-committee/' . $id);
            exit;
        }

        $repo = new WhistleblowingCommitteesRepository();
        $ok = $repo->updateCommittee(
            $id,
            $name,
            trim((string) ($_POST['description'] ?? '')) ?: null,
            isset($_POST['is_active']),
            array_map('intval', $_POST['member_ids'] ?? []),
            array_map('strval', $_POST['categories'] ?? [])
        );

        $_SESSION['msg'] = $ok ? 'Comitê atualizado com sucesso.' : 'Erro ao atualizar comitê.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-whistleblowing-committees');
        exit;
    }
}
