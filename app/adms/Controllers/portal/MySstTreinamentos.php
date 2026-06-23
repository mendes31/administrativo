<?php

declare(strict_types=1);

namespace App\adms\Controllers\portal;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

/**
 * Portal do colaborador: treinamentos SST obrigatórios, status e certificados.
 */
class MySstTreinamentos
{
    private array $data = [];

    public function index(): void
    {
        $uid = (int) ($_SESSION['user_id'] ?? 0);
        if ($uid <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'login');
            exit;
        }

        $repo = new SstTreinamentoVinculosRepository();
        $vinculos = $repo->getByUserId($uid, 200);
        $this->data['vinculos'] = $vinculos;
        $this->data['pendentes'] = array_values(array_filter(
            $vinculos,
            static fn (array $v): bool => in_array($v['status'] ?? '', ['pendente', 'agendado', 'vencido', 'proximo_vencimento'], true)
        ));
        $this->data['pendencias_sst'] = SstPendenciasService::incluirTreinamentos()
            ? (new SstPendenciasService())->getPendenciasTreinamentoPorUsuario($uid)
            : [];

        $pageElements = [
            'title_head' => 'Meus treinamentos SST',
            'menu' => 'my-sst-treinamentos',
            'buttonPermission' => ['MySstTreinamentos', 'ViewSstTreinamentoCertificadoPdf'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/portal/my_sst_treinamentos', $this->data))->loadView();
    }
}
