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
        $rows = self::skipOptionalLabelRow($rows);
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
        $all = [];
        while (($row = fgetcsv($fp, 0, $delimiter)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $cells = array_map(static fn ($v): string => trim((string) $v), $row);
            if (count(array_filter($cells, static fn ($v): bool => $v !== '')) === 0) {
                continue;
            }
            $all[] = $cells;
        }
        fclose($fp);
        @unlink($tmp);
        if ($all === []) {
            return null;
        }
        $all = self::skipOptionalLabelRow($all);
        $headers = array_map(static fn ($v): string => trim((string) $v), $all[0]);
        $data = array_values(array_slice($all, 1));

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

    /**
     * Modelo CSV: 1ª linha = rótulos da tela, 2ª = nomes dos campos.
     * Arquivos antigos (só nomes dos campos na 1ª linha) continuam válidos.
     *
     * @param list<list<mixed>> $rows
     * @return list<list<mixed>>
     */
    public static function skipOptionalLabelRow(array $rows): array
    {
        if (count($rows) < 2) {
            return $rows;
        }
        $first = array_map(static fn ($v): string => trim((string) $v), $rows[0]);
        $second = array_map(static fn ($v): string => trim((string) $v), $rows[1]);
        if (!self::looksLikeFieldHeader($first) && self::looksLikeFieldHeader($second)) {
            array_shift($rows);

            return array_values($rows);
        }

        return $rows;
    }

    /**
     * @param list<string> $headers
     * @param array<string, string> $fields campo => rótulo
     * @return array<string, int>
     */
    public static function suggestFieldMap(array $headers, array $fields): array
    {
        $aliases = [
            'departamento' => 'department',
            'setor' => 'department',
            'department_id' => 'department',
            'cargo' => 'position',
            'position_id' => 'position',
            'usuario' => 'username',
            'login' => 'username',
            'e-mail' => 'email',
            'email_corporativo' => 'email',
        ];
        $labelToField = [];
        foreach ($fields as $field => $label) {
            $labelToField[self::normalizeHeaderKey((string) $label)] = $field;
        }

        $suggested = [];
        foreach ($headers as $i => $header) {
            $raw = trim((string) $header);
            if ($raw === '') {
                continue;
            }
            if (isset($fields[$raw]) && !isset($suggested[$raw])) {
                $suggested[$raw] = $i;
                continue;
            }
            $h = self::normalizeHeaderKey($raw);
            if (isset($fields[$h]) && !isset($suggested[$h])) {
                $suggested[$h] = $i;
                continue;
            }
            if (isset($labelToField[$h]) && !isset($suggested[$labelToField[$h]])) {
                $suggested[$labelToField[$h]] = $i;
                continue;
            }
            if (isset($aliases[$h], $fields[$aliases[$h]]) && !isset($suggested[$aliases[$h]])) {
                $suggested[$aliases[$h]] = $i;
            }
        }

        return $suggested;
    }

    /**
     * @param list<string> $row
     */
    public static function looksLikeFieldHeader(array $row): bool
    {
        $named = 0;
        $total = 0;
        foreach ($row as $cell) {
            $v = trim((string) $cell);
            if ($v === '') {
                continue;
            }
            $total++;
            if (preg_match('/^[a-z][a-z0-9_]*$/i', $v) === 1) {
                $named++;
            }
        }

        return $total > 0 && $named >= (int) ceil($total * 0.7);
    }

    public static function normalizeHeaderKey(string $header): string
    {
        $h = mb_strtolower(trim($header), 'UTF-8');
        $h = strtr($h, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ú' => 'u',
            'ç' => 'c',
            'º' => 'o', '°' => 'o',
        ]);
        $h = str_replace([' ', '-'], '_', $h);

        return $h;
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
