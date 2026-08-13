<?php

declare(strict_types=1);

namespace App\adms\Controllers\cashFlow;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\cashFlow\FinCashAccountRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateFinCashAccount
{
    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        $repo = new FinCashAccountRepository();

        if (isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_update_fin_cash_account', $this->data['form']['csrf_token'])
        ) {
            $this->save($repo);
            return;
        }

        $this->data['form'] = $repo->getById((int) $id);
        if (!$this->data['form']) {
            GenerateLog::generateLog('error', 'Conta financeira SAP não encontrada', ['id' => (int) $id]);
            $_SESSION['error'] = 'Conta financeira não encontrada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-accounts');
            return;
        }
        $this->viewForm();
    }

    private function viewForm(): void
    {
        $pageElements = [
            'title_head' => 'Editar Conta Financeira SAP',
            'menu' => 'list-fin-cash-accounts',
            'buttonPermission' => ['ListFinCashAccounts'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pageLayoutService->configurePageElements($pageElements));
        $loadView = new LoadViewService('adms/Views/cashFlow/accountForm', $this->data);
        $loadView->loadView();
    }

    private function save(FinCashAccountRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        $id = (int) ($form['id'] ?? 0);
        $types = ['BANK', 'CASH', 'INVESTMENT', 'TRANSIT', 'OTHER'];
        $type = strtoupper((string) ($form['account_type'] ?? 'BANK'));
        if (!in_array($type, $types, true)) {
            $type = 'BANK';
        }
        $ok = $repo->update($id, [
            'description' => trim((string) ($form['description'] ?? '')),
            'account_type' => $type,
            'credit_limit' => $this->parseMoney((string) ($form['credit_limit'] ?? '0')),
            'active' => !empty($form['active']),
            'include_in_cash_flow' => !empty($form['include_in_cash_flow']),
            'include_in_availability' => !empty($form['include_in_availability']),
        ]);
        if ($ok) {
            $_SESSION['success'] = 'Conta financeira atualizada.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-fin-cash-accounts');
            return;
        }
        $_SESSION['error'] = 'Não foi possível atualizar a conta.';
        $this->data['form'] = $repo->getById($id) ?: $form;
        $this->viewForm();
    }

    private function parseMoney(string $raw): float
    {
        $raw = trim(str_replace(['R$', ' '], '', $raw));
        if ($raw === '') {
            return 0.0;
        }
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }
        return (float) $raw;
    }
}
