<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstEquipamentoTiposRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateEquipamentoTipo
{
    private array $data = [];

    public function index(string|int $id = 0): void
    {
        $id = (int) $id;
        $repo = new SstEquipamentoTiposRepository();
        $item = $repo->getById($id);
        if (!$item) {
            $_SESSION['msg'] = 'Tipo não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-equipamento-tipos');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }
        $this->data['item'] = $item;
        $pageElements = [
            'title_head' => 'Editar tipo de equipamento - SST',
            'menu' => 'sst-list-equipamento-tipos',
            'buttonPermission' => ['SstUpdateEquipamentoTipo'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/equipamentos/tipo_form', $this->data))->loadView();
    }

    private function update(int $id): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_equipamento_tipo_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-update-equipamento-tipo/' . $id);
            exit;
        }
        $ok = (new SstEquipamentoTiposRepository())->update($id, [
            'nome' => $_POST['nome'] ?? '',
            'codigo' => $_POST['codigo'] ?? '',
            'descricao' => $_POST['descricao'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
        ]);
        $_SESSION['msg'] = $ok ? 'Tipo atualizado.' : 'Erro ao atualizar.';
        $_SESSION['msg_type'] = $ok ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-equipamento-tipo/' . $id);
        exit;
    }
}
