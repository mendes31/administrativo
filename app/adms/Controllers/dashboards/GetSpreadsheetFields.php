<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Repository\SpreadsheetsRepository;
use App\adms\Models\Services\SpreadsheetService;

/**
 * Controller para obter campos de uma planilha
 */
class GetSpreadsheetFields
{
    public function index(?string $id = null): void
    {
        header('Content-Type: application/json');
        
        // Debug: log do que está chegando
        error_log("[GetSpreadsheetFields] ID recebido: " . var_export($id, true));
        error_log("[GetSpreadsheetFields] REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A'));
        error_log("[GetSpreadsheetFields] GET: " . json_encode($_GET));
        
        if (empty($id)) {
            echo json_encode([
                'success' => false,
                'error' => 'ID da planilha não especificado',
                'debug' => [
                    'id_received' => $id,
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
                    'get_params' => $_GET
                ]
            ]);
            exit;
        }

        try {
            $spreadsheetId = (int)$id;
            $userId = $_SESSION['user_id'] ?? 0;

            $repo = new SpreadsheetsRepository();
            
            // Verificar acesso
            if (!$repo->canAccess($spreadsheetId, $userId)) {
                throw new \Exception('Sem permissão para acessar esta planilha');
            }

            $spreadsheet = $repo->getById($spreadsheetId);
            
            if (!$spreadsheet) {
                throw new \Exception('Planilha não encontrada');
            }

            // Processar apenas o cabeçalho da planilha para obter campos
            $spreadsheetService = new SpreadsheetService();
            $headerResult = $spreadsheetService->readHeaders(
                $spreadsheet['file_path'],
                $spreadsheet['file_type'],
                $spreadsheet['header_row'],
                $spreadsheet['sheet_name']
            );

            // Converter para formato de campos
            $fields = [];
            foreach ($headerResult['headers'] as $index => $header) {
                $columnConfig = $spreadsheet['columns_config'][$index] ?? null;
                $type = $columnConfig['type'] ?? 'text';
                
                $fields[] = [
                    'name' => $header,
                    'type' => $type,
                    'index' => $index
                ];
            }

            echo json_encode([
                'success' => true,
                'name' => $spreadsheet['name'],
                'fields' => $fields,
                'total_rows' => $spreadsheet['total_rows'],
                'total_columns' => $headerResult['total_columns']
            ]);

        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        exit;
    }
}


