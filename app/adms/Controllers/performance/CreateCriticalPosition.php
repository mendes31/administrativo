<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Services\SuccessionService;
use App\adms\Views\Services\LoadViewService;

class CreateCriticalPosition
{
    private array|string|null $data = null;

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->create();
        }

        $this->data['positions'] = (new PositionsRepository())->getAllPositionsSelect();
        if (!isset($this->data['form'])) {
            $this->data['form'] = [
                'position_id' => '',
                'risk_level' => 'high',
                'status' => 'active',
                'notes' => '',
            ];
        }

        $pageElements = [
            'title_head' => 'Marcar Cargo Crítico',
            'menu' => 'list-critical-positions',
            'buttonPermission' => ['ListCriticalPositions'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/performance/create_critical_position', $this->data))->loadView();
    }

    private function create(): void
    {
        if (!CSRFHelper::validateCSRFToken('form_create_critical_position', $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido. Tente novamente.';
            header('Location: ' . $_ENV['URL_ADM'] . 'create-critical-position');
            exit;
        }

        $result = (new SuccessionService())->createCritical($_POST, (int) ($_SESSION['user_id'] ?? 0));
        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro ao criar.';
            $this->data['form'] = $_POST;

            return;
        }

        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">Cargo crítico cadastrado!</div>';
        GenerateLog::generateLog('info', 'Cargo crítico criado.', ['id' => $result['id']]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-critical-position/' . $result['id']);
        exit;
    }
}
