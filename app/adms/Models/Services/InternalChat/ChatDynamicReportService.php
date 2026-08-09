<?php

namespace App\adms\Models\Services\InternalChat;

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
     * @return array{ok:bool, resposta:string, tool:string, data?:mixed}
     */
    public function runById(int $userId, int $reportId): array
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

        $report['cache_namespace'] = 'chat_report_' . $reportId;
        $report['force_refresh'] = true;
        $report['page'] = 1;
        $report['per_page'] = self::MAX_ROWS_IN_CHAT;

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
        $total = (int) ($result['rows_count'] ?? count($rows));
        $preview = array_slice($rows, 0, self::MAX_ROWS_IN_CHAT);

        $viz = (string) ($report['visualization_type'] ?? 'table');
        $lines = [
            sprintf('Relatório «%s»: %d linha(s).', (string) $report['name'], $total),
        ];
        if ($total > self::MAX_ROWS_IN_CHAT) {
            $lines[] = 'Pré-visualização das primeiras ' . self::MAX_ROWS_IN_CHAT . ' linhas no chat (tabela/gráfico abaixo). Abra o relatório completo em Relatórios para ver tudo.';
        } elseif ($preview !== []) {
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

        $chart = self::buildChartFromRows($preview, $viz);

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
                'rows' => $preview,
                'chart' => $chart,
            ],
        ];
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
    public function resolveReport(int $userId, string $query): ?array
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

            $score = $this->scoreMatch($q, $report);
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
    private function scoreMatch(string $q, array $report): int
    {
        $name = mb_strtolower((string) ($report['name'] ?? ''));
        $tool = mb_strtolower((string) ($report['chat_tool_name'] ?? ''));
        $desc = mb_strtolower((string) ($report['chat_description'] ?? ''));

        if ($tool !== '' && ($q === $tool || str_contains($q, $tool))) {
            return 100;
        }
        // Evita match frágil: "por mes" dentro de "headcount_por_mes…"
        if ($tool !== '' && mb_strlen($q) >= 10 && str_contains($tool, $q)) {
            return 100;
        }
        if ($name !== '' && ($q === $name || str_contains($q, $name))) {
            return 95;
        }
        if ($name !== '' && mb_strlen($q) >= 10 && str_contains($name, $q)) {
            return 95;
        }

        $examples = $report['chat_example_prompts'] ?? [];
        if (!is_array($examples)) {
            $examples = [];
        }
        foreach ($examples as $ex) {
            $ex = mb_strtolower(trim((string) $ex));
            if ($ex === '') {
                continue;
            }
            if ($q === $ex || str_contains($q, $ex)) {
                return 90;
            }
            // Só aceita exemplo contendo a pergunta se a pergunta for suficientemente específica.
            if (mb_strlen($q) >= 12 && str_contains($ex, $q)) {
                return 90;
            }
        }

        if ($desc !== '' && mb_strlen($q) >= 12 && (str_contains($desc, $q) || str_contains($q, $desc))) {
            return 60;
        }

        return 0;
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
