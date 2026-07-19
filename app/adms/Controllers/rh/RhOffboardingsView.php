<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\RhOffboardingRepository;
use App\adms\Models\Services\RhOffboardingService;
use App\adms\Views\Services\LoadViewService;
use Exception;

final class RhOffboardingsView
{
    private array $data = [];

    public function index(int|string $id): void
    {
        $planoId = (int) $id;
        if ($planoId <= 0) {
            $_SESSION['msg'] = 'Plano de offboarding não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings');
            exit;
        }

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            $this->handleAction($planoId);
            return;
        }

        $repo = new RhOffboardingRepository();
        $plano = $repo->getPlanoById($planoId);
        if ($plano === null) {
            $_SESSION['msg'] = 'Plano de offboarding não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings');
            exit;
        }

        $this->data = [
            'title_head' => 'Offboarding #' . $planoId,
            'menu' => 'rh-offboardings-view',
            'buttonPermission' => ['RhOffboardingsView', 'RhOffboardings'],
            'csrf_token' => CSRFHelper::generateCSRFToken('form_rh_offboarding'),
            'plano' => $plano,
            'itens' => $repo->listItens($planoId),
            'obrigatorios_pendentes' => $repo->obrigatoriosPendentes($planoId),
        ];

        $pageLayout = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayout->configurePageElements($this->data));
        (new LoadViewService('adms/Views/rh/offboarding/view', $this->data))->loadView();
    }

    private function handleAction(int $planoId): void
    {
        if (!CSRFHelper::validateCSRFToken('form_rh_offboarding', (string) ($_POST['csrf_token'] ?? ''))) {
            $_SESSION['msg'] = 'Token inválido.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-view/' . $planoId);
            exit;
        }

        $action = (string) ($_POST['action'] ?? '');
        $service = new RhOffboardingService();
        $actorId = (int) ($_SESSION['user_id'] ?? 0);

        try {
            if ($action === 'item') {
                $service->atualizarItem(
                    $planoId,
                    (int) ($_POST['item_id'] ?? 0),
                    (string) ($_POST['item_status'] ?? ''),
                    trim((string) ($_POST['observacoes'] ?? '')) ?: null,
                    $actorId
                );
                $_SESSION['msg'] = 'Item atualizado.';
            } elseif ($action === 'cancelar') {
                $service->cancelar($planoId);
                $_SESSION['msg'] = 'Offboarding cancelado.';
            } elseif ($action === 'concluir') {
                $service->concluir(
                    $planoId,
                    trim((string) ($_POST['data_desligamento'] ?? '')) ?: null,
                    $actorId
                );
                $_SESSION['msg'] = 'Offboarding concluído e desligamento aplicado.';
            } else {
                throw new Exception('Ação inválida.');
            }
            $_SESSION['msg_type'] = 'success';
        } catch (Exception $e) {
            $_SESSION['msg'] = $e->getMessage();
            $_SESSION['msg_type'] = 'danger';
        }

        header('Location: ' . ($_ENV['URL_ADM'] ?? '') . 'rh-offboardings-view/' . $planoId);
        exit;
    }
}
