<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\InvCostAllocationSplitHelper;
use App\adms\Helpers\InvCostRhDistributionHelper;
use App\adms\Models\Repository\inventory\InvCostExpensePoolsRepository;
use App\adms\Models\Repository\inventory\InvCostPeriodsRepository;
use App\adms\Models\Repository\inventory\InvCostRhDistributionImportsRepository;
use App\adms\Models\Repository\inventory\InvCostRhDistributionLinesRepository;

class InvCostRhDistributionService
{
    public static function buildTemplateCsv(): string
    {
        $lines = [
            ['area', 'valor', 'criterio'],
            ['Produção', '181338,22', '2'],
            ['Controle de Qualidade', '48115,00', '4'],
            ['Pesquisa e Desenvolvimento', '42550,00', '6'],
            ['Administrativo', '15890,00', '1'],
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
     * @return list<array<string, mixed>>
     */
    public function getLinesForPeriod(int $periodId): array
    {
        return (new InvCostRhDistributionLinesRepository())->getByPeriod($periodId);
    }

    public function hasDistribution(int $periodId): bool
    {
        return $this->getLinesForPeriod($periodId) !== [];
    }

    /**
     * @param list<array<string, mixed>> $pools
     */
    public function sumPersonnelPoolAmount(array $pools): float
    {
        $total = 0.0;
        foreach ($pools as $pool) {
            if (!InvCostRhDistributionHelper::isPersonnelPoolRow($pool)) {
                continue;
            }
            $total += max(0.0, (float)($pool['amount'] ?? 0));
        }

        return round($total, 4);
    }

    /**
     * @return array{
     *   personnel_pool_total: float,
     *   simulation_increase_pct: float|null,
     *   effective_pool_total: float,
     *   distribution_total: float,
     *   lines: list<array<string, mixed>>,
     *   slices: list<array<string, mixed>>
     * }
     */
    public function buildPreview(int $periodId, ?float $simulationIncreasePct = null): array
    {
        $lines = $this->getLinesForPeriod($periodId);
        $pools = (new InvCostExpensePoolsRepository())->getByPeriodWithRules($periodId);
        $personnelTotal = $this->sumPersonnelPoolAmount($pools);

        if ($simulationIncreasePct === null) {
            $period = (new InvCostPeriodsRepository())->getOne($periodId);
            if (is_array($period) && $period['rh_simulation_increase_pct'] !== null && $period['rh_simulation_increase_pct'] !== '') {
                $simulationIncreasePct = (float)$period['rh_simulation_increase_pct'];
            }
        }

        $factor = 1.0;
        if ($simulationIncreasePct !== null && $simulationIncreasePct > 0) {
            $factor = 1.0 + ($simulationIncreasePct / 100.0);
        }

        $effectivePool = round($personnelTotal * $factor, 4);
        $distTotal = 0.0;
        foreach ($lines as $line) {
            $distTotal += (float)($line['amount'] ?? 0);
        }

        $slices = [];
        $lineWeights = [];
        foreach ($lines as $line) {
            $sharePct = (float)($line['share_pct'] ?? 0);
            if ($sharePct > 0) {
                $lineWeights[(string)($line['area_name'] ?? '')] = $sharePct;
            }
        }
        $sliceByArea = InvCostAllocationSplitHelper::split($effectivePool, $lineWeights);

        foreach ($lines as $line) {
            $area = (string)($line['area_name'] ?? '');
            $sharePct = (float)($line['share_pct'] ?? 0);
            $sliceAmount = $sliceByArea[$area] ?? 0.0;
            $slices[] = [
                'area_name' => $area,
                'amount' => (float)($line['amount'] ?? 0),
                'share_pct' => $sharePct,
                'criterion' => (int)($line['criterion'] ?? 2),
                'slice_amount' => $sliceAmount,
            ];
        }

        return [
            'personnel_pool_total' => $personnelTotal,
            'simulation_increase_pct' => $simulationIncreasePct,
            'effective_pool_total' => $effectivePool,
            'distribution_total' => round($distTotal, 4),
            'lines' => $lines,
            'slices' => $slices,
        ];
    }

    /**
     * @return array{success: bool, message: string, rows_imported?: int}
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
            return ['success' => false, 'message' => 'Período fechado: distribuição RH bloqueada.'];
        }
        if (!is_readable($filePath)) {
            return ['success' => false, 'message' => 'Arquivo não encontrado ou ilegível.'];
        }

        $parsed = $this->parseCsvFile($filePath);
        if ($parsed['rows'] === []) {
            return ['success' => false, 'message' => $parsed['error'] ?? 'Nenhuma linha válida no arquivo.'];
        }

        $importsRepo = new InvCostRhDistributionImportsRepository();
        $linesRepo = new InvCostRhDistributionLinesRepository();

        $importId = $importsRepo->create([
            'inv_cost_period_id' => $periodId,
            'filename' => $originalFilename,
            'rows_imported' => 0,
            'replace_previous' => $replacePrevious,
            'imported_by' => $importedBy,
            'notes' => $replacePrevious ? 'Substituiu distribuição anterior.' : 'Acrescentou/atualizou linhas.',
        ]);

        if ($replacePrevious) {
            $linesRepo->deleteByPeriod($periodId);
        }

        $inserted = $linesRepo->insertBatch($periodId, $importId, $parsed['rows']);
        if ($inserted <= 0) {
            return ['success' => false, 'message' => 'Nenhuma área foi gravada.'];
        }

        $message = "Distribuição RH importada: {$inserted} área(s). Percentuais calculados automaticamente.";
        if (!empty($parsed['warnings'])) {
            $message .= ' Atenção: ' . implode(' ', $parsed['warnings']);
        }

        return [
            'success' => true,
            'message' => $message,
            'rows_imported' => $inserted,
        ];
    }

    /**
     * @param array<string, mixed> $formLines keyed by index
     * @return array{success: bool, message: string}
     */
    public function saveManualForPeriod(int $periodId, array $formLines): array
    {
        if ($periodId <= 0) {
            return ['success' => false, 'message' => 'Período inválido.'];
        }

        $period = (new InvCostPeriodsRepository())->getOne($periodId);
        if ($period === false) {
            return ['success' => false, 'message' => 'Período não encontrado.'];
        }
        if ((string)($period['status'] ?? '') === 'closed') {
            return ['success' => false, 'message' => 'Período fechado.'];
        }

        $rows = [];
        foreach ($formLines as $row) {
            if (!is_array($row)) {
                continue;
            }
            $area = trim((string)($row['area_name'] ?? ''));
            $amount = $this->parseDecimal($row['amount'] ?? 0);
            if ($area === '' || $amount <= 0) {
                continue;
            }
            $rows[] = [
                'area_name' => $area,
                'amount' => $amount,
                'criterion' => InvCostRhDistributionHelper::parseCriterion($row['criterion'] ?? null, $area),
            ];
        }

        if ($rows === []) {
            return ['success' => false, 'message' => 'Informe ao menos uma área com valor maior que zero.'];
        }

        $count = (new InvCostRhDistributionLinesRepository())->replaceManualLines($periodId, $rows);

        return [
            'success' => true,
            'message' => "Distribuição RH salva ({$count} área(s)). Recalcule o snapshot para aplicar no CFIX.",
        ];
    }

    public function saveSimulationIncreasePct(int $periodId, ?float $pct): array
    {
        if ($periodId <= 0) {
            return ['success' => false, 'message' => 'Período inválido.'];
        }

        $periodRepo = new InvCostPeriodsRepository();
        $period = $periodRepo->getOne($periodId);
        if ($period === false) {
            return ['success' => false, 'message' => 'Período não encontrado.'];
        }

        $periodRepo->updateRhSimulationIncreasePct($periodId, $pct);

        $msg = $pct !== null && $pct > 0
            ? 'Simulação RH +' . number_format($pct, 2, ',', '.') . '% salva (não altera o custeio oficial).'
            : 'Simulação RH desativada.';

        return ['success' => true, 'message' => $msg];
    }

    /**
     * @return array{rows: list<array{area_name: string, amount: float, criterion: int}>, error?: string, warnings?: list<string>}
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

        $rows = [];
        $headerMap = null;
        $warnings = [];

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $cells = array_map(static fn($c) => trim((string)$c), $data);
            if ($cells === [] || implode('', $cells) === '') {
                continue;
            }

            if (isset($cells[0])) {
                $cells[0] = $this->stripUtf8Bom($cells[0]);
            }

            if ($headerMap === null) {
                $headerMap = $this->mapHeaders($cells);
                if ($headerMap !== null) {
                    continue;
                }
            }

            $area = $cells[0] ?? '';
            $amountRaw = $cells[1] ?? '';
            $criterionRaw = $cells[2] ?? '';
            if ($headerMap !== null) {
                $area = $cells[$headerMap['area']] ?? '';
                $amountRaw = $cells[$headerMap['valor']] ?? '';
                $criterionRaw = $headerMap['criterio'] !== null ? ($cells[$headerMap['criterio']] ?? '') : '';
            }

            $area = trim($area);
            $amount = $this->parseDecimal($amountRaw);
            if ($area === '' || $amount <= 0) {
                continue;
            }

            $rows[] = [
                'area_name' => $area,
                'amount' => $amount,
                'criterion' => InvCostRhDistributionHelper::parseCriterion($criterionRaw, $area),
            ];
        }

        fclose($handle);

        if ($rows === []) {
            return ['rows' => [], 'error' => 'Nenhuma linha válida. Use CSV UTF-8 com colunas area;valor (critério opcional).'];
        }

        $duplicateWarnings = $this->detectDuplicateAreaWarnings($rows);
        if ($duplicateWarnings !== []) {
            $warnings = array_merge($warnings, $duplicateWarnings);
        }

        $result = ['rows' => $rows];
        if ($warnings !== []) {
            $result['warnings'] = $warnings;
        }

        return $result;
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
     * @param list<array{area_name: string, amount: float, criterion: int}> $rows
     * @return list<string>
     */
    private function detectDuplicateAreaWarnings(array $rows): array
    {
        $seen = [];
        $warnings = [];
        foreach ($rows as $row) {
            $area = (string)($row['area_name'] ?? '');
            $key = InvCostRhDistributionHelper::normalizeAreaName($area);
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                $warnings[] = "Área duplicada no arquivo: \"{$area}\" (mesmo nome que \"{$seen[$key]}\"). Confira acentuação e nomes completos.";
                continue;
            }
            $seen[$key] = $area;
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @param list<string> $cells
     * @return array{area: int, valor: int, criterio: int|null}|null
     */
    private function mapHeaders(array $cells): ?array
    {
        $normalized = array_map(static function (string $c): string {
            $c = mb_strtolower($c, 'UTF-8');
            $c = str_replace(['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $c);

            return $c;
        }, $cells);

        $areaIdx = null;
        $valorIdx = null;
        $critIdx = null;
        foreach ($normalized as $i => $label) {
            if (in_array($label, ['area', 'área', 'departamento', 'setor'], true)) {
                $areaIdx = $i;
            }
            if (in_array($label, ['valor', 'salario', 'salário', 'amount', 'custo'], true)) {
                $valorIdx = $i;
            }
            if (in_array($label, ['criterio', 'critério', 'criterion'], true)) {
                $critIdx = $i;
            }
        }

        if ($areaIdx === null || $valorIdx === null) {
            return null;
        }

        return ['area' => $areaIdx, 'valor' => $valorIdx, 'criterio' => $critIdx];
    }

    private function parseDecimal(mixed $value): float
    {
        if (is_numeric($value)) {
            return max(0.0, (float)$value);
        }

        $raw = trim((string)$value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = str_replace(['R$', ' '], '', $raw);
        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        return is_numeric($raw) ? max(0.0, (float)$raw) : 0.0;
    }
}
