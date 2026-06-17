<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SstCipaRepository;
use App\adms\Views\Services\LoadViewService;

class SstCreateCipaMandato
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->save();
            return;
        }
        $this->data['item'] = ['status' => 'Ativo', 'data_inicio' => date('Y-m-d')];
        $pageElements = ['title_head' => 'Novo mandato CIPA', 'menu' => 'sst-list-cipa-mandatos', 'buttonPermission' => ['SstCreateCipaMandato']];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/cipa/form', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('sst_cipa_form', $_POST['csrf_token'] ?? '')) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-cipa-mandatos');
            exit;
        }
        $data = [
            'titulo' => trim((string) ($_POST['titulo'] ?? '')),
            'data_inicio' => $_POST['data_inicio'] ?? date('Y-m-d'),
            'data_fim' => $_POST['data_fim'] ?? null,
            'status' => $_POST['status'] ?? 'Ativo',
            'observacoes' => $_POST['observacoes'] ?? null,
        ];
        if ($data['titulo'] === '') {
            $_SESSION['msg'] = 'Informe o título.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-create-cipa-mandato');
            exit;
        }
        $id = (new SstCipaRepository())->createMandato($data);
        $_SESSION['msg'] = $id ? 'Mandato criado.' : 'Erro ao salvar.';
        $_SESSION['msg_type'] = $id ? 'success' : 'danger';
        header('Location: ' . $_ENV['URL_ADM'] . ($id ? 'sst-view-cipa-mandato/' . $id : 'sst-create-cipa-mandato'));
        exit;
    }
}
