<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiSistemaRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\TiAcessoService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class TiAcessosCreate
{
    private array $data = [];

    public function index(): void
    {
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_acesso', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save();
            return;
        }

        if (empty($this->data['form'])) {
            $this->data['form'] = [
                'adms_user_id' => (int) ($_GET['user_id'] ?? 0),
                'ti_sistema_id' => (int) ($_GET['sistema_id'] ?? 0),
                'data_liberacao' => date('Y-m-d'),
                'return_to' => trim((string) ($_GET['return'] ?? '')),
            ];
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $this->data['sistemas'] = (new TiSistemaRepository())->getAtivosSelect();
        $this->data['usuarios'] = (new UsersRepository())->getAllUsersSelect();
        $this->data['return_to'] = trim((string) (
            $this->data['form']['return_to'] ?? ($_GET['return'] ?? '')
        ));

        $pageElements = [
            'title_head' => 'Liberar Acesso (TI)',
            'menu' => 'ti-acessos-create',
            'buttonPermission' => ['TiSistemas', 'TiSistemasView'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/acessos/create', $this->data))->loadView();
    }

    private function save(): void
    {
        $form = $this->data['form'];
        $returnTo = trim((string) ($form['return_to'] ?? ''));

        try {
            (new TiAcessoService())->liberar($form, (int) ($_SESSION['user_id'] ?? 0));
            $_SESSION['msg'] = 'Acesso liberado no mapa.';
            $_SESSION['msg_type'] = 'success';

            $userId = (int) ($form['adms_user_id'] ?? 0);
            $sistemaId = (int) ($form['ti_sistema_id'] ?? 0);
            if ($returnTo !== '' && str_starts_with($returnTo, ($_ENV['URL_ADM'] ?? ''))) {
                header('Location: ' . $returnTo);
            } elseif ($sistemaId > 0) {
                header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas-view/' . $sistemaId);
            } elseif ($userId > 0) {
                header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'update-user/' . $userId . '?tab=acessos');
            } else {
                header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas');
            }
            return;
        } catch (Exception $e) {
            $this->data['errors'] = [$e->getMessage()];
            $this->viewForm();
        }
    }
}
