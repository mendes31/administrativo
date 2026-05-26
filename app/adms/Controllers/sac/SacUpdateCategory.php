<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class SacUpdateCategory
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da categoria não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $this->data['category'] = $categoriesRepo->getCategoryById((int)$id);

        if (!$this->data['category']) {
            $_SESSION['msg'] = "Categoria não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
            exit;
        }

        $pageElements = [
            'title_head' => 'Editar Categoria - SAC',
            'menu' => 'sac-list-categories',
            'buttonPermission' => ['SacUpdateCategory'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/categories/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_category_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token de segurança inválido. Tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-category/" . $id);
            exit;
        }

        $data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'color' => $_POST['color'] ?? '',
            'icon' => $_POST['icon'] ?? '',
            'default_sla_response_hours' => $_POST['default_sla_response_hours'] ?? null,
            'default_sla_resolution_hours' => $_POST['default_sla_resolution_hours'] ?? null,
            'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
            'display_order' => isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0,
        ];

        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome da categoria é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-category/" . $id);
            exit;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $result = $categoriesRepo->updateCategory($id, $data);

        if ($result) {
            $_SESSION['msg'] = "Categoria atualizada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
        } else {
            $_SESSION['msg'] = "Erro ao atualizar categoria.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-update-category/" . $id);
        }
        exit;
    }
}
