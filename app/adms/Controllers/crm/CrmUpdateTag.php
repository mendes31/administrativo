<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmTagsRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller para editar Tag CRM
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmUpdateTag
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID da tag não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update((int)$id);
            return;
        }

        // Buscar tag
        $tagsRepo = new CrmTagsRepository();
        $this->data['tag'] = $tagsRepo->getTagById((int)$id);

        if (!$this->data['tag']) {
            $_SESSION['msg'] = "Tag não encontrada.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
            exit;
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
            'title_head' => 'Editar Tag - CRM',
            'menu' => 'crm-list-tags',
            'buttonPermission' => ['CrmUpdateTag'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/tags/form", $this->data);
        $loadView->loadView();
    }

    private function update(int $id): void
    {
        $data = [
            'id' => $id,
            'name' => trim($_POST['name'] ?? ''),
            'color' => $_POST['color'] ?? '#007bff',
            'description' => $_POST['description'] ?? null,
        ];

        // Validações
        if (empty($data['name'])) {
            $_SESSION['msg'] = "O nome da tag é obrigatório.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-update-tag/" . $id);
            exit;
        }

        $tagsRepo = new CrmTagsRepository();
        $result = $tagsRepo->updateTag($data);

        if ($result) {
            $_SESSION['msg'] = "Tag atualizada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao atualizar tag.";
            $_SESSION['msg_type'] = "danger";
        }
        
        header("Location: " . $_ENV['URL_ADM'] . "crm-list-tags");
        exit;
    }
}

