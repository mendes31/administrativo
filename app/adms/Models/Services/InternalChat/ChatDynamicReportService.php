<?php

namespace App\adms\Models\Services\InternalChat;

use App\adms\Helpers\DynamicReportValueFormatter;
use App\adms\Models\Repository\DynamicReportsRepository;
use App\adms\Models\Services\DynamicQueryBuilderService;

/**
 * Expõe relatórios dinâmicos (chat_enabled) como tools do Assistente MCP.
 */
class ChatDynamicReportService
{
    private const MAX_ROWS_IN_CHAT = 15;

    private DynamicReportsRepository $repo;
    private DynamicQueryBuilderService $queryBuilder;

    public function __construct(
        ?DynamicReportsRepository $repo = null,
        ?DynamicQueryBuilderService $queryBuilder = null
    ) {
        $this->repo = $repo ?? new DynamicReportsRepository();
        $this->queryBuilder = $queryBuilder ?? new DynamicQueryBuilderService();
    }

    /**
     * @return list<array{id:int, name:string, chat_tool_name:?string, chat_description:?string, examples:list<string>, visualization_type:string}>
     */
    public function listCatalogForUser(int $userId): array
    {
        $rows = $this->repo->getChatEnabledReports();
        $out = [];
        foreach ($rows as $report) {
            // user_id 0 (CLI): lista todos os marcados para chat.
            if ($userId > 0 && !$this->repo->userCanViewReport($report, $userId)) {
                continue;
            }
            $out[] = $this->summarizeReport($report);
        }

        return $out;
    }

    /**
     * Resolve por id, tool_name, nome ou exemplo de pergunta e executa.
     *
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function resolveAndRun(int $userId, string $query): array
    {
        $report = $this->resolveReport($userId, $query);
        if ($report === null) {
            $catalog = $this->listCatalogForUser($userId);
            $lines = ['Não encontrei um relatório do chat correspondente.'];
            if ($catalog !== []) {
                $lines[] = 'Disponíveis:';
                foreach ($catalog as $item) {
                    $tool = $item['chat_tool_name'] ?: ('#' . $item['id']);
                    $lines[] = "• {$item['name']} (tool: {$tool})";
                }
            } else {
                $lines[] = 'Nenhum relatório está marcado como «Disponível no chat». Edite o relatório no construtor e ative a opção.';
            }

            return [
                'ok' => false,
                'resposta' => implode("\n", $lines),
                'tool' => 'report.run',
            ];
        }

        return $this->runById($userId, (int) $report['id']);
    }

    /**
     * Filtra o relatório por mês/ano (DocDate ou coluna de competência na saída).
     *
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function runByIdFilteredByMonth(int $userId, int $reportId, int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        if ($year < 100) {
            $year = $year >= 70 ? 1900 + $year : 2000 + $year;
        }

        $report = $this->repo->getById($reportId);
        if (!$report || empty($report['chat_enabled'])) {
            return [
                'ok' => false,
                'resposta' => 'Relatório não encontrado ou não está disponível no chat.',
                'tool' => 'report.run',
            ];
        }
        if ($userId > 0 && !$this->repo->userCanViewReport($report, $userId)) {
            return [
                'ok' => false,
                'resposta' => 'Sem permissão para executar este relatório no chat.',
                'tool' => 'report.run',
            ];
        }

        $name = (string) ($report['name'] ?? 'Relatório');
        $viz = (string) ($report['visualization_type'] ?? 'table');
        $reportUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/view-dynamic-report/' . $reportId;
        $periodLabel = self::monthYearLabel($month, $year);

        $rows = [];
        $usedSqlFilter = false;

        if (($report['query_mode'] ?? '') === 'custom_sql' || !empty($report['custom_sql'])) {
            $wrapped = $this->wrapSqlWithMonthFilter((string) $report['custom_sql'], $month, $year);
            if ($wrapped !== null) {
                $cfg = $report;
                $cfg['custom_sql'] = $wrapped;
                $cfg['query_mode'] = 'custom_sql';
                $cfg['cache_namespace'] = 'chat_report_month_' . $reportId . '_' . $year . $month;
                $cfg['force_refresh'] = true;
                $cfg['page'] = 1;
                $cfg['per_page'] = self::MAX_ROWS_IN_CHAT;
                try {
                    $result = $this->queryBuilder->executeReport($cfg);
                    if (!empty($result['success'])) {
                        $rows = is_array($result['data'] ?? null) ? $result['data'] : [];
                        $usedSqlFilter = true;
                    }
                } catch (\Throwable) {
                    $rows = [];
                }
            }
        }

        if (!$usedSqlFilter) {
            $base = $this->runById($userId, $reportId, 500);
            if (empty($base['ok'])) {
                return $base;
            }
            $data = is_array($base['data'] ?? null) ? $base['data'] : [];
            $rows = $this->filterRowsByMonth(is_array($data['rows'] ?? null) ? $data['rows'] : [], $month, $year);
            $reportUrl = (string) ($data['report_url'] ?? $reportUrl);
            $viz = (string) ($data['visualization_type'] ?? $viz);
        }

        if ($rows === []) {
            return [
                'ok' => false,
                'resposta' => sprintf(
                    'Não encontrei dados de %s no relatório «%s». Tente outro mês (ex.: 07/2026, jul/2026) ou abra o relatório completo.',
                    $periodLabel,
                    $name
                ),
                'tool' => 'report.run',
                'data' => [
                    'report_id' => $reportId,
                    'name' => $name,
                    'filter_month' => $month,
                    'filter_year' => $year,
                    'rows_count' => 0,
                    'rows' => [],
                    'report_url' => $reportUrl,
                ],
            ];
        }

        $preview = array_slice($rows, 0, self::MAX_ROWS_IN_CHAT);
        $total = count($rows);
        $lines = [
            sprintf('Filtro %s no relatório «%s»: %d linha(s).', $periodLabel, $name, $total),
        ];

        return [
            'ok' => true,
            'resposta' => implode("\n", $lines),
            'tool' => 'report.run',
            'data' => [
                'report_id' => $reportId,
                'name' => $name,
                'filter_month' => $month,
                'filter_year' => $year,
                'report_url' => $reportUrl,
                'visualization_type' => $viz,
                'chart_config' => $report['chart_config'] ?? [],
                'rows_count' => $total,
                'rows' => $preview,
                'rows_display' => DynamicReportValueFormatter::formatRows($preview),
                'chart' => self::chartWithFormattedLabels(self::buildChartFromRows($preview, $viz)),
            ],
        ];
    }

    public static function monthYearLabel(int $month, int $year): string
    {
        $names = [
            1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
            5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
            9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
        ];

        return ($names[$month] ?? (string) $month) . '/' . $year;
    }

    /**
     * Reexecuta o relatório aplicando o código na consulta (não só nas 15 linhas do chat).
     *
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function runByIdFilteredByCode(int $userId, int $reportId, string $code): array
    {
        $code = trim($code);
        if ($code === '') {
            return [
                'ok' => false,
                'resposta' => 'Informe um código para filtrar o relatório.',
                'tool' => 'report.run',
            ];
        }

        $report = $this->repo->getById($reportId);
        if (!$report || empty($report['chat_enabled'])) {
            return [
                'ok' => false,
                'resposta' => 'Relatório não encontrado ou não está disponível no chat.',
                'tool' => 'report.run',
            ];
        }
        if ($userId > 0 && !$this->repo->userCanViewReport($report, $userId)) {
            return [
                'ok' => false,
                'resposta' => 'Sem permissão para executar este relatório no chat.',
                'tool' => 'report.run',
            ];
        }

        $name = (string) ($report['name'] ?? 'Relatório');
        $viz = (string) ($report['visualization_type'] ?? 'table');
        $reportUrl = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/view-dynamic-report/' . $reportId;
        $codeCols = $this->guessCodeColumnsForReport($report);

        $rows = [];
        $usedSqlFilter = false;

        if (($report['query_mode'] ?? '') === 'custom_sql' || !empty($report['custom_sql'])) {
            $wrapped = $this->wrapSqlWithCodeFilter((string) $report['custom_sql'], $codeCols, $code);
            if ($wrapped !== null) {
                $cfg = $report;
                $cfg['custom_sql'] = $wrapped;
                $cfg['query_mode'] = 'custom_sql';
                $cfg['cache_namespace'] = 'chat_report_filter_' . $reportId . '_' . md5($code);
                $cfg['force_refresh'] = true;
                $cfg['page'] = 1;
                $cfg['per_page'] = self::MAX_ROWS_IN_CHAT;
                try {
                    $result = $this->queryBuilder->executeReport($cfg);
                    if (!empty($result['success'])) {
                        $rows = is_array($result['data'] ?? null) ? $result['data'] : [];
                        $usedSqlFilter = true;
                    }
                } catch (\Throwable) {
                    $rows = [];
                }
            }
        } elseif ($codeCols !== []) {
            $cfg = $report;
            $filters = is_array($cfg['filters'] ?? null) ? $cfg['filters'] : [];
            $filters[] = [
                'field' => $codeCols[0],
                'operator' => '=',
                'value' => $code,
            ];
            $cfg['filters'] = $filters;
            $cfg['cache_namespace'] = 'chat_report_filter_' . $reportId . '_' . md5($code);
            $cfg['force_refresh'] = true;
            $cfg['page'] = 1;
            $cfg['per_page'] = self::MAX_ROWS_IN_CHAT;
            try {
                $result = $this->queryBuilder->executeReport($cfg);
                if (!empty($result['success'])) {
                    $rows = is_array($result['data'] ?? null) ? $result['data'] : [];
                    $usedSqlFilter = true;
                }
            } catch (\Throwable) {
                $rows = [];
            }
        }

        // Fallback: carrega um lote maior e filtra em PHP (legado / falha do wrap).
        if (!$usedSqlFilter) {
            $base = $this->runById($userId, $reportId, 500);
            if (empty($base['ok'])) {
                return $base;
            }
            $data = is_array($base['data'] ?? null) ? $base['data'] : [];
            $rows = $this->filterRowsByCode(is_array($data['rows'] ?? null) ? $data['rows'] : [], $code);
            $reportUrl = (string) ($data['report_url'] ?? $reportUrl);
            $viz = (string) ($data['visualization_type'] ?? $viz);
        }

        if ($rows === []) {
            return [
                'ok' => false,
                'resposta' => sprintf(
                    'Não encontrei «%s» no relatório «%s». Confira o código (item, parceiro, documento) ou abra o relatório completo.',
                    $code,
                    $name
                ),
                'tool' => 'report.run',
                'data' => [
                    'report_id' => $reportId,
                    'name' => $name,
                    'filter_code' => $code,
                    'rows_count' => 0,
                    'rows' => [],
                    'report_url' => $reportUrl,
                ],
            ];
        }

        $preview = array_slice($rows, 0, self::MAX_ROWS_IN_CHAT);
        $total = count($rows);
        $lines = [
            sprintf('Filtro «%s» no relatório «%s»: %d ocorrência(s).', $code, $name, $total),
        ];
        if ($total > self::MAX_ROWS_IN_CHAT) {
            $lines[] = 'Mostrando as primeiras ' . self::MAX_ROWS_IN_CHAT . ' no chat.';
        }

        return [
            'ok' => true,
            'resposta' => implode("\n", $lines),
            'tool' => 'report.run',
            'data' => [
                'report_id' => $reportId,
                'name' => $name,
                'filter_code' => $code,
                'report_url' => $reportUrl,
                'visualization_type' => $viz,
                'chart_config' => $report['chart_config'] ?? [],
                'rows_count' => $total,
                'rows' => $preview,
                'rows_display' => DynamicReportValueFormatter::formatRows($preview),
                'chart' => self::chartWithFormattedLabels(self::buildChartFromRows($preview, $viz)),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $report
     * @return list<string>
     */
    private function guessCodeColumnsForReport(array $report): array
    {
        $cols = [];
        $name = mb_strtolower((string) ($report['name'] ?? ''));
        $sql = (string) ($report['custom_sql'] ?? '');
        $tool = mb_strtolower((string) ($report['chat_tool_name'] ?? ''));

        if (str_contains($name, 'item') || str_contains($tool, 'item') || preg_match('/\bOITM\b/i', $sql)) {
            $cols = array_merge($cols, ['cdItem', 'ItemCode', 'ItemCode']);
        }
        if (
            str_contains($name, 'parceiro')
            || str_contains($name, 'pn')
            || str_contains($tool, 'parceiro')
            || preg_match('/\bOCRD\b/i', $sql)
        ) {
            $cols = array_merge($cols, ['ParceiroID', 'CardCode', 'CardCode']);
        }
        if (str_contains($name, 'venda') || str_contains($name, 'compra') || preg_match('/\bOINV\b|\bOPCH\b|\bORDR\b/i', $sql)) {
            $cols = array_merge($cols, ['DocNum', 'DocEntry', 'cdItem', 'ItemCode', 'CardCode', 'ParceiroID']);
        }

        if (preg_match_all('/\bAS\s+"?([A-Za-z_][A-Za-z0-9_]*)"?/i', $sql, $mm)) {
            foreach ($mm[1] as $alias) {
                if ($this->columnLooksLikeCode(mb_strtolower((string) $alias))) {
                    $cols[] = (string) $alias;
                }
            }
        }

        $cols = array_values(array_unique(array_filter($cols, static fn($c) => is_string($c) && $c !== '')));

        return $cols !== [] ? $cols : ['cdItem', 'ParceiroID', 'CardCode', 'DocNum', 'ItemCode'];
    }

    /**
     * @param list<string> $columns
     */
    private function wrapSqlWithCodeFilter(string $sql, array $columns, string $code): ?string
    {
        $sql = trim($sql);
        $sql = preg_replace('/^[\d\s]+/i', '', $sql) ?? $sql;
        $sql = trim($sql);
        if ($sql === '' || !preg_match('/^\s*SELECT\s+/i', $sql)) {
            return null;
        }
        $sql = preg_replace('/;+\s*$/', '', $sql) ?? $sql;
        $escaped = str_replace("'", "''", $code);

        // Preferir WHERE na tabela base (API SAP/HANA rejeita alguns subselects com alias).
        if (preg_match('/\bFROM\s+OITM\b/i', $sql)) {
            return $this->injectWhereBeforeOrderBy($sql, "T0.\"ItemCode\" = '{$escaped}'");
        }
        if (preg_match('/\bFROM\s+OCRD\b/i', $sql)) {
            return $this->injectWhereBeforeOrderBy($sql, "T0.\"CardCode\" = '{$escaped}'");
        }

        // Fallback: subselect só com aliases de saída (AS "cdItem"), não colunas da origem.
        $aliases = [];
        if (preg_match_all('/\bAS\s+"([A-Za-z_][A-Za-z0-9_]*)"/i', $sql, $mm)) {
            foreach ($mm[1] as $alias) {
                if ($this->columnLooksLikeCode(mb_strtolower((string) $alias))) {
                    $aliases[] = (string) $alias;
                }
            }
        }
        foreach ($columns as $col) {
            if (in_array($col, $aliases, true) || $this->columnLooksLikeCode(mb_strtolower($col))) {
                // só usa se parecer alias de saída conhecido
                if (preg_match('/\bAS\s+"' . preg_quote($col, '/') . '"/i', $sql)) {
                    $aliases[] = $col;
                }
            }
        }
        $aliases = array_values(array_unique($aliases));
        if ($aliases === []) {
            return null;
        }

        $parts = [];
        foreach ($aliases as $alias) {
            $safe = preg_replace('/[^a-zA-Z0-9_]/', '', $alias);
            if ($safe === null || $safe === '') {
                continue;
            }
            $parts[] = "TO_NVARCHAR(_chat_f.\"{$safe}\") = '{$escaped}'";
        }
        if ($parts === []) {
            return null;
        }

        return "SELECT * FROM (\n{$sql}\n) AS _chat_f WHERE (" . implode(' OR ', $parts) . ')';
    }

    private function wrapSqlWithMonthFilter(string $sql, int $month, int $year): ?string
    {
        $sql = trim($sql);
        $sql = preg_replace('/^[\d\s]+/i', '', $sql) ?? $sql;
        $sql = trim($sql);
        if ($sql === '' || !preg_match('/^\s*SELECT\s+/i', $sql)) {
            return null;
        }
        $sql = preg_replace('/;+\s*$/', '', $sql) ?? $sql;
        $col = $this->guessDateColumnFromSql($sql);
        if ($col === null) {
            return null;
        }
        $quoted = '"' . preg_replace('/[^A-Za-z0-9_]/', '', $col) . '"';
        $condition = 'YEAR(' . $quoted . ') = ' . $year . ' AND MONTH(' . $quoted . ') = ' . $month;

        return $this->injectWhereBeforeGroupOrder($sql, $condition);
    }

    private function guessDateColumnFromSql(string $sql): ?string
    {
        if (preg_match('/"(DocDate|TaxDate|CreateDate|UpdateDate|DocDueDate|Data)"/i', $sql, $m)) {
            return (string) $m[1];
        }
        if (preg_match('/\b(DocDate|TaxDate|CreateDate|DocDueDate)\b/i', $sql, $m)) {
            return (string) $m[1];
        }
        if (preg_match('/VW_CRM_VENDAS|\bOINV\b|\bORIN\b|\bORDR\b/i', $sql)) {
            return 'DocDate';
        }

        return null;
    }

    private function injectWhereBeforeGroupOrder(string $sql, string $condition): string
    {
        if (preg_match('/\bGROUP\s+BY\b/i', $sql)) {
            if (preg_match('/\bWHERE\b/i', $sql)) {
                return preg_replace('/\bGROUP\s+BY\b/i', "AND ({$condition}) GROUP BY", $sql, 1)
                    ?? ($sql . " AND ({$condition})");
            }

            return preg_replace('/\bGROUP\s+BY\b/i', "WHERE ({$condition}) GROUP BY", $sql, 1)
                ?? ($sql . "\nWHERE ({$condition})");
        }

        return $this->injectWhereBeforeOrderBy($sql, $condition);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterRowsByMonth(array $rows, int $month, int $year): array
    {
        $needles = [
            sprintf('%04d-%02d', $year, $month),
            sprintf('%02d/%04d', $month, $year),
            sprintf('%d/%04d', $month, $year),
            sprintf('%02d/%02d', $month, $year % 100),
        ];
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            foreach ($row as $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $cell = (string) $value;
                foreach ($needles as $needle) {
                    if (str_contains($cell, $needle)) {
                        $out[] = $row;
                        continue 3;
                    }
                }
            }
        }

        return $out;
    }

    private function injectWhereBeforeOrderBy(string $sql, string $condition): string
    {
        if (preg_match('/\bWHERE\b/i', $sql)) {
            if (preg_match('/\bORDER\s+BY\b/i', $sql)) {
                return preg_replace('/\bORDER\s+BY\b/i', "AND ({$condition}) ORDER BY", $sql, 1) ?? ($sql . " AND ({$condition})");
            }

            return $sql . " AND ({$condition})";
        }
        if (preg_match('/\bORDER\s+BY\b/i', $sql)) {
            return preg_replace('/\bORDER\s+BY\b/i', "WHERE ({$condition}) ORDER BY", $sql, 1) ?? ($sql . "\nWHERE ({$condition})");
        }

        return $sql . "\nWHERE ({$condition})";
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function filterRowsByCode(array $rows, string $code): array
    {
        $codeKey = mb_strtolower(trim($code));
        if ($codeKey === '' || $rows === []) {
            return [];
        }

        $preferred = [];
        $fallback = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $hitPreferred = false;
            $hitAny = false;
            foreach ($row as $col => $value) {
                if (is_array($value) || is_object($value)) {
                    continue;
                }
                $cell = trim((string) $value);
                if ($cell === '') {
                    continue;
                }
                $cellKey = mb_strtolower($cell);
                $colKey = mb_strtolower((string) $col);
                $exact = ($cellKey === $codeKey);
                $contains = str_contains($cellKey, $codeKey);
                if (!$exact && !$contains) {
                    continue;
                }
                $hitAny = true;
                if ($exact && $this->columnLooksLikeCode($colKey)) {
                    $hitPreferred = true;
                    break;
                }
            }
            if ($hitPreferred) {
                $preferred[] = $row;
            } elseif ($hitAny) {
                $fallback[] = $row;
            }
        }

        return $preferred !== [] ? $preferred : $fallback;
    }

    private function columnLooksLikeCode(string $columnKey): bool
    {
        $columnKey = mb_strtolower(trim($columnKey));
        $exact = [
            'cditem', 'itemcode', 'coditem', 'cod_item', 'codigo', 'código', 'sku',
            'cardcode', 'codparceiro', 'card_code', 'parceiroid', 'parceiro',
            'docnum', 'docentry', 'numdoc', 'code', 'matricula', 'id',
        ];
        if (in_array($columnKey, $exact, true)) {
            return true;
        }

        return (bool) preg_match(
            '/(itemcode|cditem|cardcode|parceiroid|docnum|docentry|^cod(igo)?$|_code$|_id$)/u',
            $columnKey
        );
    }

    /**
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function runById(int $userId, int $reportId, int $perPage = self::MAX_ROWS_IN_CHAT): array
    {
        $report = $this->repo->getById($reportId);
        if (!$report || empty($report['chat_enabled'])) {
            return [
                'ok' => false,
                'resposta' => 'Relatório não encontrado ou não está disponível no chat.',
                'tool' => 'report.run',
            ];
        }

        if ($userId > 0 && !$this->repo->userCanViewReport($report, $userId)) {
            return [
                'ok' => false,
                'resposta' => 'Sem permissão para executar este relatório no chat.',
                'tool' => 'report.run',
            ];
        }

        $perPage = max(self::MAX_ROWS_IN_CHAT, min(500, $perPage));
        $report['cache_namespace'] = 'chat_report_' . $reportId;
        $report['force_refresh'] = true;
        $report['page'] = 1;
        $report['per_page'] = $perPage;

        try {
            $result = $this->queryBuilder->executeReport($report);
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'resposta' => 'Erro ao executar o relatório: ' . $e->getMessage(),
                'tool' => 'report.run',
            ];
        }

        if (empty($result['success'])) {
            return [
                'ok' => false,
                'resposta' => 'Falha na consulta: ' . (string) ($result['error'] ?? 'erro desconhecido'),
                'tool' => 'report.run',
                'data' => $result,
            ];
        }

        $rows = $result['data'] ?? $result['rows'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }
        $total = (int) ($result['rows_count'] ?? $result['total_rows'] ?? count($rows));
        $preview = array_slice($rows, 0, $perPage > self::MAX_ROWS_IN_CHAT ? count($rows) : self::MAX_ROWS_IN_CHAT);
        // Na listagem do chat, limita a prévia visual; para filtro interno mantém mais linhas em data.rows.
        $chatPreview = array_slice($rows, 0, self::MAX_ROWS_IN_CHAT);

        $viz = (string) ($report['visualization_type'] ?? 'table');
        $lines = [
            sprintf('Relatório «%s»: %d linha(s).', (string) $report['name'], $total),
        ];
        if ($total > self::MAX_ROWS_IN_CHAT) {
            $lines[] = 'Pré-visualização das primeiras ' . self::MAX_ROWS_IN_CHAT . ' linhas no chat (tabela/gráfico abaixo). Abra o relatório completo em Relatórios para ver tudo.';
        } elseif ($chatPreview !== []) {
            $lines[] = 'Resultado abaixo (tabela' . ($viz !== 'table' ? '/gráfico' : '') . ').';
        }

        if ($userId > 0) {
            try {
                $this->repo->logExecution(
                    $reportId,
                    $userId,
                    (float) ($result['execution_time'] ?? 0),
                    $total
                );
            } catch (\Throwable) {
                // auditoria opcional
            }
        }

        $outRows = $perPage > self::MAX_ROWS_IN_CHAT ? $preview : $chatPreview;
        $chart = self::buildChartFromRows($chatPreview, $viz);

        return [
            'ok' => true,
            'resposta' => implode("\n", $lines),
            'tool' => 'report.run',
            'data' => [
                'report_id' => $reportId,
                'name' => $report['name'],
                'report_url' => rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/view-dynamic-report/' . $reportId,
                'visualization_type' => $viz,
                'chart_config' => $report['chart_config'] ?? [],
                'rows_count' => $total,
                'rows' => $outRows,
                'rows_display' => DynamicReportValueFormatter::formatRows($outRows),
                'chart' => self::chartWithFormattedLabels($chart),
            ],
        ];
    }

    /**
     * @param array{type?:string, labels?:list<mixed>, values?:list<float|int>, title?:string}|null $chart
     * @return array{type?:string, labels?:list<string>, values?:list<float|int>, title?:string}|null
     */
    private static function chartWithFormattedLabels(?array $chart): ?array
    {
        if ($chart === null || empty($chart['labels']) || !is_array($chart['labels'])) {
            return $chart;
        }
        $chart['labels'] = DynamicReportValueFormatter::formatLabels($chart['labels']);

        return $chart;
    }

    /**
     * Série para Chart.js a partir de headcount por departamento.
     *
     * @param list<array{departamento?:string, total?:int|string}> $byDepartment
     * @return array{type:string, labels:list<string>, values:list<float|int>, title:string}|null
     */
    public static function chartFromByDepartment(array $byDepartment, string $title = 'Por departamento'): ?array
    {
        if ($byDepartment === []) {
            return null;
        }
        $labels = [];
        $values = [];
        foreach ($byDepartment as $row) {
            $labels[] = (string) ($row['departamento'] ?? '');
            $values[] = (float) ($row['total'] ?? 0);
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'values' => $values,
            'title' => $title,
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{type:string, labels:list<string>, values:list<float|int>, title:string}|null
     */
    public static function buildChartFromRows(array $rows, string $visualizationType): ?array
    {
        if ($rows === []) {
            return null;
        }

        $first = $rows[0] ?? null;
        if (!is_array($first) || $first === []) {
            return null;
        }

        $cols = array_keys($first);
        $labelCol = self::pickChartLabelColumn($cols, $first);
        $valueCol = self::pickChartValueColumn($cols, $first);
        if ($labelCol === null || $valueCol === null) {
            // Relatórios só com id+nome (ex.: lista de cargos): tabela sim, gráfico não.
            return null;
        }

        $labels = [];
        $values = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $labels[] = (string) ($row[$labelCol] ?? '');
            $values[] = (float) ($row[$valueCol] ?? 0);
        }

        $type = match ($visualizationType) {
            'pie_chart', 'donut_chart' => 'pie',
            'line_chart', 'area_chart' => 'line',
            'bar_chart', 'column_chart' => 'bar',
            'table' => 'bar',
            default => 'bar',
        };

        return [
            'type' => $type,
            'labels' => $labels,
            'values' => $values,
            'title' => (string) $valueCol,
        ];
    }

    /**
     * @param list<string|int> $cols
     * @param array<string, mixed> $sampleRow
     */
    private static function pickChartLabelColumn(array $cols, array $sampleRow): ?string
    {
        $preferred = ['departamento', 'department', 'nome', 'name', 'cargo', 'descricao', 'description', 'mes', 'mês', 'label'];
        foreach ($preferred as $want) {
            foreach ($cols as $col) {
                if (mb_strtolower((string) $col) === $want) {
                    return (string) $col;
                }
            }
        }
        foreach ($cols as $col) {
            $key = mb_strtolower((string) $col);
            if (self::isIdentifierColumn($key)) {
                continue;
            }
            $sample = $sampleRow[$col] ?? null;
            if (!is_numeric($sample)) {
                return (string) $col;
            }
        }

        return null;
    }

    /**
     * @param list<string|int> $cols
     * @param array<string, mixed> $sampleRow
     */
    private static function pickChartValueColumn(array $cols, array $sampleRow): ?string
    {
        $preferred = ['total', 'quantidade', 'qtd', 'count', 'valor', 'amount', 'soma', 'media', 'média', 'headcount'];
        foreach ($preferred as $want) {
            foreach ($cols as $col) {
                if (mb_strtolower((string) $col) === $want && is_numeric($sampleRow[$col] ?? null)) {
                    return (string) $col;
                }
            }
        }
        foreach ($cols as $col) {
            $key = mb_strtolower((string) $col);
            if (self::isIdentifierColumn($key)) {
                continue;
            }
            if (is_numeric($sampleRow[$col] ?? null)) {
                return (string) $col;
            }
        }

        return null;
    }

    private static function isIdentifierColumn(string $key): bool
    {
        return $key === 'id'
            || str_ends_with($key, '_id')
            || in_array($key, ['codigo', 'code', 'cod', 'ano', 'year', 'docnum', 'serial'], true);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolveReport(int $userId, string $query, bool $preferMonth = false): ?array
    {
        $q = mb_strtolower(trim($query));
        $q = preg_replace('/[?!.]+$/u', '', $q) ?? $q;
        $q = trim($q);
        $q = preg_replace('/^(rode|execut[ea]|abrir|consultar|mostrar|mostre|ver|liste|listar)\s+(o\s+)?/u', '', $q) ?? $q;
        $q = preg_replace('/^relat[oó]?rio\s+/u', '', $q) ?? $q;
        $q = trim($q);

        if ($q === '') {
            return null;
        }

        if (preg_match('/^#?(\d+)$/', $q, $m)) {
            $report = $this->repo->getById((int) $m[1]);
            if ($report && !empty($report['chat_enabled'])) {
                if ($userId < 1 || $this->repo->userCanViewReport($report, $userId)) {
                    return $report;
                }
            }

            return null;
        }

        $candidates = $this->repo->getChatEnabledReports();
        $best = null;
        $bestScore = 0;

        foreach ($candidates as $report) {
            if ($userId > 0 && !$this->repo->userCanViewReport($report, $userId)) {
                continue;
            }

            $score = $this->scoreMatch($q, $report, $preferMonth);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $report;
            }
        }

        return $bestScore >= 70 ? $best : null;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function scoreMatch(string $q, array $report, bool $preferMonth = false): int
    {
        $name = mb_strtolower((string) ($report['name'] ?? ''));
        $tool = mb_strtolower((string) ($report['chat_tool_name'] ?? ''));
        $desc = mb_strtolower((string) ($report['chat_description'] ?? ''));
        $sql = (string) ($report['custom_sql'] ?? '');

        $qWantsSales = (bool) preg_match('/\bvendas?\b|\bfaturamento\b/u', $q);
        $reportIsSales = (bool) preg_match('/venda|faturamento|vw_crm_vendas|\bOINV\b/iu', $name . ' ' . $tool . ' ' . $sql);
        $reportIsItemCatalog = (bool) preg_match('/\bOITM\b/i', $sql)
            && !preg_match('/vw_crm_vendas|\bOINV\b/i', $sql);

        // «venda por item» não deve cair no cadastro [FILTRO] Itens (OITM).
        if ($qWantsSales && $reportIsItemCatalog) {
            return 0;
        }

        $score = 0;
        if ($tool !== '' && ($q === $tool || str_contains($q, $tool))) {
            $score = 100;
        } elseif ($tool !== '' && mb_strlen($q) >= 10 && str_contains($tool, $q)) {
            $score = 100;
        } elseif ($name !== '' && ($q === $name || str_contains($q, $name))) {
            $score = 95;
        } elseif ($name !== '' && mb_strlen($q) >= 10 && str_contains($name, $q)) {
            $score = 95;
        } else {
            $examples = $report['chat_example_prompts'] ?? [];
            if (!is_array($examples)) {
                $examples = [];
            }
            foreach ($examples as $ex) {
                $ex = mb_strtolower(trim((string) $ex));
                if ($ex === '') {
                    continue;
                }
                if ($q === $ex) {
                    $score = 90;
                    break;
                }
                // Exemplos curtos («item») não podem casar no meio de outra pergunta.
                if (mb_strlen($ex) >= 10 && str_contains($q, $ex)) {
                    $score = 90;
                    break;
                }
                if (mb_strlen($q) >= 12 && str_contains($ex, $q)) {
                    $score = 90;
                    break;
                }
            }
        }

        if ($score === 0 && $desc !== '' && mb_strlen($q) >= 12 && (str_contains($desc, $q) || str_contains($q, $desc))) {
            $score = 60;
        }

        if ($score === 0 && mb_strlen($q) >= 5) {
            $hay = $name . ' ' . str_replace('_', ' ', $tool);
            $examples = $report['chat_example_prompts'] ?? [];
            if (is_array($examples)) {
                foreach ($examples as $ex) {
                    $hay .= ' ' . mb_strtolower(trim((string) $ex));
                }
            }
            if (preg_match('/\b' . preg_quote($q, '/') . '\b/u', $hay)) {
                $score = 75;
            }
        }

        if ($score > 0 && $qWantsSales && $reportIsSales) {
            $score += 15;
        }
        if ($score > 0 && $preferMonth) {
            $monthish = (bool) preg_match(
                '/mes|m[eê]s|mensal|mesano|por[_\s-]?mes|YEAR\s*\(\s*"DocDate"/iu',
                $name . ' ' . $tool . ' ' . $sql
            );
            if ($monthish) {
                $score += 25;
            }
        }

        return $score;
    }

    /**
     * @param array<string, mixed> $report
     * @return array{id:int, name:string, chat_tool_name:?string, chat_description:?string, examples:list<string>, visualization_type:string}
     */
    private function summarizeReport(array $report): array
    {
        $examples = $report['chat_example_prompts'] ?? [];
        if (!is_array($examples)) {
            $examples = [];
        }

        return [
            'id' => (int) ($report['id'] ?? 0),
            'name' => (string) ($report['name'] ?? ''),
            'chat_tool_name' => $report['chat_tool_name'] ?? null,
            'chat_description' => $report['chat_description'] ?? null,
            'examples' => array_values(array_map('strval', $examples)),
            'visualization_type' => (string) ($report['visualization_type'] ?? 'table'),
        ];
    }

}
