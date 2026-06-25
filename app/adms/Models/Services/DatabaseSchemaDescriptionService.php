<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Gera descrições legíveis para tabelas e colunas a partir do nome e metadados.
 */
class DatabaseSchemaDescriptionService
{
    /** Prefixos técnicos removidos ao humanizar o nome da tabela. */
    private const STRIP_PREFIXES = [
        'adms_', 'inv_', 'crm_', 'sst_', 'lgpd_', 'proj_', 'rh_', 'room_', 'rooms_',
        'booking_', 'sac_', 'pe_', 'phinxlog',
    ];

    private ?DatabaseSchemaModuleResolver $moduleResolver;

    public function __construct(?DatabaseSchemaModuleResolver $moduleResolver = null)
    {
        $this->moduleResolver = $moduleResolver;
    }

    /** @var array<string, string> */
    private const WORDS = [
        'access' => 'acesso',
        'level' => 'nível',
        'levels' => 'níveis',
        'page' => 'página',
        'pages' => 'páginas',
        'branch' => 'filial',
        'branches' => 'filiais',
        'department' => 'departamento',
        'departments' => 'departamentos',
        'permission' => 'permissão',
        'user' => 'usuário',
        'users' => 'usuários',
        'account' => 'conta',
        'accounts' => 'contas',
        'plan' => 'plano',
        'plans' => 'planos',
        'action' => 'ação',
        'actions' => 'ações',
        'bank' => 'banco',
        'transfer' => 'transferência',
        'transfers' => 'transferências',
        'booking' => 'reserva',
        'resource' => 'recurso',
        'resources' => 'recursos',
        'participant' => 'participante',
        'participants' => 'participantes',
        'notification' => 'notificação',
        'notifications' => 'notificações',
        'waitlist' => 'lista de espera',
        'request' => 'solicitação',
        'requests' => 'solicitações',
        'additional' => 'adicional',
        'collaborator' => 'colaborador',
        'customer' => 'cliente',
        'clients' => 'clientes',
        'rule' => 'regra',
        'rules' => 'regras',
        'sla' => 'SLA',
        'message' => 'mensagem',
        'messages' => 'mensagens',
        'from' => 'origem',
        'to' => 'destino',
        'candidato' => 'candidato',
        'candidatos' => 'candidatos',
        'vaga' => 'vaga',
        'vagas' => 'vagas',
        'anexo' => 'anexo',
        'anexos' => 'anexos',
        'competency' => 'competência',
        'competencies' => 'competências',
        'matrix' => 'matriz',
        'cost' => 'custo',
        'center' => 'centro',
        'company' => 'empresa',
        'event' => 'evento',
        'events' => 'eventos',
        'guest' => 'convidado',
        'guests' => 'convidados',
        'image' => 'imagem',
        'images' => 'imagens',
        'lead' => 'lead',
        'leads' => 'leads',
        'rsvp' => 'confirmação de presença',
        'rsvps' => 'confirmações de presença',
        'status' => 'status',
        'type' => 'tipo',
        'types' => 'tipos',
        'category' => 'categoria',
        'categories' => 'categorias',
        'config' => 'configuração',
        'settings' => 'configurações',
        'setting' => 'configuração',
        'log' => 'log',
        'logs' => 'logs',
        'history' => 'histórico',
        'histories' => 'históricos',
        'report' => 'relatório',
        'reports' => 'relatórios',
        'dashboard' => 'dashboard',
        'dashboards' => 'dashboards',
        'training' => 'treinamento',
        'trainings' => 'treinamentos',
        'inventory' => 'inventário',
        'stock' => 'estoque',
        'product' => 'produto',
        'products' => 'produtos',
        'order' => 'pedido',
        'orders' => 'pedidos',
        'invoice' => 'nota fiscal',
        'payment' => 'pagamento',
        'payments' => 'pagamentos',
        'receipt' => 'recebimento',
        'document' => 'documento',
        'documents' => 'documentos',
        'employee' => 'funcionário',
        'employees' => 'funcionários',
        'payroll' => 'folha de pagamento',
        'project' => 'projeto',
        'projects' => 'projetos',
        'task' => 'tarefa',
        'tasks' => 'tarefas',
        'risk' => 'risco',
        'risks' => 'riscos',
        'exam' => 'exame',
        'exames' => 'exames',
        'epi' => 'EPI',
        'epis' => 'EPIs',
        'aso' => 'ASO',
        'cid' => 'CID',
        'cids' => 'CIDs',
        'medico' => 'médico',
        'medicos' => 'médicos',
        'equipamento' => 'equipamento',
        'equipamentos' => 'equipamentos',
        'vistoria' => 'vistoria',
        'vistorias' => 'vistorias',
        'treinamento' => 'treinamento',
        'treinamentos' => 'treinamentos',
        'ghe' => 'GHE',
        'scope' => 'escopo',
        'combination' => 'combinação',
        'combinations' => 'combinações',
        'name' => 'nome',
        'description' => 'descrição',
        'title' => 'título',
        'code' => 'código',
        'date' => 'data',
        'amount' => 'valor',
        'value' => 'valor',
        'total' => 'total',
        'quantity' => 'quantidade',
        'qty' => 'quantidade',
        'email' => 'e-mail',
        'phone' => 'telefone',
        'address' => 'endereço',
        'city' => 'cidade',
        'state' => 'estado',
        'country' => 'país',
        'zip' => 'CEP',
        'active' => 'ativo',
        'inactive' => 'inativo',
        'deleted' => 'excluído',
        'approved' => 'aprovado',
        'pending' => 'pendente',
        'role' => 'papel',
        'roles' => 'papéis',
        'group' => 'grupo',
        'groups' => 'grupos',
        'item' => 'item',
        'items' => 'itens',
        'detail' => 'detalhe',
        'details' => 'detalhes',
        'attachment' => 'anexo',
        'attachments' => 'anexos',
        'comment' => 'comentário',
        'comments' => 'comentários',
        'note' => 'observação',
        'notes' => 'observações',
        'file' => 'arquivo',
        'files' => 'arquivos',
        'version' => 'versão',
        'module' => 'módulo',
        'menu' => 'menu',
        'form' => 'formulário',
        'field' => 'campo',
        'fields' => 'campos',
        'template' => 'modelo',
        'templates' => 'modelos',
        'workflow' => 'fluxo de trabalho',
        'approval' => 'aprovação',
        'signature' => 'assinatura',
        'consent' => 'consentimento',
        'term' => 'termo',
        'termos' => 'termos',
        'data' => 'dados',
        'mapping' => 'mapeamento',
        'subject' => 'titular',
        'incident' => 'incidente',
        'incidents' => 'incidentes',
        'supplier' => 'fornecedor',
        'suppliers' => 'fornecedores',
        'vendor' => 'fornecedor',
        'sale' => 'venda',
        'sales' => 'vendas',
        'purchase' => 'compra',
        'purchases' => 'compras',
        'budget' => 'orçamento',
        'goal' => 'meta',
        'goals' => 'metas',
        'kpi' => 'KPI',
        'kpis' => 'KPIs',
        'indicator' => 'indicador',
        'indicators' => 'indicadores',
        'schedule' => 'agenda',
        'calendar' => 'calendário',
        'room' => 'sala',
        'rooms' => 'salas',
        'meeting' => 'reunião',
        'meetings' => 'reuniões',
        'ticket' => 'chamado',
        'tickets' => 'chamados',
        'survey' => 'pesquisa',
        'surveys' => 'pesquisas',
        'feedback' => 'feedback',
        'push' => 'push',
        'subscription' => 'assinatura',
        'subscriptions' => 'assinaturas',
        'gamification' => 'gamificação',
        'badge' => 'emblema',
        'badges' => 'emblemas',
        'point' => 'ponto',
        'points' => 'pontos',
        'ledger' => 'livro razão',
        'timeline' => 'linha do tempo',
        'spreadsheet' => 'planilha',
        'spreadsheets' => 'planilhas',
        'dynamic' => 'dinâmico',
        'pdi' => 'PDI',
        'performance' => 'desempenho',
        'evaluation' => 'avaliação',
        'evaluations' => 'avaliações',
        'position' => 'cargo',
        'positions' => 'cargos',
        'vacancy' => 'vaga',
        'vacancies' => 'vagas',
        'candidate' => 'candidato',
        'candidates' => 'candidatos',
        'interview' => 'entrevista',
        'interviews' => 'entrevistas',
        'onboarding' => 'integração',
        'offboarding' => 'desligamento',
        'holiday' => 'feriado',
        'holidays' => 'feriados',
        'leave' => 'afastamento',
        'absence' => 'ausência',
        'absences' => 'ausências',
        'benefit' => 'benefício',
        'benefits' => 'benefícios',
        'operation' => 'operação',
        'operations' => 'operações',
        'movement' => 'movimentação',
        'movements' => 'movimentações',
        'warehouse' => 'almoxarifado',
        'location' => 'localização',
        'locations' => 'localizações',
        'unit' => 'unidade',
        'units' => 'unidades',
        'batch' => 'lote',
        'batches' => 'lotes',
        'serial' => 'serial',
        'barcode' => 'código de barras',
        'sku' => 'SKU',
        'own' => 'próprio',
        'specific' => 'específico',
        'all' => 'todos',
        'use' => 'uso',
    ];

    /** @var array<string, string> */
    private const COLUMN_EXACT = [
        'id' => 'Identificador único do registro',
        'uuid' => 'Identificador único universal (UUID)',
        'name' => 'Nome',
        'nome' => 'Nome',
        'title' => 'Título',
        'titulo' => 'Título',
        'description' => 'Descrição',
        'descricao' => 'Descrição',
        'status' => 'Status do registro',
        'active' => 'Indica se o registro está ativo',
        'ativo' => 'Indica se o registro está ativo',
        'enabled' => 'Indica se está habilitado',
        'disabled' => 'Indica se está desabilitado',
        'email' => 'Endereço de e-mail',
        'phone' => 'Telefone',
        'telefone' => 'Telefone',
        'cpf' => 'CPF',
        'cnpj' => 'CNPJ',
        'password' => 'Senha (armazenada com hash)',
        'senha' => 'Senha (armazenada com hash)',
        'token' => 'Token de autenticação ou validação',
        'created_at' => 'Data e hora de criação do registro',
        'create_at' => 'Data e hora de criação do registro',
        'updated_at' => 'Data e hora da última atualização',
        'update_at' => 'Data e hora da última atualização',
        'deleted_at' => 'Data e hora da exclusão lógica',
        'created_by' => 'Usuário que criou o registro',
        'updated_by' => 'Usuário que atualizou o registro',
        'deleted_by' => 'Usuário que excluiu o registro',
        'sort_order' => 'Ordem de exibição',
        'order' => 'Ordem de exibição',
        'ordem' => 'Ordem de exibição',
        'position' => 'Posição ou ordem',
        'notes' => 'Observações',
        'observacao' => 'Observação',
        'observacoes' => 'Observações',
        'permission' => 'Indica se a permissão está concedida',
        'department_scope' => 'Escopo de departamentos (todos, próprio ou específicos)',
        'branch_scope' => 'Escopo de filiais (todas, própria ou específicas)',
        'use_branch_department_combinations' => 'Usa combinações específicas de filial e departamento',
    ];

    public function describeTable(string $tableName, array $columns = [], array $foreignKeys = []): string
    {
        $moduleLabel = $this->resolveModuleLabel($tableName);
        $coreName = $this->stripTablePrefix($tableName);
        $humanized = $this->humanizeParts($coreName);

        if ($columns !== []) {
            return $this->describeTableFromStructure($tableName, $humanized, $moduleLabel, $columns, $foreignKeys);
        }

        $suffix = $this->detectTableKind($coreName);
        $body = match ($suffix) {
            'log' => 'Registros de log de ' . $humanized,
            'settings' => 'Configurações de ' . $humanized,
            'history' => 'Histórico de ' . $humanized,
            'mapping' => 'Mapeamento de ' . $humanized,
            default => 'Cadastro de ' . $humanized,
        };

        if ($moduleLabel !== '' && !str_contains(strtolower($body), strtolower($moduleLabel))) {
            $body .= " — {$moduleLabel}";
        }

        return $this->capitalizeFirst($body);
    }

    /**
     * Analisa colunas reais da tabela para montar a descrição.
     *
     * @param list<array<string, mixed>> $columns
     * @param list<array<string, mixed>> $foreignKeys
     */
    private function describeTableFromStructure(
        string $tableName,
        string $humanized,
        string $moduleLabel,
        array $columns,
        array $foreignKeys
    ): string {
        $auditFields = [
            'id', 'uuid', 'created_at', 'create_at', 'updated_at', 'update_at',
            'deleted_at', 'created_by', 'updated_by', 'deleted_by',
        ];

        $dataColumns = [];
        $fkLabels = [];

        foreach ($columns as $col) {
            $name = strtolower((string) ($col['COLUMN_NAME'] ?? ''));
            if ($name === '' || in_array($name, $auditFields, true)) {
                continue;
            }

            if (str_ends_with($name, '_id')) {
                $ref = trim((string) ($col['relation_table'] ?? ''));
                if ($ref === '') {
                    $ref = $this->guessTableFromFkColumn($name) ?? '';
                }
                if ($ref !== '' && $ref !== 'adms_users') {
                    $fkLabels[$ref] = $this->humanizeParts($this->stripTablePrefix($ref));
                }
                continue;
            }

            $dataColumns[] = $name;
        }

        $coreName = $this->stripTablePrefix($tableName);
        $kind = $this->detectTableKind($coreName);
        $fkCount = count($fkLabels);
        $dataCount = count($dataColumns);

        if ($kind === 'log' || $this->looksLikeLogTable($dataColumns)) {
            $body = 'Registros de log — ' . $humanized;
        } elseif ($kind === 'settings') {
            $body = 'Configurações de ' . $humanized;
        } elseif ($fkCount >= 2 && $dataCount <= 2) {
            $body = 'Relacionamento entre ' . $this->joinNatural(array_values($fkLabels));
        } elseif ($kind === 'mapping') {
            $body = 'Mapeamento de ' . $humanized;
        } else {
            $body = 'Cadastro de ' . $humanized;
            $highlights = $this->highlightColumns($dataColumns);
            if ($highlights !== []) {
                $body .= '. Campos: ' . implode(', ', $highlights);
            }
            if ($fkCount > 0 && $fkCount <= 4) {
                $body .= '. Vinculado a ' . $this->joinNatural(array_values($fkLabels));
            }
        }

        if ($moduleLabel !== '' && !str_contains(strtolower($body), strtolower($moduleLabel))) {
            $body .= " — {$moduleLabel}";
        }

        return $this->capitalizeFirst($body);
    }

    /**
     * @param list<string> $dataColumns
     * @return list<string>
     */
    private function highlightColumns(array $dataColumns): array
    {
        $priority = [
            'name', 'nome', 'title', 'titulo', 'description', 'descricao', 'code', 'codigo',
            'status', 'type', 'tipo', 'email', 'cpf', 'cnpj', 'phone', 'telefone',
            'amount', 'valor', 'total', 'quantity', 'quantidade', 'date', 'data',
            'start_date', 'end_date', 'data_inicio', 'data_fim', 'permission', 'active', 'ativo',
        ];

        $found = [];
        foreach ($priority as $p) {
            if (in_array($p, $dataColumns, true)) {
                $found[] = $this->humanizeParts($p);
            }
        }

        if (count($found) >= 3) {
            return array_slice($found, 0, 5);
        }

        foreach ($dataColumns as $col) {
            if (count($found) >= 5) {
                break;
            }
            $label = $this->humanizeParts($col);
            if (!in_array($label, $found, true)) {
                $found[] = $label;
            }
        }

        return array_slice($found, 0, 5);
    }

    /**
     * @param list<string> $dataColumns
     */
    private function looksLikeLogTable(array $dataColumns): bool
    {
        $logHints = ['action', 'acao', 'message', 'mensagem', 'ip', 'user_agent', 'old_value', 'new_value', 'event', 'evento'];
        $hits = 0;
        foreach ($dataColumns as $col) {
            foreach ($logHints as $hint) {
                if (str_contains($col, $hint)) {
                    $hits++;
                    break;
                }
            }
        }

        return $hits >= 2;
    }

    /**
     * @param array<string, mixed> $col
     */
    public function describeColumn(string $columnName, array $col = [], ?string $referencedTable = null): string
    {
        $lower = strtolower($columnName);
        if (isset(self::COLUMN_EXACT[$lower])) {
            return self::COLUMN_EXACT[$lower];
        }

        if (str_ends_with($lower, '_id') && $lower !== 'uuid') {
            $refTable = $referencedTable ?? $this->guessTableFromFkColumn($lower);
            $entity = $refTable !== null
                ? $this->humanizeParts($this->stripTablePrefix($refTable))
                : $this->humanizeParts(substr($lower, 0, -3));

            return 'Referência a ' . $entity . ($refTable !== null ? " ({$refTable})" : '');
        }

        if (str_starts_with($lower, 'is_') || str_starts_with($lower, 'has_')) {
            $flag = $this->humanizeParts(substr($lower, 3));

            return 'Indica se ' . $flag;
        }

        if (str_ends_with($lower, '_at') || str_ends_with($lower, '_date') || str_ends_with($lower, '_data')) {
            return 'Data/hora de ' . $this->humanizeParts(preg_replace('/_(at|date|data)$/', '', $lower) ?? $lower);
        }

        if (str_ends_with($lower, '_by')) {
            return 'Responsável por ' . $this->humanizeParts(substr($lower, 0, -3));
        }

        $baseType = strtolower((string) ($col['base_type'] ?? $col['DATA_TYPE'] ?? ''));
        if (in_array($baseType, ['tinyint', 'bit'], true) && preg_match('/^(is_|has_|use_|active|enabled)/', $lower)) {
            return 'Indicador booleano — ' . $this->humanizeParts($lower);
        }

        if (in_array($baseType, ['json', 'text', 'mediumtext', 'longtext'], true)) {
            return 'Conteúdo em ' . strtoupper($baseType) . ' — ' . $this->humanizeParts($lower);
        }

        if (in_array($baseType, ['decimal', 'float', 'double'], true)) {
            return 'Valor numérico — ' . $this->humanizeParts($lower);
        }

        return $this->capitalizeFirst($this->humanizeParts($lower));
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function enrichTableRow(array $row, array $columns = [], array $foreignKeys = []): array
    {
        $tableName = (string) ($row['table_name'] ?? '');
        if ($columns !== []) {
            $row['table_comment'] = $this->describeTable($tableName, $columns, $foreignKeys);
            $row['description_generated'] = true;

            return $row;
        }

        $comment = trim((string) ($row['table_comment'] ?? ''));
        if ($comment === '') {
            $row['table_comment'] = $this->describeTable($tableName);
            $row['description_generated'] = true;
        }

        return $row;
    }

    /**
     * @param array<string, mixed> $col
     * @return array<string, mixed>
     */
    public function enrichColumn(array $col): array
    {
        $comment = trim((string) ($col['COLUMN_COMMENT'] ?? ''));
        if ($comment === '') {
            $name = (string) ($col['COLUMN_NAME'] ?? '');
            $col['COLUMN_COMMENT'] = $this->describeColumn(
                $name,
                $col,
                isset($col['relation_table']) && $col['relation_table'] !== null
                    ? (string) $col['relation_table']
                    : null
            );
            $col['description_generated'] = true;
        }

        return $col;
    }

    private function resolveModuleLabel(string $tableName): string
    {
        if ($this->moduleResolver !== null) {
            return $this->moduleResolver->resolve($tableName);
        }

        return '';
    }

    private function stripTablePrefix(string $tableName): string
    {
        $lower = strtolower($tableName);
        foreach (self::STRIP_PREFIXES as $prefix) {
            if (str_starts_with($lower, $prefix)) {
                return substr($tableName, strlen($prefix));
            }
        }

        return $tableName;
    }

    private function humanizeParts(string $name): string
    {
        $parts = array_values(array_filter(explode('_', strtolower($name)), static fn (string $p) => $p !== ''));
        if ($parts === []) {
            return $name;
        }
        if (count($parts) === 1) {
            return self::WORDS[$parts[0]] ?? $parts[0];
        }

        $head = array_pop($parts);
        $headTr = self::WORDS[$head] ?? $head;
        $modifier = [];
        foreach ($parts as $part) {
            $modifier[] = self::WORDS[$part] ?? $part;
        }

        return $headTr . ' de ' . implode(' ', $modifier);
    }

    /**
     * @param list<array<string, mixed>> $columns
     * @param list<array<string, mixed>> $foreignKeys
     * @return list<string>
     */
    private function extractFkEntities(array $columns, array $foreignKeys): array
    {
        $entities = [];
        foreach ($foreignKeys as $fk) {
            $ref = (string) ($fk['REFERENCED_TABLE_NAME'] ?? '');
            if ($ref !== '' && $ref !== 'adms_users') {
                $entities[$ref] = $this->stripTablePrefix($ref);
            }
        }

        if (count($entities) >= 2) {
            return array_values($entities);
        }

        foreach ($columns as $col) {
            $name = (string) ($col['COLUMN_NAME'] ?? '');
            if (!str_ends_with(strtolower($name), '_id') || strtolower($name) === 'id') {
                continue;
            }
            $ref = (string) ($col['relation_table'] ?? '');
            if ($ref === '') {
                $ref = $this->guessTableFromFkColumn(strtolower($name)) ?? '';
            }
            if ($ref !== '' && $ref !== 'adms_users') {
                $entities[$ref] = $this->stripTablePrefix($ref);
            }
        }

        return array_values($entities);
    }

    private function detectTableKind(string $coreName): string
    {
        $lower = strtolower($coreName);
        if (preg_match('/(^|_)(log|logs)($|_)/', $lower)) {
            return 'log';
        }
        if (str_contains($lower, 'settings') || str_contains($lower, 'config')) {
            return 'settings';
        }
        if (str_contains($lower, 'history') || str_contains($lower, 'historico')) {
            return 'history';
        }
        if (str_contains($lower, 'mapping') || str_contains($lower, 'mapeamento')) {
            return 'mapping';
        }
        $parts = explode('_', $lower);
        $idCount = 0;
        foreach ($parts as $part) {
            if ($part === 'id' || str_ends_with($part, 'id') && $part !== 'id') {
                $idCount++;
            }
        }
        if ($idCount >= 2 || substr_count($lower, '_') >= 3) {
            return 'default';
        }

        return 'default';
    }

    private function guessTableFromFkColumn(string $columnName): ?string
    {
        if (!str_ends_with($columnName, '_id')) {
            return null;
        }
        $base = substr($columnName, 0, -3);
        if ($base === '') {
            return null;
        }

        if (!str_ends_with($base, 's')) {
            return $base . 's';
        }

        return $base;
    }

    /**
     * @param list<string> $items
     */
    private function joinNatural(array $items): string
    {
        $items = array_values(array_filter($items, static fn (string $i) => $i !== ''));
        if ($items === []) {
            return '';
        }
        if (count($items) === 1) {
            return $items[0];
        }
        $last = array_pop($items);

        return implode(', ', $items) . ' e ' . $last;
    }

    private function capitalizeFirst(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
    }
}