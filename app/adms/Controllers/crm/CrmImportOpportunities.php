<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmOpportunitiesRepository;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Views\Services\LoadViewService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Controller para Importar Oportunidades via Excel
 */
class CrmImportOpportunities
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->import();
            return;
        }

        $pageElements = [
            'title_head' => 'Importar Oportunidades - CRM',
            'menu' => 'crm-list-opportunities',
            'buttonPermission' => ['CrmImportOpportunities'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/opportunities/import", $this->data);
        $loadView->loadView();
    }

    private function import(): void
    {
        if (empty($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = "Nenhum arquivo foi enviado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-import-opportunities");
            exit;
        }

        try {
            $spreadsheet = IOFactory::load($_FILES['excel_file']['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            array_shift($rows); // Pular cabeçalho

            $imported = 0;
            $errors = [];
            $opportunitiesRepo = new CrmOpportunitiesRepository();
            $partnersRepo = new CrmPartnersRepository();

            foreach ($rows as $index => $row) {
                if (empty($row[0]) && empty($row[1])) continue;

                // Buscar parceiro pelo código
                $partnerCode = $row[2] ?? '';
                $partner = null;
                if ($partnerCode) {
                    $allPartners = $partnersRepo->getAllPartners(1, 1, ['search' => $partnerCode])['data'];
                    $partner = $allPartners[0] ?? null;
                }

                if (!$partner) {
                    $errors[] = "Linha " . ($index + 2) . ": Parceiro não encontrado: " . $partnerCode;
                    continue;
                }

                $data = [
                    'code' => $row[0] ?? $opportunitiesRepo->getNextOpportunityCode(),
                    'title' => $row[1] ?? '',
                    'partner_id' => $partner['id'],
                    'value' => str_replace(',', '.', $row[4] ?? '0'),
                    'probability' => $row[5] ?? 50,
                    'stage_id' => 1, // Prospecção por padrão
                    'responsible_user_id' => $_SESSION['user_id'],
                    'description' => $row[8] ?? null,
                    'status' => 'Aberta',
                ];

                if (empty($data['title'])) {
                    $errors[] = "Linha " . ($index + 2) . ": Título obrigatório";
                    continue;
                }

                $result = $opportunitiesRepo->createOpportunity($data);

                if ($result) {
                    $imported++;
                } else {
                    $errors[] = "Linha " . ($index + 2) . ": Erro ao importar";
                }
            }

            $_SESSION['msg'] = "$imported oportunidade(s) importada(s)!";
            if (!empty($errors)) {
                $_SESSION['msg'] .= "<br><small>" . implode('<br>', array_slice($errors, 0, 5)) . "</small>";
            }
            $_SESSION['msg_type'] = $imported > 0 ? "success" : "warning";

        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-opportunities");
        exit;
    }
}

