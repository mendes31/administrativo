<?php

declare(strict_types=1);

namespace App\adms\Controllers\sst;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\UsersRepository;
use App\adms\Models\Services\SstPendenciasService;
use App\adms\Views\Services\LoadViewService;

class SstReportPendencias
{
    private array $data = [];

    public function index(): void
    {
        $filters = [
            'adms_user_id' => $_GET['adms_user_id'] ?? '',
            'adms_department_id' => $_GET['adms_department_id'] ?? '',
            'situacao' => $_GET['situacao'] ?? '',
        ];

        $service = new SstPendenciasService();
        $report = $service->getRelatorioCompleto($filters);

        if (!empty($filters['situacao'])) {
            $report['epis_obrigatorios'] = $this->filtrarSituacao($report['epis_obrigatorios'] ?? [], $filters['situacao']);
            $report['exames_obrigatorios'] = $this->filtrarSituacao($report['exames_obrigatorios'] ?? [], $filters['situacao']);
            $report['treinamentos_obrigatorios'] = $this->filtrarSituacao($report['treinamentos_obrigatorios'] ?? [], $filters['situacao']);
        }

        $this->data['report'] = $report;
        $this->data['filters'] = $filters;
        $this->data['users'] = (new UsersRepository())->getAllUsersForSelect();
        $this->data['departments'] = (new DepartmentsRepository())->getAllDepartmentsSelect();
        $this->data['situacoes'] = SstPendenciasService::situacoesFiltro();
        $this->data['incluirTreinamentos'] = SstPendenciasService::incluirTreinamentos();

        $pageElements = [
            'title_head' => 'Relatório de Pendências - SST',
            'menu' => 'sst-dashboard',
            'buttonPermission' => ['SstReportPendencias', 'SstEmployeeProfile', 'SstAbrirAsoPendencia', 'SstRegistrarResultadosAso'],
        ];
        $this->data = array_merge($this->data, (new PageLayoutService())->configurePageElements($pageElements));
        (new LoadViewService('adms/Views/sst/report_pendencias', $this->data))->loadView();
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function filtrarSituacao(array $rows, string $situacao): array
    {
        return array_values(array_filter($rows, static fn(array $r): bool => ($r['situacao'] ?? '') === $situacao));
    }
}
