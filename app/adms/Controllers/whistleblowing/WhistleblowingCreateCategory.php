<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WhistleblowingCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingCreateCategory
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }

        $this->data['category'] = null;
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_category');

        $pageElements = [
            'title_head' => 'Nova classificação — Canal de Denúncias',
            'menu' => 'list-whistleblowing-categories',
            'buttonPermission' => ['WhistleblowingCreateCategory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/categories/form', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_category', $_POST['csrf_token'] ?? '')) {
            $this->redirectWithMessage('Token CSRF inválido.', 'danger', 'create-whistleblowing-category');
        }

        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $this->redirectWithMessage('O nome da classificação é obrigatório.', 'danger', 'create-whistleblowing-category');
        }

        $repo = new WhistleblowingCategoriesRepository();
        if ($repo->nameExists($name)) {
            $this->redirectWithMessage('Já existe uma classificação com este nome.', 'danger', 'create-whistleblowing-category');
        }

        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $description = trim((string) ($_POST['description'] ?? ''));
        $id = $repo->create($name, $sortOrder, $description !== '' ? $description : null);

        $this->redirectWithMessage(
            $id ? 'Classificação cadastrada com sucesso.' : 'Erro ao cadastrar classificação.',
            $id ? 'success' : 'danger',
            'list-whistleblowing-categories'
        );
    }

    private function redirectWithMessage(string $msg, string $type, string $route): void
    {
        $_SESSION['msg'] = $msg;
        $_SESSION['msg_type'] = $type;
        header('Location: ' . $_ENV['URL_ADM'] . $route);
        exit;
    }
}
