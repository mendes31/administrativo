<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Views\Services\LoadViewService;

final class TiSistemasCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_sistema', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save();
            return;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $this->data['tipos'] = TiSistemaRepository::TIPOS;
        $pageElements = [
            'title_head' => 'Cadastrar Sistema (TI)',
            'menu' => 'ti-sistemas',
            'buttonPermission' => ['TiSistemas'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/sistemas/create', $this->data))->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'];
        $errors = [];
        $nome = trim((string) ($form['nome'] ?? ''));
        if ($nome === '') {
            $errors[] = 'Informe o nome do sistema.';
        }
        $tipo = (string) ($form['tipo'] ?? 'outro');
        if (!in_array($tipo, TiSistemaRepository::TIPOS, true)) {
            $errors[] = 'Tipo inválido.';
        }
        $status = (string) ($form['status'] ?? TiSistemaRepository::STATUS_ATIVO);
        if (!in_array($status, [TiSistemaRepository::STATUS_ATIVO, TiSistemaRepository::STATUS_INATIVO], true)) {
            $errors[] = 'Status inválido.';
        }

        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->viewForm();
            return;
        }

        $id = (new TiSistemaRepository())->create([
            'codigo' => $form['codigo'] ?? null,
            'nome' => $nome,
            'descricao' => $form['descricao'] ?? null,
            'tipo' => $tipo,
            'localizacao' => $form['localizacao'] ?? null,
            'observacoes' => $form['observacoes'] ?? null,
            'status' => $status,
        ], (int) ($_SESSION['user_id'] ?? 0));

        if ($id) {
            $_SESSION['msg'] = 'Sistema cadastrado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . $id);
            return;
        }

        $this->data['errors'] = ['Não foi possível cadastrar o sistema. Verifique se o código já existe.'];
        $this->viewForm();
    }
}
