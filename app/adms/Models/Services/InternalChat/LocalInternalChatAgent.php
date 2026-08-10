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
    private ChatRoomsService $rooms;
    private ChatRoomsBookingWizard $roomsWizard;
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
        ?ChatRoomsService $rooms = null,
        ?ChatRoomsBookingWizard $roomsWizard = null,
        ?InternalChatLlmClient $llm = null
    ) {
        $this->rh = $rh ?? new RhChatIndicatorsService();
        $this->reports = $reports ?? new ChatDynamicReportService();
        $this->rooms = $rooms ?? new ChatRoomsService();
        $this->roomsWizard = $roomsWizard ?? new ChatRoomsBookingWizard($this->rooms);
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
            return ['resposta' => 'Envie uma pergunta. Exemplos: "quantos colaboradores ativos?", "agendar", "salas".'];
        }

        // Fluxo guiado de salas (sala → data → horários).
        if ($this->roomsWizard->isActive()) {
            $wizard = $this->roomsWizard->continue($this->userId, $message);
            if ($wizard !== null) {
                return $wizard;
            }
        }
        if (preg_match('/^(agendar|reservar|nova\s+reserva|agendar\s+sala|reservar\s+sala)$/iu', $message)) {
            return $this->roomsWizard->start($this->userId);
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
                    . "• desligados 2025\n"
                    . "• quantos usuários bloqueados?\n"
                    . "• bloqueados sem desligamento\n"
                    . "• lista de desligados / lista de desligados em janeiro / lista desligados Produção\n"
                    . "• (após totais) digite «lista» para ver os nomes\n"
                    . "• departamento do Rafael / Wladimir está bloqueado? / anos de empresa do X\n"
                    . "• ativos na TI\n"
                    . "• headcount por departamento\n"
                    . "• inativos por mês\n"
                    . "• quais relatórios no chat?\n"
                    . "• relatório [nome ou tool]\n"
                    . "• agendar / reservar (fluxo simples: sala → data → horários)\n"
                    . "• salas / listar salas\n"
                    . "• agenda da sala [nome] hoje\n"
                    . "• minhas reservas\n"
                    . "• cancelar reserva #123\n"
                    . "• depois de «ativos» ou «inativos», digite o departamento ou «por mês» / «por departamento»",
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

        // Follow-up «lista» após totais de desligados.
        $listFollowUp = $this->detectTerminatedListFollowUp($m, $message);
        if ($listFollowUp !== null) {
            return $listFollowUp;
        }

        $personRefine = $this->detectPersonCandidateRefine($message);
        if ($personRefine !== null) {
            return $personRefine;
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
        $matchedReport = $this->reports->resolveReport($this->userId, $message);
        if ($matchedReport !== null) {
            return ['name' => 'report_run', 'query' => $message, 'report_id' => (int) $matchedReport['id']];
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
        } elseif ($tool === 'rh.count_active' || $tool === 'rh.count_active_by_department') {
            $_SESSION['internal_chat_last_status'] = 'active';
            unset($_SESSION['internal_chat_person_candidates'], $_SESSION['internal_chat_last_terminated']);
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
        } elseif ($tool !== '' && str_starts_with($tool, 'rooms.')) {
            unset($_SESSION['internal_chat_person_candidates']);
        }
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
        if ($raw === '' || mb_strlen($raw) > 60) {
            return null;
        }

        // Nova pergunta completa sobre outra pessoa → não refinar.
        if ($this->extractPersonQuery($message) !== null) {
            return null;
        }

        $m = mb_strtolower($raw);
        if (preg_match(
            '/\b(quantos|qtd|quantidade|headcount|agendar|reservar|salas?|relat[oó]rios?|desligad|inativos?|bloqueados?|ativos?\s+(na|em|por)|por\s+m[eê]s|por\s+departamento)\b/u',
            $m
        )) {
            return null;
        }

        return ['name' => 'lookup_person_refine', 'token' => $raw];
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
            '/qual\s+(?:o\s+)?departamentos?\s+(?:do|da|de)\s+(.+)$/iu',
            '/departamentos?\s+(?:do|da|de)\s+(.+)$/iu',
            '/(?:ficha|dados|informa[cç][oõ]es|perfil)\s+(?:do|da|de)\s+(.+)$/iu',
            '/sobre\s+(?:o|a)\s+(.+)$/iu',
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
            return [
                'resposta' => sprintf(
                    'Não encontrei colaborador com «%s». Tente nome completo, username ou e-mail.',
                    $query
                ),
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
            'resposta' => $this->formatPersonCard($matches[0]),
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
        $token = trim($token);
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
    private function formatPersonCard(array $p): string
    {
        $blockedLabel = !empty($p['blocked']) ? 'sim' : 'não';
        $termLabel = !empty($p['termination_date'])
            ? ('desligado em ' . $this->formatBrDate((string) $p['termination_date']))
            : 'sem desligamento';
        $admLabel = !empty($p['admission_date'])
            ? $this->formatBrDate((string) $p['admission_date'])
            : 'não informada';

        return sprintf(
            "%s (@%s)\n"
            . "• Departamento: %s\n"
            . "• Cargo: %s\n"
            . "• Status: %s\n"
            . "• Bloqueado: %s\n"
            . "• Admissão: %s\n"
            . "• Tempo de empresa: %s\n"
            . "• Desligamento: %s",
            (string) ($p['name'] ?? ''),
            ($p['username'] ?? '') !== '' ? $p['username'] : '—',
            (string) ($p['department'] ?? '—'),
            (string) ($p['position'] ?? '—'),
            ($p['status'] ?? '') !== '' ? $p['status'] : '—',
            $blockedLabel,
            $admLabel,
            (string) ($p['tenure_label'] ?? '—'),
            $termLabel
        );
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
            $data = $this->rh->lookupPerson($query);

            return $this->buildLookupPersonResult($data);
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
            . "{\"intent\":\"terminated_by_month\",\"year\":2026}\n"
            . "{\"intent\":\"blocked\"}\n"
            . "{\"intent\":\"blocked_not_terminated\"}\n"
            . "{\"intent\":\"terminated_list\",\"year\":2025,\"department\":\"Produção\"}\n"
            . "{\"intent\":\"terminated_total\"}\n"
            . "{\"intent\":\"terminated_by_department\",\"year\":2025}\n"
            . "{\"intent\":\"lookup_person\",\"query\":\"Rafael\"}\n"
            . "{\"intent\":\"by_department\"}\n"
            . "{\"intent\":\"report_list\"}\n"
            . "{\"intent\":\"report_run\",\"query\":\"nome ou tool do relatório\"}\n"
            . "{\"intent\":\"rooms_list\"}\n"
            . "{\"intent\":\"rooms_agenda\",\"room\":\"Nome da sala\",\"date\":\"2026-08-10\"}\n"
            . "{\"intent\":\"rooms_my\"}\n"
            . "{\"intent\":\"rooms_reserve\",\"room\":\"Nome\",\"start\":\"2026-08-10 14:00:00\",\"end\":\"2026-08-10 15:00:00\",\"title\":\"Reunião\"}\n"
            . "{\"intent\":\"rooms_cancel\",\"booking_id\":12}\n"
            . "{\"intent\":\"unknown\"}\n"
            . "Se perguntar departamento/bloqueio/anos de empresa de uma pessoa, use lookup_person com query=só o nome (ex.: Rafael), sem prefixos.\n"
            . "Lista nominativa de desligados → terminated_list (month/year/department opcionais). «quantos desligados» → terminated_total.\n"
            . "Bloqueados sem desligamento → blocked_not_terminated. «quantos desligados» sem período → terminated_total.\n"
            . "«desligados por departamento» → terminated_by_department (year opcional).\n"
            . "Se a pergunta tiver mês (ex.: inativos em janeiro), use terminated_in_period — NÃO use department=janeiro.\n"
            . "Se pedir inativos/desligados «por mês» ou só o ano (ex.: desligados 2025), use terminated_by_month — NÃO use report_run nem inactive.\n"
            . "Salas: listar/agenda/reservar/cancelar usam as intents rooms_* (não misturar com RH).";

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

        $allowed = [
            'active', 'inactive', 'terminated_in_period', 'terminated_by_month', 'terminated_total',
            'terminated_list', 'terminated_by_department', 'blocked', 'blocked_not_terminated', 'lookup_person',
            'by_department', 'report_list', 'report_run', 'clarify_by_month',
            'rooms_list', 'rooms_agenda', 'rooms_my', 'rooms_reserve', 'rooms_cancel', 'rooms_reserve_help',
        ];
        if (!in_array($name, $allowed, true)) {
            return null;
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
            $dept = $this->resolveDepartmentAlias((string) $intentJson['department']);
            if ($dept !== null) {
                $first = mb_strtolower(explode(' ', $dept)[0] ?? '');
                if (isset(self::MONTHS[$first])) {
                    $dept = null;
                    if ($name === 'inactive' || $name === 'terminated_in_period' || $name === 'terminated_list') {
                        $intent['name'] = $name === 'terminated_list' ? 'terminated_list' : 'terminated_in_period';
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
