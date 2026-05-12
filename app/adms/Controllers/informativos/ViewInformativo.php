<?php

namespace App\adms\Controllers\informativos;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\InformativosRepository;
use App\adms\Models\Services\InformativosPermissionService;
use App\adms\Models\Services\InformativosStatusUpdaterService;
use App\adms\Models\Services\LogResumoService;
use App\adms\Views\Services\LoadViewService;

class ViewInformativo
{
    private array|string|null $data = null;

    public function index(string|int $id = null)
    {
        if (!$id) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">ID do informativo não informado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        InformativosStatusUpdaterService::ensureUpdated();

        $repo = new InformativosRepository();
        $informativo = $repo->getInformativoById((int)$id);

        if (!$informativo) {
            $_SESSION['msg'] = '<div class="alert alert-danger" role="alert">Informativo não encontrado!</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-informativos');
            exit;
        }

        $this->data['informativo'] = $informativo;
        $infId = (int) $informativo['id'];
        $returnUrl = $_ENV['URL_ADM'] . 'view-informativo/' . $infId;
        $this->data['log_resumo'] = LogResumoService::getResumo('adms_informativos', $infId, $returnUrl);
        $uid = InformativosPermissionService::sessionUserId();
        $this->data['can_manage_informativo'] = InformativosPermissionService::canManageRecord(
            $informativo,
            $uid,
            InformativosPermissionService::sessionUserDepartmentId()
        );
        // Status de leitura/ciência do usuário logado
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $this->data['read_status'] = $repo->getReadByUser((int)$informativo['id'], (int)$userId);
        }

        $pageElements = [
            'title_head' => 'Visualizar Informativo',
            'menu' => 'view-informativo',
            'buttonPermission' => ['ViewInformativo', 'UpdateInformativo', 'DeleteInformativo'],
        ];

        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        // Marcar leitura para o usuário logado
        if ($userId) {
            $repo->upsertRead((int)$informativo['id'], (int)$userId);
        }

        $loadView = new LoadViewService('adms/Views/informativos/view', $this->data);
        $loadView->loadView();
    }
} 