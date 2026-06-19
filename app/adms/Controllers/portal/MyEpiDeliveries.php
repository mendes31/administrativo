<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Portal do colaborador: fichas pendentes/assinadas e histórico consolidado de EPIs entregues.
 */
class MyEpiDeliveries
{
    private array $data = [];

    public function index(): void
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new SstEpiFichasRepository();
        $fichas = $repo->getByUserId($uid, 100);
        $this->data['fichas'] = $fichas;
        $this->data['pendentes'] = array_values(array_filter(
            $fichas,
            static fn(array $f): bool => ($f['status_assinatura'] ?? '') === 'Pendente'
        ));
        $this->data['epis_entregues'] = $repo->getEpisEntreguesPorColaborador($uid, 300);

        $pageElements = [
            'title_head' => 'Meus EPIs',
            'menu' => 'my-epi-deliveries',
            'buttonPermission' => ['MyEpiDeliveries', 'SignEpiFicha', 'SstExportEpiFichaPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/portal/my_epi_deliveries', $this->data))->loadView();
    }
}
