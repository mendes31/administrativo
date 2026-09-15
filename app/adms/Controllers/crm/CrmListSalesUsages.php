<?php

declare(strict_types=1);

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\crm\CrmSalesUsageNatureRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Classifica utilizações SAP (OUSG) usadas no Dashboard de Vendas.
 */
class CrmListSalesUsages
{
    private array $data = [];

    public function index(): void
    {
        $repo = new CrmSalesUsageNatureRepository();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save($repo);
            return;
        }

        $this->data['usages'] = $repo->listAll();
        $this->data['natures'] = CrmSalesUsageNatureRepository::NATURES;
        $this->data['unclassified'] = $repo->countUnclassified();
        $this->data['csrf_token'] = CSRFHelper::generateCSRFToken('form_crm_sales_usages');

        $pageElements = [
            'title_head' => 'Utilizações de venda SAP - CRM',
            'menu' => 'crm-list-sales-usages',
            'buttonPermission' => ['CrmListSalesUsages', 'CrmSalesDashboard'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/crm/sales_usages/list', $this->data);
        $loadView->loadView();
    }

    private function save(CrmSalesUsageNatureRepository $repo): void
    {
        $token = (string) ($_POST['csrf_token'] ?? '');
        if (!CSRFHelper::validateCSRFToken('form_crm_sales_usages', $token)) {
            $_SESSION['msg'] = 'Token de segurança inválido. Recarregue a página e tente de novo.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'crm-list-sales-usages');
            exit;
        }

        $posted = $_POST['natureza'] ?? [];
        if (!is_array($posted)) {
            $posted = [];
        }

        $acao = trim((string) ($_POST['acao'] ?? 'salvar'));
        if ($acao === 'sugerir_entradas') {
            $ok = $repo->applyEntradasSuggestion();
            $_SESSION['msg'] = $ok > 0
                ? "Sugestão aplicada em {$ok} utilização(ões) iniciadas com E: E Dev Venda → venda; demais entradas → ignorar. Revise e salve se quiser ajustar."
                : 'Nenhuma utilização iniciada com E precisava de alteração (já classificadas ou inexistentes).';
            $_SESSION['msg_type'] = $ok > 0 ? 'success' : 'warning';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'crm-list-sales-usages');
            exit;
        }

        $ok = 0;
        foreach ($posted as $id => $natureza) {
            $usageId = (int) $id;
            $nat = trim((string) $natureza);
            if ($usageId === 0 && $id !== '0' && $id !== 0) {
                continue;
            }
            if ($repo->updateNature($usageId, $nat)) {
                $ok++;
            }
        }

        $_SESSION['msg'] = $ok > 0
            ? "Natureza atualizada em {$ok} utilização(ões). Os cards do dashboard mudam na hora (sem re-sincronizar o SAP)."
            : 'Nenhuma utilização foi alterada.';
        $_SESSION['msg_type'] = $ok > 0 ? 'success' : 'warning';
        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'crm-list-sales-usages');
        exit;
    }
}
