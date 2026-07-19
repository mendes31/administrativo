<?php

declare(strict_types=1);

// Reenvio FTP experiencia/movimentacoes (controllers ausentes no servidor).

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\RhMovimentacoesRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\RhMovimentacaoService;
use App\adms\Models\Services\RhPermissionService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhMovimentacoesCreate
{
    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $preUserId = (int) ($id ?: ($_GET['user_id'] ?? 0));

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->save();
            return;
        }

        if (!RhPermissionService::isSuperAdmin() && !RhPermissionService::isManager()) {
            // ACL da página ainda controla acesso; gestores/RH típicos são managers.
        }

        $form = [
            'adms_user_id' => $preUserId > 0 ? (string) $preUserId : '',
            'tipo' => 'transferencia',
            'data_vigencia' => date('Y-m-d'),
            'departamento_id_depois' => '',
            'cargo_id_depois' => '',
            'gestor_id_depois' => '',
            'motivo' => '',
            'observacoes' => '',
        ];

        if ($preUserId > 0) {
            $user = (new UsersRepository())->getUser($preUserId);
            if (is_array($user)) {
                $form['departamento_id_depois'] = (string) ($user['user_department_id'] ?? '');
                $form['cargo_id_depois'] = (string) ($user['user_position_id'] ?? '');
                $form['gestor_id_depois'] = (string) ($user['immediate_supervisor_id'] ?? '');
            }
        }

        $this->data = [
            'title_head' => 'Registrar Movimentação',
            'menu' => 'rh-movimentacoes-create',
            'buttonPermission' => ['RhMovimentacoesCreate', 'RhMovimentacoes'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_movimentacao'),
            'form' => $form,
            'tipos' => RhMovimentacoesRepository::TIPOS,
            'listUsers' => (new UsersRepository())->getAllUsersSelect(),
            'listDepartments' => (new DepartmentsRepository())->getAllDepartmentsSelect(),
            'listPositions' => (new PositionsRepository())->getAllPositionsSelect(),
            'listSupervisors' => (new UsersRepository())->getAllUsersSelect(),
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/movimentacoes/create', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_movimentacao', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-create');
            exit;
        }

        try {
            $result = (new RhMovimentacaoService())->registrar(
                $_POST['form'] ?? [],
                (int) ($_SESSION['user_id'] ?? 0)
            );
            $_SESSION['msg'] = 'Movimentação registrada e lotação aplicada.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-view/' . $result['movimentacao_id']);
            exit;
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-movimentacoes-create');
            exit;
        }
    }
}
