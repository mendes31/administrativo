<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Lê CSV/Excel e normaliza para CSV UTF-8 com separador ;.
 */
final class SpreadsheetImportReader
{
    /**
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string, row_count: int}
     */
    public function parseUpload(string $tmpPath, string $originalName): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $parsed = match ($ext) {
            'xlsx', 'xls' => $this->parseExcel($tmpPath),
            'csv', 'txt' => $this->parseCsv($tmpPath),
            default => null,
        };
        if ($parsed === null) {
            throw new \RuntimeException('Envie um arquivo Excel (.xlsx) ou CSV.');
        }
        if (count($parsed['headers']) < 1) {
            @unlink($parsed['normalized_path']);
            throw new \RuntimeException('Não foi possível ler o cabeçalho da planilha.');
        }

        return $parsed;
    }

    /**
     * @return list<list<string>>
     */
    public function readDataRows(string $path, string $delimiter = ';'): array
    {
        $fp = fopen($path, 'r');
        if ($fp === false) {
            throw new \RuntimeException('Arquivo de importação não encontrado.');
        }
        $bom = fread($fp, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fp);
        }
        $header = fgetcsv($fp, 0, $delimiter);
        if ($header === false) {
            fclose($fp);
            return [];
        }
        $rows = [];
        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $empty = true;
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    $empty = false;
                    break;
                }
            }
            if ($empty) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($fp);

        return $rows;
    }

    /**
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string, row_count: int}
     */
    private function parseExcel(string $tmpPath): ?array
    {
        try {
            $spreadsheet = IOFactory::load($tmpPath);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray(null, true, true, false);
        } catch (\Throwable) {
            return null;
        }
        if ($rows === []) {
            return null;
        }
        $rows = array_values(array_filter($rows, static function ($row): bool {
            if (!is_array($row)) {
                return false;
            }
            foreach ($row as $cell) {
                if (trim((string) $cell) !== '') {
                    return true;
                }
            }
            return false;
        }));
        if ($rows === []) {
            return null;
        }
        $headers = array_map(static fn ($v): string => trim((string) $v), $rows[0]);
        while ($headers !== [] && end($headers) === '') {
            array_pop($headers);
        }
        $colCount = count($headers);
        $data = [];
        for ($r = 1, $n = count($rows); $r < $n; $r++) {
            $row = $rows[$r];
            if (!is_array($row)) {
                continue;
            }
            $cells = [];
            for ($c = 0; $c < $colCount; $c++) {
                $cells[] = trim((string) ($row[$c] ?? ''));
            }
            if (count(array_filter($cells, static fn ($v): bool => $v !== '')) === 0) {
                continue;
            }
            $data[] = $cells;
        }

        return $this->writeNormalized($headers, $data);
    }

    /**
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string, row_count: int}|null
     */
    private function parseCsv(string $tmpPath): ?array
    {
        $content = (string) file_get_contents($tmpPath);
        $content = $this->normalizeCsvContent($content);
        $delimiter = $this->detectDelimiter($content);
        $tmp = tempnam(sys_get_temp_dir(), 'imp_csv_');
        if ($tmp === false) {
            return null;
        }
        file_put_contents($tmp, $content);
        $fp = fopen($tmp, 'r');
        if ($fp === false) {
            @unlink($tmp);
            return null;
        }
        $headers = fgetcsv($fp, 0, $delimiter);
        if ($headers === false) {
            fclose($fp);
            @unlink($tmp);
            return null;
        }
        $headers = array_map(static fn ($v): string => trim((string) $v), $headers);
        $data = [];
        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $cells = array_map(static fn ($v): string => trim((string) $v), $row);
            if (count(array_filter($cells, static fn ($v): bool => $v !== '')) === 0) {
                continue;
            }
            $data[] = $cells;
        }
        fclose($fp);
        @unlink($tmp);

        return $this->writeNormalized($headers, $data);
    }

    /**
     * @param list<string> $headers
     * @param list<list<string>> $data
     * @return array{headers: list<string>, preview: list<list<string>>, delimiter: string, normalized_path: string, row_count: int}
     */
    private function writeNormalized(array $headers, array $data): array
    {
        $path = tempnam(sys_get_temp_dir(), 'imp_norm_');
        if ($path === false) {
            throw new \RuntimeException('Não foi possível criar arquivo temporário.');
        }
        $fp = fopen($path, 'w');
        if ($fp === false) {
            throw new \RuntimeException('Não foi possível gravar o arquivo normalizado.');
        }
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, $headers, ';');
        foreach ($data as $line) {
            fputcsv($fp, $line, ';');
        }
        fclose($fp);

        return [
            'headers' => $headers,
            'preview' => array_slice($data, 0, 5),
            'delimiter' => ';',
            'normalized_path' => $path,
            'row_count' => count($data),
        ];
    }

    private function normalizeCsvContent(string $content): string
    {
        if (str_starts_with($content, "\xFF\xFE")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($content, "\xFE\xFF")) {
            $content = mb_convert_encoding(substr($content, 2), 'UTF-8', 'UTF-16BE');
        } elseif (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    private function detectDelimiter(string $content): string
    {
        $line = strtok($content, "\n") ?: '';
        $counts = [';' => substr_count($line, ';'), ',' => substr_count($line, ','), "\t" => substr_count($line, "\t")];
        arsort($counts);
        $best = array_key_first($counts);

        return ($counts[$best] ?? 0) > 0 ? (string) $best : ';';
    }
}
