<?php

declare(strict_types=1);

namespace App\adms\Controllers\rh;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\RhCandidatoAnexoAccessLogRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Listagem de auditoria de download de currículos/anexos (DPO/LGPD).
 */
class ListRhCandidatoAnexoAccessLogs
{
    private array|string|null $data = null;
    private int $limitResult = 50;

    public function index(string|int $page = 1): void
    {
        if (isset($_GET['page']) && is_numeric($_GET['page'])) {
            $page = (int) $_GET['page'];
        }

        $allowedPerPage = [10, 20, 50, 100];
        $perPage = $this->limitResult;
        if (isset($_GET['per_page']) && is_numeric($_GET['per_page'])) {
            $candidate = (int) $_GET['per_page'];
            if (in_array($candidate, $allowedPerPage, true)) {
                $perPage = $candidate;
            }
        }
        $this->limitResult = $perPage;

        $filtros = [
            'actor_nome' => trim((string) ($_GET['actor_nome'] ?? '')),
            'candidato_id' => trim((string) ($_GET['candidato_id'] ?? '')),
            'candidato_nome' => trim((string) ($_GET['candidato_nome'] ?? '')),
            'source' => trim((string) ($_GET['source'] ?? '')),
            'ip' => trim((string) ($_GET['ip'] ?? '')),
            'data_inicio' => trim((string) ($_GET['data_inicio'] ?? '')),
            'data_fim' => trim((string) ($_GET['data_fim'] ?? '')),
        ];

        $paginaAtual = max(1, (int) $page);
        $repo = new RhCandidatoAnexoAccessLogRepository();
        $this->data['logs'] = $repo->getAll($paginaAtual, $perPage, $filtros);
        $this->data['per_page'] = $perPage;
        $this->data['filtros'] = $filtros;
        $this->data['pagina_atual'] = $paginaAtual;
        $this->data['total_registros'] = $repo->countAll($filtros);
        $this->data['total_paginas'] = max(1, (int) ceil($this->data['total_registros'] / $perPage));

        $pageElements = [
            'title_head' => 'Log de download de currículos',
            'menu' => 'list-rh-candidato-anexo-access-logs',
            'buttonPermission' => ['ExportRhCandidatoAnexoAccessLogsExcel'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService('adms/Views/rh/candidatos/list_anexo_access_logs', $this->data);
        $loadView->loadView();
    }
}
