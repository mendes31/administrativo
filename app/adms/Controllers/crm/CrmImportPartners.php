<?php

namespace App\adms\Controllers\crm;

use App\adms\Controllers\Services\PageLayoutService;
use App\adms\Models\Repository\CrmPartnersRepository;
use App\adms\Views\Services\LoadViewService;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Controller para Importar Parceiros via Excel
 *
 * @package App\adms\Controllers\crm
 * @author Rafael Mendes
 */
class CrmImportPartners
{
    private array $data = [];

    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->import();
            return;
        }

        // Layout
        $pageElements = [
            'title_head' => 'Importar Parceiros - CRM',
            'menu' => 'crm-list-partners',
            'buttonPermission' => ['CrmImportPartners'],
        ];
        
        $pageLayoutService = new PageLayoutService();
        $this->data = array_merge($this->data, $pageLayoutService->configurePageElements($pageElements));

        $loadView = new LoadViewService("adms/Views/crm/partners/import", $this->data);
        $loadView->loadView();
    }

    private function import(): void
    {
        if (empty($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['msg'] = "Nenhum arquivo foi enviado ou ocorreu um erro no upload.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-import-partners");
            exit;
        }

        $file = $_FILES['excel_file'];
        
        // Validar extensão
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'])) {
            $_SESSION['msg'] = "Formato de arquivo inválido. Use .xlsx ou .xls";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "crm-import-partners");
            exit;
        }

        try {
            $spreadsheet = IOFactory::load($file['tmp_name']);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            // Pular linha de cabeçalho
            array_shift($rows);

            $imported = 0;
            $errors = [];
            $partnersRepo = new CrmPartnersRepository();

            foreach ($rows as $index => $row) {
                // Ignorar linhas vazias
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                $data = [
                    'code' => $row[0] ?? $partnersRepo->getNextPartnerCode(),
                    'name' => $row[1] ?? '',
                    'trading_name' => $row[2] ?? null,
                    'type_person' => $row[3] ?? 'PJ',
                    'document' => $row[4] ?? null,
                    'email' => $row[5] ?? null,
                    'phone' => $row[6] ?? null,
                    'mobile' => $row[7] ?? null,
                    'segment' => $row[8] ?? 'Farma',
                    'partner_type' => $row[9] ?? 'Lead',
                    'priority' => $row[10] ?? 'Média',
                    'status' => $row[11] ?? 'Ativo',
                    'responsible_user_id' => $_SESSION['user_id'],
                    'estimated_revenue' => $row[13] ?? 0,
                ];

                // Validação básica
                if (empty($data['name'])) {
                    $errors[] = "Linha " . ($index + 2) . ": Nome obrigatório";
                    continue;
                }

                $result = $partnersRepo->createPartner($data);

                if ($result) {
                    $imported++;
                } else {
                    $errors[] = "Linha " . ($index + 2) . ": Erro ao importar " . $data['name'];
                }
            }

            $_SESSION['msg'] = "Importação concluída! $imported parceiro(s) importado(s)";
            if (!empty($errors)) {
                $_SESSION['msg'] .= "<br><small>" . implode('<br>', array_slice($errors, 0, 5)) . "</small>";
            }
            $_SESSION['msg_type'] = $imported > 0 ? "success" : "warning";

        } catch (\Exception $e) {
            $_SESSION['msg'] = "Erro ao processar arquivo: " . $e->getMessage();
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "crm-list-partners");
        exit;
    }
}

