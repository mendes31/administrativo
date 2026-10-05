<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Services\PoliticaRelatorioService;
use App\adms\Views\Services\LoadViewService;

class RelatorioPolicy
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        $policyId = $this->data['form']['policy_id'] ?? $_GET['policy_id'] ?? null;

        if (!empty($policyId)) {
            $this->gerarRelatorio($policyId);
        } else {
            $this->viewRelatorio();
        }
    }

    private function viewRelatorio(): void
    {
        $this->data['title_head'] = 'Relatório de Políticas Internas';

        $repo = new PoliciesRepository();
        $this->data['policies'] = $repo->getAllPolicies(1, 1000, []);

        $pageElements = [
            'title_head' => 'Relatório de Políticas Internas',
            'menu' => 'list-policies',
            'buttonPermission' => [],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/relatorio', $this->data);
        $loadView->loadView();
    }

    private function gerarRelatorio(string $policyId): void
    {
        $policyId = (int) $policyId;

        $repo = new PoliciesRepository();
        $policy = $repo->getPolicyById($policyId);
        if (!$policy) {
            $_SESSION['error'] = 'Política não encontrada!';
            $this->viewRelatorio();
            return;
        }

        $relatorio = (new PoliticaRelatorioService())->build($policyId, $policy);

        $this->data['policy'] = $policy;
        $this->data['dados_relatorio'] = $relatorio['ativos'];
        $this->data['dados_inativos_historico'] = $relatorio['inativos_historico'];
        $this->data['excluidos_sem_historico'] = $relatorio['excluidos_sem_historico'];
        $this->data['relatorio_kpis'] = $relatorio['kpis'];
        $this->data['requires_ack'] = $relatorio['requires_ack'];
        $this->data['title_head'] = 'Relatório: ' . ($policy['titulo'] ?? '');

        $pageElements = [
            'title_head' => 'Relatório de Políticas Internas',
            'menu' => 'list-policies',
            'buttonPermission' => [],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/relatorioResultado', $this->data);
        $loadView->loadView();
    }
}
