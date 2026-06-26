<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\inventory\InvCostDreImportsRepository;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Services\InvCostEnergyRedistributionService;

class InvCostDreImportService
{
    public static function buildTemplateCsv(): string
    {
        $lines = [
            ['codigo', 'descricao', 'valor'],
            ['29', 'Energia Elétrica', '2013724,85'],
            ['872', 'Despesas com Análises/Pesquisas', '2000930,07'],
            ['877', 'Despesas com Manutenção de Máq.e Equip.', '1751193,30'],
            ['874', 'Despesas com Serviços de Vigilância/Segurança', '717190,21'],
            ['820', 'Salários', '5140264,92'],
        ];

        $out = fopen('php://temp', 'r+');
        if ($out === false) {
            return '';
        }
        foreach ($lines as $line) {
            fputcsv($out, $line, ';');
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return is_string($csv) ? $csv : '';
    }

    /**
     * @return array{success: bool, message: string, rows_imported?: int, import_id?: int}
     */
    public function importCsvForPeriod(
        int $periodId,
        string $filePath,
        ?string $originalFilename,
        bool $replacePrevious,
        ?int $importedBy
    ): array {
        if ($periodId <= 0) {
            return ['success' => false, 'message' => 'Período inválido.'];
        }

        $periodRepo = new InvCostPeriodsRepository();
        $period = $periodRepo->getOne($periodId);
        if ($period === false) {
            return ['success' => false, 'message' => 'Período não encontrado.'];
        }
        if ((string)($period['status'] ?? '') === 'closed') {
            return ['success' => false, 'message' => 'Período fechado: não é permitido importar DRE.'];
        }
        if (!is_readable($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado ou ilegível.'];
        }

        $parsed = $this->parseCsvFile($filePath);
        if ($parsed['rows'] === []) {
            return ['success' => false, 'message' => $parsed['error'] ?? 'Nenhuma linha válida no arquivo.'];
        }

        $poolsRepo = new InvCostExpensePoolsRepository();

        $importsRepo = new InvCostDreImportsRepository();
        $importId = $importsRepo->create([
            'inv_cost_period_id' => $periodId,
            'filename' => $originalFilename,
            'rows_imported' => 0,
            'replace_previous' => $replacePrevious,
            'imported_by' => $importedBy,
            'notes' => $replacePrevious ? 'Substituiu despesas anteriores do período.' : 'Atualizou contas existentes (sem duplicar).',
        ]);

        $updated = 0;
        $deduped = 0;
        if ($replacePrevious) {
            $poolsRepo->deleteByPeriod($periodId);
            $inserted = $poolsRepo->insertBatch($periodId, $importId, $parsed['rows']);
        } else {
            $upsert = $poolsRepo->upsertImportRows($periodId, $importId, $parsed['rows']);
            $inserted = (int)($upsert['inserted'] ?? 0);
            $updated = (int)($upsert['updated'] ?? 0);
            $deduped = (int)($upsert['deduped'] ?? 0);
        }

        $rowsAffected = $inserted + $updated;
        if ($rowsAffected === 0) {
            return ['success' => false, 'message' => 'Nenhuma despesa foi gravada. Verifique o formato do arquivo.'];
        }

        $importsRepo->updateRowsImported($importId, $rowsAffected);

        $energyNote = '';
        if (!empty($period['energy_auto_split'])) {
            $splitResult = (new InvCostEnergyRedistributionService())->applyForPeriod($periodId);
            if ($splitResult['success'] && (int)($splitResult['pools_split'] ?? 0) > 0) {
                $energyNote = ' ' . $splitResult['message'];
            }
        }

        $importedTotal = array_sum(array_map(static fn(array $r): float => (float) ($r['amount'] ?? 0), $parsed['rows']));
        if ($replacePrevious) {
            $message = sprintf(
                'Importadas %d linha(s) (substituiu despesas anteriores). Total R$ %s.',
                $rowsAffected,
                number_format($importedTotal, 2, ',', '.')
            );
        } else {
            $message = sprintf(
                'Processadas %d linha(s): %d nova(s), %d atualizada(s). Total R$ %s.',
                $rowsAffected,
                $inserted,
                $updated,
                number_format($importedTotal, 2, ',', '.')
            );
            if ($deduped > 0) {
                $message .= sprintf(' Removidas %d duplicata(s) de contas repetidas.', $deduped);
            }
        }
        if ($energyNote !== '') {
            $message .= $energyNote;
        }
        $withoutCode = (int) ($parsed['without_code_count'] ?? 0);
        if ($withoutCode > 0) {
            $message .= " {$withoutCode} linha(s) sem código na coluna A foram incluídas com prefixo NC- (confira a descrição).";
        }
        $skippedAmount = (int) ($parsed['skipped_no_amount'] ?? 0);
        if ($skippedAmount > 0) {
            $message .= " {$skippedAmount} linha(s) ignorada(s) por valor zerado ou inválido.";
        }

        return [
            'success' => true,
            'message' => $message,
            'rows_imported' => $rowsAffected,
            'import_id' => $importId,
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, error?: string}
     */
    private function parseCsvFile(string $filePath): array
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            return ['rows' => [], 'error' => 'Não foi possível abrir o arquivo.'];
        }

        if (str_starts_with($raw, "PK\x03\x04")) {
            return [
                'rows' => [],
                'error' => 'O arquivo parece ser Excel (.xlsx). No Excel use Salvar como → CSV UTF-8 (separado por ponto e vírgula) e importe o .csv.',
            ];
        }

        $raw = $this->normalizeFileEncoding($raw);
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            return ['rows' => [], 'error' => 'Não foi possível processar o arquivo.'];
        }
        fwrite($handle, $raw);
        rewind($handle);

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return ['rows' => [], 'error' => 'Arquivo vazio.'];
        }

        $delimiter = $this->detectDelimiter($firstLine);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter) ?: [];
        if (isset($header[0])) {
            $header[0] = $this->stripUtf8Bom((string)$header[0]);
        }
        $header = array_map(fn($h) => $this->normalizeHeader((string)$h), $header);
        $hasHeader = $this->looksLikeHeader($header);

        $rows = [];
        $skippedNoAmount = 0;
        $withoutCodeCount = 0;
        $lineNum = 0;

        if (!$hasHeader) {
            rewind($handle);
        }

        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNum++;
            if ($line === [null] || $line === []) {
                continue;
            }

            $row = $hasHeader ? $this->mapHeaderRow($header, $line) : $this->mapPositionalRow($line);
            $description = trim((string)($row['description'] ?? ''));
            $account = $this->normalizeAccountCode(trim((string)($row['account_code'] ?? '')));
            $amount = $this->parseAmount($row['amount'] ?? 0);

            if ($account === '' && $description === '' && abs($amount) < 0.0001) {
                continue;
            }

            if (abs($amount) < 0.0001) {
                $skippedNoAmount++;
                continue;
            }

            if ($account === '') {
                $account = 'NC-' . $lineNum;
                $withoutCodeCount++;
            }

            $rows[] = [
                'account_code' => $account,
                'description' => $description,
                'amount' => abs($amount),
                'area' => trim((string)($row['area'] ?? '')),
                'source' => 'DRE',
            ];
        }

        fclose($handle);

        if ($rows === []) {
            $hint = 'Use 3 colunas: codigo;descricao;valor. Valores negativos e formato -R$ 1.234,56 são aceitos.';
            if ($skippedNoAmount > 0) {
                $hint = "Valores não reconhecidos em {$skippedNoAmount} linha(s). Confira formato monetário (ex.: -R$ 2.013.724,85). " . $hint;
            }

            return ['rows' => [], 'error' => $hint];
        }

        return [
            'rows' => $rows,
            'without_code_count' => $withoutCodeCount,
            'skipped_no_amount' => $skippedNoAmount,
        ];
    }

    private function normalizeAccountCode(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        $numeric = str_replace(',', '.', $raw);
        if (is_numeric($numeric)) {
            $n = (float) $numeric;
            if (abs($n - round($n)) < 0.0001) {
                return (string) (int) round($n);
            }
        }

        return $raw;
    }

    private function stripUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    private function normalizeFileEncoding(string $raw): string
    {
        $raw = $this->stripUtf8Bom($raw);
        if (mb_check_encoding($raw, 'UTF-8')) {
            return $raw;
        }

        foreach (['Windows-1252', 'ISO-8859-1', 'CP850'] as $encoding) {
            $converted = @mb_convert_encoding($raw, 'UTF-8', $encoding);
            if (is_string($converted) && mb_check_encoding($converted, 'UTF-8')) {
                return $converted;
            }
        }

        return mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
    }

    private function detectDelimiter(string $firstLine): string
    {
        $counts = [
            ';' => substr_count($firstLine, ';'),
            ',' => substr_count($firstLine, ','),
            "\t" => substr_count($firstLine, "\t"),
        ];
        arsort($counts);
        $best = (string) array_key_first($counts);

        return ($counts[$best] ?? 0) > 0 ? $best : ';';
    }

  /**
     * @param list<string> $header
     * @param list<string|null> $line
     * @return array<string, string>
     */
    private function mapHeaderRow(array $header, array $line): array
    {
        $row = [];
        foreach ($header as $i => $key) {
            if ($key === '') {
                continue;
            }
            $row[$key] = trim((string)($line[$i] ?? ''));
        }

        if (!isset($row['account_code']) && isset($line[0])) {
            $row['account_code'] = trim((string)$line[0]);
        }
        if (!isset($row['description']) && isset($line[1])) {
            $row['description'] = trim((string)$line[1]);
        }
        if (!isset($row['amount'])) {
            $amountIdx = null;
            foreach ($header as $i => $key) {
                if ($key === 'amount') {
                    $amountIdx = $i;
                    break;
                }
            }
            if ($amountIdx === null) {
                $amountIdx = 2;
            }
            $row['amount'] = $this->joinAmountColumns(array_slice($line, $amountIdx));
        } elseif (isset($line[2]) && count($line) > 3) {
            $amountIdx = 2;
            foreach ($header as $i => $key) {
                if ($key === 'amount') {
                    $amountIdx = $i;
                    break;
                }
            }
            $row['amount'] = $this->joinAmountColumns(array_slice($line, $amountIdx));
        }

        return $row;
    }

    /**
     * @param list<string|null> $line
     * @return array<string, string>
     */
    private function mapPositionalRow(array $line): array
    {
        $account = trim((string)($line[0] ?? ''));
        $description = trim((string)($line[1] ?? ''));
        $rest = array_slice($line, 2);
        $area = '';
        if (count($rest) > 1) {
            $last = trim((string) $rest[array_key_last($rest)]);
            if ($last !== '' && !preg_match('/^-?[\dR\$]/u', $last)) {
                $area = $last;
                array_pop($rest);
            }
        }
        $amount = $this->joinAmountColumns($rest);

        return [
            'account_code' => $account,
            'description' => $description,
            'amount' => $amount,
            'area' => $area,
        ];
    }

    /**
     * @param list<string|null> $parts
     */
    private function joinAmountColumns(array $parts): string
    {
        $parts = array_values(array_map(static fn($p) => trim((string)$p), $parts));
        $parts = array_filter($parts, static fn(string $p): bool => $p !== '');
        if ($parts === []) {
            return '';
        }
        if (count($parts) === 1) {
            return $parts[0];
        }

        $last = array_pop($parts);
        if (preg_match('/^-?\d{1,2}$/', $last) && $parts !== []) {
            $head = array_pop($parts);

            return $head . ',' . $last;
        }

        return implode(',', $parts);
    }

    /**
     * @param list<string> $header
     */
    private function looksLikeHeader(array $header): bool
    {
        foreach ($header as $cell) {
            if (str_contains($cell, 'conta') || str_contains($cell, 'account')
                || str_contains($cell, 'cod') || str_contains($cell, 'código') || str_contains($cell, 'codigo')) {
                return true;
            }
            if (str_contains($cell, 'descr') || str_contains($cell, 'nome')) {
                return true;
            }
            if (str_contains($cell, 'valor') || str_contains($cell, 'amount') || str_contains($cell, 'saldo')) {
                return true;
            }
        }

        return false;
    }

    private function normalizeHeader(string $value): string
    {
        $v = mb_strtolower(trim($value), 'UTF-8');
        return match (true) {
            str_contains($v, 'conta') || str_contains($v, 'account')
                || str_contains($v, 'cod') || str_contains($v, 'código') || str_contains($v, 'codigo') => 'account_code',
            str_contains($v, 'descr') || str_contains($v, 'nome') => 'description',
            str_contains($v, 'valor') || str_contains($v, 'amount') || str_contains($v, 'saldo') || $v === 'r$' => 'amount',
            str_contains($v, 'area') || str_contains($v, 'área') => 'area',
            default => $v,
        };
    }

    private function parseAmount(mixed $value): float
    {
        if (!is_string($value)) {
            return is_numeric($value) ? round((float) $value, 4) : 0.0;
        }

        $value = trim($this->stripUtf8Bom($value));
        $value = str_replace("\xc2\xa0", '', $value);

        $negative = false;
        if (preg_match('/^\s*\((.+)\)\s*$/u', $value, $m)) {
            $negative = true;
            $value = trim($m[1]);
        }

        if (preg_match('/^[\-\x{2212}]/u', $value)) {
            $negative = true;
            $value = preg_replace('/^[\-\x{2212}]+\s*/u', '', $value) ?? $value;
        }

        $value = preg_replace('/^R\$\s*/iu', '', $value) ?? $value;
        $value = trim($value);

        if (preg_match('/^[\-\x{2212}]/u', $value)) {
            $negative = true;
            $value = preg_replace('/^[\-\x{2212}]+\s*/u', '', $value) ?? $value;
        }
        $value = preg_replace('/^R\$\s*/iu', '', $value) ?? $value;
        $value = trim($value);

        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return 0.0;
        }

        $num = (float) $value;

        return round($negative ? -$num : $num, 4);
    }
}
