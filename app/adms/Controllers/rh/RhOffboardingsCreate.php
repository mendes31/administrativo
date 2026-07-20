<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOffboardingRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\RhOffboardingService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhOffboardingsCreate
{
    private array $data = [];

    public function index(int|string $id = 0): void
    {
        $preUserId = (int) ($id ?: ($_GET['user_id'] ?? 0));

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->save();
            return;
        }

        $this->data = [
            'title_head' => 'Iniciar Offboarding',
            'menu' => 'rh-offboardings',
            'buttonPermission' => ['RhOffboardingsCreate', 'RhOffboardings'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_offboarding'),
            'form' => [
                'adms_user_id' => $preUserId > 0 ? (string) $preUserId : '',
                'tipo' => 'pedido_demissao',
                'data_prevista' => date('Y-m-d'),
                'motivo' => '',
                'tipo_impacto' => 'nao_classificado',
                'observacoes' => '',
            ],
            'tipos' => RhOffboardingRepository::TIPOS,
            'listUsers' => (new UsersRepository())->getAllUsersSelect(),
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/offboarding/create', $this->data))->loadView();
    }

    private function save(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_offboarding', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-create');
            exit;
        }

        try {
            $result = (new RhOffboardingService())->iniciar(
                $_POST['form'] ?? [],
                (int) ($_SESSION['user_id'] ?? 0)
            );
            $_SESSION['msg'] = 'Offboarding iniciado.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-view/' . $result['plano_id']);
            exit;
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-create');
            exit;
        }
    }
}
