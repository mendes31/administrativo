<?php

namespace App\adms\Models\Services\InternalChat;

/**
 * Agente do Tiarajuzinho: com IA configurada interpreta texto livre (Groq/Gemini/etc.);
 * o PHP só executa a consulta. Sem IA, cai no piloto por regras locais.
 */
class LocalInternalChatAgent
{
    private RhChatIndicatorsService $rh;
    private ChatDynamicReportService $reports;
    private ChatRoomsService $rooms;
    private ChatRoomsBookingWizard $roomsWizard;
    private InternalChatLlmClient $llm;
    private ChatToolPermissionGate $perms;
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
        'controle de qualidade' => 'Controle de Qualidade',
        'controle qualidade' => 'Controle de Qualidade',
        'gq' => 'Garantia da Qualidade',
        'garantia da qualidade' => 'Garantia da Qualidade',
        'garantia qualidade' => 'Garantia da Qualidade',
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
        ?ChatRoomsService $rooms = null,
        ?ChatRoomsBookingWizard $roomsWizard = null,
        ?InternalChatLlmClient $llm = null,
        ?ChatToolPermissionGate $perms = null
    ) {
        $this->rh = $rh ?? new RhChatIndicatorsService();
        $this->reports = $reports ?? new ChatDynamicReportService();
        $this->rooms = $rooms ?? new ChatRoomsService();
        $this->roomsWizard = $roomsWizard ?? new ChatRoomsBookingWizard($this->rooms);
        $this->llm = $llm ?? new InternalChatLlmClient();
        $this->perms = $perms ?? new ChatToolPermissionGate();
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
            return $this->buildUnknownHelpReply('Envie uma pergunta para eu começar.');
        }

        // Fluxo guiado de salas (sala → data → horários).
        if ($this->roomsWizard->isActive()) {
            if (!$this->perms->canUseTool('rooms.wizard') && !$this->perms->canUseTool('rooms.reserve')) {
                return $this->denyTool('rooms.wizard');
            }
            $wizard = $this->roomsWizard->continue($this->userId, $message);
            if ($wizard !== null) {
                return $wizard;
            }
        }
        if (preg_match('/^(agendar|reservar|nova\s+reserva|agendar\s+sala|reservar\s+sala)$/iu', $message)) {
            if (!$this->perms->canUseTool('rooms.wizard')) {
                return $this->denyTool('rooms.wizard');
            }
            return $this->roomsWizard->start($this->userId);
        }

        if ($this->isGreetingOnly($message)) {
            return $this->buildGreetingReply($message);
        }

        // Com IA configurada o texto livre vai ao modelo. Frases/regex só em
        // follow-up de sessão ou se a IA falhar/não mapear.
        $followUp = $this->detectSessionFollowUp($message);
        if ($followUp !== null) {
            return $this->finishIntent($followUp, $message);
        }

        if ($this->llm->isAnyConfigured()) {
            $llm = $this->tryLlmInterpret($message);
            if ($llm !== null && ($llm['tool'] ?? '') !== 'chat.help') {
                $llm = $this->maybeEnrichWithAnalysis($llm);
                $this->rememberFromResult($llm);
                return $llm;
            }
            if ($llm !== null && ($llm['provider'] ?? '') === 'llm-error') {
                $this->rememberFromResult($llm);
                return $llm;
            }
        }

        $intent = $this->detectIntent($message);
        if ($intent === null) {
            if (!$this->llm->isAnyConfigured()) {
                $didYouMean = $this->replyDidYouMean($message);
                if ($didYouMean !== null) {
                    $this->rememberFromResult($didYouMean);
                    return $didYouMean;
                }
            }

            return $this->buildUnknownHelpReply(null, $message);
        }

        return $this->finishIntent($intent, $message);
    }

    /**
     * @param array{name: string, department?: string|null} $intent
     * @return array{resposta: string, tool?: string, data?: mixed, provider?: string}
     */
    private function finishIntent(array $intent, string $message): array
    {
        $denied = $this->denyIfUnauthorizedIntent((string) ($intent['name'] ?? ''));
        if ($denied !== null) {
            return $denied;
        }

        $result = $this->executeIntent($intent, $message);
        $result = $this->maybeEnrichWithAnalysis($result);
        $this->rememberFromResult($result);

        return $result;
    }

    /**
     * @return array{resposta: string, tool: string, data: null, provider: string}|null
     */
    private function denyIfUnauthorizedIntent(string $intentName): ?array
    {
        if ($intentName === '' || $this->perms->canUseIntent($intentName)) {
            return null;
        }

        return $this->denyTool(
            ChatToolPermissionGate::intentToToolMap()[$intentName] ?? $intentName
        );
    }

    /**
     * @return array{resposta: string, tool: string, data: null, provider: string}
     */
    private function denyTool(string $tool): array
    {
        return [
            'resposta' => $this->perms->denyMessageForTool($tool),
            'tool' => 'chat.denied',
            'data' => ['denied_tool' => $tool],
            'provider' => 'local-rules',
        ];
    }

    /**
     * Saudação pura («bom dia», «olá», «oi», «como está»…) sem pedido de consulta.
     */
    private function isGreetingOnly(string $message): bool
    {
        $m = trim(preg_replace('/[!?？。．…]+$/u', '', trim($message)) ?? trim($message));
        $m = preg_replace('/\s+/u', ' ', $m) ?? $m;
        if ($m === '' || mb_strlen($m) > 80) {
            return false;
        }

        $wellbeing = 'tudo\s+bem|tudo\s+bom|td\s+bem|beleza|como\s+vai|como\s+voc[eê]\s+est[aá]|'
            . 'como\s+est[aá]|como\s+andas|e\s+a[ií]\s+tudo\s+bem';

        // «bom dia», «olá», «bom dia, como está?»
        if (preg_match(
            '/^(ol[aá]|oie+|oi|hey|hello|hi|salve|opa|fala|eai|e\s+a[ií]|'
            . 'bom\s+dia|boa\s+tarde|boa\s+noite)'
            . '(\s*[,!]?\s*(' . $wellbeing . ')(\s*(com\s+voc[eê]|voc[eê]|a[ií]|ai)?)?)?'
            . '\s*$/iu',
            $m
        )) {
            return true;
        }

        // «como está?», «tudo bem?», «como vai você?»
        return (bool) preg_match(
            '/^(e\s+a[ií]|eai\s*[,!]?\s*)?'
            . '(' . $wellbeing . ')'
            . '(\s*(com\s+voc[eê]|voc[eê]|a[ií]|ai)?)?'
            . '\s*$/iu',
            $m
        );
    }

    /**
     * @return array{resposta: string, tool: string, data: null, provider: string}
     */
    private function buildGreetingReply(string $message): array
    {
        $m = mb_strtolower($message);
        $hello = 'Olá';
        if (preg_match('/bom\s+dia/u', $m)) {
            $hello = 'Bom dia';
        } elseif (preg_match('/boa\s+tarde/u', $m)) {
            $hello = 'Boa tarde';
        } elseif (preg_match('/boa\s+noite/u', $m)) {
            $hello = 'Boa noite';
        } elseif (preg_match('/^(oi|oie+)\b/u', $m)) {
            $hello = 'Oi';
        }

        $wellbeing = (bool) preg_match(
            '/tudo\s+bem|tudo\s+bom|td\s+bem|beleza|como\s+vai|como\s+est[aá]|como\s+andas|como\s+voc[eê]/u',
            $m
        );

        $resposta = $wellbeing
            ? "{$hello}! Estou bem, obrigado por perguntar. Como posso ajudar hoje?"
            : "{$hello}! Como posso ajudar hoje?";

        return [
            'resposta' => $resposta,
            'tool' => 'chat.greeting',
            'data' => null,
            'provider' => 'local-rules',
        ];
    }

    /**
     * @return array{resposta: string, tool: string, data: null, provider: string}
     */
    private function buildUnknownHelpReply(?string $intro = null, ?string $query = null): array
    {
        $q = trim((string) $query);
        $intro = $intro ?? ($q !== ''
            ? sprintf('Não encontrei «%s». Tente outra formulação ou um dos exemplos:', mb_substr($q, 0, 80))
            : 'Olá! Não encontrei nada referente à sua solicitação. Segue uma listagem com as possibilidades de consulta:');

        return [
            'resposta' => $intro . "\n\n" . $this->formatHelpOptions(),
            'tool' => 'chat.help',
            'data' => null,
            'provider' => 'local-rules',
        ];
    }

    private function formatHelpOptions(): string
    {
        $lines = [];
        if ($this->perms->canUseTool('rh.count_active')) {
            $lines[] = '• quantos colaboradores ativos? / ativos na TI / quantos no Financeiro';
        }
        if ($this->perms->canUseTool('rh.list_active')) {
            $lines[] = '• nomes dos usuários do Compras / quem é da TI';
        }
        if ($this->perms->canUseTool('rh.list_active_by_age')) {
            $lines[] = '• usuários maiores de 30 anos / ativos da TI com mais de 30 anos';
        }
        if ($this->perms->canUseTool('rh.count_inactive') || $this->perms->canUseTool('rh.count_terminated_in_month')) {
            $lines[] = '• quantos inativos? / inativos em janeiro / desligados 2025';
        }
        if ($this->perms->canUseTool('rh.count_blocked') || $this->perms->canUseTool('rh.count_blocked_not_terminated')) {
            $lines[] = '• quantos bloqueados? / bloqueados sem desligamento';
        }
        if ($this->perms->canUseTool('rh.list_terminated')) {
            $lines[] = '• lista de desligados / lista desligados Produção';
        }
        if ($this->perms->canUseTool('rh.list_hired') || $this->perms->canUseTool('rh.count_hired')) {
            $lines[] = '• lista de contratações em junho / quantas admissões em 2026';
        }
        if ($this->perms->canUseTool('rh.lookup_person')) {
            $lines[] = '• status do Rafael / Wladimir está bloqueado? / anos de empresa do X';
        }
        if ($this->perms->canUseTool('report.list') || $this->perms->canUseTool('report.run')) {
            $lines[] = '• itens / parceiros / item 43000001 / parceiro C00001';
            $lines[] = '• quais relatórios no chat? / relatório [nome]';
        }
        if ($this->perms->canUseTool('rooms.wizard') || $this->perms->canUseTool('rooms.list')) {
            $lines[] = '• agendar / salas / agenda da sala [nome] hoje';
        }
        if ($this->perms->canUseTool('rooms.my') || $this->perms->canUseTool('rooms.cancel')) {
            $lines[] = '• minhas reservas / cancelar reserva #123';
        }
        $lines[] = '• limpar / nova consulta (zera o contexto)';

        if (count($lines) <= 1) {
            return "• No momento não há consultas liberadas no seu nível além de limpar o contexto.\n"
                . "• Peça ao administrador as páginas necessárias (ex.: ListUsers, relatórios do chat, salas).";
        }

        return implode("\n", $lines);
    }

    /**
     * @return array{name: string, token?: string}|null
     */
    private function detectSuggestionPick(string $message): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        if (!preg_match('/^\s*(\d{1,2})\s*$/u', $message, $m)) {
            return null;
        }
        $pending = $_SESSION['internal_chat_suggestions'] ?? null;
        if (!is_array($pending) || empty($pending['items']) || !is_array($pending['items'])) {
            return null;
        }
        $n = (int) $m[1];
        foreach ($pending['items'] as $item) {
            if (!is_array($item) || (int) ($item['n'] ?? 0) !== $n) {
                continue;
            }
            $intent = $item['intent'] ?? null;
            if (!is_array($intent) || empty($intent['name'])) {
                return null;
            }

            return $intent;
        }

        return [
            'name' => 'suggest_invalid',
            'max' => count($pending['items']),
        ];
    }

    /**
     * @return array{resposta: string, tool: string, data: mixed, provider: string}|null
     */
    private function replyDidYouMean(string $message): ?array
    {
        $items = $this->buildDidYouMeanSuggestions($message);
        if ($items === []) {
            return null;
        }

        $shown = mb_substr(trim($message), 0, 80);
        $lines = [
            sprintf('Não encontrei «%s». Você quis dizer?', $shown),
        ];
        $options = [];
        foreach ($items as $item) {
            $lines[] = $item['n'] . ' ' . $item['label'];
            $options[] = [
                'label' => $item['n'] . '. ' . $item['label'],
                'value' => (string) $item['n'],
            ];
        }
        $lines[] = '';
        $lines[] = 'Digite o número da opção.';

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['internal_chat_suggestions'] = [
                'query' => $shown,
                'items' => $items,
            ];
        }

        return [
            'resposta' => implode("\n", $lines),
            'tool' => 'chat.suggest',
            'data' => [
                'ui' => [
                    'type' => 'suggest',
                    'options' => $options,
                ],
            ],
            'provider' => 'local-rules',
        ];
    }

    /**
     * @return list<array{n:int, label:string, score:float, intent:array<string, mixed>}>
     */
    private function buildDidYouMeanSuggestions(string $message): array
    {
        $period = $this->extractPeriod($message);
        $raw = $period !== null ? $this->stripPeriodFromMessage($message) : $message;
        $q = $this->normalizeChatPrompt($raw);
        if (mb_strlen($q) < 4) {
            return [];
        }

        $ranked = [];

        if ($this->perms->canUseTool('report.run') || $this->perms->canUseTool('report.list')) {
            foreach ($this->reports->listCatalogForUser($this->userId) as $item) {
                $phrases = [(string) ($item['name'] ?? ''), (string) ($item['chat_tool_name'] ?? '')];
                foreach ($item['examples'] ?? [] as $ex) {
                    $phrases[] = (string) $ex;
                }
                $best = 0.0;
                $label = (string) ($item['name'] ?? 'Relatório');
                foreach ($phrases as $phrase) {
                    $phrase = trim($phrase);
                    if ($phrase === '') {
                        continue;
                    }
                    $score = $this->suggestSimilarity($q, $phrase);
                    if ($score > $best) {
                        $best = $score;
                        $label = $phrase;
                    }
                }
                if ($best < 0.58) {
                    continue;
                }
                $intent = [
                    'name' => 'report_run',
                    'report_id' => (int) ($item['id'] ?? 0),
                    'query' => (string) ($item['name'] ?? $q),
                ];
                if ($period !== null) {
                    $intent = [
                        'name' => 'report_month_filter',
                        'report_id' => (int) ($item['id'] ?? 0),
                        'month' => $period['month'],
                        'year' => $period['year'],
                        'query' => (string) ($item['name'] ?? $q),
                    ];
                }
                $ranked[] = [
                    'label' => $label,
                    'score' => $best,
                    'intent' => $intent,
                    'key' => 'report:' . (int) ($item['id'] ?? 0),
                ];
            }
        }

        foreach ($this->builtinSuggestCatalog() as $row) {
            if (!$this->perms->canUseTool((string) $row['tool'])) {
                continue;
            }
            $best = 0.0;
            foreach ($row['phrases'] as $phrase) {
                $best = max($best, $this->suggestSimilarity($q, (string) $phrase));
            }
            if ($best < 0.62) {
                continue;
            }
            $ranked[] = [
                'label' => (string) $row['label'],
                'score' => $best,
                'intent' => $row['intent'],
                'key' => 'tool:' . $row['tool'],
            ];
        }

        usort($ranked, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);
        $out = [];
        $seen = [];
        foreach ($ranked as $row) {
            $key = (string) $row['key'];
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = [
                'n' => count($out) + 1,
                'label' => (string) $row['label'],
                'score' => (float) $row['score'],
                'intent' => $row['intent'],
            ];
            if (count($out) >= 5) {
                break;
            }
        }

        return $out;
    }

    /**
     * @return list<array{label:string, tool:string, phrases:list<string>, intent:array<string, mixed>}>
     */
    private function builtinSuggestCatalog(): array
    {
        return [
            [
                'label' => 'Ativos por idade',
                'tool' => 'rh.list_active_by_age',
                'phrases' => [
                    'maiores de 30 anos',
                    'usuarios maiores de 30 anos',
                    'usuários maiores de 30 anos',
                    'mais de 30 anos',
                    'acima de 30 anos',
                ],
                'intent' => ['name' => 'active_by_age', 'min_age' => 30],
            ],
            [
                'label' => 'Inativos / desligados',
                'tool' => 'rh.count_inactive',
                'phrases' => ['inativos', 'desligados', 'ex colaboradores'],
                'intent' => ['name' => 'inactive'],
            ],
            [
                'label' => 'Usuários bloqueados',
                'tool' => 'rh.count_blocked',
                'phrases' => ['bloqueados', 'bloqueio', 'usuarios bloqueados'],
                'intent' => ['name' => 'blocked'],
            ],
            [
                'label' => 'Lista de desligados',
                'tool' => 'rh.list_terminated',
                'phrases' => ['lista de desligados', 'listar desligados'],
                'intent' => ['name' => 'terminated_list'],
            ],
            [
                'label' => 'Lista de contratações',
                'tool' => 'rh.list_hired',
                'phrases' => ['contratacoes', 'contratações', 'admissoes', 'admissões', 'admitidos'],
                'intent' => ['name' => 'hired_list'],
            ],
            [
                'label' => 'Agendar sala',
                'tool' => 'rooms.wizard',
                'phrases' => ['agendar', 'reservar sala', 'salas', 'agendar sala'],
                'intent' => ['name' => 'rooms_wizard_start'],
            ],
        ];
    }

    private function suggestSimilarity(string $query, string $candidate): float
    {
        $q = $this->normalizeChatPrompt($query);
        $c = $this->normalizeChatPrompt($candidate);
        if ($q === '' || $c === '') {
            return 0.0;
        }
        if ($q === $c) {
            return 1.0;
        }
        if (str_contains($c, $q)) {
            return min(0.96, 0.74 + (mb_strlen($q) / max(mb_strlen($c), 1)) * 0.22);
        }

        $best = 0.0;
        $parts = preg_split('/\s+/u', $c) ?: [];
        $parts[] = $c;
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }
            if (function_exists('similar_text')) {
                similar_text($q, $part, $pct);
                $best = max($best, ((float) $pct) / 100);
            }
            $qBytes = (string) $q;
            $pBytes = (string) $part;
            if (strlen($qBytes) <= 255 && strlen($pBytes) <= 255) {
                $max = max(strlen($qBytes), strlen($pBytes), 1);
                $best = max($best, 1 - (levenshtein($qBytes, $pBytes) / $max));
            }
        }

        return $best;
    }

    /**
     * Follow-ups de sessão (não são texto livre): limpar, 1/2/3, código do relatório, etc.
     *
     * @return array<string, mixed>|null
     */
    private function detectSessionFollowUp(string $message): ?array
    {
        $m = mb_strtolower(trim($message));
        $m = preg_replace('/[?!.]+$/u', '', $m) ?? $m;
        $m = trim($m);

        if (!$this->mentionsHiring($m)) {
            $listFollowUp = $this->detectTerminatedListFollowUp($m, $message);
            if ($listFollowUp !== null) {
                return $listFollowUp;
            }
        }

        if (preg_match('/^(limpar|nova\s+consulta|esqueci|esquecer|outra\s+pessoa|reiniciar)(\s+contexto)?$/iu', $m)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset(
                    $_SESSION['internal_chat_person_candidates'],
                    $_SESSION['internal_chat_last_terminated'],
                    $_SESSION['internal_chat_last_hired'],
                    $_SESSION['internal_chat_last_status'],
                    $_SESSION['internal_chat_last_report'],
                    $_SESSION['internal_chat_suggestions']
                );
            }

            return ['name' => 'clear_context'];
        }

        $reportMonth = $this->detectReportMonthFollowUp($message);
        if ($reportMonth !== null) {
            return $reportMonth;
        }

        $reportCode = $this->detectReportCodeFollowUp($message, $m);
        if ($reportCode !== null) {
            return $reportCode;
        }

        $personRefine = $this->detectPersonCandidateRefine($message);
        if ($personRefine !== null) {
            return $personRefine;
        }

        $suggestPick = $this->detectSuggestionPick($message);
        if ($suggestPick !== null) {
            return $suggestPick;
        }

        $byMonthFollowUp = $this->extractByMonthFollowUp($m);
        if ($byMonthFollowUp !== null) {
            return $byMonthFollowUp;
        }

        $bareYear = $this->extractYearOnly($m);
        if ($bareYear !== null && preg_match('/^(em\s+|de\s+|s[oó]\s+(os\s+(de\s+)?)?)?20\d{2}$/u', $m)) {
            $lastTerm = (session_status() === PHP_SESSION_ACTIVE)
                ? ($_SESSION['internal_chat_last_terminated'] ?? null)
                : null;
            if (is_array($lastTerm)) {
                return [
                    'name' => 'terminated_list',
                    'month' => null,
                    'year' => $bareYear,
                    'department' => isset($lastTerm['department']) && $lastTerm['department'] !== ''
                        ? (string) $lastTerm['department']
                        : null,
                ];
            }
            if ($this->getLastStatusIntent() === 'inactive') {
                return ['name' => 'terminated_by_month', 'year' => $bareYear];
            }
        }

        $bareDept = $this->matchBareDepartmentName($m);
        if ($bareDept !== null) {
            return ['name' => $this->getLastStatusIntent(), 'department' => $bareDept];
        }

        return null;
    }

    /**
     * @return array{name: string, department?: string|null}|null
     */
    private function detectIntent(string $message): ?array
    {
        $m = mb_strtolower(trim($message));
        $m = preg_replace('/[?!.]+$/u', '', $m) ?? $m;
        $m = trim($m);

        // Follow-up «lista» após totais de desligados (nunca para contratações/admissões).
        if (!$this->mentionsHiring($m)) {
            $listFollowUp = $this->detectTerminatedListFollowUp($m, $message);
            if ($listFollowUp !== null) {
                return $listFollowUp;
            }
        }

        $hireIntent = $this->detectHireIntent($m, $message);
        if ($hireIntent !== null) {
            return $hireIntent;
        }

        $ageIntent = $this->detectAgeIntent($m, $message);
        if ($ageIntent !== null) {
            return $ageIntent;
        }

        if (preg_match('/^(limpar|nova\s+consulta|esqueci|esquecer|outra\s+pessoa|reiniciar)(\s+contexto)?$/iu', $m)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                unset(
                    $_SESSION['internal_chat_person_candidates'],
                    $_SESSION['internal_chat_last_terminated'],
                    $_SESSION['internal_chat_last_hired'],
                    $_SESSION['internal_chat_last_status'],
                    $_SESSION['internal_chat_last_report'],
                    $_SESSION['internal_chat_suggestions']
                );
            }

            return ['name' => 'clear_context'];
        }

        $reportMonth = $this->detectReportMonthFollowUp($message);
        if ($reportMonth !== null) {
            return $reportMonth;
        }

        // Código de item/parceiro/doc (sozinho ou «item 43000001») → filtra relatório.
        $reportCode = $this->detectReportCodeFollowUp($message, $m);
        if ($reportCode !== null) {
            return $reportCode;
        }

        // Matrícula/id numérico só vira pessoa se NÃO houver relatório recente.
        if (preg_match('/^\d{3,}$/u', $m)) {
            return ['name' => 'lookup_person', 'query' => $m];
        }

        $personRefine = $this->detectPersonCandidateRefine($message);
        if ($personRefine !== null) {
            return $personRefine;
        }

        $suggestPick = $this->detectSuggestionPick($message);
        if ($suggestPick !== null) {
            return $suggestPick;
        }

        if (preg_match('/bloquead|bloqueio|tentativas?\s+de\s+login/', $m)) {
            $personBlocked = $this->extractPersonQuery($message);
            if ($personBlocked !== null) {
                return ['name' => 'lookup_person', 'query' => $personBlocked];
            }
            if (preg_match('/n[aã]o\s+desligad|sem\s+desligamento|bloquead[oa]s?\s+(mas\s+)?n[aã]o\s+desligad|desligad[oa]s?\s+n[aã]o/u', $m)
                || preg_match('/bloquead[oa]s?\s+(ainda\s+)?(na\s+empresa|ativos?)/u', $m)
            ) {
                return ['name' => 'blocked_not_terminated'];
            }

            return ['name' => 'blocked'];
        }

        $personQuery = $this->extractPersonQuery($message);
        if ($personQuery !== null) {
            return ['name' => 'lookup_person', 'query' => $personQuery];
        }

        $roomsIntent = $this->detectRoomsIntent($m, $message);
        if ($roomsIntent !== null) {
            return $roomsIntent;
        }

        if (preg_match('/quais\s+relat[oó]?rios|listar\s+relat[oó]?rios|relat[oó]?rios\s+(no\s+)?chat|cat[aá]logo\s+(do\s+)?chat/u', $m)) {
            return ['name' => 'report_list'];
        }

        if (preg_match('/\brelat[oó]?rio\b|report\.run|execut[ea]\s+relat|rode\s+o\s+relat/u', $m)) {
            return ['name' => 'report_run', 'query' => $message];
        }

        // Follow-ups curtos ("por mês") NÃO devem cair no match frágil de exemplos de relatório.
        $byMonthFollowUp = $this->extractByMonthFollowUp($m);
        if ($byMonthFollowUp !== null) {
            return $byMonthFollowUp;
        }

        if (preg_match('/por\s+departamento|headcount\s+por|ativos\s+por\s+depto|resumo\s+por\s+departamento/', $m)) {
            if (preg_match('/\binativos?\b|\bdesligad[oa]s?\b|\bex[- ]?colaboradores?\b/', $m)) {
                $yearOnly = $this->extractYearOnly($m);
                $period = $this->extractPeriod($message);
                if ($period !== null) {
                    return [
                        'name' => 'terminated_in_period',
                        'month' => $period['month'],
                        'year' => $period['year'],
                        'by_department' => true,
                    ];
                }

                return [
                    'name' => 'terminated_by_department',
                    'year' => $yearOnly,
                    'month' => null,
                ];
            }
            if ($this->getLastStatusIntent() === 'inactive') {
                return ['name' => 'inactive', 'department' => null];
            }

            return ['name' => 'by_department'];
        }

        // Exemplos / tool_name / nome de relatório marcado para o chat.
        $periodInQuery = $this->extractPeriod($message);
        $matchQuery = $periodInQuery !== null
            ? ($this->stripPeriodFromMessage($message) ?: $message)
            : $message;
        $matchedReport = $this->reports->resolveReport($this->userId, $matchQuery, $periodInQuery !== null);
        if ($matchedReport !== null) {
            $matchNorm = $this->normalizeChatPrompt($matchQuery);
            $tokens = preg_split('/\s+/u', $matchNorm) ?: [];
            $singleWord = count($tokens) === 1 && mb_strlen($matchNorm) < 14
                && !$this->messageMatchesReportPrompt($matchQuery, $matchedReport);
            if ($periodInQuery !== null) {
                return [
                    'name' => 'report_month_filter',
                    'report_id' => (int) $matchedReport['id'],
                    'month' => $periodInQuery['month'],
                    'year' => $periodInQuery['year'],
                ];
            }
            if (!$singleWord) {
                $codeInMsg = $this->extractReportCodeFromMessage($message);
                if ($codeInMsg !== null && !$this->messageMatchesReportPrompt($message, $matchedReport)) {
                    return [
                        'name' => 'report_code_filter',
                        'code' => $codeInMsg,
                        'report_id' => (int) $matchedReport['id'],
                    ];
                }

                return ['name' => 'report_run', 'query' => $message, 'report_id' => (int) $matchedReport['id']];
            }
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
            $wantsList = (bool) preg_match(
                '/\b(lista|listar|nomes|nominativa|quais\s+(s[aã]o|foram)|quem\s+(s[aã]o|foram)|mostrar\s+(os\s+)?nomes)\b/u',
                $m
            );
            $wantsByDept = (bool) preg_match('/\bpor\s+departamento\b|\bpor\s+depto\b/u', $m);
            // "desligados 2025" / "inativos por mês" → série mensal do ano
            $yearOnly = $this->extractYearOnly($m);
            $wantsByMonth = (bool) preg_match('/\bpor\s+m[eê]s\b|\bmensal\b|\bpor\s+meses\b/u', $m);
            $listDept = $dept;
            if ($listDept === null && $wantsList) {
                // «lista desligados Produção» / «desligados da TI»
                $listDept = $this->extractDepartment($message);
                $listDept = $this->resolveDepartmentAlias($listDept);
                if ($listDept === null) {
                    $listDept = $this->extractTerminatedListDepartment($message);
                }
                if ($period !== null) {
                    $listDept = null;
                }
            }

            if ($wantsList && preg_match('/\bdesligad[oa]s?\b/u', $m)) {
                return [
                    'name' => 'terminated_list',
                    'month' => $period['month'] ?? null,
                    'year' => $period['year'] ?? $yearOnly,
                    'department' => $listDept,
                ];
            }
            if ($period !== null) {
                return [
                    'name' => 'terminated_in_period',
                    'month' => $period['month'],
                    'year' => $period['year'],
                    'by_department' => $wantsByDept,
                ];
            }
            if ($wantsByDept) {
                return [
                    'name' => 'terminated_by_department',
                    'year' => $yearOnly,
                    'month' => null,
                ];
            }
            if ($wantsByMonth || $yearOnly !== null) {
                return [
                    'name' => 'terminated_by_month',
                    'year' => $yearOnly ?? (int) date('Y'),
                    // «inativos 2025» não é o estoque atual — é série de desligamentos do ano.
                    'clarify_inactive' => (bool) preg_match('/\binativos?\b/u', $m),
                ];
            }
            // «quantos desligados?» → total com data_desligamento (não misturar com estoque inativo)
            if (preg_match('/\bdesligad[oa]s?\b/u', $m) && !preg_match('/\binativos?\b/u', $m)) {
                return ['name' => 'terminated_total'];
            }

            return ['name' => 'inactive', 'department' => $dept];
        }

        // Só o ano (ex.: «2026») após desligados na sessão → lista do ano (não gráfico mensal).
        $bareYear = $this->extractYearOnly($m);
        if ($bareYear !== null && preg_match('/^(em\s+|de\s+|s[oó]\s+(os\s+(de\s+)?)?)?20\d{2}$/u', $m)) {
            $lastTerm = (session_status() === PHP_SESSION_ACTIVE)
                ? ($_SESSION['internal_chat_last_terminated'] ?? null)
                : null;
            if (is_array($lastTerm)) {
                return [
                    'name' => 'terminated_list',
                    'month' => null,
                    'year' => $bareYear,
                    'department' => isset($lastTerm['department']) && $lastTerm['department'] !== ''
                        ? (string) $lastTerm['department']
                        : null,
                ];
            }
            if ($this->getLastStatusIntent() === 'inactive') {
                return ['name' => 'terminated_by_month', 'year' => $bareYear];
            }
        }

        if (preg_match('/\b(quantos|qtd|quantidade|headcount|pessoas)\b|\bativos?\b|\bcolaboradores?\b|\bfuncionarios?\b|\busuarios?\b/u', $m)) {
            if ($this->messageWantsPersonList($message) && !$this->messageWantsHeadcount($message)) {
                return ['name' => 'active_list', 'department' => $dept];
            }

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
        if (preg_match('/\b(quantos|qtd|quantidade|ativos?|inativos?|bloqueados?|colaboradores?|funcionarios?|usuarios?|headcount|pessoas|desligad[oa]s?|departamento|anos?|empresa)\b/u', $normalizedMessage)) {
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
     * «por mês» / «mensal» — follow-up após inativos, ou frase completa.
     *
     * @return array{name:string, year?:int}|null
     */
    private function extractByMonthFollowUp(string $normalizedMessage): ?array
    {
        $m = trim($normalizedMessage);
        if ($m === '') {
            return null;
        }

        $year = $this->extractYearOnly($m) ?? (int) date('Y');

        // Frase só com a dimensão (contexto da sessão).
        if (preg_match('/^(por\s+m[eê]s|mensal|por\s+meses)(\s+(de\s+)?\d{4})?$/u', $m)) {
            if ($this->getLastStatusIntent() === 'inactive') {
                return ['name' => 'terminated_by_month', 'year' => $year];
            }

            return ['name' => 'clarify_by_month'];
        }

        return null;
    }

    private function extractYearOnly(string $message): ?int
    {
        if (!preg_match('/\b(20\d{2})\b/', mb_strtolower($message), $ym)) {
            return null;
        }
        $year = (int) $ym[1];
        if ($year < 2000 || $year > 2100) {
            return null;
        }

        return $year;
    }

    /**
     * Intenções de salas de reunião.
     *
     * @return array<string, mixed>|null
     */
    private function detectRoomsIntent(string $normalized, string $original): ?array
    {
        if (!preg_match('/\b(salas?|reserva|reservas|reservar|agendar|agenda|cancelar\s+reserva|book-room|reuni[aã]o)\b/u', $normalized)) {
            return null;
        }

        if (preg_match('/cancelar\s+reserva\s*#?\s*(\d+)/u', $normalized, $mm)) {
            return ['name' => 'rooms_cancel', 'booking_id' => (int) $mm[1]];
        }
        if (preg_match('/\bcancelar\s+reserva\b/u', $normalized)) {
            return ['name' => 'rooms_cancel', 'booking_id' => 0];
        }

        if (preg_match('/\b(minhas\s+reservas|minha\s+agenda(\s+de\s+salas?)?)\b/u', $normalized)) {
            return ['name' => 'rooms_my'];
        }

        if (preg_match('/^(salas?|listar\s+salas?|quais\s+salas?|salas?\s+dispon[ií]veis)$/u', $normalized)) {
            // Lista com fotos + convite ao fluxo «agendar»
            return ['name' => 'rooms_list'];
        }
        if (preg_match('/\b(listar|quais|ver)\s+salas?\b/u', $normalized)) {
            return ['name' => 'rooms_list'];
        }
        if (preg_match('/^(agendar|reservar)$/u', $normalized)) {
            return ['name' => 'rooms_wizard_start'];
        }

        if (preg_match('/\b(agenda|ocupa[cç][aã]o|disponibilidade)\b/u', $normalized)) {
            $day = $this->extractRelativeDay($normalized) ?? date('Y-m-d');
            $roomQuery = null;
            // «agenda da sala X hoje» / «agenda sala grande 10/08/2026»
            if (preg_match('/(?:agenda|ocupa[cç][aã]o|disponibilidade)\s+(?:da\s+|de\s+|na\s+)?sala\s+(.+)$/u', $normalized, $mm)) {
                $roomQuery = trim($mm[1]);
                $roomQuery = preg_replace('/\s+(hoje|amanh[aã]|\d{1,2}\/\d{1,2}(?:\/\d{2,4})?)\s*$/u', '', $roomQuery) ?? $roomQuery;
                $roomQuery = trim($roomQuery);
                if ($roomQuery === '' || preg_match('/^(hoje|amanh[aã])$/u', $roomQuery)) {
                    $roomQuery = null;
                }
            }

            return ['name' => 'rooms_agenda', 'room' => $roomQuery, 'date' => $day];
        }

        if (preg_match('/\b(reservar|agendar)\b/u', $normalized)) {
            $parsed = $this->parseRoomReservation($normalized, $original);
            if ($parsed === null) {
                return [
                    'name' => 'rooms_reserve_help',
                ];
            }

            return array_merge(['name' => 'rooms_reserve'], $parsed);
        }

        if (preg_match('/^salas?\b/u', $normalized)) {
            return ['name' => 'rooms_list'];
        }

        return null;
    }

    /**
     * @return array{room:?string, start:string, end:string, title:string}|null
     */
    private function parseRoomReservation(string $normalized, string $original): ?array
    {
        // reservar sala NOME [hoje|amanhã|dd/mm/yyyy] HH:MM às HH:MM [reunião|título ...]
        if (!preg_match(
            '/(?:reservar|agendar)\s+(?:a\s+|uma\s+)?sala\s+(.+?)\s+(hoje|amanh[aã]|\d{1,2}\/\d{1,2}(?:\/\d{2,4})?)\s+(?:das?\s*)?(\d{1,2}):(\d{2})\s*(?:às?|ate|até|a|-|–)\s*(\d{1,2}):(\d{2})(?:\s+(?:reuni[aã]o|t[ií]tulo|:)?\s*(.+))?$/u',
            $normalized,
            $mm
        )) {
            return null;
        }

        $room = trim($mm[1]);
        $dayToken = $mm[2];
        $sh = (int) $mm[3];
        $sm = (int) $mm[4];
        $eh = (int) $mm[5];
        $em = (int) $mm[6];
        $title = isset($mm[7]) ? trim($mm[7]) : 'Reunião';
        if ($title === '') {
            $title = 'Reunião';
        }

        $day = $this->resolveDayToken($dayToken);
        if ($day === null) {
            return null;
        }

        $start = sprintf('%s %02d:%02d:00', $day, $sh, $sm);
        $end = sprintf('%s %02d:%02d:00', $day, $eh, $em);

        return [
            'room' => $room,
            'start' => $start,
            'end' => $end,
            'title' => $title,
        ];
    }

    private function extractRelativeDay(string $normalized): ?string
    {
        if (preg_match('/\bhoje\b/u', $normalized)) {
            return date('Y-m-d');
        }
        if (preg_match('/\bamanh[aã]\b/u', $normalized)) {
            return date('Y-m-d', strtotime('+1 day') ?: time());
        }
        if (preg_match('/\b(\d{1,2})\/(\d{1,2})(?:\/(\d{2,4}))?\b/', $normalized, $mm)) {
            $d = (int) $mm[1];
            $m = (int) $mm[2];
            $y = isset($mm[3]) && $mm[3] !== '' ? (int) $mm[3] : (int) date('Y');
            if ($y < 100) {
                $y += 2000;
            }
            if (!checkdate($m, $d, $y)) {
                return null;
            }

            return sprintf('%04d-%02d-%02d', $y, $m, $d);
        }

        return null;
    }

    private function resolveDayToken(string $token): ?string
    {
        $token = mb_strtolower(trim($token));
        if ($token === 'hoje') {
            return date('Y-m-d');
        }
        if (preg_match('/^amanh[aã]$/u', $token)) {
            return date('Y-m-d', strtotime('+1 day') ?: time());
        }

        return $this->extractRelativeDay($token);
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
        if ($tool !== '' && $tool !== 'chat.suggest') {
            unset($_SESSION['internal_chat_suggestions']);
        }
        if (
            $tool === 'rh.count_inactive'
            || $tool === 'rh.count_terminated_in_month'
            || $tool === 'rh.count_terminated_by_month'
            || $tool === 'rh.count_terminated'
            || $tool === 'rh.count_terminated_by_department'
            || $tool === 'rh.list_terminated'
        ) {
            $_SESSION['internal_chat_last_status'] = 'inactive';
            unset($_SESSION['internal_chat_person_candidates']);
            if (in_array($tool, [
                'rh.count_terminated_in_month',
                'rh.count_terminated_by_month',
                'rh.count_terminated',
                'rh.count_terminated_by_department',
                'rh.list_terminated',
            ], true)) {
                $data = is_array($result['data'] ?? null) ? $result['data'] : [];
                $_SESSION['internal_chat_last_terminated'] = [
                    'month' => isset($data['month']) ? (int) $data['month'] : null,
                    'year' => isset($data['year']) ? (int) $data['year'] : null,
                    'department' => isset($data['department']) ? (string) $data['department'] : null,
                ];
                unset($_SESSION['internal_chat_last_hired']);
                // count_terminated_in_month uses month/year keys
                if ($tool === 'rh.count_terminated_in_month') {
                    $_SESSION['internal_chat_last_terminated']['month'] = (int) ($data['month'] ?? 0) ?: null;
                    $_SESSION['internal_chat_last_terminated']['year'] = (int) ($data['year'] ?? 0) ?: null;
                }
                if ($tool === 'rh.count_terminated_by_month') {
                    $_SESSION['internal_chat_last_terminated']['year'] = (int) ($data['year'] ?? 0) ?: null;
                    $_SESSION['internal_chat_last_terminated']['month'] = null;
                    $_SESSION['internal_chat_last_terminated']['department'] = null;
                }
            }
        } elseif ($tool === 'rh.list_hired' || $tool === 'rh.count_hired') {
            $_SESSION['internal_chat_last_status'] = 'active';
            unset($_SESSION['internal_chat_person_candidates'], $_SESSION['internal_chat_last_terminated']);
            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $_SESSION['internal_chat_last_hired'] = [
                'month' => isset($data['month']) ? (int) $data['month'] : null,
                'year' => isset($data['year']) ? (int) $data['year'] : null,
                'department' => isset($data['department']) ? (string) $data['department'] : null,
            ];
        } elseif ($tool === 'rh.count_active' || $tool === 'rh.count_active_by_department'
            || $tool === 'rh.list_active' || $tool === 'rh.list_active_by_age') {
            $_SESSION['internal_chat_last_status'] = 'active';
            unset(
                $_SESSION['internal_chat_person_candidates'],
                $_SESSION['internal_chat_last_terminated'],
                $_SESSION['internal_chat_last_hired']
            );
        } elseif ($tool === 'rh.lookup_person') {
            $data = $result['data'] ?? null;
            $preserve = is_array($data) && !empty($data['preserve_candidates']);
            $matches = is_array($data) ? ($data['matches'] ?? []) : [];
            if ($preserve) {
                // Mantém a lista ambígua original para novos refinamentos (TI → Comercial → 2…).
                return;
            }
            if (is_array($matches) && count($matches) > 1) {
                $_SESSION['internal_chat_person_candidates'] = [
                    'query' => (string) ($data['query'] ?? ''),
                    'matches' => array_values(array_map(static function (array $row): array {
                        return [
                            'id' => (int) ($row['id'] ?? 0),
                            'name' => (string) ($row['name'] ?? ''),
                            'username' => (string) ($row['username'] ?? ''),
                            'department' => (string) ($row['department'] ?? ''),
                        ];
                    }, $matches)),
                ];
            } else {
                unset($_SESSION['internal_chat_person_candidates']);
            }
            unset($_SESSION['internal_chat_last_report']);
        } elseif ($tool === 'report.run') {
            $data = is_array($result['data'] ?? null) ? $result['data'] : [];
            $reportId = (int) ($data['report_id'] ?? 0);
            if ($reportId > 0) {
                $_SESSION['internal_chat_last_report'] = [
                    'report_id' => $reportId,
                    'name' => (string) ($data['name'] ?? ''),
                    'filter_code' => isset($data['filter_code']) ? (string) $data['filter_code'] : null,
                ];
            }
            unset($_SESSION['internal_chat_person_candidates']);
        } elseif ($tool !== '' && str_starts_with($tool, 'rooms.')) {
            unset($_SESSION['internal_chat_person_candidates'], $_SESSION['internal_chat_last_report']);
        }
    }

    /**
     * Só o período («08/2026», «jun/26») após um relatório → filtra o último relatório.
     *
     * @return array{name: string, report_id: int, month: int, year: int}|null
     */
    private function detectReportMonthFollowUp(string $message): ?array
    {
        $period = $this->extractPeriod($message);
        if ($period === null) {
            return null;
        }
        if (preg_match('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/u', $message)) {
            return null;
        }
        $left = $this->stripPeriodFromMessage($message);
        $left = preg_replace('/\b(em|de|do|da|no|na|o|a|os|as|mes|mês|mensal|por)\b/iu', ' ', $left) ?? $left;
        $left = trim(preg_replace('/\s+/u', ' ', $left) ?? $left);
        if ($left !== '') {
            return null;
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $last = $_SESSION['internal_chat_last_report'] ?? null;
        $reportId = is_array($last) ? (int) ($last['report_id'] ?? 0) : 0;
        if ($reportId < 1) {
            return null;
        }

        return [
            'name' => 'report_month_filter',
            'report_id' => $reportId,
            'month' => $period['month'],
            'year' => $period['year'],
        ];
    }

    /**
     * Após um relatório, «43000001» / «item 43000001» / código de parceiro filtra as linhas.
     *
     * @return array{name: string, code: string, report_id: int}|null
     */
    private function detectReportCodeFollowUp(string $message, string $normalized): ?array
    {
        if ($this->extractPeriod($message) !== null) {
            return null;
        }

        $code = $this->extractReportCodeFromMessage($message);
        if ($code === null) {
            return null;
        }

        // Não roubar perguntas completas de pessoa / RH / salas.
        if ($this->extractPersonQuery($message) !== null) {
            return null;
        }
        if (preg_match(
            '/\b(quantos|qtd|quantidade|headcount|agendar|reservar|salas?|desligad|inativos?|bloqueados?|ativos?\b|departamento|status\s+(do|da|de)|ficha|lista\s+de)\b/u',
            $normalized
        )) {
            return null;
        }

        $reportId = 0;
        $hint = $this->stripReportCodeFromMessage($message, $code);

        // Se a frase pede domínio explícito («item …», «parceiro …»), troca o relatório
        // — não fica preso no último (ex.: PN → item).
        if ($hint !== '') {
            $matched = $this->reports->resolveReport($this->userId, $hint);
            if ($matched !== null) {
                $reportId = (int) $matched['id'];
            }
        }

        if ($reportId < 1 && session_status() === PHP_SESSION_ACTIVE) {
            // Ano puro após contexto de desligados continua no fluxo de RH.
            if (preg_match('/^20\d{2}$/u', $code) && is_array($_SESSION['internal_chat_last_terminated'] ?? null)) {
                return null;
            }
            $last = $_SESSION['internal_chat_last_report'] ?? null;
            if (is_array($last)) {
                $reportId = (int) ($last['report_id'] ?? 0);
            }
        }

        if ($reportId < 1) {
            return null;
        }

        $aliasKey = mb_strtolower(preg_replace('/\s+/u', ' ', $code) ?? $code);
        if (isset(self::DEPARTMENT_ALIASES[$aliasKey]) || $this->matchBareDepartmentName($aliasKey) !== null) {
            return null;
        }

        return [
            'name' => 'report_code_filter',
            'code' => $code,
            'report_id' => $reportId,
        ];
    }

    /**
     * Extrai código de «43000001», «item 43000001», «parceiro C00001», «código: 1000001».
     */
    private function extractReportCodeFromMessage(string $message): ?string
    {
        $raw = trim(preg_replace('/[?!.]+$/u', '', trim($message)) ?? trim($message));
        if ($raw === '') {
            return null;
        }

        if (preg_match(
            '/\b(?:item|itens|cd\s*item|cditem|itemcode|c[oó]digos?|cod(?:igo)?|sku|parceiros?|card\s*code|cardcode|clientes?|fornecedores?|doc(?:num|umento)?|pedidos?|notas?)\b\s*[:=\-]?\s*([A-Za-z0-9][A-Za-z0-9\-_\.\/]{1,40}|\d{3,})\b/iu',
            $raw,
            $mm
        )) {
            $code = trim((string) $mm[1]);
            if ($this->looksLikeReportFilterCode($code)) {
                return $code;
            }
        }

        // Código no fim: «relatório itens 43000001» (não «vendas por mes»).
        if (preg_match('/\b([A-Za-z0-9][A-Za-z0-9\-_\.\/]{2,40}|\d{3,})\s*$/u', $raw, $mm)
            && preg_match('/\b(?:item|itens|parceiros?|vendas?|compras?|relat[oó]rio)\b/iu', $raw)
        ) {
            $code = trim((string) $mm[1]);
            if ($this->looksLikeReportFilterCode($code)) {
                return $code;
            }
        }

        $normalized = mb_strtolower($raw);
        if (preg_match('/^\d{3,}$/u', $normalized) && $this->looksLikeReportFilterCode($raw)) {
            return $raw;
        }
        if (preg_match('/^[a-z0-9][a-z0-9\-_\.\/]{2,40}$/iu', $raw)
            && $this->looksLikeReportFilterCode($raw)
            && !isset(self::DEPARTMENT_ALIASES[$normalized])
            && $this->matchBareDepartmentName($normalized) === null
        ) {
            return $raw;
        }

        return null;
    }

    private function isReportCodeStopword(string $token): bool
    {
        $t = mb_strtolower(trim($token));
        $stop = [
            'item', 'itens', 'parceiro', 'parceiros', 'cliente', 'clientes', 'fornecedor', 'fornecedores',
            'venda', 'vendas', 'compra', 'compras', 'codigo', 'código', 'codigos', 'códigos', 'sku',
            'documento', 'documentos', 'pedido', 'pedidos', 'nota', 'notas', 'relatorio', 'relatório',
            'cardcode', 'cditem', 'itemcode',
            'mes', 'mês', 'meses', 'ano', 'anos', 'dia', 'dias', 'data', 'datas',
            'mensal', 'anual', 'diario', 'diário', 'semanal', 'periodo', 'período',
            'liquido', 'líquido', 'faturamento', 'total', 'por', 'para', 'com', 'sem',
        ];
        if (in_array($t, $stop, true)) {
            return true;
        }

        return isset(self::MONTHS[$t]);
    }

    /**
     * Código SAP (item/parceiro/documento) tem dígito. Palavras como «mes» não filtram.
     */
    private function looksLikeReportFilterCode(string $code): bool
    {
        $code = trim($code);
        if ($code === '' || $this->isReportCodeStopword($code)) {
            return false;
        }
        if (preg_match('/^\d{1,2}[\/\-]\d{2,4}$/u', $code)) {
            return false;
        }
        if (!preg_match('/\d/u', $code)) {
            return false;
        }

        return mb_strlen($code) >= 3;
    }

    /**
     * «vendas por mes» é o exemplo do relatório, não «filtra por código mes».
     *
     * @param array<string, mixed> $report
     */
    private function messageMatchesReportPrompt(string $message, array $report): bool
    {
        $q = $this->normalizeChatPrompt($message);
        if ($q === '') {
            return false;
        }
        $candidates = [
            (string) ($report['name'] ?? ''),
            (string) ($report['chat_tool_name'] ?? ''),
        ];
        $examples = $report['chat_example_prompts'] ?? [];
        if (is_array($examples)) {
            foreach ($examples as $ex) {
                $candidates[] = (string) $ex;
            }
        }
        foreach ($candidates as $candidate) {
            $n = $this->normalizeChatPrompt($candidate);
            if ($n !== '' && $q === $n) {
                return true;
            }
        }

        return false;
    }

    private function normalizeChatPrompt(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
        ]);
        $value = str_replace(['_', '-'], ' ', $value);
        $value = preg_replace('/[?!.]+$/u', '', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function stripReportCodeFromMessage(string $message, string $code): string
    {
        $hint = trim($message);
        $hint = preg_replace('/\b' . preg_quote($code, '/') . '\b/iu', ' ', $hint) ?? $hint;
        $hint = preg_replace(
            '/\b(?:cd\s*item|cditem|itemcode|c[oó]digos?|cod(?:igo)?|sku|card\s*code|cardcode)\b/iu',
            ' ',
            $hint
        ) ?? $hint;
        $hint = preg_replace('/\s+/u', ' ', $hint) ?? $hint;

        return trim($hint, " \t:=-");
    }

    /**
     * Após lista ambígua (vários Rafaéis), aceita «TI», username ou número da lista.
     *
     * @return array{name: string, token?: string}|null
     */
    private function detectPersonCandidateRefine(string $message): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $pending = $_SESSION['internal_chat_person_candidates'] ?? null;
        if (!is_array($pending) || empty($pending['matches']) || !is_array($pending['matches'])) {
            return null;
        }

        $raw = trim($message);
        if ($raw === '' || mb_strlen($raw) > 80) {
            return null;
        }

        // Nova pergunta completa sobre pessoa (status/ficha/departamento…) → nova busca, não refinar.
        if ($this->extractPersonQuery($message) !== null) {
            return null;
        }

        $m = mb_strtolower($raw);
        if (preg_match(
            '/\b(quantos|qtd|quantidade|headcount|agendar|reservar|salas?|relat[oó]rios?|desligad|inativos?|bloqueados?|ativos?\s+(na|em|por)|por\s+m[eê]s|por\s+departamento|status\s+(do|da|de)|ficha|nova\s+consulta)\b/u',
            $m
        )) {
            return null;
        }

        $token = $this->normalizePersonRefineToken($raw);

        return ['name' => 'lookup_person_refine', 'token' => $token];
    }

    /**
     * Remove prefixos de frase («status do», «departamento da») para casar nome na lista.
     */
    private function normalizePersonRefineToken(string $token): string
    {
        $token = trim($token);
        $token = preg_replace(
            '/^(status|departamento|depto|ficha|dados|informa[cç][oõ]es|perfil|bloqueio|sobre)(\s+(do|da|de))?\s+/iu',
            '',
            $token
        ) ?? $token;
        $token = preg_replace('/^(do|da|de|o|a)\s+/iu', '', $token) ?? $token;

        return trim($token);
    }

    /**
     * Contratações / admissões (não confundir com desligados).
     */
    private function mentionsHiring(string $normalizedMessage): bool
    {
        return (bool) preg_match(
            '/\b(contrata[cç][oõ]es|contrata[cç][aã]o|contratad[oa]s?|admiss[oõ]es|admiss[aã]o|admitid[oa]s?|nov[oa]s?\s+colaboradores?|novas?\s+admiss)\b/u',
            $normalizedMessage
        );
    }

    /**
     * Faixa etária no texto (fallback se a IA omitir o parâmetro).
     *
     * @return array{name: string, min_age?: int, max_age?: int, department?: string|null}|null
     */
    private function detectAgeIntent(string $normalizedMessage, string $message): ?array
    {
        if (preg_match('/anos?\s+de\s+empresa|tempo\s+de\s+empresa/u', $normalizedMessage)) {
            return null;
        }

        $bounds = $this->extractAgeBoundsFromMessage($message);
        if ($bounds === []) {
            return null;
        }

        return array_merge(
            ['name' => 'active_by_age', 'department' => $this->canonicalDepartment(null, $message)],
            $bounds
        );
    }

    /**
     * @return array{min_age?: int, max_age?: int}
     */
    private function extractAgeBoundsFromMessage(string $message): array
    {
        $n = mb_strtolower($message);
        $out = [];
        if (preg_match('/entre\s+(\d{1,2})\s+e\s+(\d{1,2})(\s+anos)?/u', $n, $mm)) {
            $a = (int) $mm[1];
            $b = (int) $mm[2];
            if ($a > $b) {
                [$a, $b] = [$b, $a];
            }
            $out['min_age'] = $a;
            $out['max_age'] = $b + 1;

            return $out;
        }
        if (preg_match('/(?:menos|menor(?:es)?|abaixo)\s+de\s+(\d{1,2})(\s+anos)?/u', $n, $mm)) {
            $out['max_age'] = (int) $mm[1];
        }
        if (preg_match('/(?:mais|maior(?:es)?|acima)\s+de\s+(\d{1,2})(\s+anos)?/u', $n, $mm)) {
            $out['min_age'] = (int) $mm[1];
        }
        if ($out === [] && preg_match('/\b(\d{1,2})\s+anos\b/u', $n, $mm)) {
            $nAge = (int) $mm[1];
            if ($nAge >= 16 && $nAge <= 90) {
                $out['min_age'] = $nAge;
            }
        }

        foreach (['min_age', 'max_age'] as $k) {
            if (isset($out[$k]) && ((int) $out[$k] < 16 || (int) $out[$k] > 91)) {
                unset($out[$k]);
            }
        }

        return $out;
    }

    private function canonicalDepartment(?string $raw, string $message): ?string
    {
        $names = $this->rh->listDepartmentNames();
        $fromText = ChatDepartmentMention::match($message, $names, self::DEPARTMENT_ALIASES);
        if ($fromText !== null) {
            return $fromText;
        }
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $fromRaw = ChatDepartmentMention::match($raw, $names, self::DEPARTMENT_ALIASES);
        if ($fromRaw !== null) {
            return $fromRaw;
        }
        $aliased = $this->resolveDepartmentAlias($raw);
        if ($aliased === null) {
            return null;
        }
        foreach ($names as $name) {
            if (ChatDepartmentMention::fold($name) === ChatDepartmentMention::fold($aliased)) {
                return $name;
            }
        }

        return $aliased;
    }

    private function mergeCombinedRhFilters(array $intent, string $message): array
    {
        $name = (string) ($intent['name'] ?? '');
        if ($name === 'lookup_person' || str_starts_with($name, 'rooms_') || str_starts_with($name, 'report_')) {
            return $intent;
        }

        $dept = $this->canonicalDepartment(
            isset($intent['department']) ? (string) $intent['department'] : null,
            $message
        );
        if ($dept !== null) {
            $intent['department'] = $dept;
        }

        if (!preg_match('/anos?\s+de\s+empresa|tempo\s+de\s+empresa/u', mb_strtolower($message))) {
            foreach ($this->extractAgeBoundsFromMessage($message) as $key => $value) {
                if (!isset($intent[$key])) {
                    $intent[$key] = $value;
                }
            }
        }

        if (
            in_array($name, ['active', 'by_department', 'unknown', 'active_list'], true)
            && (!empty($intent['min_age']) || !empty($intent['max_age']))
        ) {
            $intent['name'] = 'active_by_age';
        } elseif (
            in_array($name, ['active', 'by_department', 'unknown'], true)
            && $this->messageWantsPersonList($message)
            && !$this->messageWantsHeadcount($message)
        ) {
            $intent['name'] = 'active_list';
        }

        return $intent;
    }

    private function messageWantsPersonList(string $message): bool
    {
        return (bool) preg_match(
            '/\b(nomes?|lista|listar|quem\s+(s[aã]o|est[aá]|trabalha)|quais\s+(s[aã]o|os|as)|nominativ)\b/u',
            mb_strtolower($message)
        );
    }

    private function messageWantsHeadcount(string $message): bool
    {
        return (bool) preg_match(
            '/\b(quantos|quantas|qtd|quantidade|headcount)\b/u',
            mb_strtolower($message)
        );
    }

    private function formatAgeFilterLabel(?int $minAge, ?int $maxAge): string
    {
        if ($minAge !== null && $maxAge !== null) {
            return sprintf('com %d anos ou mais e menos de %d anos', $minAge, $maxAge);
        }
        if ($maxAge !== null) {
            return sprintf('com menos de %d anos', $maxAge);
        }
        if ($minAge !== null) {
            return sprintf('com %d anos ou mais', $minAge);
        }

        return '';
    }

    /**
     * Une {"filters":{...}} ao JSON de topo (a IA pode mandar os critérios nos dois formatos).
     *
     * @param array<string, mixed> $intentJson
     * @return array<string, mixed>
     */
    private function flattenLlmFilterBag(array $intentJson): array
    {
        $bag = $intentJson['filters'] ?? null;
        if (!is_array($bag)) {
            return $intentJson;
        }
        foreach ($bag as $key => $value) {
            if (!is_string($key) || $key === 'intent' || $key === 'filters') {
                continue;
            }
            if (!array_key_exists($key, $intentJson) || $intentJson[$key] === null || $intentJson[$key] === '') {
                $intentJson[$key] = $value;
            }
        }

        return $intentJson;
    }

    /**
     * @return array{name: string, month?: ?int, year?: ?int, department?: ?string}|null
     */
    private function detectHireIntent(string $normalizedMessage, string $message): ?array
    {
        if (!$this->mentionsHiring($normalizedMessage)) {
            return null;
        }

        $period = $this->extractPeriod($message);
        $yearOnly = $this->extractYearOnly($normalizedMessage);
        $dept = $this->extractDepartment($message);
        $dept = $this->resolveDepartmentAlias($dept);
        if ($period !== null) {
            $dept = null;
        }

        $wantsList = (bool) preg_match(
            '/\b(lista|listar|nomes|nominativa|quais\s+(s[aã]o|foram)|quem\s+(s[aã]o|foram)|mostrar\s+(os\s+)?nomes)\b/u',
            $normalizedMessage
        );

        if ($wantsList) {
            return [
                'name' => 'hired_list',
                'month' => $period['month'] ?? null,
                'year' => $period['year'] ?? $yearOnly,
                'department' => $dept,
            ];
        }

        if ($period !== null) {
            return [
                'name' => 'hired_in_period',
                'month' => $period['month'],
                'year' => $period['year'],
            ];
        }

        if ($yearOnly !== null) {
            return [
                'name' => 'hired_in_period',
                'month' => null,
                'year' => $yearOnly,
            ];
        }

        return ['name' => 'hired_total'];
    }

    /**
     * «lista» / «lista Produção» após um total de desligados.
     *
     * @return array{name: string, month?: ?int, year?: ?int, department?: ?string}|null
     */
    private function detectTerminatedListFollowUp(string $normalizedMessage, string $message): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        $last = $_SESSION['internal_chat_last_terminated'] ?? null;
        if (!is_array($last)) {
            return null;
        }

        $m = trim($normalizedMessage);
        if ($m === '') {
            return null;
        }

        // Frases completas «lista de desligados…» ficam no ramo principal.
        if (preg_match('/\bdesligad[oa]s?\b/u', $m)) {
            return null;
        }
        // Contratações/admissões nunca herdam o contexto de desligados.
        if ($this->mentionsHiring($m)) {
            return null;
        }

        // «2026» / «em 2026» após totais/lista de desligados → lista filtrada do ano.
        $yearOnly = $this->extractYearOnly($m);
        if (
            $yearOnly !== null
            && preg_match('/^(em\s+|de\s+|s[oó]\s+(os\s+(de\s+)?)?)?20\d{2}$/u', $m)
        ) {
            $lastTerm = (session_status() === PHP_SESSION_ACTIVE)
                ? ($_SESSION['internal_chat_last_terminated'] ?? null)
                : null;
            if (is_array($lastTerm)) {
                return [
                    'name' => 'terminated_list',
                    'month' => null,
                    'year' => $yearOnly,
                    'department' => isset($lastTerm['department']) && $lastTerm['department'] !== ''
                        ? (string) $lastTerm['department']
                        : null,
                ];
            }
        }

        $period = $this->extractPeriod($message);
        $dept = $this->extractTerminatedListDepartment($message);
        if ($dept === null) {
            $dept = $this->extractDepartment($message);
            $dept = $this->resolveDepartmentAlias($dept);
        }
        if ($period !== null) {
            $dept = null;
        }

        $isListCmd = (bool) preg_match(
            '/^(lista|listar|nomes|mostrar(\s+lista)?|quem)(\s+.*)?$/iu',
            $m
        ) || (bool) preg_match('/^(lista|listar)\s+/iu', $m);

        if (!$isListCmd) {
            return null;
        }

        return [
            'name' => 'terminated_list',
            'month' => $period['month'] ?? ($last['month'] ?? null),
            'year' => $period['year'] ?? ($yearOnly ?? ($last['year'] ?? null)),
            'department' => $dept ?? ($last['department'] ?? null),
        ];
    }

    /**
     * Extrai nome/username de perguntas sobre uma pessoa.
     */
    private function extractPersonQuery(string $message): ?string
    {
        $raw = trim(preg_replace('/[?!.]+$/u', '', trim($message)) ?? trim($message));
        if ($raw === '') {
            return null;
        }

        $lower = mb_strtolower($raw);
        // Agregados: não tratar como pessoa (exceto «quantos anos de empresa…»).
        if (preg_match('/\b(quantos|qtd|quantidade|headcount|por\s+departamento|por\s+m[eê]s|todos)\b/u', $lower)
            && !preg_match('/anos?\s+de\s+empresa|tempo\s+de\s+empresa/u', $lower)
        ) {
            return null;
        }

        $patterns = [
            '/anos?\s+de\s+empresa\s+(?:que\s+)?(?:o|a|do|da|de)\s+(.+?)(?:\s+possui|\s+tem)?$/iu',
            '/(?:quanto\s+tempo|h[aá]\s+quanto\s+tempo).{0,40}empresa.{0,20}(?:o|a|do|da|de)\s+(.+)$/iu',
            '/tempo\s+de\s+empresa\s+(?:do|da|de)\s+(.+)$/iu',
            '/(?:usu[aá]rio|colaborador|funcion[aá]rio)\s+(.+?)\s+est[aá]\s+bloquead/iu',
            '/(.+?)\s+est[aá]\s+bloquead/iu',
            '/qual\s+(?:o\s+)?status\s+(?:do|da|de\s+)?(.+)$/iu',
            '/status\s+(?:do|da|de\s+)?(.+)$/iu',
            '/(?:est[aá]\s+)?bloquead[oa]?\s+(?:o|a|do|da|de)\s+(.+)$/iu',
            '/qual\s+(?:o\s+)?departamentos?\s+(?:do|da|de\s+)?(.+)$/iu',
            '/departamentos?\s+(?:do|da|de\s+)?(.+)$/iu',
            '/(?:ficha|dados|informa[cç][oõ]es|perfil)\s+(?:do|da|de\s+)?(.+)$/iu',
            '/sobre\s+(?:o|a\s+)?(.+)$/iu',
            '/quem\s+[eé]\s+(.+)$/iu',
        ];

        $stop = [
            'usuario', 'usuário', 'usuarios', 'usuários', 'colaborador', 'colaboradores',
            'funcionario', 'funcionário', 'funcionarios', 'funcionários', 'alguem', 'alguém',
            'ele', 'ela', 'este', 'esta', 'esse', 'essa', 'nome', 'pessoa',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $raw, $mm)) {
                continue;
            }
            $name = $this->sanitizePersonQuery((string) ($mm[1] ?? ''));
            if ($name === null) {
                continue;
            }
            $nameKey = mb_strtolower($name);
            if (in_array($nameKey, $stop, true)) {
                continue;
            }
            if (preg_match('/^(quantos?|bloquead|desligad|inativos?|ativos?|departamentos?)/u', $nameKey)) {
                continue;
            }
            // Evitar capturar frases longas demais.
            if (mb_strlen($name) > 80 || substr_count($name, ' ') > 6) {
                continue;
            }

            return $name;
        }

        return null;
    }

    /**
     * Remove placeholders do LLM («Nome da pessoa X») e artigos.
     */
    private function sanitizePersonQuery(string $name): ?string
    {
        $name = trim($name);
        $name = preg_replace('/^(o|a|os|as|do|da|de|um|uma)\s+/iu', '', $name) ?? $name;
        $name = preg_replace(
            '/^(nome\s+(da\s+pessoa|do\s+usu[aá]rio|ou\s+username|completo)|pessoa|usu[aá]rio)\s+/iu',
            '',
            $name
        ) ?? $name;
        $name = trim($name, " \t\"'`");
        if (mb_strlen($name) < 2) {
            return null;
        }

        return $name;
    }

    /**
     * @param array{query: string, matches: list<array<string, mixed>>, match_count: int} $data
     * @return array{resposta: string, tool: string, data: mixed, provider: string}
     */
    private function buildLookupPersonResult(array $data): array
    {
        $query = (string) ($data['query'] ?? '');
        $matches = $data['matches'] ?? [];
        if (!is_array($matches) || $matches === []) {
            $isCodeLike = (bool) preg_match('/^\d{3,}$/u', $query)
                || (bool) preg_match('/^[a-z0-9][a-z0-9\-_\.\/]{2,40}$/iu', $query);
            $hasReport = session_status() === PHP_SESSION_ACTIVE
                && is_array($_SESSION['internal_chat_last_report'] ?? null)
                && (int) (($_SESSION['internal_chat_last_report']['report_id'] ?? 0)) > 0;

            if ($isCodeLike || $hasReport) {
                $reportName = $hasReport
                    ? (string) ($_SESSION['internal_chat_last_report']['name'] ?? 'relatório')
                    : '';
                $resposta = $hasReport
                    ? sprintf(
                        'Não encontrei «%s» no contexto do relatório «%s». Confira o código (item, parceiro, documento) ou abra o relatório completo.',
                        $query,
                        $reportName !== '' ? $reportName : 'atual'
                    )
                    : sprintf(
                        'Não encontrei «%s». Se for código de relatório, abra o relatório no chat e tente de novo; se for pessoa, use o nome.',
                        $query
                    );
            } else {
                $resposta = sprintf(
                    'Não encontrei colaborador com «%s». Tente nome completo, username, e-mail ou matrícula.',
                    $query
                );
            }

            return [
                'resposta' => $resposta,
                'tool' => 'rh.lookup_person',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if (count($matches) > 1) {
            $lines = [
                sprintf('Encontrei %d pessoas para «%s». Seja mais específico:', count($matches), $query),
                'Digite o departamento (ex.: TI), o username ou o número da lista:',
            ];
            foreach ($matches as $idx => $row) {
                $n = $idx + 1;
                $lines[] = sprintf(
                    '%d. %s (%s) — %s — %s',
                    $n,
                    $row['name'] ?? '',
                    ($row['username'] ?? '') !== '' ? $row['username'] : 'sem user',
                    $row['department'] ?? '',
                    !empty($row['blocked']) ? 'bloqueado' : 'não bloqueado'
                );
            }

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.lookup_person',
                'data' => array_merge($data, [
                    'match_count' => count($matches),
                    'matches' => $matches,
                ]),
                'provider' => 'local-rules',
            ];
        }

        return [
            'resposta' => $this->formatPersonCard($matches[0], (string) ($data['focus'] ?? 'card')),
            'tool' => 'rh.lookup_person',
            'data' => array_merge($data, [
                'match_count' => 1,
                'matches' => $matches,
            ]),
            'provider' => 'local-rules',
        ];
    }

    /**
     * @return array{resposta: string, tool: string, data: mixed, provider: string}
     */
    private function executePersonRefine(string $token): array
    {
        $pending = $_SESSION['internal_chat_person_candidates'] ?? null;
        $candidates = is_array($pending) ? ($pending['matches'] ?? []) : [];
        if (!is_array($candidates) || $candidates === []) {
            return [
                'resposta' => 'Não há uma lista anterior de pessoas para refinar. Pergunte de novo, ex.: «departamento do Rafael».',
                'tool' => 'rh.lookup_person',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        $filtered = $this->filterPersonCandidates($candidates, $token);
        if ($filtered === []) {
            return [
                'resposta' => sprintf(
                    'Ninguém na lista anterior combina com «%s». Digite o departamento, o username ou o número (1, 2…).',
                    $token
                ),
                'tool' => 'rh.lookup_person',
                'data' => [
                    'query' => (string) ($pending['query'] ?? ''),
                    'matches' => $candidates,
                    'match_count' => count($candidates),
                    'refine_token' => $token,
                    'preserve_candidates' => true,
                ],
                'provider' => 'local-rules',
            ];
        }

        if (count($filtered) === 1) {
            $id = (int) ($filtered[0]['id'] ?? 0);
            $full = $this->rh->lookupPerson(
                (string) (($filtered[0]['username'] ?? '') !== ''
                    ? $filtered[0]['username']
                    : ($filtered[0]['name'] ?? ''))
            );
            // Prefer exact id if multiple username collisions
            if ($id > 0 && !empty($full['matches'])) {
                $exact = array_values(array_filter(
                    $full['matches'],
                    static fn(array $row): bool => (int) ($row['id'] ?? 0) === $id
                ));
                if ($exact !== []) {
                    $full['matches'] = $exact;
                    $full['match_count'] = 1;
                    $full['query'] = (string) ($exact[0]['name'] ?? $full['query']);
                }
            }

            $result = $this->buildLookupPersonResult($full);
            if (is_array($result['data'] ?? null)) {
                $result['data']['preserve_candidates'] = true;
                $result['data']['refine_token'] = $token;
            }

            return $result;
        }

        $query = (string) ($pending['query'] ?? '');
        $fullMatches = [];
        foreach ($filtered as $cand) {
            $look = $this->rh->lookupPerson(
                (string) (($cand['username'] ?? '') !== '' ? $cand['username'] : ($cand['name'] ?? ''))
            );
            $id = (int) ($cand['id'] ?? 0);
            foreach ($look['matches'] as $row) {
                if ($id > 0 && (int) ($row['id'] ?? 0) === $id) {
                    $fullMatches[] = $row;
                    break;
                }
            }
        }
        if ($fullMatches === []) {
            $fullMatches = $filtered;
        }

        $result = $this->buildLookupPersonResult([
            'query' => $query !== '' ? ($query . ' / ' . $token) : $token,
            'matches' => $fullMatches,
            'match_count' => count($fullMatches),
        ]);
        if (is_array($result['data'] ?? null)) {
            $result['data']['preserve_candidates'] = true;
            $result['data']['refine_token'] = $token;
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $candidates
     * @return list<array<string, mixed>>
     */
    private function filterPersonCandidates(array $candidates, string $token): array
    {
        $token = $this->normalizePersonRefineToken($token);
        if ($token === '') {
            return [];
        }

        if (preg_match('/^\d+$/', $token)) {
            $idx = (int) $token - 1;
            if ($idx >= 0 && isset($candidates[$idx])) {
                return [$candidates[$idx]];
            }

            return [];
        }

        $tokenKey = mb_strtolower($token);
        $deptAlias = $this->resolveDepartmentAlias($token);
        $deptKey = $deptAlias !== null ? mb_strtolower($deptAlias) : $tokenKey;

        $byUsername = [];
        $byDept = [];
        $byName = [];
        $short = mb_strlen($tokenKey) <= 3;
        foreach ($candidates as $row) {
            $username = mb_strtolower((string) ($row['username'] ?? ''));
            $dept = mb_strtolower((string) ($row['department'] ?? ''));
            $name = mb_strtolower((string) ($row['name'] ?? ''));

            if ($username !== '') {
                if ($username === $tokenKey || (!$short && str_contains($username, $tokenKey))) {
                    $byUsername[] = $row;
                }
            }
            if ($dept !== '') {
                $deptHit = $dept === $deptKey
                    || (!$short && (str_contains($dept, $deptKey) || str_contains($deptKey, $dept)));
                // Tokens curtos (TI, RH): só igualdade / alias canônico.
                if ($short) {
                    $deptHit = $dept === $deptKey;
                }
                if ($deptHit) {
                    $byDept[] = $row;
                }
            }
            if (!$short && $name !== '' && str_contains($name, $tokenKey)) {
                $byName[] = $row;
            }
        }

        if ($byUsername !== []) {
            return $byUsername;
        }
        if ($byDept !== []) {
            return $byDept;
        }

        return $byName;
    }

    /**
     * @param array<string, mixed> $p
     */
    private function formatPersonCard(array $p, string $focus = 'card'): string
    {
        $name = (string) ($p['name'] ?? '');
        $blockedLabel = !empty($p['blocked']) ? 'sim' : 'não';
        $termLabel = !empty($p['termination_date'])
            ? ('desligado em ' . $this->formatBrDate((string) $p['termination_date']))
            : 'sem desligamento';
        $admLabel = !empty($p['admission_date'])
            ? $this->formatBrDate((string) $p['admission_date'])
            : 'não informada';
        $age = isset($p['age']) && $p['age'] !== null && $p['age'] !== '' ? (int) $p['age'] : null;
        $birthLabel = !empty($p['birth_date']) ? $this->formatBrDate((string) $p['birth_date']) : null;
        $ageBullet = $age !== null
            ? ($age . ' anos' . ($birthLabel !== null ? ' (nasc. ' . $birthLabel . ')' : ''))
            : 'não informada no cadastro';

        $lead = null;
        if ($focus === 'age') {
            $lead = $age !== null
                ? sprintf('%s tem %d anos%s.', $name, $age, $birthLabel !== null ? ' (nasc. ' . $birthLabel . ')' : '')
                : sprintf('%s não tem data de nascimento no cadastro.', $name);
        } elseif ($focus === 'tenure') {
            $lead = sprintf('%s está há %s na empresa.', $name, (string) ($p['tenure_label'] ?? '—'));
        } elseif ($focus === 'department') {
            $lead = sprintf('%s está no departamento %s.', $name, (string) ($p['department'] ?? '—'));
        } elseif ($focus === 'blocked') {
            $lead = sprintf('%s %s bloqueado.', $name, !empty($p['blocked']) ? 'está' : 'não está');
        } elseif ($focus === 'status') {
            $lead = sprintf('%s está com status %s.', $name, (string) ($p['status'] ?? '—'));
        } elseif ($focus === 'position') {
            $lead = sprintf('%s: cargo %s.', $name, (string) ($p['position'] ?? '—'));
        }

        $card = sprintf(
            "%s (@%s)\n"
            . "• Departamento: %s\n"
            . "• Cargo: %s\n"
            . "• Idade: %s\n"
            . "• Status: %s\n"
            . "• Bloqueado: %s\n"
            . "• Admissão: %s\n"
            . "• Tempo de empresa: %s\n"
            . "• Desligamento: %s",
            $name,
            ($p['username'] ?? '') !== '' ? $p['username'] : '—',
            (string) ($p['department'] ?? '—'),
            (string) ($p['position'] ?? '—'),
            $ageBullet,
            ($p['status'] ?? '') !== '' ? $p['status'] : '—',
            $blockedLabel,
            $admLabel,
            (string) ($p['tenure_label'] ?? '—'),
            $termLabel
        );

        return $lead !== null ? $lead . "\n\n" . $card : $card;
    }

    private function resolvePersonFocus(string $message, ?string $fromLlm): string
    {
        $allowed = ['age', 'tenure', 'status', 'department', 'blocked', 'position', 'card'];
        $fromLlm = $fromLlm !== null ? mb_strtolower(trim($fromLlm)) : '';
        if (in_array($fromLlm, $allowed, true) && $fromLlm !== 'card') {
            return $fromLlm;
        }

        $m = mb_strtolower($message);
        if (preg_match('/anos?\s+de\s+empresa|tempo\s+de\s+empresa/u', $m)) {
            return 'tenure';
        }
        if (preg_match('/\bidade\b|quantos\s+anos\s+tem|data\s+de\s+nascimento|quando\s+nasceu/u', $m)) {
            return 'age';
        }
        if (preg_match('/bloquead/u', $m)) {
            return 'blocked';
        }
        if (preg_match('/\bcargo\b|\bfun[cç][aã]o\b/u', $m)) {
            return 'position';
        }
        if (preg_match('/\bdepartamento\b|\bsetor\b/u', $m)) {
            return 'department';
        }
        if (preg_match('/\bstatus\b|\bsitua[cç][aã]o\b/u', $m)) {
            return 'status';
        }

        return 'card';
    }

    private function formatBrDate(string $ymd): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $mm)) {
            return $ymd;
        }

        return $mm[3] . '/' . $mm[2] . '/' . $mm[1];
    }

    private function formatBrDateOrDash(string $ymd): string
    {
        $ymd = trim($ymd);
        if ($ymd === '') {
            return '—';
        }
        $br = $this->formatBrDate($ymd);

        return $br !== '' ? $br : '—';
    }

    /**
     * @return array{month:int, year:int}|null
     */
    private function extractPeriod(string $message): ?array
    {
        $m = mb_strtolower($message);
        $m = strtr($m, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
        ]);

        if (preg_match('/\b(esse|este)\s+mes(\s+atual)?\b/u', $m) || preg_match('/\bmes\s+atual\b/u', $m)) {
            return ['month' => (int) date('n'), 'year' => (int) date('Y')];
        }
        if (preg_match('/\bmes\s+passado\b/u', $m)) {
            $dt = new \DateTimeImmutable('first day of last month');

            return ['month' => (int) $dt->format('n'), 'year' => (int) $dt->format('Y')];
        }

        // 10/08/2026
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})\b/', $m, $mm)) {
            $month = (int) $mm[2];
            $year = $this->expandTwoDigitYear((int) $mm[3]);
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        // 08/2026, 8/2026, 08-2026
        if (preg_match('/\b(\d{1,2})[\/\-](\d{4})\b/', $m, $mm)) {
            $month = (int) $mm[1];
            $year = (int) $mm[2];
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        // 2026-08
        if (preg_match('/\b(20\d{2})[\/\-](\d{1,2})\b/', $m, $mm)) {
            $year = (int) $mm[1];
            $month = (int) $mm[2];
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        // 08/26 (mm/aa)
        if (preg_match('/\b(\d{1,2})[\/\-](\d{2})\b/', $m, $mm)) {
            $month = (int) $mm[1];
            $year = $this->expandTwoDigitYear((int) $mm[2]);
            if ($month >= 1 && $month <= 12) {
                return ['month' => $month, 'year' => $year];
            }
        }

        $monthNames = implode('|', array_map(static fn ($k) => preg_quote($k, '/'), array_keys(self::MONTHS)));
        if (preg_match('/\b(?:mes\s+de\s+)?(' . $monthNames . ')(?:\s*\/\s*|\s+de\s+|\s+)?(\d{2,4})?\b/u', $m, $mm)) {
            $monthKey = $mm[1];
            $month = self::MONTHS[$monthKey] ?? null;
            if ($month === null) {
                return null;
            }
            $year = isset($mm[2]) && $mm[2] !== ''
                ? $this->expandTwoDigitYear((int) $mm[2])
                : (int) date('Y');

            return ['month' => $month, 'year' => $year];
        }

        return null;
    }

    private function expandTwoDigitYear(int $year): int
    {
        if ($year < 100) {
            return $year >= 70 ? 1900 + $year : 2000 + $year;
        }

        return $year;
    }

    private function stripPeriodFromMessage(string $message): string
    {
        $monthNames = implode('|', array_map(static fn ($k) => preg_quote($k, '/'), array_keys(self::MONTHS)));
        $s = $message;
        $s = preg_replace('/\b\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\b20\d{2}[\/\-]\d{1,2}\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\b\d{1,2}[\/\-]\d{2,4}\b/u', ' ', $s) ?? $s;
        $s = preg_replace('/\b(?:mes\s+de\s+|mês\s+de\s+)?(' . $monthNames . ')(?:\s*\/\s*|\s+de\s+|\s+)?(\d{2,4})?\b/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\b(esse|este)\s+m[eê]s(\s+atual)?\b/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\bm[eê]s\s+(atual|passado)\b/iu', ' ', $s) ?? $s;
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
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
     * Departamento no fim de frases tipo «lista desligados Produção».
     */
    private function extractTerminatedListDepartment(string $message): ?string
    {
        $m = trim($message);
        $m = preg_replace(
            '/^(lista|listar)\s+(de\s+)?(usu[aá]rios?\s+|colaboradores?\s+)?desligad[oa]s?\s+/iu',
            '',
            $m
        ) ?? $m;
        $m = preg_replace('/^desligad[oa]s?\s+(da|do|de|em|na|no)\s+/iu', '', $m) ?? $m;
        $m = preg_replace('/^(lista|listar)\s+/iu', '', $m) ?? $m;
        $m = trim($m, " \t\"'`");
        if ($m === '' || preg_match('/^(em\s+)?20\d{2}$/u', mb_strtolower($m))) {
            return null;
        }
        if ($this->extractPeriod($m) !== null || $this->extractPeriod('em ' . $m) !== null) {
            return null;
        }

        $alias = $this->resolveDepartmentAlias($m);
        $candidates = array_filter([$m, $alias], static fn($v) => is_string($v) && $v !== '');
        foreach ($this->rh->listDepartmentNames() as $name) {
            foreach ($candidates as $cand) {
                if (mb_strtolower($name) === mb_strtolower($cand)) {
                    return $name;
                }
            }
        }

        // Último token (ex.: «lista de desligados Produção»)
        $parts = preg_split('/\s+/u', $m) ?: [];
        $last = (string) end($parts);
        if ($last !== '') {
            $lastAlias = $this->resolveDepartmentAlias($last);
            foreach ($this->rh->listDepartmentNames() as $name) {
                if (mb_strtolower($name) === mb_strtolower($last)
                    || ($lastAlias !== null && mb_strtolower($name) === mb_strtolower($lastAlias))
                ) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * @param array{name: string, department?: string|null} $intent
     * @return array{resposta: string, tool: string, data: mixed, provider: string}
     */
    private function executeIntent(array $intent, string $message): array
    {
        if ($intent['name'] === 'rooms_wizard_start') {
            return $this->roomsWizard->start($this->userId);
        }

        $intent = $this->mergeCombinedRhFilters($intent, $message);

        if ($intent['name'] === 'rooms_list') {
            $run = $this->rooms->listRooms(null);
            $rooms = $run['data']['rooms'] ?? [];
            if (is_array($rooms) && $rooms !== []) {
                $this->roomsWizard->armPickRoomFromList($this->userId, $rooms);
            }

            return [
                'resposta' => (string) ($run['resposta'] ?? ''),
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'rooms_agenda') {
            $run = $this->rooms->agenda(
                isset($intent['room']) ? (string) $intent['room'] : null,
                (string) ($intent['date'] ?? date('Y-m-d'))
            );

            return [
                'resposta' => $run['resposta'],
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'rooms_my') {
            $run = $this->rooms->myBookings($this->userId);

            return [
                'resposta' => $run['resposta'],
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'rooms_reserve_help') {
            return [
                'resposta' => "Para reservar pelo chat, use:\n"
                    . "• reservar sala [nome] amanhã 14:00 às 15:00 reunião [título]\n"
                    . "• reservar sala [nome] 10/08/2026 09:00 às 10:30 reunião Alinhamento\n"
                    . "Antes: «salas» e «agenda da sala [nome] hoje». Também dá para reservar na tela book-room.",
                'tool' => 'rooms.reserve',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'rooms_reserve') {
            $run = $this->rooms->reserve(
                $this->userId,
                isset($intent['room']) ? (string) $intent['room'] : null,
                (string) ($intent['start'] ?? ''),
                (string) ($intent['end'] ?? ''),
                (string) ($intent['title'] ?? 'Reunião'),
                null
            );

            return [
                'resposta' => $run['resposta'],
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'rooms_cancel') {
            $run = $this->rooms->cancel(
                $this->userId,
                (int) ($intent['booking_id'] ?? 0),
                'Cancelado via Tiarajuzinho'
            );

            return [
                'resposta' => $run['resposta'],
                'tool' => $run['tool'],
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

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
            $period = $this->extractPeriod($message);
            $reportId = !empty($intent['report_id']) ? (int) $intent['report_id'] : 0;
            if ($period !== null && $reportId > 0) {
                $run = $this->reports->runByIdFilteredByMonth(
                    $this->userId,
                    $reportId,
                    (int) $period['month'],
                    (int) $period['year']
                );

                return [
                    'resposta' => (string) ($run['resposta'] ?? ''),
                    'tool' => $run['tool'] ?? 'report.run',
                    'data' => $run['data'] ?? null,
                    'provider' => 'local-rules',
                ];
            }
            $codeInMsg = $this->extractReportCodeFromMessage($message);
            if ($codeInMsg !== null) {
                if ($reportId < 1) {
                    $hint = $this->stripReportCodeFromMessage($message, $codeInMsg);
                    $matched = $this->reports->resolveReport(
                        $this->userId,
                        $hint !== '' ? $hint : (string) ($intent['query'] ?? $message)
                    );
                    $reportId = $matched !== null ? (int) $matched['id'] : 0;
                }
                if ($reportId > 0) {
                    $run = $this->reports->runByIdFilteredByCode($this->userId, $reportId, $codeInMsg);

                    return [
                        'resposta' => (string) ($run['resposta'] ?? ''),
                        'tool' => $run['tool'] ?? 'report.run',
                        'data' => $run['data'] ?? null,
                        'provider' => 'local-rules',
                    ];
                }
            }
            if ($reportId > 0) {
                $run = $this->reports->runById($this->userId, $reportId);
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
            $notTerm = $this->rh->countBlockedNotTerminated();
            $resposta = sprintf(
                'Há %d usuário(s) bloqueado(s) no Portal. Destes, %d têm tentativas de login registradas. '
                . 'Bloqueados sem data de desligamento: %d.',
                $data['total'],
                $data['with_attempts'],
                $notTerm['total']
            );

            return [
                'resposta' => $resposta,
                'tool' => 'rh.count_blocked',
                'data' => array_merge($data, ['blocked_not_terminated' => $notTerm['total']]),
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'blocked_not_terminated') {
            $data = $this->rh->countBlockedNotTerminated();
            $lines = [
                sprintf(
                    'Há %d usuário(s) bloqueado(s) sem data de desligamento (ainda constam sem desligamento no cadastro).',
                    $data['total']
                ),
            ];
            if ($data['by_department'] !== []) {
                $lines[] = '';
                $lines[] = 'Por departamento:';
                foreach ($data['by_department'] as $row) {
                    $lines[] = "• {$row['departamento']}: {$row['total']}";
                }
                $data['visualization_type'] = 'bar_chart';
                $data['chart'] = ChatDynamicReportService::chartFromByDepartment(
                    $data['by_department'],
                    'Bloqueados sem desligamento'
                );
            }

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_blocked_not_terminated',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'suggest_invalid') {
            $max = (int) ($intent['max'] ?? 0);
            $resposta = $max > 0
                ? sprintf('Opção inválida. Digite um número de 1 a %d.', $max)
                : 'Opção inválida. Digite o número da sugestão.';

            return [
                'resposta' => $resposta,
                'tool' => 'chat.suggest',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'clear_context') {
            return [
                'resposta' => 'Contexto da conversa limpo (pessoas e último relatório). Pode perguntar de novo.',
                'tool' => 'chat.clear_context',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'report_month_filter') {
            $reportId = (int) ($intent['report_id'] ?? 0);
            if ($reportId < 1) {
                $hint = $this->stripPeriodFromMessage($message);
                $matched = $this->reports->resolveReport(
                    $this->userId,
                    $hint !== '' ? $hint : (string) ($intent['query'] ?? $message),
                    true
                );
                $reportId = $matched !== null ? (int) $matched['id'] : 0;
            }
            if ($reportId < 1) {
                $last = $_SESSION['internal_chat_last_report'] ?? null;
                $reportId = is_array($last) ? (int) ($last['report_id'] ?? 0) : 0;
            }
            $run = $this->reports->runByIdFilteredByMonth(
                $this->userId,
                $reportId,
                (int) ($intent['month'] ?? 0),
                (int) ($intent['year'] ?? 0)
            );

            return [
                'resposta' => (string) ($run['resposta'] ?? ''),
                'tool' => $run['tool'] ?? 'report.run',
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'report_code_filter') {
            $reportId = (int) ($intent['report_id'] ?? 0);
            $code = trim((string) ($intent['code'] ?? $message));
            if ($reportId < 1) {
                $last = $_SESSION['internal_chat_last_report'] ?? null;
                $reportId = is_array($last) ? (int) ($last['report_id'] ?? 0) : 0;
            }
            $run = $this->reports->runByIdFilteredByCode($this->userId, $reportId, $code);

            return [
                'resposta' => (string) ($run['resposta'] ?? ''),
                'tool' => $run['tool'] ?? 'report.run',
                'data' => $run['data'] ?? null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'lookup_person_refine') {
            return $this->executePersonRefine((string) ($intent['token'] ?? $message));
        }

        if ($intent['name'] === 'lookup_person') {
            $query = trim((string) ($intent['query'] ?? ''));
            $fromMessage = $this->extractPersonQuery($message);
            if ($fromMessage !== null) {
                $query = $fromMessage;
            } else {
                $cleaned = $this->sanitizePersonQuery($query);
                $query = $cleaned ?? $query;
            }

            // Código após relatório (itens/parceiros/vendas) → filtra o relatório, não RH.
            $reportFollowUp = $this->detectReportCodeFollowUp($query !== '' ? $query : $message, mb_strtolower($query !== '' ? $query : $message));
            if ($reportFollowUp !== null) {
                $run = $this->reports->runByIdFilteredByCode(
                    $this->userId,
                    (int) $reportFollowUp['report_id'],
                    (string) $reportFollowUp['code']
                );

                return [
                    'resposta' => (string) ($run['resposta'] ?? ''),
                    'tool' => $run['tool'] ?? 'report.run',
                    'data' => $run['data'] ?? null,
                    'provider' => 'local-rules',
                ];
            }

            $data = $this->rh->lookupPerson($query);
            $data['focus'] = $this->resolvePersonFocus($message, isset($intent['focus']) ? (string) $intent['focus'] : null);

            return $this->buildLookupPersonResult($data);
        }

        if ($intent['name'] === 'hired_list') {
            $month = isset($intent['month']) ? (int) $intent['month'] : null;
            $year = isset($intent['year']) ? (int) $intent['year'] : null;
            $department = isset($intent['department']) ? $this->resolveDepartmentAlias((string) $intent['department']) : null;
            if ($month !== null && $month < 1) {
                $month = null;
            }
            if ($year !== null && $year < 1) {
                $year = null;
            }
            if ($department === '') {
                $department = null;
            }
            $data = $this->rh->listHired($month, $year, $department, 150);
            $periodLabel = 'todos os períodos';
            $monthNames = [
                1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
            ];
            if ($month && $year) {
                $periodLabel = ($monthNames[$month] ?? (string) $month) . '/' . $year;
            } elseif ($year) {
                $periodLabel = (string) $year;
            }
            $deptLabel = $department ? (' · ' . $department) : '';
            $shown = count($data['rows']);
            $lines = [
                sprintf('Lista de contratações (%s%s): %d no total.', $periodLabel, $deptLabel, $data['total']),
            ];
            if ($shown < 1) {
                $lines[] = 'Nenhum registro neste filtro.';
            } else {
                $lines[] = sprintf(
                    'Tabela com %d nome(s). Use Baixar Excel/CSV para exportar%s.',
                    $shown,
                    !empty($data['truncated']) ? ' (lista truncada; refine o filtro)' : ''
                );
            }

            $displayRows = [];
            foreach ($data['rows'] as $row) {
                $displayRows[] = [
                    'Nome' => (string) ($row['nome'] ?? ''),
                    'User' => (string) ($row['username'] ?? ''),
                    'Depto' => (string) ($row['departamento'] ?? ''),
                    'Cargo' => (string) ($row['cargo'] ?? ''),
                    'Admissão' => $this->formatBrDateOrDash((string) ($row['admissao'] ?? '')),
                ];
            }
            $data['rows'] = $displayRows;
            $data['name'] = 'Contratações (' . $periodLabel . $deptLabel . ')';
            $data['ui'] = ['compact_table' => true];

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.list_hired',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'hired_in_period' || $intent['name'] === 'hired_total') {
            $month = isset($intent['month']) ? (int) $intent['month'] : null;
            $year = isset($intent['year']) ? (int) $intent['year'] : null;
            if ($month !== null && $month < 1) {
                $month = null;
            }
            if ($year !== null && $year < 1) {
                $year = null;
            }
            $data = $this->rh->countHired($month, $year);
            $periodLabel = 'todos os períodos';
            $monthNames = [
                1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
            ];
            if ($month && $year) {
                $periodLabel = ($monthNames[$month] ?? (string) $month) . '/' . $year;
            } elseif ($year) {
                $periodLabel = (string) $year;
            }
            $lines = [
                sprintf('Contratações (%s): %d no total (pela data de admissão).', $periodLabel, $data['total']),
                'Para nomes: «lista de contratações em junho/2026» ou «lista de admissões 2025».',
            ];
            if ($data['by_department'] !== []) {
                $lines[] = '';
                $lines[] = 'Por departamento:';
                foreach (array_slice($data['by_department'], 0, 10) as $row) {
                    $lines[] = "• {$row['departamento']}: {$row['total']}";
                }
            }

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_hired',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'terminated_list') {
            $month = isset($intent['month']) ? (int) $intent['month'] : null;
            $year = isset($intent['year']) ? (int) $intent['year'] : null;
            $department = isset($intent['department']) ? $this->resolveDepartmentAlias((string) $intent['department']) : null;
            if ($month !== null && $month < 1) {
                $month = null;
            }
            if ($year !== null && $year < 1) {
                $year = null;
            }
            if ($department === '') {
                $department = null;
            }
            $data = $this->rh->listTerminated(
                $month,
                $year,
                $department,
                150
            );
            $periodLabel = 'todos os períodos';
            $monthNames = [
                1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
            ];
            if ($month && $year) {
                $periodLabel = ($monthNames[$month] ?? (string) $month) . '/' . $year;
            } elseif ($year) {
                $periodLabel = (string) $year;
            }
            $deptLabel = $department ? (' · ' . $department) : '';
            $shown = count($data['rows']);
            $lines = [
                sprintf('Lista de desligados (%s%s): %d no total.', $periodLabel, $deptLabel, $data['total']),
            ];
            if ($shown < 1) {
                $lines[] = 'Nenhum registro neste filtro.';
            } else {
                $lines[] = sprintf(
                    'Tabela com %d nome(s). Use Baixar Excel/CSV para exportar%s.',
                    $shown,
                    !empty($data['truncated']) ? ' (lista truncada; refine o filtro)' : ''
                );
            }

            // Linhas amigáveis para a UI (sem duplicar em bullets).
            $displayRows = [];
            foreach ($data['rows'] as $row) {
                $displayRows[] = [
                    'Nome' => (string) ($row['nome'] ?? ''),
                    'User' => (string) ($row['username'] ?? ''),
                    'Depto' => (string) ($row['departamento'] ?? ''),
                    'Cargo' => (string) ($row['cargo'] ?? ''),
                    'Desligamento' => $this->formatBrDateOrDash((string) ($row['desligamento'] ?? '')),
                ];
            }
            $data['rows'] = $displayRows;
            $data['name'] = 'Desligados (' . $periodLabel . $deptLabel . ')';
            $data['ui'] = ['compact_table' => true];

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.list_terminated',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'terminated_total') {
            $data = $this->rh->countTerminated(null, null);
            $lines = [
                sprintf('Há %d colaborador(es) com data de desligamento cadastrada.', $data['total']),
                'Para ver nomes: «lista de desligados». Por período: «lista de desligados em janeiro» / «lista desligados 2025». Por depto: «lista desligados Produção».',
                'Ou, depois deste total, digite «lista» e em seguida o ano («2026») ou o departamento.',
            ];
            if ($data['by_department'] !== []) {
                $lines[] = '';
                $lines[] = 'Por departamento (todos os períodos):';
                foreach (array_slice($data['by_department'], 0, 10) as $row) {
                    $lines[] = "• {$row['departamento']}: {$row['total']}";
                }
            }

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_terminated',
                'data' => $data,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'terminated_by_department') {
            $month = isset($intent['month']) ? (int) $intent['month'] : null;
            $year = isset($intent['year']) ? (int) $intent['year'] : null;
            $data = $this->rh->countTerminated($month > 0 ? $month : null, $year > 0 ? $year : null);
            $periodLabel = 'todos os períodos';
            if ($month && $year) {
                $monthNames = [
                    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril',
                    5 => 'maio', 6 => 'junho', 7 => 'julho', 8 => 'agosto',
                    9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
                ];
                $periodLabel = ($monthNames[$month] ?? (string) $month) . '/' . $year;
            } elseif ($year) {
                $periodLabel = (string) $year;
            }
            $lines = [
                sprintf('Desligados (%s): %d no total.', $periodLabel, $data['total']),
            ];
            if ($data['by_department'] === []) {
                $lines[] = 'Nenhum registro por departamento neste filtro.';
            } else {
                $lines[] = '';
                $lines[] = 'Por departamento:';
                foreach ($data['by_department'] as $row) {
                    $lines[] = "• {$row['departamento']}: {$row['total']}";
                }
                $data['visualization_type'] = 'bar_chart';
                $data['chart'] = ChatDynamicReportService::chartFromByDepartment(
                    $data['by_department'],
                    'Desligados por departamento (' . $periodLabel . ')'
                );
            }

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_terminated_by_department',
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

        if ($intent['name'] === 'clarify_by_month') {
            return [
                'resposta' => "Para ver desligamentos por mês, diga «inativos» e depois «por mês», "
                    . "ou «inativos por mês». Para um mês específico: «inativos em janeiro».",
                'tool' => 'rh.clarify_by_month',
                'data' => null,
                'provider' => 'local-rules',
            ];
        }

        if ($intent['name'] === 'terminated_by_month') {
            $year = (int) ($intent['year'] ?? (int) date('Y'));
            $data = $this->rh->countTerminatedByMonth($year);
            $inactiveNow = $this->rh->countInactiveEmployees(null);
            $clarify = !empty($intent['clarify_inactive']);

            $note = sprintf(
                'Obs.: «Inativos» = estoque atual no Portal (status Inativo ou com data de desligamento): %d agora. '
                . 'Com ano/mês, mostro desligamentos pela data de desligamento naquele período — são contagens diferentes.',
                $inactiveNow['total']
            );

            if ($data['by_month'] === []) {
                $emptyLines = [
                    sprintf('Não há desligamentos registrados em %d (pela data de desligamento).', $year),
                ];
                if ($clarify) {
                    $emptyLines[] = '';
                    $emptyLines[] = $note;
                    $emptyLines[] = 'Para o estoque atual, diga só «inativos».';
                }

                return [
                    'resposta' => implode("\n", $emptyLines),
                    'tool' => 'rh.count_terminated_by_month',
                    'data' => array_merge($data, ['inactive_total_now' => $inactiveNow['total']]),
                    'provider' => 'local-rules',
                ];
            }
            $lines = [
                sprintf('Desligamentos em %d (pela data de desligamento): %d no total.', $year, $data['total']),
            ];
            if ($clarify) {
                $lines[] = '';
                $lines[] = $note;
            }
            $lines[] = '';
            $lines[] = 'Meses com desligamento:';
            $series = [];
            foreach ($data['by_month'] as $row) {
                $lines[] = "• {$row['rotulo']}: {$row['total']}";
                $series[] = [
                    'departamento' => (string) $row['rotulo'],
                    'total' => (int) $row['total'],
                ];
            }
            $data['inactive_total_now'] = $inactiveNow['total'];
            $data['visualization_type'] = 'bar_chart';
            $data['chart'] = ChatDynamicReportService::chartFromByDepartment(
                $series,
                'Desligamentos por mês (' . $year . ')'
            );

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.count_terminated_by_month',
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

        if ($intent['name'] === 'active_list') {
            $intent = $this->mergeCombinedRhFilters($intent, $message);
            if (($intent['name'] ?? '') === 'active_by_age') {
                // cai no bloco seguinte após reatribuir — trata abaixo
            } else {
                $department = $this->canonicalDepartment(
                    isset($intent['department']) ? (string) $intent['department'] : null,
                    $message
                );
                $data = $this->rh->listActive($department, 200);
                $deptLabel = $department ? (' da ' . $department) : '';
                $shown = count($data['rows']);
                $lines = [
                    sprintf('Usuários ativos%s: %d.', $deptLabel, $data['total']),
                ];
                if ($shown < 1) {
                    $lines[] = 'Nenhum cadastro atende ao filtro.';
                } else {
                    $lines[] = sprintf(
                        'Tabela com %d nome(s). Use Baixar Excel/CSV para exportar%s.',
                        $shown,
                        !empty($data['truncated']) ? ' (lista truncada; refine o filtro)' : ''
                    );
                }
                $displayRows = [];
                foreach ($data['rows'] as $row) {
                    $displayRows[] = [
                        'Nome' => (string) ($row['nome'] ?? ''),
                        'Depto' => (string) ($row['departamento'] ?? ''),
                        'Cargo' => (string) ($row['cargo'] ?? ''),
                    ];
                }
                $data['rows'] = $displayRows;
                $data['name'] = 'Ativos' . $deptLabel;
                $data['ui'] = ['compact_table' => true];

                return [
                    'resposta' => implode("\n", $lines),
                    'tool' => 'rh.list_active',
                    'data' => $data,
                    'provider' => 'local-rules',
                ];
            }
        }

        if ($intent['name'] === 'active_by_age') {
            $intent = $this->mergeCombinedRhFilters($intent, $message);
            $minAge = isset($intent['min_age']) ? (int) $intent['min_age'] : null;
            $maxAge = isset($intent['max_age']) ? (int) $intent['max_age'] : null;
            if ($minAge !== null && ($minAge < 16 || $minAge > 90)) {
                $minAge = null;
            }
            if ($maxAge !== null && ($maxAge < 17 || $maxAge > 91)) {
                $maxAge = null;
            }
            if ($minAge === null && $maxAge === null) {
                return [
                    'resposta' => 'Informe a faixa de idade (ex.: maiores de 30, menores de 40, entre 25 e 40).',
                    'tool' => 'rh.list_active_by_age',
                    'data' => null,
                    'provider' => 'local-rules',
                ];
            }
            $department = $this->canonicalDepartment(
                isset($intent['department']) ? (string) $intent['department'] : null,
                $message
            );
            $data = $this->rh->listActiveByAge($minAge, $maxAge, $department, 200);
            $deptLabel = $department ? (' da ' . $department) : '';
            $ageLabel = $this->formatAgeFilterLabel($minAge, $maxAge);
            $shown = count($data['rows']);
            $lines = [
                sprintf('Usuários ativos%s %s: %d.', $deptLabel, $ageLabel, $data['total']),
            ];
            if ($shown < 1) {
                $lines[] = 'Nenhum cadastro com data de nascimento atende ao filtro.';
            } else {
                $lines[] = sprintf(
                    'Tabela com %d nome(s). Use Baixar Excel/CSV para exportar%s.',
                    $shown,
                    !empty($data['truncated']) ? ' (lista truncada; refine o filtro)' : ''
                );
            }
            if (!empty($data['without_birthdate'])) {
                $lines[] = sprintf(
                    '%d ativo(s) sem data de nascimento no cadastro não entram nesta lista.',
                    (int) $data['without_birthdate']
                );
            }

            $displayRows = [];
            foreach ($data['rows'] as $row) {
                $displayRows[] = [
                    'Nome' => (string) ($row['nome'] ?? ''),
                    'Idade' => (int) ($row['idade'] ?? 0),
                    'Depto' => (string) ($row['departamento'] ?? ''),
                ];
            }
            $data['rows'] = $displayRows;
            $data['name'] = 'Ativos' . $deptLabel . ' ' . $ageLabel;
            $data['ui'] = ['compact_table' => true];

            return [
                'resposta' => implode("\n", $lines),
                'tool' => 'rh.list_active_by_age',
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
                    'Há %d colaborador(es) inativo(s) no Portal agora (status Inativo ou com data de desligamento). '
                    . 'Para desligamentos de um ano, diga «desligados 2025» ou «inativos 2025».',
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

        $dept = $this->canonicalDepartment(
            isset($intent['department']) ? (string) $intent['department'] : null,
            $message
        );
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
     * System prompt do roteador LLM (data do dia e departamentos do cadastro).
     */
    private function buildLlmRouterSystemPrompt(string $departmentCatalog): string
    {
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day') ?: time());
        $thisMonth = (int) date('n');
        $thisYear = (int) date('Y');

        return "Você interpreta perguntas em português livre sobre RH, salas e relatórios do Portal.\n"
            . "O usuário fala como quiser. Sua única tarefa é identificar TODOS os filtros da solicitação "
            . "e devolvê-los no mesmo JSON. O PHP executa a consulta — você não inventa SQL nem lista de pessoas.\n"
            . "Responda SOMENTE com um objeto JSON válido — sem markdown, sem texto antes/depois.\n\n"
            . "CONTEXTO\n"
            . "Data de hoje: {$today}\n\n"
            . "COMBINAR FILTROS (regra principal)\n"
            . "Se a pergunta tiver 1, 2, 3 ou mais critérios, coloque TODOS no mesmo objeto. "
            . "Nunca descarte um filtro citado (setor + idade, setor + período, idade mín + máx, etc.).\n"
            . "Chaves opcionais — inclua só as que o usuário pediu, mas não omita nenhuma delas:\n"
            . "- department: nome EXATO de DEPARTAMENTOS (abaixo)\n"
            . "- min_age: idade mínima inclusive (>=). «mais/maiores/acima de 30» → 30\n"
            . "- max_age: idade exclusiva (<). «menos/menores/abaixo de 40» → 40\n"
            . "- month, year, query, room, date, start, end, title, booking_id\n"
            . "Pode enviar também {\"filters\":{...}} com as mesmas chaves; o Portal une com o topo do JSON.\n"
            . "Qualquer filtro de idade (min_age e/ou max_age) → intent active_by_age, mesmo com department.\n"
            . "Quantos/quantas/headcount SEM pedir nomes → intent active (só o número).\n"
            . "Nomes/lista/quem são/quais usuários → intent active_list (lista nominativa) + department se citado.\n"
            . "NÃO use active (contagem) se o usuário pediu nomes. NÃO use lookup_person para listas de setor.\n"
            . "NÃO confunda idade da pessoa (lookup_person + focus age) com faixa etária de um grupo (active_by_age).\n"
            . "NÃO confunda idade com anos de empresa.\n\n"
            . "DEPARTAMENTOS (use um destes nomes; sinônimo deve mapear para esta lista)\n"
            . ($departmentCatalog !== '' ? $departmentCatalog : "- (cadastro indisponível)\n")
            . "«TI»/«informática»/«tecnologia» → TI; «RH» → Recursos Humanos; "
            . "«GQ»/«garantia da qualidade» → Garantia da Qualidade; "
            . "«CQ»/«controle de qualidade» → Controle de Qualidade.\n\n"
            . "INTENTS VÁLIDAS (nunca invente uma nova)\n"
            . "- {\"intent\":\"active\"}\n"
            . "- {\"intent\":\"active\",\"department\":\"Financeiro\"}\n"
            . "- {\"intent\":\"active_list\",\"department\":\"Compras\"}\n"
            . "- {\"intent\":\"active_by_age\",\"min_age\":30}\n"
            . "- {\"intent\":\"active_by_age\",\"min_age\":30,\"department\":\"Garantia da Qualidade\"}\n"
            . "- {\"intent\":\"active_by_age\",\"max_age\":40,\"department\":\"TI\"}\n"
            . "- {\"intent\":\"active_by_age\",\"min_age\":25,\"max_age\":40,\"department\":\"Produção\"}\n"
            . "- {\"intent\":\"inactive\"}\n"
            . "- {\"intent\":\"terminated_in_period\",\"month\":1,\"year\":{$thisYear}}\n"
            . "- {\"intent\":\"terminated_by_month\",\"year\":{$thisYear}}\n"
            . "- {\"intent\":\"blocked\"}\n"
            . "- {\"intent\":\"blocked_not_terminated\"}\n"
            . "- {\"intent\":\"terminated_list\",\"year\":{$thisYear},\"department\":\"Produção\"}\n"
            . "- {\"intent\":\"terminated_total\"}\n"
            . "- {\"intent\":\"terminated_by_department\",\"year\":{$thisYear}}\n"
            . "- {\"intent\":\"hired_list\",\"month\":6,\"year\":{$thisYear}}\n"
            . "- {\"intent\":\"hired_in_period\",\"month\":6,\"year\":{$thisYear}}\n"
            . "- {\"intent\":\"hired_total\"}\n"
            . "- {\"intent\":\"lookup_person\",\"query\":\"Rafael\"}\n"
            . "- {\"intent\":\"by_department\"}\n"
            . "- {\"intent\":\"report_list\"}\n"
            . "- {\"intent\":\"report_run\",\"query\":\"nome ou tool do relatório\"}\n"
            . "- {\"intent\":\"report_month_filter\",\"query\":\"vendas\",\"month\":8,\"year\":2026}\n"
            . "- {\"intent\":\"rooms_list\"}\n"
            . "- {\"intent\":\"rooms_agenda\",\"room\":\"Nome da sala\",\"date\":\"{$today}\"}\n"
            . "- {\"intent\":\"rooms_my\"}\n"
            . "- {\"intent\":\"rooms_reserve\",\"room\":\"Nome\",\"start\":\"{$today} 14:00:00\",\"end\":\"{$today} 15:00:00\",\"title\":\"Reunião\"}\n"
            . "- {\"intent\":\"rooms_cancel\",\"booking_id\":12}\n"
            . "- {\"intent\":\"rooms_reserve_help\"}\n"
            . "- {\"intent\":\"unknown\"}\n\n"
            . "REGRAS DE PESSOA (lookup_person)\n"
            . "Ficha de UMA pessoa → lookup_person com query = só o nome.\n"
            . "Se pediu um dado específico, inclua focus: age | tenure | status | department | blocked | position\n"
            . "\"qual a idade do wladimir\" → {\"intent\":\"lookup_person\",\"query\":\"wladimir\",\"focus\":\"age\"}\n"
            . "\"anos de empresa do X\" → focus tenure. NÃO use active_by_age para uma pessoa só.\n\n"
            . "REGRAS DE DESLIGADOS/INATIVOS\n"
            . "\"quantos desligados\" sem período → terminated_total.\n"
            . "Lista nominativa de desligados → terminated_list (month/year/department opcionais; combine os que existirem).\n"
            . "Bloqueados sem desligamento → blocked_not_terminated.\n"
            . "\"desligados por departamento\" → terminated_by_department (year opcional).\n"
            . "Se a pergunta tiver mês (ex.: \"inativos em janeiro\") → terminated_in_period.\n"
            . "   NUNCA use department para um valor de mês (ex.: department=\"janeiro\" está errado).\n"
            . "Se pedir inativos/desligados \"por mês\" ou apenas um ano (ex.: \"desligados 2025\") → terminated_by_month.\n"
            . "\"esse mês\" → terminated_in_period com month={$thisMonth}, year={$thisYear}.\n\n"
            . "REGRAS DE CONTRATAÇÕES/ADMISSÕES\n"
            . "Contratação, admissões, admitidos, contratados → NUNCA terminated_* (isso é desligamento).\n"
            . "Lista nominativa → hired_list (combine month/year/department se o usuário citou).\n"
            . "\"quantas contratações em junho\" / \"admissões em 2026\" → hired_in_period.\n"
            . "\"quantas contratações\" sem período → hired_total.\n\n"
            . "REGRAS DE SALAS\n"
            . "Intents rooms_* são exclusivas de reserva de salas — nunca misturar com intents de RH.\n"
            . "Resolva datas/horas relativas usando a data de hoje acima.\n"
            . "Formato de data: \"YYYY-MM-DD\". Formato de data+hora: \"YYYY-MM-DD HH:MM:SS\".\n"
            . "Se pedir como reservar sem dados completos → rooms_reserve_help.\n\n"
            . "QUANDO NÃO TIVER CERTEZA\n"
            . "Se não houver tool para o pedido, {\"intent\":\"unknown\"}. "
            . "Nunca invente departamento fora da lista nem campos que o usuário não pediu.\n"
            . "Nunca deixe de enviar um campo que o usuário pediu.\n\n"
            . "EXEMPLOS DE COMBINAÇÃO\n"
            . "\"garantia da qualidade maiores de 30 anos\" → "
            . "{\"intent\":\"active_by_age\",\"department\":\"Garantia da Qualidade\",\"min_age\":30}\n"
            . "\"TI com menos de 40 anos\" → {\"intent\":\"active_by_age\",\"department\":\"TI\",\"max_age\":40}\n"
            . "\"nome dos usuários do compras\" → {\"intent\":\"active_list\",\"department\":\"Compras\"}\n"
            . "\"quantos colaboradores tem no financeiro\" → {\"intent\":\"active\",\"department\":\"Financeiro\"}\n"
            . "\"qual a idade do wladimir\" → {\"intent\":\"lookup_person\",\"query\":\"wladimir\",\"focus\":\"age\"}\n"
            . "\"status do wladimir\" → {\"intent\":\"lookup_person\",\"query\":\"wladimir\"}\n"
            . "\"quantos desligados esse mês\" → {\"intent\":\"terminated_in_period\",\"month\":{$thisMonth},\"year\":{$thisYear}}\n"
            . "\"lista de contratações em junho/{$thisYear}\" → {\"intent\":\"hired_list\",\"month\":6,\"year\":{$thisYear}}\n"
            . "\"quantas admissões em 2025\" → {\"intent\":\"hired_in_period\",\"year\":2025}\n"
            . "\"desligados 2025\" → {\"intent\":\"terminated_by_month\",\"year\":2025}\n"
            . "\"desligados por departamento em 2025\" → {\"intent\":\"terminated_by_department\",\"year\":2025}\n"
            . "\"quem está bloqueado mas não desligado\" → {\"intent\":\"blocked_not_terminated\"}\n"
            . "\"reservar sala azul amanhã 14h às 15h para reunião de time\" → "
            . "{\"intent\":\"rooms_reserve\",\"room\":\"Azul\",\"start\":\"{$tomorrow} 14:00:00\",\"end\":\"{$tomorrow} 15:00:00\",\"title\":\"reunião de time\"}\n"
            . "\"agenda da sala azul\" → {\"intent\":\"rooms_agenda\",\"room\":\"Azul\",\"date\":\"{$today}\"}";
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

        $deptCatalog = '';
        foreach ($this->rh->listDepartmentNames() as $deptName) {
            $deptCatalog .= '- ' . $deptName . "\n";
        }

        $system = $this->buildLlmRouterSystemPrompt($deptCatalog);

        $user = ($catalogHint !== '' ? "Relatórios no chat:\n{$catalogHint}\n" : '')
            . "Identifique todos os filtros da solicitação e combine-os no mesmo JSON.\n"
            . 'Pergunta do usuário: ' . $message;

        $llm = $this->llm->complete($system, $user, true);
        if ($llm === null) {
            $err = $this->llm->getLastError();
            if ($err !== null && $err !== '') {
                return [
                    'resposta' => 'Não consegui usar a IA para interpretar «'
                        . mb_substr($message, 0, 80)
                        . '». '
                        . $err
                        . "\n\nTente de novo ou use uma das consultas abaixo:\n\n"
                        . $this->formatHelpOptions(),
                    'tool' => 'chat.help',
                    'data' => null,
                    'provider' => 'llm-error',
                ];
            }

            return null;
        }

        $raw = $llm['text'];
        // Remove cercas markdown se a API devolver
        $raw = preg_replace('/^```(?:json)?\s*/i', '', trim($raw)) ?? $raw;
        $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;
        $intentJson = json_decode($raw, true);
        if (!is_array($intentJson)) {
            return [
                'resposta' => 'A IA ('
                    . (string) ($llm['provider'] ?? '?')
                    . ') respondeu, mas não em JSON de intenção. Tente de novo ou uma consulta da lista.',
                'tool' => 'chat.help',
                'data' => null,
                'provider' => (string) ($llm['provider'] ?? 'llm'),
            ];
        }

        $intentJson = $this->flattenLlmFilterBag($intentJson);

        $name = (string) ($intentJson['intent'] ?? 'unknown');
        if ($name === 'unknown' || $name === '') {
            if (!empty($intentJson['min_age']) || !empty($intentJson['max_age'])) {
                $name = 'active_by_age';
                $intentJson['intent'] = $name;
            } elseif (!empty($intentJson['department'])) {
                $name = 'active';
                $intentJson['intent'] = $name;
            }
        }
        if ($name === 'unknown' || $name === '') {
            return [
                'resposta' => 'A IA ('
                    . (string) ($llm['provider'] ?? '?')
                    . ') não mapeou «'
                    . mb_substr($message, 0, 80)
                    . '» para uma consulta existente.',
                'tool' => 'chat.help',
                'data' => null,
                'provider' => (string) ($llm['provider'] ?? 'llm'),
            ];
        }

        $allowed = [
            'active', 'active_list', 'active_by_age', 'inactive', 'terminated_in_period', 'terminated_by_month', 'terminated_total',
            'terminated_list', 'terminated_by_department', 'hired_list', 'hired_in_period', 'hired_total',
            'blocked', 'blocked_not_terminated', 'lookup_person',
            'by_department', 'report_list', 'report_run', 'report_month_filter', 'suggest_invalid', 'clarify_by_month',
            'rooms_list', 'rooms_agenda', 'rooms_my', 'rooms_reserve', 'rooms_cancel', 'rooms_reserve_help',
        ];
        if (!in_array($name, $allowed, true)) {
            return null;
        }

        // Guardrail: LLM não pode mapear contratação → desligamento.
        if ($this->mentionsHiring(mb_strtolower($message)) && str_starts_with($name, 'terminated')) {
            $period = $this->extractPeriod($message);
            $yearOnly = $this->extractYearOnly(mb_strtolower($message));
            $wantsList = (bool) preg_match(
                '/\b(lista|listar|nomes|nominativa|quais\s+(s[aã]o|foram)|quem\s+(s[aã]o|foram))\b/u',
                mb_strtolower($message)
            );
            if ($wantsList) {
                $name = 'hired_list';
            } elseif ($period !== null || $yearOnly !== null) {
                $name = 'hired_in_period';
            } else {
                $name = 'hired_total';
            }
            $intentJson['intent'] = $name;
            if ($period !== null) {
                $intentJson['month'] = $period['month'];
                $intentJson['year'] = $period['year'];
            } elseif ($yearOnly !== null) {
                $intentJson['year'] = $yearOnly;
                unset($intentJson['month']);
            }
        }

        $intent = ['name' => $name];
        if (!empty($intentJson['query']) && ($name === 'lookup_person' || $name === 'report_run')) {
            $intent['query'] = (string) $intentJson['query'];
        }
        if (!empty($intentJson['room'])) {
            $intent['room'] = (string) $intentJson['room'];
        }
        if (!empty($intentJson['date'])) {
            $intent['date'] = (string) $intentJson['date'];
        }
        if (!empty($intentJson['start'])) {
            $intent['start'] = (string) $intentJson['start'];
        }
        if (!empty($intentJson['end'])) {
            $intent['end'] = (string) $intentJson['end'];
        }
        if (!empty($intentJson['title'])) {
            $intent['title'] = (string) $intentJson['title'];
        }
        if (!empty($intentJson['booking_id'])) {
            $intent['booking_id'] = (int) $intentJson['booking_id'];
        }
        if (!empty($intentJson['department'])) {
            $dept = $this->canonicalDepartment((string) $intentJson['department'], (string) $intentJson['department']);
            if ($dept === null) {
                $dept = $this->resolveDepartmentAlias((string) $intentJson['department']);
            }
            if ($dept !== null) {
                $first = mb_strtolower(explode(' ', $dept)[0] ?? '');
                if (isset(self::MONTHS[$first])) {
                    $dept = null;
                    if ($name === 'inactive' || $name === 'terminated_in_period' || $name === 'terminated_list'
                        || $name === 'hired_list' || $name === 'hired_in_period'
                    ) {
                        if ($name === 'terminated_list' || $name === 'hired_list') {
                            $intent['name'] = $name;
                        } elseif ($name === 'hired_in_period') {
                            $intent['name'] = 'hired_in_period';
                        } else {
                            $intent['name'] = 'terminated_in_period';
                        }
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
        if (!empty($intentJson['min_age'])) {
            $intent['min_age'] = (int) $intentJson['min_age'];
        }
        if (!empty($intentJson['max_age'])) {
            $intent['max_age'] = (int) $intentJson['max_age'];
        }
        if (!empty($intentJson['focus'])) {
            $intent['focus'] = mb_strtolower(trim((string) $intentJson['focus']));
        }
        if (!empty($intentJson['report_id'])) {
            $intent['report_id'] = (int) $intentJson['report_id'];
        }
        if ($name === 'report_run' && !empty($intent['month']) && !empty($intent['year'])) {
            $intent['name'] = 'report_month_filter';
        }
        if (!empty($intentJson['query'])) {
            $intent['query'] = (string) $intentJson['query'];
        }

        if ($name === 'lookup_person') {
            $safeQuery = $this->sanitizeLlmPersonQuery($message, (string) ($intent['query'] ?? ''));
            if ($safeQuery === '') {
                return null;
            }
            $intent['query'] = $safeQuery;
        }

        $intent = $this->mergeCombinedRhFilters($intent, $message);
        $name = (string) ($intent['name'] ?? $name);

        $denied = $this->denyIfUnauthorizedIntent($name);
        if ($denied !== null) {
            $denied['provider'] = $llm['provider'];

            return $denied;
        }

        $result = $this->executeIntent($intent, $message);
        $result['provider'] = $llm['provider'];

        return $result;
    }

    /**
     * Impede o LLM de trocar um código/nome da mensagem por outra pessoa inventada.
     */
    private function sanitizeLlmPersonQuery(string $message, string $llmQuery): string
    {
        $fromMsg = $this->extractPersonQuery($message);
        if ($fromMsg !== null) {
            return $fromMsg;
        }

        $msg = trim(preg_replace('/[?!.]+$/u', '', trim($message)) ?? trim($message));
        if ($msg === '') {
            return '';
        }

        if (preg_match('/^\d{3,}$/u', $msg)) {
            return $msg;
        }

        $llmQuery = trim($llmQuery);
        if ($llmQuery === '') {
            return $msg;
        }

        $msgKey = mb_strtolower($msg);
        $qKey = mb_strtolower($llmQuery);
        if (str_contains($msgKey, $qKey) || str_contains($qKey, $msgKey)) {
            return $llmQuery;
        }

        // Mensagem parece só um nome: use a mensagem, não o chute do modelo.
        if (preg_match('/^[a-záàâãéêíóôõúç][a-záàâãéêíóôõúç0-9\s\.\-]{1,80}$/iu', $msg)) {
            return $msg;
        }

        return '';
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
        if (!in_array($tool, [
            'report.run',
            'rh.count_active_by_department',
            'rh.count_terminated_in_month',
            'rh.count_terminated_by_department',
            'rh.count_blocked_not_terminated',
        ], true)) {
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

        $system = "Você é analista de RH/dados do Portal.\n"
            . "Responda SEMPRE em português do Brasil.\n"
            . "Responda em no máximo 3 frases objetivas (destaque, concentração, alerta se fizer sentido).\n"
            . "Baseie-se APENAS nos números presentes no JSON fornecido — nunca invente, estime ou arredonde "
            . "para um valor não presente nos dados.\n"
            . "Não exponha IDs internos, chaves técnicas ou nomes de campos do JSON — traduza para linguagem natural.\n"
            . "Se o JSON estiver vazio ou não tiver dados suficientes para uma conclusão, diga isso em vez de forçar uma análise.\n"
            . "Sem markdown.";
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
