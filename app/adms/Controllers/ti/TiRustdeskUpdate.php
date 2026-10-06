<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\TiRustdeskSecretService;
use App\adms\Views\Services\LoadViewService;

final class TiRustdeskUpdate
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $registroId = (int) $id;
        $repo = new TiRustdeskRepository();
        $registro = $repo->getById($registroId);
        if ($registro === null) {
            $_SESSION['msg'] = 'Cadastro RustDesk não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-rustdesk');
            exit;
        }

        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: null;

        if (
            is_array($this->data['form'])
            && isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_rustdesk', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save($registroId);
            return;
        }

        $this->data['form'] = $registro;
        $this->viewForm($registroId);
    }

    private function viewForm(int $registroId): void
    {
        $this->data['users'] = (new TiRustdeskRepository())->getUsersSelect(
            (int) ($this->data['form']['adms_user_id'] ?? 0)
        );
        $this->data['encryption_ok'] = TiRustdeskSecretService::isConfigured();
        $this->data['is_edit'] = true;
        $this->data['registro_id'] = $registroId;
        if (!is_array($this->data['form'] ?? null)) {
            $this->data['form'] = [];
        }
        if (!isset($this->data['form']['has_senha'])) {
            $atual = (new TiRustdeskRepository())->getById($registroId);
            $this->data['form']['has_senha'] = !empty($atual['has_senha']);
        }
        $pageElements = [
            'title_head' => 'Editar RustDesk (TI)',
            'menu' => 'ti-rustdesk',
            'buttonPermission' => ['TiRustdesk', 'TiRustdeskView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/rustdesk/update', $this->data))->loadView();
    }

    private function save(int $registroId): void
    {
        $repo = new TiRustdeskRepository();
        [$errors, $payload] = $repo->validateAndNormalize($this->data['form'] ?? [], $registroId);

        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->viewForm($registroId);
            return;
        }

        $ok = $repo->update($registroId, $payload, (int) ($_SESSION['user_id'] ?? 0));

        if ($ok) {
            $_SESSION['msg'] = 'Cadastro RustDesk atualizado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-rustdesk-view/' . $registroId);
            return;
        }

        $this->data['errors'] = ['Não foi possível atualizar. Verifique se o ID já existe.'];
        $this->viewForm($registroId);
    }
}
