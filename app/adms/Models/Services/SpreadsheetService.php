<?php

namespace App\adms\Models\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Exception;

/**
 * Service para processar planilhas (Excel/CSV) para uso em dashboards
 */
class SpreadsheetService
{
    private string $uploadDir;

    public function __construct()
    {
        $basePath = dirname(__DIR__, 4);
        $this->uploadDir = $basePath . '/public/adms/uploads/spreadsheets/';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Processa upload de planilha e retorna dados estruturados
     */
    public function processUpload(array $file, array $options = []): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload do arquivo');
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception('Tipo de arquivo não permitido. Use: ' . implode(', ', $allowedTypes));
        }

        // Validar tamanho (max 50MB)
        $maxSize = 50 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception('Arquivo excede o tamanho máximo de 50MB');
        }

        // Salvar arquivo
        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $this->uploadDir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Erro ao salvar arquivo no servidor');
        }

        // Processar planilha
        $headerRow = $options['header_row'] ?? 1;
        $dataStartRow = $options['data_start_row'] ?? 2;
        $sheetName = $options['sheet_name'] ?? null;

        // Caminho relativo usado pelo restante da aplicação
        $relativePath = 'spreadsheets/' . $uniqueName;

        $result = $this->readSpreadsheet($relativePath, $fileExtension, $headerRow, $dataStartRow, $sheetName);

        return [
            'file_name' => $file['name'],
            'file_path' => $relativePath,
            'file_type' => $fileExtension,
            'file_size' => $file['size'],
            'sheet_name' => $result['sheet_name'],
            'headers' => $result['headers'],
            'data' => $result['data'],
            'total_rows' => $result['total_rows'],
            'total_columns' => $result['total_columns'],
            'columns_config' => $this->generateColumnsConfig($result['headers'])
        ];
    }

    /**
     * Lê planilha e retorna dados estruturados
     */
    public function readSpreadsheet(string $filePath, string $fileType, int $headerRow = 1, int $dataStartRow = 2, ?string $sheetName = null): array
    {
        // Garantir tempo e memória suficientes para leitura de planilhas grandes
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        // Log adicional para entender valores efetivos durante a leitura
        error_log('[SpreadsheetService::readSpreadsheet] max_execution_time: ' . ini_get('max_execution_time'));
        error_log('[SpreadsheetService::readSpreadsheet] memory_limit: ' . ini_get('memory_limit'));

        $fullPath = dirname(__DIR__, 4) . '/public/adms/uploads/' . $filePath;
        
        if (!file_exists($fullPath)) {
            throw new Exception('Arquivo não encontrado: ' . $filePath);
        }

        if ($fileType === 'csv') {
            return $this->readCsv($fullPath, $headerRow, $dataStartRow);
        } else {
            return $this->readExcel($fullPath, $headerRow, $dataStartRow, $sheetName);
        }
    }

    /**
     * Lê arquivo CSV
     */
    private function readCsv(string $filePath, int $headerRow, int $dataStartRow): array
    {
        // Detectar encoding
        $content = file_get_contents($filePath);
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);
        
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Salvar conteúdo convertido temporariamente
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
        file_put_contents($tempFile, $content);

        $fp = fopen($tempFile, 'r');
        if (!$fp) {
            unlink($tempFile);
            throw new Exception('Erro ao abrir arquivo CSV');
        }

        $rows = [];
        $lineNumber = 0;
        $headers = [];
        
        // Detectar delimitador (tentar ; e ,)
        $firstLine = fgets($fp);
        rewind($fp);
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            $lineNumber++;
            
            if ($lineNumber === $headerRow) {
                $headers = array_map('trim', $row);
                continue;
            }
            
            if ($lineNumber >= $dataStartRow) {
                // Garantir que a linha tem o mesmo número de colunas do cabeçalho
                $row = array_pad($row, count($headers), '');
                $rows[] = array_combine($headers, array_slice($row, 0, count($headers)));
            }
        }

        fclose($fp);
        unlink($tempFile);

        return [
            'sheet_name' => 'Sheet1',
            'headers' => $headers,
            'data' => $rows,
            'total_rows' => count($rows),
            'total_columns' => count($headers)
        ];
    }

    /**
     * Lê apenas os dados completos da planilha (xlsx, xls)
     * - Usado quando a planilha é fonte de dados do dashboard
     */
    private function readExcel(string $filePath, int $headerRow, int $dataStartRow, ?string $sheetName = null): array
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);

            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            if (method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }
            if ($sheetName && method_exists($reader, 'setLoadSheetsOnly')) {
                $reader->setLoadSheetsOnly([$sheetName]);
            }

            $spreadsheet = $reader->load($filePath);
            
            // Selecionar aba
            if ($sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) {
                    throw new Exception("Aba '{$sheetName}' não encontrada");
                }
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $sheetName = $sheet->getTitle();
            $allRows = $sheet->toArray();
            
            if (empty($allRows)) {
                throw new Exception('Planilha vazia');
            }

            // Extrair cabeçalhos
            $headerIndex = $headerRow - 1;
            if (!isset($allRows[$headerIndex])) {
                throw new Exception("Linha de cabeçalho ({$headerRow}) não encontrada");
            }

            $headers = array_map(function($cell) {
                return trim($cell ?? '');
            }, $allRows[$headerIndex]);

            // Remover colunas vazias do cabeçalho
            $headers = array_filter($headers, function($header) {
                return !empty($header);
            });
            $headers = array_values($headers);

            // Extrair dados
            $data = [];
            $dataStartIndex = $dataStartRow - 1;
            
            for ($i = $dataStartIndex; $i < count($allRows); $i++) {
                $row = $allRows[$i];
                
                // Verificar se a linha está vazia
                if (empty(array_filter($row, function($cell) {
                    return !empty(trim($cell ?? ''));
                }))) {
                    continue;
                }

                // Garantir que a linha tem o mesmo número de colunas do cabeçalho
                $row = array_pad($row, count($headers), '');
                $row = array_slice($row, 0, count($headers));
                
                $dataRow = [];
                foreach ($headers as $index => $header) {
                    $value = $row[$index] ?? '';
                    // Converter objetos DateTime para string
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }
                    $dataRow[$header] = $value;
                }
                
                $data[] = $dataRow;
            }

            return [
                'sheet_name' => $sheetName,
                'headers' => $headers,
                'data' => $data,
                'total_rows' => count($data),
                'total_columns' => count($headers)
            ];

        } catch (Exception $e) {
            throw new Exception('Erro ao processar Excel: ' . $e->getMessage());
        }
    }

    /**
     * Lê somente o cabeçalho (nomes das colunas) de uma planilha grande de forma otimizada.
     * - Usado para montar a lista de campos na tela de criação de dashboard.
     */
    public function readHeaders(string $filePath, string $fileType, int $headerRow = 1, ?string $sheetName = null): array
    {
        // Mesmos limites de segurança, mas a leitura é bem mais leve
        @ini_set('memory_limit', '512M');
        @ini_set('max_execution_time', '300');
        @set_time_limit(300);

        $fullPath = dirname(__DIR__, 4) . '/public/adms/uploads/' . $filePath;

        if (!file_exists($fullPath)) {
            throw new Exception('Arquivo não encontrado: ' . $filePath);
        }

        // CSV é simples: podemos reutilizar readCsv e pegar apenas os headers
        if (strtolower($fileType) === 'csv') {
            $csvResult = $this->readCsv($fullPath, $headerRow, $headerRow + 1);
            return [
                'headers' => $csvResult['headers'],
                'total_columns' => count($csvResult['headers'])
            ];
        }

        try {
            $reader = IOFactory::createReaderForFile($fullPath);

            if (method_exists($reader, 'setReadDataOnly')) {
                $reader->setReadDataOnly(true);
            }
            if ($sheetName && method_exists($reader, 'setLoadSheetsOnly')) {
                $reader->setLoadSheetsOnly([$sheetName]);
            }

            $spreadsheet = $reader->load($fullPath);

            if ($sheetName) {
                $sheet = $spreadsheet->getSheetByName($sheetName);
                if (!$sheet) {
                    throw new Exception("Aba '{$sheetName}' não encontrada");
                }
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $rowIterator = $sheet->getRowIterator($headerRow, $headerRow);
            $headers = [];

            foreach ($rowIterator as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $value = trim((string)($cell ? $cell->getValue() : ''));
                    $headers[] = $value;
                }
            }

            // Remover colunas completamente vazias
            $headers = array_filter($headers, function ($header) {
                return $header !== '';
            });
            $headers = array_values($headers);

            return [
                'headers' => $headers,
                'total_columns' => count($headers)
            ];
        } catch (Exception $e) {
            throw new Exception('Erro ao ler cabeçalho da planilha: ' . $e->getMessage());
        }
    }

    /**
     * Gera configuração de colunas baseada nos cabeçalhos
     */
    private function generateColumnsConfig(array $headers): array
    {
        $config = [];
        
        foreach ($headers as $index => $header) {
            // Detectar tipo de dado
            $type = 'text';
            $headerLower = strtolower($header);
            
            if (stripos($headerLower, 'data') !== false || stripos($headerLower, 'date') !== false) {
                $type = 'date';
            } elseif (stripos($headerLower, 'valor') !== false || 
                     stripos($headerLower, 'preço') !== false || 
                     stripos($headerLower, 'total') !== false ||
                     stripos($headerLower, 'quantidade') !== false ||
                     stripos($headerLower, 'qtd') !== false) {
                $type = 'number';
            } elseif (stripos($headerLower, 'id') !== false || 
                     stripos($headerLower, 'codigo') !== false ||
                     stripos($headerLower, 'código') !== false) {
                $type = 'number';
            }

            $config[] = [
                'name' => $header,
                'type' => $type,
                'index' => $index
            ];
        }

        return $config;
    }

    /**
     * Obtém lista de abas de um arquivo Excel
     */
    public function getSheetNames(string $filePath): array
    {
        $fullPath = dirname(__DIR__, 4) . '/public/adms/uploads/' . $filePath;
        
        if (!file_exists($fullPath)) {
            throw new Exception('Arquivo não encontrado');
        }

        try {
            $spreadsheet = IOFactory::load($fullPath);
            return $spreadsheet->getSheetNames();
        } catch (Exception $e) {
            throw new Exception('Erro ao ler abas do Excel: ' . $e->getMessage());
        }
    }
}


