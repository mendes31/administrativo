<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Views\Services\LoadViewService;

class UpdateInvCostPeriod
{
    private array|string|null $data = null;

    public function index(int|string $id = 0): void
    {
        $periodId = (int)$id;
        if ($periodId <= 0) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período inválido.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        $repo = new InvCostPeriodsRepository();
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);

        if (isset($this->data['form']['csrf_token']) && CSRFHelper::validateCSRFToken('form_update_inv_cost_period', (string)$this->data['form']['csrf_token'])) {
            $this->save($periodId, $repo);
            return;
        }

        $period = $repo->getOne($periodId);
        if ($period === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        $this->data['period'] = $period;
        $this->view();
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Período de Custeio',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInvCostPeriods', 'ViewInvCostPeriod'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data ?? [], $pls->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/inventory/costs/period_edit', $this->data);
        $loadView->loadView();
    }

    private function save(int $periodId, InvCostPeriodsRepository $repo): void
    {
        $form = $this->data['form'] ?? [];
        $period = $repo->getOne($periodId);
        if ($period === false) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Período não encontrado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-cost-periods');
            return;
        }

        $isClosed = (string)($period['status'] ?? '') === 'closed';
        $name = trim((string)($form['name'] ?? ''));
        $dateFrom = $this->normalizeDate((string)($form['date_from'] ?? ''));
        $dateTo = $this->normalizeDate((string)($form['date_to'] ?? ''));

        if ($name === '' || $dateFrom === null || $dateTo === null) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Nome e datas são obrigatórios.</div>";
            $this->data['period'] = $period;
            $this->view();
            return;
        }

        if ($dateFrom > $dateTo) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Data inicial não pode ser maior que a final.</div>";
            $this->data['period'] = $period;
            $this->view();
            return;
        }

        $newStatus = trim((string)($form['status'] ?? $period['status'] ?? 'draft')) ?: 'draft';
        if ($isClosed && $newStatus !== 'closed') {
            $_SESSION['msg'] = "<div class='alert alert-warning'>Período fechado: apenas observações e tarifa kWh podem ser ajustadas se necessário.</div>";
            $newStatus = 'closed';
        }

        $payload = [
            'name' => $name,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status' => $newStatus,
            'kwh_tariff' => $form['kwh_tariff'] ?? null,
            'energy_kwh_hvac' => $form['energy_kwh_hvac'] ?? null,
            'energy_kwh_production_common' => $form['energy_kwh_production_common'] ?? null,
            'energy_kwh_direct_cfix' => $form['energy_kwh_direct_cfix'] ?? null,
            'energy_auto_split' => array_key_exists('energy_auto_split', $form) ? !empty($form['energy_auto_split']) : !empty($period['energy_auto_split']),
            'notes' => trim((string)($form['notes'] ?? '')) ?: null,
        ];

        if ($isClosed) {
            $payload['date_from'] = (string)$period['date_from'];
            $payload['date_to'] = (string)$period['date_to'];
            $payload['name'] = (string)$period['name'];
            $payload['energy_kwh_hvac'] = $period['energy_kwh_hvac'] ?? null;
            $payload['energy_kwh_production_common'] = $period['energy_kwh_production_common'] ?? null;
            $payload['energy_kwh_direct_cfix'] = $period['energy_kwh_direct_cfix'] ?? null;
            $payload['energy_auto_split'] = !empty($period['energy_auto_split']);
        }

        if ($repo->update($periodId, $payload)) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Período atualizado.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'view-inventory-cost-period/' . $periodId);
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger'>Erro ao atualizar período.</div>";
        $this->data['period'] = $repo->getOne($periodId) ?: $period;
        $this->view();
    }

    private function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $value;
        }
        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
}
