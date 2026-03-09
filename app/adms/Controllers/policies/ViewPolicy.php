<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Views\Services\LoadViewService;

class ViewPolicy
{
    private array|string|null $data = null;

    public function index(string $id = ''): void
    {
        $policyId = (int) ($id ?: ($_GET['id'] ?? 0));
        if ($policyId <= 0) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política inválida.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);

        if (!$policy) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Política não encontrada.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-policies');
            return;
        }

        $this->data['policy'] = $policy;

        $pageElements = [
            'title_head'       => 'Visualizar Política Interna',
            'menu'             => 'gestao_pessoas',
            'buttonPermission' => ['ListPolicies', 'ViewPolicy', 'UpdatePolicy', 'DeletePolicy'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/view', $this->data);
        $loadView->loadView();
    }
}

