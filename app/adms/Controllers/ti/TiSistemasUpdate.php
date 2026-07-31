<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Views\Services\LoadViewService;

final class TiSistemasUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $sistemaId = (int) $id;
        $repo = new TiSistemaRepository();
        $sistema = $repo->getById($sistemaId);
        if ($sistema === null) {
            $_SESSION['msg'] = 'Sistema não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas');
            exit;
        }

        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: null;

        if (
            is_array($this->data['form'])
            && isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_sistema', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save($sistemaId);
            return;
        }

        $this->data['form'] = $sistema;
        $this->viewForm($sistemaId);
    }

    private function viewForm(int $sistemaId): void
    {
        $this->data['tipos'] = TiSistemaRepository::TIPOS;
        $this->data['sistema_id'] = $sistemaId;
        $pageElements = [
            'title_head' => 'Editar Sistema (TI)',
            'menu' => 'ti-sistemas',
            'buttonPermission' => ['TiSistemas', 'TiSistemasView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/sistemas/update', $this->data))->loadView();
    }

    private function save(int $sistemaId): void
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
            $this->viewForm($sistemaId);
            return;
        }

        $ok = (new TiSistemaRepository())->update($sistemaId, [
            'codigo' => $form['codigo'] ?? null,
            'nome' => $nome,
            'descricao' => $form['descricao'] ?? null,
            'tipo' => $tipo,
            'localizacao' => $form['localizacao'] ?? null,
            'observacoes' => $form['observacoes'] ?? null,
            'status' => $status,
        ], (int) ($_SESSION['user_id'] ?? 0));

        if ($ok) {
            $_SESSION['msg'] = 'Sistema atualizado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . $sistemaId);
            return;
        }

        $this->data['errors'] = ['Não foi possível atualizar. Verifique se o código já existe.'];
        $this->viewForm($sistemaId);
    }
}
