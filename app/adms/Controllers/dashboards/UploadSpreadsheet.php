<?php

namespace App\adms\Controllers\dashboards;

use App\adms\Models\Services\SpreadsheetService;
use App\adms\Models\Repository\SpreadsheetsRepository;
use Exception;

/**
 * Controller para upload de planilhas para dashboards
 */
class UploadSpreadsheet
{
    public function index(): void
    {
        header('Content-Type: application/json');

        // Aumentar limites para processamento de planilhas maiores
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        // Logar valores efetivos de configuração para diagnóstico
        error_log('[UploadSpreadsheet] max_execution_time atual: ' . ini_get('max_execution_time'));
        error_log('[UploadSpreadsheet] memory_limit atual: ' . ini_get('memory_limit'));
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Método não permitido'
            ]);
            exit;
        }

        try {
            if (empty($_FILES['spreadsheet']) || $_FILES['spreadsheet']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo foi enviado ou ocorreu erro no upload');
            }

            $userId = $_SESSION['user_id'] ?? 0;
            if ($userId === 0) {
                throw new Exception('Usuário não autenticado');
            }

            $spreadsheetService = new SpreadsheetService();
            $options = [
                'header_row' => (int)($_POST['header_row'] ?? 1),
                'data_start_row' => (int)($_POST['data_start_row'] ?? 2),
                'sheet_name' => $_POST['sheet_name'] ?? null
            ];

            // Processar upload
            $processed = $spreadsheetService->processUpload($_FILES['spreadsheet'], $options);

            // Salvar no banco
            $repo = new SpreadsheetsRepository();
            $spreadsheetId = $repo->create([
                'name' => $_POST['name'] ?? $processed['file_name'],
                'description' => $_POST['description'] ?? null,
                'file_name' => $processed['file_name'],
                'file_path' => $processed['file_path'],
                'file_type' => $processed['file_type'],
                'file_size' => $processed['file_size'],
                'sheet_name' => $processed['sheet_name'],
                'header_row' => $options['header_row'],
                'data_start_row' => $options['data_start_row'],
                'columns_config' => $processed['columns_config'],
                'total_rows' => $processed['total_rows'],
                'total_columns' => $processed['total_columns'],
                'created_by' => $userId,
                'is_public' => isset($_POST['is_public']),
                'category' => $_POST['category'] ?? null
            ]);

            echo json_encode([
                'success' => true,
                'spreadsheet_id' => $spreadsheetId,
                'data' => [
                    'id' => $spreadsheetId,
                    'name' => $_POST['name'] ?? $processed['file_name'],
                    'file_name' => $processed['file_name'],
                    'total_rows' => $processed['total_rows'],
                    'total_columns' => $processed['total_columns'],
                    'headers' => $processed['headers']
                ]
            ]);

        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        exit;
    }

    /**
     * Obter abas de um arquivo Excel (para preview antes de salvar)
     */
    public function getSheets(): void
    {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                'success' => false,
                'error' => 'Método não permitido'
            ]);
            exit;
        }

        try {
            if (empty($_FILES['spreadsheet']) || $_FILES['spreadsheet']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Nenhum arquivo foi enviado');
            }

            $fileExtension = strtolower(pathinfo($_FILES['spreadsheet']['name'], PATHINFO_EXTENSION));
            
            if ($fileExtension === 'csv') {
                echo json_encode([
                    'success' => true,
                    'sheets' => [['name' => 'Sheet1', 'is_default' => true]]
                ]);
                exit;
            }

            // Salvar temporariamente para ler abas
            $tempFile = sys_get_temp_dir() . '/' . uniqid() . '_' . $_FILES['spreadsheet']['name'];
            move_uploaded_file($_FILES['spreadsheet']['tmp_name'], $tempFile);

            $spreadsheetService = new SpreadsheetService();
            $sheets = $spreadsheetService->getSheetNames('temp/' . basename($tempFile));
            
            unlink($tempFile);

            $sheetsData = [];
            foreach ($sheets as $index => $sheetName) {
                $sheetsData[] = [
                    'name' => $sheetName,
                    'is_default' => $index === 0
                ];
            }

            echo json_encode([
                'success' => true,
                'sheets' => $sheetsData
            ]);

        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        
        exit;
    }
}


