<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\SstAcidentesRepository;
use App\adms\Models\Repository\SstAfastamentosRepository;
use App\adms\Models\Repository\SstAsosRepository;
use App\adms\Models\Repository\SstEpiEntregasRepository;
use App\adms\Models\Repository\SstEpiFichasRepository;
use App\adms\Models\Repository\SstPppRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstEmployeeProfileService;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

class SstEmployeeProfile
{
    private array $data = [];

    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = 'Colaborador não informado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-users');
            exit;
        }
        $userId = (int) $id;
        $usersRepo = new UsersRepository();
        $this->data['user'] = $usersRepo->getUser($userId);
        if (!$this->data['user']) {
            $_SESSION['msg'] = 'Colaborador não encontrado.';
            $_SESSION['msg_type'] = 'danger';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-users');
            exit;
        }

        $pendenciasService = new SstPendenciasService();
        $profileService = new SstEmployeeProfileService();
        $this->data['pendencias'] = $pendenciasService->getPendenciasPorUsuario($userId);
        $this->data['asos'] = (new SstAsosRepository())->getByUserId($userId);
        $this->data['afastamentos'] = (new SstAfastamentosRepository())->getByUserId($userId);
        $this->data['epi_entregas'] = (new SstEpiEntregasRepository())->getByUserId($userId);
        $fichaRepo = new SstEpiFichasRepository();
        $this->data['epi_fichas'] = $fichaRepo->getByUserId($userId);
        $this->data['epis_entregues_consolidado'] = $fichaRepo->getEpisEntreguesPorColaborador($userId);
        $this->data['acidentes'] = (new SstAcidentesRepository())->getByUserId($userId);
        $this->data['resumo'] = $profileService->getResumo($userId);
        $this->data['riscos'] = $profileService->getRiscosVinculados($userId);
        $this->data['obrigatoriedades'] = $profileService->getObrigatoriedades($userId);
        $this->data['timeline'] = $profileService->buildTimeline(
            $this->data['asos'],
            $this->data['epi_entregas'],
            $this->data['afastamentos'],
            $this->data['acidentes']
        );
        $this->data['ppp_historico'] = (new SstPppRepository())->getByUserId($userId);

        $pageElements = [
            'title_head' => 'SST - ' . ($this->data['user']['name'] ?? 'Colaborador'),
            'menu' => 'sst-dashboard',
            'buttonPermission' => [
                'SstEmployeeProfile',
                'SstCreateAso', 'SstCreateAfastamento', 'SstCreateEpiEntrega', 'SstCreateEpiFicha', 'SstCreateAcidente',
                'SstAbrirAsoPendencia', 'SstRegistrarResultadosAso',
                'SstEncaminhamentoAso', 'SstExportEncaminhamentoAsoPdf',
                'SstListAsos', 'SstListAfastamentos', 'SstListEpiEntregas', 'SstListEpiFichas', 'SstListAcidentes',
                'SstViewAso', 'SstViewAfastamento', 'SstViewEpiEntrega', 'SstViewEpiFicha', 'SstViewAcidente',
                'SstExportEpiFichaPdf',
                'SstGeneratePpp', 'SstListPpp', 'SstViewPpp', 'SstExportPppPdf',
            ],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/employee_profile', $this->data))->loadView();
    }
}
