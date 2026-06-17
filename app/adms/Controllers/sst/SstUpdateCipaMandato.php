<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstCipaRepository;
use App\adms\Views\Services\LoadViewService;

class SstUpdateCipaMandato
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        if (!$id) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $this->data['item'] = (new SstCipaRepository())->getMandatoById((int) $id);
        if (!$this->data['item']) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $pageElements = ['title_head' => 'Editar mandato CIPA', 'menu' => 'sst-list-cipa-mandatos', 'buttonPermission' => ['SstUpdateCipaMandato']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/cipa/form', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_cipa_form', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $id = (int) ($_POST['id'] ?? 0);
        (new SstCipaRepository())->updateMandato($id, [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-d'),
            'data_fim' => $_POST['data_fim'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
            'observacoes' => $_POST['observacoes'] ?? null,
        ]);
        $_SESSION['msg'] = 'Mandato atualizado.';
        $_SESSION['msg_type'] = 'success';
        header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-cipa-mandato/' . $id);
        exit;
    }
}
