<?php

namespace App\adms\Controllers\lgpd;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\LgpdAipdRepository;
use App\adms\Views\Services\LoadViewService;

/**
 * Controller responsável pela visualização de AIPD (Avaliação de Impacto à Proteção de Dados).
 *
 * @package App\adms\Controllers\lgpd
 */
class LgpdAipdView
{
    private array|string|null $data = null;

    public function index(string|int|null $id = null): void
    {
        if (empty($id)) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        $repo = new LgpdAipdRepository();
        $aipd = $repo->getAipdById($id);

        if (!$aipd) {
            $_SESSION['error'] = "AIPD não encontrada!";
            header("Location: " . $_ENV['URL_ADM'] . "lgpd-aipd");
            exit;
        }

        // Mapear campos da tabela para os campos esperados pela view
        $this->data['aipd'] = [
            'id' => $aipd['id'],
            'nome' => $aipd['titulo'],
            'status' => $aipd['status'],
            'departamento_nome' => $aipd['departamento_nome'],
            'responsavel_nome' => $aipd['responsavel_nome'],
            'data_inicio' => $aipd['data_inicio'],
            'data_fim' => $aipd['data_conclusao'], // Mapear data_conclusao para data_fim
            'created' => $aipd['created_at'], // Mapear created_at para created
            'modified' => $aipd['updated_at'], // Mapear updated_at para modified
            'descricao' => $aipd['descricao'],
            'objetivo' => $aipd['observacoes'], // Usar observacoes como objetivo
            'escopo' => $aipd['descricao'], // Usar descricao como escopo
            'metodologia' => $aipd['observacoes'], // Usar observacoes como metodologia
            'observacoes' => $aipd['observacoes']
        ];
        $this->data['data_groups'] = $repo->getDataGroupsByAipdId($id);

        $pageElements = [
            'title_head' => 'Visualizar AIPD',
            'menu' => 'lgpd-aipd',
            'buttonPermission' => ['ViewLgpdAipd'],
        ];
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/lgpd/aipd/view", $this->data);
        $loadView->loadView();
    }
}
