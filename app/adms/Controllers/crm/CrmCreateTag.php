<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para criar Tag CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmCreateTag
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
            return;
        }

        // Cores disponíveis
        $this->data['colors'] = [
            '#007bff' => 'Azul',
            '#28a745' => 'Verde',
            '#dc3545' => 'Vermelho',
            '#ffc107' => 'Amarelo',
            '#17a2b8' => 'Ciano',
            '#6f42c1' => 'Roxo',
            '#fd7e14' => 'Laranja',
            '#6c757d' => 'Cinza',
            '#e83e8c' => 'Rosa',
            '#20c997' => 'Verde-água',
        ];

        // Layout
        $pageElements = [
            'title_head' => 'Nova Tag - CRM',
            'menu' => 'crm-list-tags',
            'buttonPermission' => ['CrmCreateTag'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/tags/form", $this->data);
        $loadView->loadView();
    }

    private function create(): void
    {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'color' => $_POST['color'] ?? '#007bff',
            'description' => $_POST['description'] ?? null,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome da tag é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-tag");
            exit;
        }

        $tagsRepo = new CrmTagsRepository();
        $result = $tagsRepo->createTag($data);

        if ($result) {
            $_SESSION['msg'] = "Tag criada com sucesso!";
            $_SESSION['msg_type'] = "success";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
        } else {
            $_SESSION['msg'] = "Erro ao criar tag.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-create-tag");
        }
        exit;
    }
}

