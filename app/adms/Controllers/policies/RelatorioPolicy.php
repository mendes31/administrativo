<?php

namespace App\adms\Controllers\policies;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\PoliciesRepository;
use App\adms\Models\Repository\UsersRepository;
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
        // Buscar uma lista grande o suficiente de políticas para o select
        $this->data['policies'] = $repo->getAllPolicies(1, 1000, []);

        $pageElements = [
            'title_head' => 'Relatório de Políticas Internas',
            'menu'       => 'gestao_pessoas',
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

        // Interpreta requires_ack em boolean (1/0, true/false, 'Sim'/'Não')
        $requiresAck = false;
        if (isset($policy['requires_ack'])) {
            $val = $policy['requires_ack'];
            $requiresAck = ($val === 1 || $val === '1' || $val === true || $val === 'true' || $val === 'Sim' || $val === 'sim');
        }

        // Usuários para o relatório:
        // - Todos ativos
        // - E também inativos que já deram ciência desta política
        $usuarios = $repo->getUsersForPolicyReport($policyId);

        $dadosRelatorio = [];
        foreach ($usuarios as $usuario) {
            $visualizacao = $repo->getReadByUser($policyId, (int) $usuario['id']);

            $dadosRelatorio[] = [
                'usuario_id'        => $usuario['id'],
                'usuario_nome'      => $usuario['name'],
                'usuario_email'     => $usuario['email'],
                'visualizou'        => $visualizacao ? 'SIM' : 'NÃO',
                'data_visualizacao' => $visualizacao && $visualizacao['read_at']
                    ? date('d/m/Y H:i:s', strtotime($visualizacao['read_at']))
                    : '-',
                'esta_ciente'       => $requiresAck
                    ? ($visualizacao && !empty($visualizacao['acknowledged']) ? 'SIM' : 'NÃO')
                    : 'N/A',
                'data_ciencia'      => $requiresAck && $visualizacao && !empty($visualizacao['ack_at'])
                    ? date('d/m/Y H:i:s', strtotime($visualizacao['ack_at']))
                    : '-',
                'status'            => $this->getStatus($visualizacao, $requiresAck),
            ];
        }

        $this->data['policy'] = $policy;
        $this->data['dados_relatorio'] = $dadosRelatorio;
        $this->data['title_head'] = 'Relatório: ' . ($policy['titulo'] ?? '');

        $pageElements = [
            'title_head' => 'Relatório de Políticas Internas',
            'menu'       => 'gestao_pessoas',
            'buttonPermission' => [],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/policies/relatorioResultado', $this->data);
        $loadView->loadView();
    }

    private function getStatus(?array $visualizacao, bool $requiresAck): string
    {
        if (!$visualizacao) {
            return 'PENDENTE';
        }

        if ($requiresAck) {
            if (empty($visualizacao['acknowledged'])) {
                return 'VISUALIZOU MAS NÃO CIENTE';
            }
            return 'CIENTE';
        }

        return 'VISUALIZOU';
    }
}

