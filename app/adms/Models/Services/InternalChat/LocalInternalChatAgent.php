<?php

namespace App\adms\Models\Services\InternalChat;

/**
 * Agente local de piloto: interpreta perguntas simples de RH sem LLM pago.
 * Opcionalmente usa Ollama (gratuito/local) se OLLAMA_URL estiver configurado.
 *
 * Retorno no formato esperado pelo chat do Portal: JSON com chave "resposta".
 */
class LocalInternalChatAgent
{
    private RhChatIndicatorsService $rh;
    private ChatDynamicReportService $reports;
    private InternalChatLlmClient $llm;
    private int $userId = 0;

    /** @var array<string, string> sinônimo → nome canônico no cadastro */
    private const DEPARTMENT_ALIASES = [
        'ti' => 'TI',
        't.i' => 'TI',
        't.i.' => 'TI',
        'tecnologia da informacao' => 'TI',
        'tecnologia da informação' => 'TI',
        'tecnologia da informaçao' => 'TI',
        'informatica' => 'TI',
        'informática' => 'TI',
        'rh' => 'Recursos Humanos',
        'recursos humanos' => 'Recursos Humanos',
        'cq' => 'Controle de Qualidade',
        'gq' => 'Garantia da Qualidade',
    ];

    /** @var array<string, int> */
    private const MONTHS = [
        'janeiro' => 1, 'jan' => 1,
        'fevereiro' => 2, 'fev' => 2,
        'marco' => 3, 'março' => 3, 'mar' => 3,
        'abril' => 4, 'abr' => 4,
        'maio' => 5, 'mai' => 5,
        'junho' => 6, 'jun' => 6,
        'julho' => 7, 'jul' => 7,
        'agosto' => 8, 'ago' => 8,
        'setembro' => 9, 'set' => 9,
        'outubro' => 10, 'out' => 10,
        'novembro' => 11, 'nov' => 11,
        'dezembro' => 12, 'dez' => 12,
    ];

    public function __construct(
        ?RhChatIndicatorsService $rh = null,
        ?ChatDynamicReportService $reports = null,
        ?InternalChatLlmClient $llm = null
    ) {
        $this->rh = $rh ?? new RhChatIndicatorsService();
        $this->reports = $reports ?? new ChatDynamicReportService();
        $this->llm = $llm ?? new InternalChatLlmClient();
    }

    /**
     * @param array<string, mixed> $authContext
     * @return array{resposta: string, tool?: string, data?: mixed, provider?: string}
     */
    public function handle(string $message, array $authContext = []): array
    {
        $this->userId = (int) ($authContext['user_id'] ?? 0);
        $message = trim($message);
        if ($message === '') {
            return ['resposta' => 'Envie uma pergunta. Exemplos: "quantos colaboradores ativos?", "relatório …", "quais relatórios no chat?".'];
        }

        $intent = $this->detectIntent($message);
        if ($intent === null) {
            $llm = $this->tryLlmInterpret($message);
            if ($llm !== null) {
                $llm = $this->maybeEnrichWithAnalysis($llm);
                $this->rememberFromResult($llm);
                return $llm;
            }

            return [
                'resposta' => "Ainda não entendi no modo local. Experimente:\n"
                    . "• quantos colaboradores ativos?\n"
                    . "• quantos colaboradores inativos?\n"
                    . "• inativos em janeiro\n"
                    . "• quantos usuários bloqueados?\n"
                    . "• ativos na TI\n"
                    . "• headcount por departamento\n"
                    . "• quais relatórios no chat?\n"
                    . "• relatório [nome ou tool]\n"
                    . "• depois de «ativos» ou «inativos», pode digitar só o departamento (ex.: financeiro)",
                'provider' => 'local-rules',
            ];
        }

        $result = $this->executeIntent($intent, $message);
        $result = $this->maybeEnrichWithAnalysis($result);
        $this->rememberFromResult($result);

        return $result;
    }

    /**
     * @return array{name: string, department?: string|null}|null
     */
    private function detectIntent(string $message): ?array
    {
        $m = mb_strtolower(trim($message));
        $m = preg_replace('/[?!.]+$/u', '', $m) ?? $m;
        $m = trim($m);

        if (preg_match('/bloquead|bloqueio|tentativas?\s+de\s+login/', $m)) {
            return ['name' => 'blocked'];
        }

        if (preg_match('/quais\s+relat[oó]?rios|listar\s+relat[oó]?rios|relat[oó]?rios\s+(no\s+)?chat|cat[aá]logo\s+(do\s+)?chat/u', $m)) {
            return ['name' => 'report_list'];
        }

        if (preg_match('/\brelat[oó]?rio\b|report\.run|execut[ea]\s+relat|rode\s+o\s+relat/u', $m)) {
            return ['name' => 'report_run', 'query' => $message];
        }

        // Exemplos / tool_name / nome de relatório marcado para o chat.
        $matchedReport = $this->reports->resolveReport($this->userId, $message);
        if ($matchedReport !== null) {
            return ['name' => 'report_run', 'query' => $message, 'report_id' => (int) $matchedReport['id']];
        }

        if (preg_match('/por\s+departamento|headcount\s+por|ativos\s+por\s+depto|resumo\s+por\s+departamento/', $m)) {
            return ['name' => 'by_department'];
        }

        $period = $this->extractPeriod($message);
        $dept = $this->extractDepartment($message);
        $dept = $this->resolveDepartmentAlias($dept);
        // "em janeiro" não é departamento
        if ($period !== null) {
            $dept = null;
        }

        // "inativos" contém "ativos" — tratar inativo ANTES do padrão de ativos.
        if (preg_match('/\binativos?\b|\bdesligad[oa]s?\b|\bex[- ]?colaboradores?\b/', $m)) {
            if ($period !== null) {
                return [
                    'name' => 'terminated_in_period',
                    'month' => $period['month'],
                    'year' => $period['year'],
                ];
            }

            return ['name' => 'inactive', 'department' => $dept];
        }

        if (preg_match('/\b(quantos|qtd|quantidade|headcount|pessoas)\b|\bativos?\b|\bcolaboradores?\b|\bfuncionarios?\b|\busuarios?\b/u', $m)) {
            return ['name' => 'active', 'department' => $dept];
        }

        // Só o nome do departamento: usa última intenção (ativos/inativos) da sessão.
        $bareDept = $this->matchBareDepartmentName($m);
        if ($bareDept !== null) {
            $status = $this->getLastStatusIntent();

            return ['name' => $status, 'department' => $bareDept];
        }

        if ($dept !== null && preg_match('/produ|admin|qualidade|financeiro|comercial|\bti\b|\brh\b|estoque/u', mb_strtolower($dept))) {
            return ['name' => 'active', 'department' => $dept];
        }

        return null;
    }

    private function matchBareDepartmentName(string $normalizedMessage): ?string
    {
        $normalizedMessage = trim($normalizedMessage);
        if ($normalizedMessage === '') {
            return null;
        }

        // Não tratar frases de intenção como nome de departamento.
        if (preg_match('/\b(quantos|qtd|quantidade|ativos?|inativos?|bloqueados?|colaboradores?|funcionarios?|usuarios?|headcount|pessoas|desligad[oa]s?)\b/u', $normalizedMessage)) {
            return null;
        }

        $key = mb_strtolower(preg_replace('/\s+/u', ' ', $normalizedMessage) ?? $normalizedMessage);
        $first = explode(' ', $key)[0] ?? '';
        if (isset(self::MONTHS[$first]) || isset(self::MONTHS[$key])) {
            return null;
        }
        if (isset(self::DEPARTMENT_ALIASES[$key])) {
            return self::DEPARTMENT_ALIASES[$key];
        }

        foreach ($this->rh->listDepartmentNames() as $name) {
            if (mb_strtolower($name) === $key) {
                return $name;
            }
        }

        return null;
    }

    private function getLastStatusIntent(): string
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $last = (string) ($_SESSION['internal_chat_last_status'] ?? 'active');
            if ($last === 'inactive' || $last === 'active') {
                return $last;
            }
        }

        return 'active';
    }

    /**
     * @param array{tool?: string, resposta?: string} $result
     */
    private function rememberFromResult(array $result): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $tool = (string) ($result['tool'] ?? '');
        if ($tool === 'rh.count_inactive') {
            $_SESSION['internal_chat_last_status'] = 'inactive';
        } elseif ($tool === 'rh.count_active' || $tool === 'rh.count_active_by_department') {
            $_SESSION['internal_chat_last_status'] = 'active';
        }
    }

    /**
     * @return array{month:int, year:int}|null
     */
    private function extractPeriod(string $message): ?array
    {
        $m = mb_strtolower($message);

        // 01/2026 ou 1/2026
        if (preg_match('/\b(\d{1,2})[\/\-](\d{4})\b/', $m, $mm)) {
            $month = (int) $mm[1];
            $year = (int) $mm[2];
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        $monthNames = implode('|', array_map(static fn ($k) => preg_quote($k, '/'), array_keys(self::MONTHS)));
        if (preg_match('/\b(?:mes\s+de\s+|mês\s+de\s+)?(' . $monthNames . ')\b(?:\s+de\s+(\d{4}))?/u', $m, $mm)) {
            $monthKey = $mm[1];
            $month = self::MONTHS[$monthKey] ?? null;
            if ($month === null) {
                return null;
            }
            $year = isset($mm[2]) && $mm[2] !== '' ? (int) $mm[2] : (int) date('Y');

            return ['month' => $month, 'year' => $year];
        }

        return null;
    }

    private function extractDepartment(string $message): ?string
    {
        if (!preg_match('/(?:departamento|depto|setor|em|na|no)\s+([a-záàâãéêíóôõúç0-9\s\.\-]+)/iu', $message, $mm)) {
            return null;
        }

        $dept = trim($mm[1]);
        $dept = preg_replace('/[?!.]+$/u', '', $dept) ?? $dept;
        $dept = trim($dept);
        if ($dept === '' || mb_strlen($dept) < 2) {
            return null;
        }
        if (preg_match('/^(ativos?|inativos?|colaboradores?|funcionarios?|usuarios?|bloqueados?)$/iu', $dept)) {
            return null;
        }

        // "janeiro", "janeiro de 2026", "01/2026" não são departamentos
        $firstToken = mb_strtolower(explode(' ', $dept)[0] ?? '');
        if (isset(self::MONTHS[$firstToken]) || preg_match('/^\d{1,2}[\/\-]\d{4}$/', $dept)) {
            return null;
        }
        if ($this->extractPeriod($dept) !== null || $this->extractPeriod('em ' . $dept) !== null) {
            return null;
        }

        return $dept;
    }

    private function resolveDepartmentAlias(?string $department): ?string
    {
        if ($department === null) {
            return null;
        }

        $key = mb_strtolower(trim($department));
        $key = preg_replace('/\s+/u', ' ', $key) ?? $key;

        return self::DEPARTMENT_ALIASES[$key] ?? $department;
    }

    /**
     * @param array{name: string, department?: string|null} $intent
     * @return array{resposta: string, tool: string, data: mixed, provider: string}
     */
    private function executeIntent(array $intent, string $message): array
    {
        if ($intent['name'] === 'report_list') {
            $catalog = $this->reports->listCatalogForUser($this->userId);
            if ($catalog === []) {
                return [
                    'resposta' => 'Nenhum relatório está disponível no chat. No construtor de Relatórios Dinâmicos, marque «Disponível no chat» e salve.',
                    'tool' => 'report.list',
                    'data' => [],
                    'provider' => 'local-rules',
                ];
            }
            $lines = ['Relatórios disponíveis no Tiarajuzinho:'];
            foreach ($catalog as $item) {
                $tool = $item['chat_tool_name'] ?: ('#' . $item['id']);
                $desc = $item['chat_description'] ? ' — ' . $item['chat_description'] : '';
                $lines[] = "• {$item['name']} (tool: {$tool}){$desc}";
                foreach (array_slice($item['examples'], 0, 2) as $ex) {
                    $lines[] = "  ex.: {$ex}";
                }
            }
            $lines[] = '';
            $lines[] = 'Peça: «relatório [nome]» ou use um dos exemplos.';

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'report.list',
                'data' => $catalog,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'report_run') {
            if (!empty($intent['report_id'])) {
                $run = $this->reports->runById($this->userId, (int) $intent['report_id']);
            } else {
                $run = $this->reports->resolveAndRun($this->userId, (string) ($intent['query'] ?? $message));
            }

            return [
                'resposta' => $run['resposta'],
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'blocked') {
            $data = $this->rh->countBlockedUsers();
            $resposta = sprintf(
                'Há %d usuário(s) bloqueado(s) no Portal. Destes, %d têm tentativas de login registradas.',
                $data['total'],
                $data['with_attempts']
            );

            return [
                'resposta' => $resposta,
                'tool' => 'rh.count_blocked',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'by_department') {
            $data = $this->rh->countActiveEmployees(null);
            $lines = ["Colaboradores ativos (sem data de desligamento): {$data['total']}", '', 'Por departamento:'];
            foreach ($data['by_department'] as $row) {
                $lines[] = "• {$row['departamento']}: {$row['total']}";
            }
            $data['visualization_type'] = 'bar_chart';
            $data['chart'] = ChatDynamicReportService::chartFromByDepartment(
                $data['by_department'],
                'Ativos por departamento'
            );

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_active_by_department',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'terminated_in_period') {
            $month = (int) ($intent['month'] ?? (int) date('n'));
            $year = (int) ($intent['year'] ?? (int) date('Y'));
            $data = $this->rh->countTerminatedInMonth($month, $year);
            $monthNames = [
                1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
            ];
            $label = ($monthNames[$month] ?? (string) $month) . '/' . $year;
            $resposta = sprintf(
                'Há %d colaborador(es) com data de desligamento em %s.',
                $data['total'],
                $label
            );
            if ($data['by_department'] !== []) {
                $parts = [];
                foreach ($data['by_department'] as $row) {
                    $parts[] = "{$row['departamento']} ({$row['total']})";
                }
                $resposta .= ' Por departamento: ' . implode(', ', $parts) . '.';
            }
            $data['visualization_type'] = 'bar_chart';
            $data['chart'] = ChatDynamicReportService::chartFromByDepartment(
                $data['by_department'],
                'Desligados em ' . $label
            );

            return [
                'resposta' => $resposta,
                'tool' => 'rh.count_terminated_in_month',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'inactive') {
            $dept = isset($intent['department']) ? $this->resolveDepartmentAlias($intent['department']) : null;
            $data = $this->rh->countInactiveEmployees($dept);
            if ($dept) {
                $resposta = sprintf(
                    'Colaboradores inativos no departamento "%s": %d.',
                    $dept,
                    $data['total']
                );
            } else {
                $resposta = sprintf(
                    'Há %d colaborador(es) inativo(s) no Portal (status Inativo ou com data de desligamento).',
                    $data['total']
                );
            }
            if ($dept && $data['by_department'] !== []) {
                $parts = [];
                foreach ($data['by_department'] as $row) {
                    $parts[] = "{$row['departamento']} ({$row['total']})";
                }
                $resposta .= ' Detalhe: ' . implode(', ', $parts) . '.';
            }

            return [
                'resposta' => $resposta,
                'tool' => 'rh.count_inactive',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] !== 'active') {
            return [
                'resposta' => 'Intenção não suportada no modo local.',
                'tool' => 'unknown',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        $dept = isset($intent['department']) ? $this->resolveDepartmentAlias($intent['department']) : null;
        $data = $this->rh->countActiveEmployees($dept);
        if ($dept) {
            $mode = ($data['match_mode'] ?? '') === 'exact' ? 'igual a' : 'que contém';
            $resposta = sprintf(
                'Colaboradores ativos no departamento %s "%s": %d.',
                $mode,
                $dept,
                $data['total']
            );
            if ($data['by_department'] !== []) {
                $parts = [];
                foreach ($data['by_department'] as $row) {
                    $parts[] = "{$row['departamento']} ({$row['total']})";
                }
                $resposta .= ' Detalhe: ' . implode(', ', $parts) . '.';
            }
        } else {
            $resposta = sprintf('Há %d colaboradores ativos no Portal (status Ativo e sem data de desligamento).', $data['total']);
        }

        return [
            'resposta' => $resposta,
            'tool' => 'rh.count_active',
            'data' => $data,
            'provider' => 'local-rules',
        ];
    }

    /**
     * Roteamento via API de IA (OpenAI/Anthropic) ou Ollama.
     *
     * @return array{resposta: string, tool?: string, data?: mixed, provider: string}|null
     */
    private function tryLlmInterpret(string $message): ?array
    {
        if (!$this->llm->isAnyConfigured()) {
            return null;
        }

        $catalogHint = '';
        foreach ($this->reports->listCatalogForUser($this->userId) as $item) {
            $tool = $item['chat_tool_name'] ?: ('id:' . $item['id']);
            $catalogHint .= "- {$tool} | {$item['name']}\n";
        }

        $system = "Você é um roteador de intenções para RH e relatórios do Portal. "
            . "Responda APENAS com JSON válido, sem markdown.\n"
            . "Use department canônico quando possível: TI, Produção, Recursos Humanos, Financeiro, Comercial.\n"
            . "Sinônimos: tecnologia da informação/informática → TI; RH → Recursos Humanos.\n"
            . "Formatos possíveis:\n"
            . "{\"intent\":\"active\"}\n"
            . "{\"intent\":\"active\",\"department\":\"TI\"}\n"
            . "{\"intent\":\"inactive\"}\n"
            . "{\"intent\":\"terminated_in_period\",\"month\":1,\"year\":2026}\n"
            . "{\"intent\":\"blocked\"}\n"
            . "{\"intent\":\"by_department\"}\n"
            . "{\"intent\":\"report_list\"}\n"
            . "{\"intent\":\"report_run\",\"query\":\"nome ou tool do relatório\"}\n"
            . "{\"intent\":\"unknown\"}\n"
            . "Se a pergunta tiver mês (ex.: inativos em janeiro), use terminated_in_period — NÃO use department=janeiro.";

        $user = ($catalogHint !== '' ? "Relatórios no chat:\n{$catalogHint}\n" : '')
            . 'Pergunta do usuário: ' . $message;

        $llm = $this->llm->complete($system, $user, true);
        if ($llm === null) {
            return null;
        }

        $raw = $llm['text'];
        // Remove cercas markdown se a API devolver
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw)) ?? $raw;
        $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;
        $intentJson = json_decode($raw, true);
        if (!is_array($intentJson)) {
            return null;
        }

        $name = (string) ($intentJson['intent'] ?? 'unknown');
        if ($name === 'unknown' || $name === '') {
            return null;
        }

        $allowed = ['active', 'inactive', 'terminated_in_period', 'blocked', 'by_department', 'report_list', 'report_run'];
        if (!in_array($name, $allowed, true)) {
            return null;
        }

        $intent = ['name' => $name];
        if (!empty($intentJson['department'])) {
            $dept = $this->resolveDepartmentAlias((string) $intentJson['department']);
            if ($dept !== null) {
                $first = mb_strtolower(explode(' ', $dept)[0] ?? '');
                if (isset(self::MONTHS[$first])) {
                    $dept = null;
                    if ($name === 'inactive' || $name === 'terminated_in_period') {
                        $intent['name'] = 'terminated_in_period';
                        $intent['month'] = self::MONTHS[$first];
                        $intent['year'] = !empty($intentJson['year'])
                            ? (int) $intentJson['year']
                            : (int) date('Y');
                    }
                }
            }
            if ($dept !== null) {
                $intent['department'] = $dept;
            }
        }
        if (!empty($intentJson['month'])) {
            $intent['month'] = (int) $intentJson['month'];
        }
        if (!empty($intentJson['year'])) {
            $intent['year'] = (int) $intentJson['year'];
        }
        if (!empty($intentJson['query'])) {
            $intent['query'] = (string) $intentJson['query'];
        }

        $result = $this->executeIntent($intent, $message);
        $result['provider'] = $llm['provider'];

        return $result;
    }

    /**
     * Acrescenta resumo curto via LLM quando há dados tabulares/gráfico.
     *
     * @param array{resposta: string, tool?: string, data?: mixed, provider?: string} $result
     * @return array{resposta: string, tool?: string, data?: mixed, provider?: string, analysis?: string}
     */
    private function maybeEnrichWithAnalysis(array $result): array
    {
        $tool = (string) ($result['tool'] ?? '');
        if (!in_array($tool, ['report.run', 'rh.count_active_by_department', 'rh.count_terminated_in_month'], true)) {
            return $result;
        }

        $data = $result['data'] ?? null;
        if (!is_array($data)) {
            return $result;
        }

        $hasSeries = !empty($data['chart']['labels']) || !empty($data['rows']) || !empty($data['by_department']);
        if (!$hasSeries) {
            return $result;
        }

        $analyzeFlag = (string) ($_ENV['INTERNAL_CHAT_ANALYZE'] ?? $_ENV['OLLAMA_ANALYZE'] ?? '1');
        if ($analyzeFlag === '0' || strcasecmp($analyzeFlag, 'false') === 0) {
            return $result;
        }

        if (!$this->llm->isAnyConfigured()) {
            return $result;
        }

        $analysis = $this->tryLlmAnalyze((string) ($result['resposta'] ?? ''), $data);
        if ($analysis === null || $analysis === '') {
            return $result;
        }

        $result['analysis'] = $analysis;
        $result['resposta'] = rtrim((string) $result['resposta']) . "\n\nAnálise:\n" . $analysis;

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function tryLlmAnalyze(string $summary, array $data): ?string
    {
        $payloadData = [
            'chart' => $data['chart'] ?? null,
            'by_department' => isset($data['by_department']) ? array_slice($data['by_department'], 0, 15) : null,
            'rows_sample' => isset($data['rows']) && is_array($data['rows']) ? array_slice($data['rows'], 0, 8) : null,
            'rows_count' => $data['rows_count'] ?? $data['total'] ?? null,
            'name' => $data['name'] ?? null,
        ];

        $system = 'Você é analista de RH/dados do Portal. Responda em português, no máximo 3 frases objetivas '
            . '(destaque, concentração, alerta se fizer sentido). Não invente números fora do JSON. Sem markdown.';
        $user = "Resumo: {$summary}\nJSON: " . json_encode($payloadData, JSON_UNESCAPED_UNICODE);

        $llm = $this->llm->complete($system, $user, false);
        if ($llm === null) {
            return null;
        }

        $text = trim($llm['text']);
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > 600) {
            $text = mb_substr($text, 0, 600) . '…';
        }

        return $text;
    }
}
