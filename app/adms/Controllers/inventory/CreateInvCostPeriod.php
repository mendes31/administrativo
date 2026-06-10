<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Views\Services\LoadViewService;

class CreateInvCostPeriod
{
    private array|string|null $data = null;

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_create_inv_cost_period', (string)$this->data['form']['csrf_token'])) {
            $this->save();
            return;
        }

        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Cadastrar Período de Custeio',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInvCostPeriods'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/period_create', $this->data);
        $loadView->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'] ?? [];
        $name = trim((string)($form['name'] ?? ''));
        $dateFrom = $this->normalizeDate((string)($form['date_from'] ?? ''));
        $dateTo = $this->normalizeDate((string)($form['date_to'] ?? ''));

        if ($name === '' || $dateFrom === null || $dateTo === null) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Nome e datas são obrigatórios.</div>";
            $this->view();
            return;
        }

        if ($dateFrom > $dateTo) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Data inicial não pode ser maior que a final.</div>";
            $this->view();
            return;
        }

        $repo = new InvCostPeriodsRepository();
        $id = $repo->create([
            'name' => $name,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => trim((string)($form['status'] ?? 'draft')) ?: 'draft',
            'kwh_tariff' => $form['kwh_tariff'] ?? null,
            'notes' => trim((string)($form['notes'] ?? '')) ?: null,
        ]);

        if ($id > 0) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Período cadastrado com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao cadastrar período.</div>";
        $this->view();
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            return $m[3] . '-' . $m[2] . '-' . $m[1];
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
