<?php

namespace App\adms\Controllers\financialReports;

use App\adms\Models\Repository\FinancialMovementsRepository;
use App\adms\Views\Services\LoadViewService;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\PaymentMethodsRepository;

class Movements
{
    private array|string|null $data = null;

    public function index(): void
    {
        $filtros = [
            'data_type' => $_GET['data_type'] ?? 'created_at',
            'data_inicial' => $_GET['data_inicial'] ?? '',
            'data_final' => $_GET['data_final'] ?? '',
            'bank_id' => $_GET['bank_id'] ?? '',
            'method_id' => $_GET['method_id'] ?? '',
            'type' => $_GET['type'] ?? '',
        ];
        $repo = new FinancialMovementsRepository();
        $this->data['movements'] = $repo->getMovements($filtros);
        $this->data['form'] = $filtros;

        // Filtros para o total (sem data)
        $filtrosTotal = $filtros;
        unset($filtrosTotal['data_inicial'], $filtrosTotal['data_final']);
        $movsTotal = $repo->getMovements($filtrosTotal);
        $totalGeral = 0;
        foreach ($movsTotal as $mov) {
            $valor = (float)$mov['movement_value'];
            if (strtolower($mov['type']) === 'saída') {
                $totalGeral -= $valor;
            } else {
                $totalGeral += $valor;
            }
        }
        $this->data['totalGeral'] = $totalGeral;

        // Carregar bancos e formas de pagamento para os filtros
        $banksRepo = new BanksRepository();
        $this->data['listBanks'] = $banksRepo->getAllBanksSelect();
        $paymentMethodsRepo = new PaymentMethodsRepository();
        $this->data['listPaymentMethods'] = $paymentMethodsRepo->getAllPaymentMethodsSelect();

        $pageElements = [
            'title_head' => 'Rel Extrato Caixa',
            'menu' => 'movements',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new \App\adms\Controllers\Services\PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/financialReports/list', $this->data);
        $loadView->loadView();
    }
} 