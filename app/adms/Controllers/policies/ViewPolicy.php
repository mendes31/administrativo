<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\LogResumoService;
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

        $this->data = [];
        $this->data['policy'] = $policy;
        $returnUrl = $_ENV['URL_ADM'] . 'view-policy/' . $policyId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_policies', $policyId, $returnUrl);

        // Se usuário logado, registrar leitura e buscar status de leitura/ciência para exibir na view
        if (!empty($_SESSION['user_id'])) {
            $repo->upsertRead($policyId, (int)$_SESSION['user_id']);
            // Invalida o cache do navbar para o contador refletir a leitura já nesta página
            \App\adms\Helpers\NavbarLayoutCacheHelper::clear();
            $this->data['read_status'] = $repo->getReadByUser($policyId, (int)$_SESSION['user_id']);
        }

        $pageElements = [
            'title_head'       => 'Visualizar Política Interna',
            'menu' => 'list-policies',
            'buttonPermission' => ['ListPolicies', 'ViewPolicy', 'UpdatePolicy', 'DeletePolicy', 'ResendPolicyPush'],
        ];

        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/view', $this->data);
        $loadView->loadView();
    }
}

