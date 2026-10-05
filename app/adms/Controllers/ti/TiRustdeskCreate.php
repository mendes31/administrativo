<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\TiRustdeskSecretService;
use App\adms\Views\Services\LoadViewService;

final class TiRustdeskCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_rustdesk', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save();
            return;
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $this->data['users'] = (new TiRustdeskRepository())->getUsersSelect();
        $this->data['encryption_ok'] = TiRustdeskSecretService::isConfigured();
        $this->data['is_edit'] = false;
        $pageElements = [
            'title_head' => 'Cadastrar RustDesk (TI)',
            'menu' => 'ti-rustdesk',
            'buttonPermission' => ['TiRustdesk'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/rustdesk/create', $this->data))->loadView();
    }

    private function save(): void
    {
        $repo = new TiRustdeskRepository();
        [$errors, $payload] = $repo->validateAndNormalize($this->data['form'] ?? []);

        if ($errors !== []) {
            $this->data['errors'] = $errors;
            $this->viewForm();
            return;
        }

        $id = $repo->create($payload, (int) ($_SESSION['user_id'] ?? 0));

        if ($id) {
            $_SESSION['msg'] = 'RustDesk cadastrado com sucesso.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-rustdesk-view/' . $id);
            return;
        }

        $this->data['errors'] = ['Não foi possível cadastrar. Verifique se o ID já existe.'];
        $this->viewForm();
    }
}
