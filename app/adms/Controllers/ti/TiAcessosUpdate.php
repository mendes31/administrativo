<?php

declare(strict_types=1);

namespace App\adms\Controllers\ti;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\TiAcessoRepository;
use App\adms\Models\Services\TiAcessoService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class TiAcessosUpdate
{
    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $acessoId = (int) $id;
        $acesso = (new TiAcessoRepository())->getById($acessoId);
        if ($acesso === null) {
            $_SESSION['msg'] = 'Acesso não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'ti-sistemas');
            exit;
        }

        $this->data['acesso'] = $acesso;
        $this->data['form'] = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?: [];

        if (
            isset($this->data['form']['csrf_token'])
            && CSRFHelper::validateCSRFToken('form_ti_acesso_update', (string) $this->data['form']['csrf_token'])
        ) {
            $this->save($acessoId, $acesso);
            return;
        }

        if ($this->data['form'] === []) {
            $this->data['form'] = [
                'login_externo' => (string) ($acesso['login_externo'] ?? ''),
                'perfil_obs' => (string) ($acesso['perfil_obs'] ?? ''),
                'data_liberacao' => (string) ($acesso['data_liberacao'] ?? date('Y-m-d')),
                'observacoes' => (string) ($acesso['observacoes'] ?? ''),
                'return_to' => trim((string) ($_GET['return'] ?? '')),
            ];
        }

        $this->viewForm();
    }

    private function viewForm(): void
    {
        $acesso = $this->data['acesso'] ?? [];
        $this->data['return_to'] = trim((string) (
            $this->data['form']['return_to'] ?? ($_GET['return'] ?? '')
        ));

        $pageElements = [
            'title_head' => 'Editar Acesso (TI)',
            'menu' => 'ti-acessos-create',
            'buttonPermission' => ['TiSistemas', 'TiSistemasView', 'TiAcessosCreate'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/ti/acessos/update', $this->data))->loadView();
    }

    /**
     * @param array<string, mixed> $acesso
     */
    private function save(int $acessoId, array $acesso): void
    {
        $form = $this->data['form'];
        $returnTo = trim((string) ($form['return_to'] ?? ''));
        $sistemaId = (int) ($acesso['ti_sistema_id'] ?? 0);
        $userId = (int) ($acesso['adms_user_id'] ?? 0);

        try {
            (new TiAcessoService())->atualizar($acessoId, $form, (int) ($_SESSION['user_id'] ?? 0));
            $_SESSION['msg'] = 'Dados do acesso atualizados.';
            $_SESSION['msg_type'] = 'success';

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
