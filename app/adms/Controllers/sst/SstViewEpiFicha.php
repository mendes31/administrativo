<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Services\SstEpiFichaPublishNotifier;
use App\adms\Views\Services\LoadViewService;

class SstViewEpiFicha
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        $fichaId = (int) $id;
        if ($fichaId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-fichas');
            exit;
        }
        $repo = new SstEpiFichasRepository();
        $item = $repo->getById($fichaId);
        if (!$item) {
            $_SESSION['msg'] = 'Ficha não encontrada.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-list-epi-fichas');
            exit;
        }
        $this->data['item'] = $item;
        $this->data['itens'] = $repo->getItens($fichaId);
        $this->data['epis_entregues'] = $repo->getEpisEntreguesPorColaborador((int) $item['adms_user_id'], 100);

        if (($_GET['action'] ?? '') === 'resend_push' && ($item['status_assinatura'] ?? '') === 'Pendente') {
            SstEpiFichaPublishNotifier::notifyPendingSignature(
                (int) $item['adms_user_id'],
                $fichaId,
                (string) ($item['colaborador_nome'] ?? ''),
                true
            );
            $_SESSION['msg'] = 'Notificação reenviada ao colaborador.';
            $_SESSION['msg_type'] = 'success';
            header('Location: ' . $_ENV['URL_ADM'] . 'sst-view-epi-ficha/' . $fichaId);
            exit;
        }

        $pageElements = [
            'title_head' => 'Ficha de entrega EPI #' . $fichaId,
            'menu' => 'sst-list-epi-fichas',
            'buttonPermission' => ['SstViewEpiFicha', 'SstExportEpiFichaPdf', 'SstCreateEpiFicha'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/epi_fichas/view', $this->data))->loadView();
    }
}
