<?php

namespace App\adms\Controllers\sac;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacCategoriesRepository;
use App\adms\Views\Services\LoadViewService;

class SacCreateCategory
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        $pageElements = [
            'title_head' => 'Nova Categoria - SAC',
            'menu' => 'sac-list-categories',
            'buttonPermission' => ['SacCreateCategory'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/sac/categories/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('sac_category_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token de segurança inválido. Tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-category");
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
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-category");
            exit;
        }

        $categoriesRepo = new SacCategoriesRepository();
        $categoryId = $categoriesRepo->createCategory($data);

        if ($categoryId) {
            $_SESSION['msg'] = "Categoria cadastrada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-categories");
        } else {
            $_SESSION['msg'] = "Erro ao cadastrar categoria.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-create-category");
        }
        exit;
    }
}
