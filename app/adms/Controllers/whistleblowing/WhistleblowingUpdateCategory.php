<?php

declare(strict_types=1);

namespace App\adms\Controllers\whistleblowing;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\WhistleblowingCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class WhistleblowingUpdateCategory
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $categoryId = (int) ($id ?? 0);
        if ($categoryId <= 0) {
            $this->redirectWithMessage('Classificação não informada.', 'danger', 'list-whistleblowing-categories');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save($categoryId);
            return;
        }

        $repo = new WhistleblowingCategoriesRepository();
        $category = $repo->getById($categoryId);
        if (!$category) {
            $this->redirectWithMessage('Classificação não encontrada.', 'danger', 'list-whistleblowing-categories');
        }

        $this->data['category'] = $category;
        $this->data['reports_count'] = $repo->countReportsByName((string) ($category['name'] ?? ''));
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('whistleblowing_category');

        $pageElements = [
            'title_head' => 'Editar classificação — Canal de Denúncias',
            'menu' => 'list-whistleblowing-categories',
            'buttonPermission' => ['WhistleblowingUpdateCategory'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/whistleblowing/categories/form', $this->data);
        $loadView->loadView();
    }

    private function save(int $categoryId): void
    {
        if (!CSRFHelper::validateCSRFToken('whistleblowing_category', $_POST['csrf_token'] ?? '')) {
            $this->redirectWithMessage('Token CSRF inválido.', 'danger', 'update-whistleblowing-category/' . $categoryId);
        }

        $repo = new WhistleblowingCategoriesRepository();
        $existing = $repo->getById($categoryId);
        if (!$existing) {
            $this->redirectWithMessage('Classificação não encontrada.', 'danger', 'list-whistleblowing-categories');
        }

        $oldName = (string) ($existing['name'] ?? '');
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            $this->redirectWithMessage('O nome da classificação é obrigatório.', 'danger', 'update-whistleblowing-category/' . $categoryId);
        }

        if ($repo->nameExists($name, $categoryId)) {
            $this->redirectWithMessage('Já existe outra classificação com este nome.', 'danger', 'update-whistleblowing-category/' . $categoryId);
        }

        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        $isActive = !empty($_POST['is_active']);
        $description = trim((string) ($_POST['description'] ?? ''));

        $reportsCount = $repo->countReportsByName($oldName);
        if ($reportsCount > 0 && $name !== $oldName) {
            $repo->renameReportsCategory($oldName, $name);
        }

        $ok = $repo->update(
            $categoryId,
            $name,
            $sortOrder,
            $isActive,
            $description !== '' ? $description : null,
            !empty($_POST['sla_first_response_hours']) ? (int) $_POST['sla_first_response_hours'] : null
        );

        $this->redirectWithMessage(
            $ok ? 'Classificação atualizada com sucesso.' : 'Erro ao atualizar classificação.',
            $ok ? 'success' : 'danger',
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
