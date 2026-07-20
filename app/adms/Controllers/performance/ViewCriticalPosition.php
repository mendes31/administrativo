<?php

declare(strict_types=1);

namespace App\adms\Controllers\performance;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Models\Repository\CriticalPositionsRepository;
use App\adms\Models\Repository\SuccessionSuccessorsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\LogResumoService;
use App\adms\Models\Services\SuccessionService;
use App\adms\Views\Services\LoadViewService;

class ViewCriticalPosition
{
    private array|string|null $data = null;

    public function index(int|string $id): void
    {
        $cid = (int) $id;
        $repository = new CriticalPositionsRepository();
        $item = $cid > 0 ? $repository->getById($cid) : null;
        if (!$item) {
            $_SESSION['error'] = 'Cargo crítico não encontrado.';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-critical-positions');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handlePost($cid);
            $item = $repository->getById($cid) ?? $item;
        }

        $this->data['item'] = $item;
        $this->data['successors'] = (new SuccessionSuccessorsRepository())->getByCriticalPositionId($cid);
        $this->data['employees'] = (new UsersRepository())->getAllUsers(1, 1000);
        $this->data['log_resumo'] = LogResumoService::getResumo(
            'adms_critical_positions',
            $cid,
            $_ENV['URL_ADM'] . 'view-critical-position/' . $cid
        );

        $pageElements = [
            'title_head' => 'Sucessão — ' . ($item['position_name'] ?? ''),
            'menu' => 'list-critical-positions',
            'buttonPermission' => [
                'ListCriticalPositions',
                'UpdateCriticalPosition',
                'ListTalentNominations',
                'ListPdiPlans',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/performance/view_critical_position', $this->data))->loadView();
    }

    private function handlePost(int $cid): void
    {
        $action = (string) ($_POST['form_action'] ?? '');
        $csrfMap = [
            'add_successor' => 'form_succ_add',
            'update_successor' => 'form_succ_update',
            'remove_successor' => 'form_succ_remove',
        ];
        if (!isset($csrfMap[$action])) {
            $_SESSION['error'] = 'Ação inválida.';

            return;
        }
        if (!CSRFHelper::validateCSRFToken($csrfMap[$action], $_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Token de segurança inválido.';

            return;
        }

        $service = new SuccessionService();
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $result = match ($action) {
            'add_successor' => $service->addSuccessor($cid, $_POST, $userId),
            'update_successor' => $service->updateSuccessor((int) ($_POST['successor_id'] ?? 0), $cid, $_POST),
            'remove_successor' => $service->removeSuccessor((int) ($_POST['successor_id'] ?? 0), $cid),
            default => ['ok' => false, 'error' => 'Ação inválida.'],
        };

        if (!$result['ok']) {
            $_SESSION['error'] = $result['error'] ?? 'Erro.';

            return;
        }

        $messages = [
            'add_successor' => 'Sucessor adicionado.',
            'update_successor' => 'Sucessor atualizado.',
            'remove_successor' => 'Sucessor removido.',
        ];
        $_SESSION['msg'] = '<div class="alert alert-success" role="alert">' . ($messages[$action] ?? 'OK') . '</div>';
        GenerateLog::generateLog('info', 'Sucessão: ' . $action, ['critical_position_id' => $cid]);
        header('Location: ' . $_ENV['URL_ADM'] . 'view-critical-position/' . $cid);
        exit;
    }
}
