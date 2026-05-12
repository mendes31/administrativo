<?php

namespace App\adms\Controllers\inventory;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\inventory\InvOperationsRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class UpdateInventoryOperation
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT);
        if (isset($this->data['form']['csrf_token']) &&
            CSRFHelper::validateCSRFToken('form_update_inventory_operation', $this->data['form']['csrf_token'])) {
            $this->save((int)$id);
        } else {
            $repo = new InvOperationsRepository();
            $this->data['form'] = $repo->getOne((int)$id);
            if (!$this->data['form']) {
                GenerateLog::generateLog('error', 'Operação não encontrada', ['id' => (int)$id]);
                $_SESSION['error'] = 'Operação não encontrada!';
                header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
                return;
            }
            $this->view();
        }
    }

    private function view(): void
    {
        $pageElements = [
            'title_head' => 'Editar Operação de Produção',
            'menu' => 'estoque',
            'buttonPermission' => ['ListInventoryOperations'],
        ];
        $pls = new PageLayoutService();
        $this->data = array_merge($this->data, $pls->configurePageElements($pageElements));

        $oid = (int) ($this->data['form']['id'] ?? 0);
        if ($oid > 0) {
            $returnUrl = $_ENV['URL_ADM'] . 'update-inventory-operation/' . $oid;
            $this->data['log_resumo'] = LogResumoService::getResumo('inv_operations', $oid, $returnUrl);
        }

        $loadView = new LoadViewService('adms/Views/inventory/operations/update', $this->data);
        $loadView->loadView();
    }

    private function save(int $id): void
    {
        $form = $this->data['form'] ?? [];
        $errors = [];
        if (empty($form['name'])) {
            $errors['name'] = 'Nome é obrigatório.';
        }

        $repo = new InvOperationsRepository();
        if (!empty($form['code']) && $repo->existsCode(trim($form['code']), $id)) {
            $errors['code'] = 'Código já existente.';
        }

        if ($errors) {
            $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Verifique os campos obrigatórios.</div>";
            $this->data['errors'] = $errors;
            $this->view();
            return;
        }

        $ok = $repo->update($id, [
            'code' => trim((string)($form['code'] ?? '')),
            'name' => trim((string)$form['name']),
            'description' => trim((string)($form['description'] ?? '')),
            'default_cost_per_hour' => (float)($form['default_cost_per_hour'] ?? 0),
            'active' => isset($form['active']) ? 1 : 0,
        ]);

        if ($ok) {
            $_SESSION['msg'] = "<div class='alert alert-success' role='alert'>Operação atualizada com sucesso.</div>";
            header('Location: ' . $_ENV['URL_ADM'] . 'list-inventory-operations');
            return;
        }

        $_SESSION['msg'] = "<div class='alert alert-danger' role='alert'>Erro ao atualizar a operação.</div>";
        $this->data['form'] = $repo->getOne($id) ?: [];
        $this->view();
    }
}

