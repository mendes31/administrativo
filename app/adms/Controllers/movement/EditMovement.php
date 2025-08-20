<?php

namespace App\adms\Controllers\movement;

use App\adms\Models\Repository\FinancialMovementsRepository;
use App\adms\Models\Repository\BanksRepository;
use App\adms\Models\Repository\PaymentMethodsRepository;
use App\adms\Views\Services\LoadViewService;

class EditMovement
{
    private array|string|null $data = null;

    public function index($id_mov)
    {
        // Buscar movimento usando PartialValuesRepository para trazer todos os campos e joins
        $repoPartial = new \App\adms\Models\Repository\PartialValuesRepository();
        $movement = $repoPartial->getMovementById($id_mov);
        if (!$movement) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Movimento não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-payments');
            exit;
        }

        // Carregar bancos e formas de pagamento para os selects
        $banksRepo = new BanksRepository();
        $paymentMethodsRepo = new PaymentMethodsRepository();
        $this->data['movement'] = $movement;
        $this->data['listBanks'] = $banksRepo->getAllBanksSelect();
        $this->data['listPaymentMethods'] = $paymentMethodsRepo->getAllPaymentMethodsSelect();

        // Se for POST, processa atualização
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // $valorPago = str_replace([',', '.'], ['', '.'], $_POST['movement_value'] ?? ''); // NÃO ATUALIZAR MAIS
            $formaPgto = $_POST['method_id'] ?? null;
            $banco = $_POST['bank_id'] ?? null;
            $userId = $_SESSION['user_id'] ?? null;

            // Atualização permanece usando FinancialMovementsRepository
            $repo = new FinancialMovementsRepository();
            $update = $repo->updateMovement($id_mov, [
                // 'movement_value' => $valorPago, // NÃO ATUALIZAR MAIS
                'method_id' => $formaPgto,
                'bank_id' => $banco,
                'user_id' => $userId,
            ]);
            if ($update) {
                $_SESSION['msg'] = '<div class="alert alert-success">Movimento atualizado com sucesso!</div>';
                header('Location: ' . $_ENV['URL_ADM'] . 'view-pay/' . $movement['id_mov']);
                exit;
            } else {
                $_SESSION['msg'] = '<div class="alert alert-danger">Erro ao atualizar movimento!</div>';
            }
        }

        $pageElements = [
            'title_head' => 'Editar Movimento',
            'menu' => 'list-payments',
            'buttonPermission' => [],
        ];
        $pageLayoutService = new \App\adms\Controllers\Services\PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/movement/editMovement', $this->data);
        $loadView->loadView();
    }
} 