<?php

declare(strict_types=1);

namespace App\adms\Models\Services\SapProjects;

use App\adms\Models\Services\SapReportApiService;
use Exception;
use InvalidArgumentException;

/**
 * Dashboard e relatórios do Gerenciamento de Projetos SAP B1 (consulta ao vivo).
 *
 * Cards e gráficos são agregados no PHP a partir das mesmas linhas de Q_PROJETOS e
 * Q_DOCUMENTOS que alimentam as tabelas: duas idas ao SAP e totais sempre iguais.
 */
class SapProjectsService
{
    private const PAGE_SIZE = 500;
    private const MAX_PAGES = 40;

    private const MESES = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
    ];

    /** @var array<string, string> */
    private const TYPES_PROJETOS = [
        'Chave' => 'int', 'NumProjeto' => 'int', 'NomeProjeto' => 'text', 'TipoCod' => 'text', 'Tipo' => 'text',
        'StatusCod' => 'text', 'Status' => 'text', 'RiscoCod' => 'text', 'NivelRisco' => 'text',
        'ComSubprojetosCod' => 'text', 'ComSubprojetos' => 'text',
        'CodigoPN' => 'text', 'NomePN' => 'text', 'PessoaContato' => 'text', 'Territorio' => 'text',
        'Vendedor' => 'text', 'Titular' => 'text', 'SetorIndustrial' => 'text', 'FilialId' => 'int',
        'Filial' => 'text', 'ProjetoFinanceiro' => 'text', 'NomeProjetoFinanceiro' => 'text',
        'DataInicio' => 'date', 'AnoInicio' => 'int', 'DataVencimento' => 'date', 'DataEncerramento' => 'date',
        'PercConcluido' => 'number', 'DiasAtraso' => 'int', 'QtdEtapas' => 'int', 'EtapasConcluidas' => 'int',
        'CustoPlanejadoEtapas' => 'money', 'QtdDocumentos' => 'int', 'QtdDocCancelados' => 'int',
        'ValorVinculado' => 'money', 'ValorNFEntrada' => 'money', 'ValorOutrosVinculos' => 'money',
        'ValorPedidosCompra' => 'money', 'ValorTotalNotas' => 'money',
        'Orcamento' => 'money', 'ResumoAbertoCP' => 'money', 'ResumoFaturadoCP' => 'money',
        'ResumoTotalCP' => 'money', 'ResumoDesvioCP' => 'money', 'ValorPotencial' => 'money',
        'ResumoFaturadoCR' => 'money', 'ResumoTotalCR' => 'money', 'CustoRealProduto' => 'money',
        'DesvioTotalOT' => 'money', 'ResumoDesatualizado' => 'text', 'QtdProblemas' => 'int',
        'ProblemasAbertos' => 'int', 'QtdAtividades' => 'int', 'AtividadesAbertas' => 'int',
        'QtdOrdensTrabalho' => 'int', 'QtdSubprojetos' => 'int', 'QtdAnexos' => 'int',
        'ObservacoesVisaoGeral' => 'text', 'ObservacoesAba' => 'text',
    ];

    /** @var array<string, string> */
    private const TYPES_PROJETO_CADASTRO = [
        'Chave' => 'int', 'PessoaContato' => 'text', 'Territorio' => 'text', 'Vendedor' => 'text',
        'SetorIndustrial' => 'text', 'NomeProjetoFinanceiro' => 'text', 'QtdEtapas' => 'int',
        'EtapasConcluidas' => 'int', 'CustoPlanejadoEtapas' => 'money', 'QtdProblemas' => 'int',
        'ProblemasAbertos' => 'int', 'QtdAtividades' => 'int', 'AtividadesAbertas' => 'int',
        'QtdOrdensTrabalho' => 'int', 'QtdSubprojetos' => 'int', 'QtdAnexos' => 'int',
        'ObservacoesVisaoGeral' => 'text', 'ObservacoesAba' => 'text',
    ];

    /** @var array<string, string> */
    private const TYPES_DOCUMENTOS = [
        'Chave' => 'int', 'NumProjeto' => 'int', 'NomeProjeto' => 'text', 'StatusCod' => 'text',
        'StatusProjeto' => 'text', 'TipoProjeto' => 'text', 'CodigoPN' => 'text', 'NomePN' => 'text',
        'AnoInicioProjeto' => 'int', 'LinhaVinculo' => 'int', 'Etapa' => 'int', 'ObjType' => 'int',
        'TipoDocumento' => 'text', 'DocEntry' => 'int', 'NumDocumento' => 'int', 'NumNF' => 'int',
        'DataDocumento' => 'date', 'AnoDocumento' => 'int', 'MesDocumento' => 'int', 'Fornecedor' => 'text',
        'NomeFornecedor' => 'text', 'StatusDocumento' => 'text', 'Cancelado' => 'text',
        'ValorNoProjeto' => 'money', 'ValorTotalNota' => 'money', 'DiferencaNotaProjeto' => 'money',
        'ConsiderarNoTotal' => 'text',
    ];

    /** @var array<string, string> */
    private const TYPES_ITENS = [
        'Chave' => 'int', 'NumProjeto' => 'int', 'NomeProjeto' => 'text', 'StatusProjeto' => 'text',
        'ObjType' => 'int', 'TipoDocumento' => 'text',
        'DocEntry' => 'int', 'NumDocumento' => 'int', 'NumNF' => 'int', 'DataDocumento' => 'date',
        'AnoDocumento' => 'int', 'Fornecedor' => 'text', 'NomeFornecedor' => 'text', 'Cancelado' => 'text',
        'LinhaNota' => 'int', 'CodigoItem' => 'text', 'DescricaoItem' => 'text', 'Quantidade' => 'number',
        'TotalLinha' => 'money', 'ProjetoFinanceiroLinha' => 'text', 'LinhaVinculada' => 'text',
        'ConsiderarNoTotal' => 'text',
    ];

    /** Relatórios: título, colunas exibidas (= exportadas) e colunas somadas no rodapé. */
    public const REPORTS = [
        'projetos' => [
            'title' => 'Projetos',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT],
            'columns' => [
                ['key' => 'NumProjeto', 'label' => 'Nº', 'type' => 'int'],
                ['key' => 'NomeProjeto', 'label' => 'Projeto', 'type' => 'text'],
                ['key' => 'Tipo', 'label' => 'Tipo', 'type' => 'text'],
                ['key' => 'Status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'NivelRisco', 'label' => 'Risco', 'type' => 'text'],
                ['key' => 'NomePN', 'label' => 'Cliente', 'type' => 'text'],
                ['key' => 'Filial', 'label' => 'Filial', 'type' => 'text'],
                ['key' => 'Titular', 'label' => 'Titular', 'type' => 'text'],
                ['key' => 'DataInicio', 'label' => 'Início', 'type' => 'date'],
                ['key' => 'DataVencimento', 'label' => 'Vencimento', 'type' => 'date'],
                ['key' => 'DiasAtraso', 'label' => 'Dias atraso', 'type' => 'int'],
                ['key' => 'PercConcluido', 'label' => '% concl.', 'type' => 'number'],
                ['key' => 'QtdDocumentos', 'label' => 'Docs', 'type' => 'int'],
                ['key' => 'ValorVinculado', 'label' => 'Valor vinculado', 'type' => 'money'],
                ['key' => 'ValorTotalNotas', 'label' => 'Total das notas', 'type' => 'money'],
                ['key' => 'Orcamento', 'label' => 'Orçamento', 'type' => 'money'],
                ['key' => 'ResumoDesatualizado', 'label' => 'Resumo desatualizado', 'type' => 'flag'],
            ],
            'totals' => ['ValorVinculado', 'Orcamento'],
        ],
        'documentos' => [
            'title' => 'Documentos por projeto',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT, SapProjectsSql::SCOPE_DOCUMENT],
            'columns' => [
                ['key' => 'TipoDocumento', 'label' => 'Tipo doc.', 'type' => 'text'],
                ['key' => 'NumDocumento', 'label' => 'Nº doc.', 'type' => 'int'],
                ['key' => 'NumNF', 'label' => 'Nº NF', 'type' => 'int'],
                ['key' => 'DataDocumento', 'label' => 'Data', 'type' => 'date'],
                ['key' => 'NomeFornecedor', 'label' => 'Parceiro', 'type' => 'text'],
                ['key' => 'StatusDocumento', 'label' => 'Status doc.', 'type' => 'text'],
                ['key' => 'ValorNoProjeto', 'label' => 'Valor no projeto', 'type' => 'money'],
                ['key' => 'ValorTotalNota', 'label' => 'Total da nota', 'type' => 'money'],
                ['key' => 'DiferencaNotaProjeto', 'label' => 'Diferença', 'type' => 'money'],
                ['key' => 'ConsiderarNoTotal', 'label' => 'No total', 'type' => 'text'],
            ],
            'totals' => ['ValorNoProjeto', 'ValorTotalNota'],
        ],
        'itens' => [
            'title' => 'Itens por projeto',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT, SapProjectsSql::SCOPE_DOCUMENT, SapProjectsSql::SCOPE_ITEM],
            'columns' => [
                ['key' => 'TipoDocumento', 'label' => 'Tipo doc.', 'type' => 'text'],
                ['key' => 'NumDocumento', 'label' => 'Nº doc.', 'type' => 'int'],
                ['key' => 'NumNF', 'label' => 'Nº NF', 'type' => 'int'],
                ['key' => 'DataDocumento', 'label' => 'Data', 'type' => 'date'],
                ['key' => 'NomeFornecedor', 'label' => 'Parceiro', 'type' => 'text'],
                ['key' => 'LinhaNota', 'label' => 'Linha', 'type' => 'int'],
                ['key' => 'CodigoItem', 'label' => 'Item', 'type' => 'text'],
                ['key' => 'DescricaoItem', 'label' => 'Descrição', 'type' => 'text'],
                ['key' => 'Quantidade', 'label' => 'Qtd.', 'type' => 'number'],
                ['key' => 'TotalLinha', 'label' => 'Total linha', 'type' => 'money'],
                ['key' => 'ProjetoFinanceiroLinha', 'label' => 'Proj. financeiro', 'type' => 'text'],
                ['key' => 'LinhaVinculada', 'label' => 'Linha vinculada', 'type' => 'text'],
                ['key' => 'ConsiderarNoTotal', 'label' => 'No total', 'type' => 'text'],
            ],
            'totals' => ['TotalLinha'],
        ],
        'periodo' => [
            'title' => 'Totais por período (data do documento)',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT, SapProjectsSql::SCOPE_DOCUMENT],
            'columns' => [
                ['key' => 'Periodo', 'label' => 'Período', 'type' => 'text'],
                ['key' => 'QtdDocumentos', 'label' => 'Documentos', 'type' => 'int'],
                ['key' => 'ValorNoProjeto', 'label' => 'Valor no projeto', 'type' => 'money'],
                ['key' => 'ValorTotalNotas', 'label' => 'Total das notas', 'type' => 'money'],
            ],
            'totals' => ['QtdDocumentos', 'ValorNoProjeto', 'ValorTotalNotas'],
        ],
        'status' => [
            'title' => 'Totais por status do projeto',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT],
            'columns' => [
                ['key' => 'Status', 'label' => 'Status', 'type' => 'text'],
                ['key' => 'QtdProjetos', 'label' => 'Projetos', 'type' => 'int'],
                ['key' => 'QtdDocumentos', 'label' => 'Documentos', 'type' => 'int'],
                ['key' => 'ValorVinculado', 'label' => 'Valor vinculado', 'type' => 'money'],
                ['key' => 'Orcamento', 'label' => 'Orçamento', 'type' => 'money'],
            ],
            'totals' => ['QtdProjetos', 'QtdDocumentos', 'ValorVinculado', 'Orcamento'],
        ],
        'ano_projeto' => [
            'title' => 'Carteira por ano de início do projeto',
            'scopes' => [SapProjectsSql::SCOPE_PROJECT],
            'columns' => [
                ['key' => 'AnoInicio', 'label' => 'Ano de início', 'type' => 'text'],
                ['key' => 'QtdProjetos', 'label' => 'Projetos', 'type' => 'int'],
                ['key' => 'ValorVinculado', 'label' => 'Valor vinculado', 'type' => 'money'],
                ['key' => 'Orcamento', 'label' => 'Orçamento', 'type' => 'money'],
            ],
            'totals' => ['QtdProjetos', 'ValorVinculado', 'Orcamento'],
        ],
    ];

    public const AVISO_ITENS = 'Itens listam as linhas dos documentos mapeados (compra/venda/estoque) vinculados ao projeto. Pedidos e solicitações costumam ficar Fechados quando a NF posterior é adicionada.';

    private SapProjectsSql $sql;
    private ?SapReportApiService $sap;
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(?SapReportApiService $sap = null, ?SapProjectsSql $sql = null)
    {
        $this->sap = $sap;
        $this->sql = $sql ?? new SapProjectsSql();
    }

    /** @return array<string, mixed> */
    public function options(): array
    {
        $projetos = $this->fetchAll($this->sql->opcoesProjetos());
        $documentos = [];
        foreach (array_keys(SapProjectsSql::DOC_SOURCES) as $objType) {
            try {
                foreach ($this->fetchAll($this->sql->opcoesDocumentos((int) $objType)) as $raw) {
                    $documentos[] = $raw;
                }
            } catch (\Throwable $e) {
                $this->warnings[] = 'Opções doc ObjType ' . $objType . ': ' . mb_substr($e->getMessage(), 0, 180);
            }
        }

        $out = [
            'projetos' => [], 'status' => [], 'tipos' => [], 'clientes' => [], 'filiais' => [],
            'anos_projeto' => [], 'anos_documento' => [], 'fornecedores' => [],
        ];
        foreach ($projetos as $raw) {
            $chave = $this->toInt($this->col($raw, 'Chave'));
            $out['projetos'][$chave] = [
                'value' => (string) $chave,
                'label' => trim($this->toInt($this->col($raw, 'NumProjeto')) . ' — ' . $this->toText($this->col($raw, 'NomeProjeto'))),
            ];
            $status = $this->toText($this->col($raw, 'StatusCod'));
            if ($status !== '') {
                $out['status'][$status] = ['value' => $status, 'label' => SapProjectsSql::label(SapProjectsSql::STATUS, $status)];
            }
            $tipo = $this->toText($this->col($raw, 'TipoCod'));
            if ($tipo !== '') {
                $out['tipos'][$tipo] = ['value' => $tipo, 'label' => SapProjectsSql::label(SapProjectsSql::TIPO, $tipo)];
            }
            $card = $this->toText($this->col($raw, 'CodigoPN'));
            if ($card !== '') {
                $out['clientes'][$card] = ['value' => $card, 'label' => $card . ' — ' . $this->toText($this->col($raw, 'NomePN'))];
            }
            $filial = $this->col($raw, 'FilialId');
            if ($filial !== null && $filial !== '') {
                $id = $this->toInt($filial);
                $nome = $this->toText($this->col($raw, 'Filial'));
                $out['filiais'][$id] = ['value' => (string) $id, 'label' => $nome !== '' ? $nome : 'Filial ' . $id];
            }
            $ano = $this->toInt($this->col($raw, 'AnoInicio'));
            if ($ano > 0) {
                $out['anos_projeto'][$ano] = ['value' => (string) $ano, 'label' => (string) $ano];
            }
        }
        foreach ($documentos as $raw) {
            $forn = $this->toText($this->col($raw, 'Fornecedor'));
            if ($forn !== '') {
                $out['fornecedores'][$forn] = ['value' => $forn, 'label' => $forn . ' — ' . $this->toText($this->col($raw, 'NomeFornecedor'))];
            }
            $ano = $this->toInt($this->col($raw, 'Ano'));
            if ($ano > 0) {
                $out['anos_documento'][$ano] = ['value' => (string) $ano, 'label' => (string) $ano];
            }
        }

        krsort($out['anos_projeto']);
        krsort($out['anos_documento']);
        foreach (['clientes', 'fornecedores', 'filiais'] as $key) {
            uasort($out[$key], static fn (array $a, array $b): int => strcasecmp($a['label'], $b['label']));
        }
        foreach ($out as $key => $list) {
            $out[$key] = array_values($list);
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function dashboard(array $input): array
    {
        $started = microtime(true);
        $filters = $this->sql->normalizeFilters($input);
        $projetos = $this->loadProjetoRows($filters);
        $documentos = $this->loadDocumentRows($filters);

        return [
            'filters' => $filters,
            'filters_text' => $this->describeFilters($filters),
            'doc_filters_only' => $this->hasDocumentFilters($filters),
            'kpis' => $this->kpis($projetos),
            'por_periodo' => $this->porPeriodo($documentos, 'mes'),
            'por_status' => $this->porStatus($projetos),
            'por_ano_projeto' => $this->porAnoProjeto($projetos),
            'projetos' => array_map(fn (array $row): array => $this->markRow($row), $projetos),
            'warnings' => $this->warnings,
            'generated_at' => date('Y-m-d H:i:s'),
            'execution_time' => round(microtime(true) - $started, 2),
        ];
    }

    /**
     * Ficha do projeto. Use $with para evitar dezenas de GETs no SAP:
     * - summary: cabeçalho + resumo PMG8/agregados (rápido; abertura do drawer)
     * - documents: + vínculos/parceiros sem itens
     * - documents,items | all: árvore completa (PDF / abas pesadas)
     *
     * @return array<string, mixed>
     */
    public function projectDetail(int $projectId, string $with = 'all'): array
    {
        if ($projectId < 1) {
            throw new InvalidArgumentException('Projeto inválido.');
        }
        $sections = $this->detailSections($with);
        $filters = ['projeto' => $projectId];
        $projetos = $this->loadProjetoRows($filters);
        if ($projetos === []) {
            throw new InvalidArgumentException('Projeto não encontrado no SAP.');
        }
        $projeto = $this->markRow($this->mergeProjetoCadastro($projetos[0], $projectId));
        if ((int) ($projeto['Chave'] ?? 0) !== $projectId) {
            throw new InvalidArgumentException('Projeto retornado não corresponde à seleção.');
        }

        $documentos = [];
        $itens = [];
        if ($sections['documents']) {
            $documentos = $this->loadDocumentRows($filters);
            $projeto = $this->reconcileProjectDocMetrics($projeto, $documentos);
            if ($sections['items']) {
                $types = $this->objTypesFromDocuments($documentos);
                $itens = $types === [] ? [] : $this->itensRows($filters, $types);
            }
        }
        $parceiros = $sections['documents']
            ? $this->groupDocumentsByPartner($documentos, $itens)
            : [];

        return [
            'projeto' => $projeto,
            'identificacao' => $this->buildProjectIdentificacao($projeto),
            'resumo' => $this->buildProjectResumo($projeto),
            'parceiros' => $parceiros,
            'totais' => $this->fichaTotais($parceiros),
            'documentos' => $this->table('documentos', $documentos),
            'itens' => $this->table('itens', $itens),
            'aviso_itens' => self::AVISO_ITENS,
            'loaded' => $sections,
            'warnings' => $this->warnings,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * @return array{summary: bool, documents: bool, items: bool}
     */
    private function detailSections(string $with): array
    {
        $with = strtolower(trim($with));
        if ($with === '' || $with === 'all' || $with === 'full') {
            return ['summary' => true, 'documents' => true, 'items' => true];
        }
        $parts = array_values(array_filter(array_map('trim', explode(',', $with))));
        $documents = in_array('documents', $parts, true)
            || in_array('docs', $parts, true)
            || in_array('tree', $parts, true);
        $items = in_array('items', $parts, true)
            || in_array('itens', $parts, true)
            || in_array('tree', $parts, true);
        if ($items) {
            $documents = true;
        }

        return ['summary' => true, 'documents' => $documents, 'items' => $items];
    }

    /**
     * @param list<array<string, mixed>> $documentos
     * @return list<int>
     */
    private function objTypesFromDocuments(array $documentos): array
    {
        $types = [];
        foreach ($documentos as $doc) {
            $type = (int) ($doc['ObjType'] ?? 0);
            if ($type > 0 && SapProjectsSql::documentSource($type) !== null) {
                $types[$type] = $type;
            }
        }

        return array_values($types);
    }

    /**
     * Dados cadastrais/cabeçalho do projeto selecionado (OPMG).
     *
     * @param array<string, mixed> $projeto
     * @return list<array{label: string, key: string, value: mixed, type: string}>
     */
    public function buildProjectIdentificacao(array $projeto): array
    {
        return $this->mapProjectFields($projeto, [
            ['Nº projeto', 'NumProjeto', 'int'],
            ['Nome', 'NomeProjeto', 'text'],
            ['Status', 'Status', 'text'],
            ['Tipo', 'Tipo', 'text'],
            ['Risco', 'NivelRisco', 'text'],
            ['Cliente', 'NomePN', 'text'],
            ['Código cliente', 'CodigoPN', 'text'],
            ['Filial', 'Filial', 'text'],
            ['Titular', 'Titular', 'text'],
            ['Vendedor', 'Vendedor', 'text'],
            ['Contato', 'PessoaContato', 'text'],
            ['Território', 'Territorio', 'text'],
            ['Setor industrial', 'SetorIndustrial', 'text'],
            ['Projeto financeiro', 'NomeProjetoFinanceiro', 'text'],
            ['Código proj. financeiro', 'ProjetoFinanceiro', 'text'],
            ['Início', 'DataInicio', 'date'],
            ['Vencimento', 'DataVencimento', 'date'],
            ['Encerramento', 'DataEncerramento', 'date'],
            ['% concluído', 'PercConcluido', 'number'],
            ['Dias em atraso', 'DiasAtraso', 'int'],
            ['Com subprojetos', 'ComSubprojetos', 'text'],
            ['Etapas', 'QtdEtapas', 'int'],
            ['Etapas concluídas', 'EtapasConcluidas', 'int'],
            ['Problemas abertos', 'ProblemasAbertos', 'int'],
            ['Atividades abertas', 'AtividadesAbertas', 'int'],
            ['Ordens de trabalho', 'QtdOrdensTrabalho', 'int'],
            ['Observações (visão geral)', 'ObservacoesVisaoGeral', 'text'],
            ['Observações (aba)', 'ObservacoesAba', 'text'],
        ]);
    }

    /**
     * Bloco Resumo SAP (PMG8) de um único projeto — não é agregado da carteira.
     *
     * @param array<string, mixed> $projeto
     * @return list<array{label: string, key: string, value: mixed, type: string}>
     */
    public function buildProjectResumo(array $projeto): array
    {
        return $this->mapProjectFields($projeto, [
            // PMG8 (aba Resumo do SAP)
            ['Orçamento (PMG8)', 'Orcamento', 'money'],
            ['Aberto C/P (PMG8)', 'ResumoAbertoCP', 'money'],
            ['Faturado C/P (PMG8)', 'ResumoFaturadoCP', 'money'],
            ['Total C/P (PMG8)', 'ResumoTotalCP', 'money'],
            ['Desvio C/P (orç. − gasto)', 'ResumoDesvioCP', 'variance'],
            ['Valor potencial (PMG8)', 'ValorPotencial', 'money'],
            ['Faturado C/R (PMG8)', 'ResumoFaturadoCR', 'money'],
            ['Total C/R (PMG8)', 'ResumoTotalCR', 'money'],
            ['Custo real produto', 'CustoRealProduto', 'money'],
            ['Desvio total OT', 'DesvioTotalOT', 'money'],
            // Vínculos PMG4 (separados: NF ≠ pedido/outros)
            ['Valor vinculado (todos)', 'ValorVinculado', 'money'],
            ['Pedidos de compra (vínculos)', 'ValorPedidosCompra', 'money'],
            ['Outros vínculos (não NF)', 'ValorOutrosVinculos', 'money'],
            ['NF entrada (sem impostos)', 'ValorNFEntrada', 'money'],
            ['Total DocTotal das NFs', 'ValorTotalNotas', 'money'],
            ['Documentos', 'QtdDocumentos', 'int'],
            ['Docs cancelados', 'QtdDocCancelados', 'int'],
            ['Resumo desatualizado', 'ResumoDesatualizado', 'flag'],
        ]);
    }

    /**
     * Recalcula métricas PMG4 a partir dos documentos enriquecidos da ficha
     * (cancela pedido/NF corretamente; DocTotal uma vez por DocEntry).
     *
     * @param array<string, mixed> $projeto
     * @param list<array<string, mixed>> $documentos
     * @return array<string, mixed>
     */
    private function reconcileProjectDocMetrics(array $projeto, array $documentos): array
    {
        $vinculado = 0.0;
        $nf = 0.0;
        $outros = 0.0;
        $pedidos = 0.0;
        $cancelados = 0;
        $docTotals = [];

        foreach ($documentos as $doc) {
            $canceled = in_array((string) ($doc['Cancelado'] ?? 'N'), ['Y', 'C'], true);
            if ($canceled) {
                $cancelados++;
                continue;
            }
            $valor = (float) ($doc['ValorNoProjeto'] ?? 0);
            $vinculado += $valor;
            $type = (int) ($doc['ObjType'] ?? 0);
            if ($type === 18) {
                $nf += $valor;
                $entry = (int) ($doc['DocEntry'] ?? 0);
                if ($entry > 0) {
                    $docTotals[$entry] = (float) ($doc['ValorTotalNota'] ?? 0);
                }
            } else {
                $outros += $valor;
                if ($type === 22) {
                    $pedidos += $valor;
                }
            }
        }

        $projeto['ValorVinculado'] = round($vinculado, 2);
        $projeto['ValorNFEntrada'] = round($nf, 2);
        $projeto['ValorOutrosVinculos'] = round($outros, 2);
        $projeto['ValorPedidosCompra'] = round($pedidos, 2);
        $projeto['ValorTotalNotas'] = round(array_sum($docTotals), 2);
        $projeto['QtdDocumentos'] = count($documentos);
        $projeto['QtdDocCancelados'] = $cancelados;
        $faturadoCp = round((float) ($projeto['ResumoFaturadoCP'] ?? 0), 2);
        $projeto['ResumoDesatualizado'] = abs($faturadoCp - round($nf, 2)) > 0.009 ? 'Sim' : 'Não';

        return $projeto;
    }

    /**
     * @param array<string, mixed> $projeto
     * @param list<array{0: string, 1: string, 2: string}> $fields
     * @return list<array{label: string, key: string, value: mixed, type: string, tone?: string}>
     */
    private function mapProjectFields(array $projeto, array $fields): array
    {
        $out = [];
        foreach ($fields as [$label, $key, $type]) {
            if (!array_key_exists($key, $projeto)) {
                continue;
            }
            $value = $projeto[$key];
            if ($value === null || $value === '') {
                continue;
            }
            if ($type === 'variance') {
                $amount = round((float) $value, 2);
                $tone = abs($amount) < 0.005 ? 'neutral' : ($amount > 0 ? 'good' : 'bad');
                $out[] = [
                    'label' => $label,
                    'key' => $key,
                    'value' => $amount,
                    'type' => 'money',
                    'tone' => $tone,
                ];
                continue;
            }
            $out[] = ['label' => $label, 'key' => $key, 'value' => $value, 'type' => $type];
        }

        return $out;
    }

    /**
     * Desvio C/P na convenção de negócio: orçamento − gasto.
     * Positivo = gastou menos que o orçado; negativo = estourou o orçamento.
     * Se Total C/P vier 0 mas Aberto+Faturado > 0, recompõe o total (como na tela SAP).
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function applyBudgetVarianceConvention(array $row): array
    {
        $aberto = round((float) ($row['ResumoAbertoCP'] ?? 0), 2);
        $faturado = round((float) ($row['ResumoFaturadoCP'] ?? 0), 2);
        $total = round((float) ($row['ResumoTotalCP'] ?? 0), 2);
        if (abs($total) < 0.005 && abs($aberto) + abs($faturado) >= 0.005) {
            $total = round($aberto + $faturado, 2);
            $row['ResumoTotalCP'] = $total;
        }
        $orcamento = round((float) ($row['Orcamento'] ?? 0), 2);
        $row['ResumoDesvioCP'] = round($orcamento - $total, 2);
        $nf = round((float) ($row['ValorNFEntrada'] ?? 0), 2);
        $row['ResumoDesatualizado'] = abs($faturado - $nf) > 0.009 ? 'Sim' : 'Não';

        return $row;
    }

    /**
     * Agrupa documentos por fornecedor e anexa itens de cada nota.
     *
     * @param list<array<string, mixed>> $documentos
     * @param list<array<string, mixed>> $itens
     * @return list<array<string, mixed>>
     */
    public function groupDocumentsByPartner(array $documentos, array $itens): array
    {
        $itensPorDoc = [];
        foreach ($itens as $item) {
            $docKey = $this->docGroupKey($item);
            $itensPorDoc[$docKey][] = $this->markRow($item);
        }

        $groups = [];
        foreach ($documentos as $doc) {
            $marked = $this->markRow($doc);
            $code = trim((string) ($marked['Fornecedor'] ?? ''));
            $name = trim((string) ($marked['NomeFornecedor'] ?? ''));
            $partnerKey = $code !== '' ? $code : ('nome:' . mb_strtolower($name !== '' ? $name : 'sem-fornecedor'));
            if (!isset($groups[$partnerKey])) {
                $groups[$partnerKey] = [
                    'Codigo' => $code,
                    'Nome' => $name !== '' ? $name : ($code !== '' ? $code : 'Sem fornecedor'),
                    'documentos' => [],
                    'ValorNoProjeto' => 0.0,
                    'ValorTotalNota' => 0.0,
                    'QtdDocumentos' => 0,
                ];
            }
            $docKey = $this->docGroupKey($marked);
            $marked['itens'] = $itensPorDoc[$docKey] ?? [];
            $groups[$partnerKey]['documentos'][] = $marked;
            $groups[$partnerKey]['QtdDocumentos']++;
            if (empty($marked['_excluido'])) {
                $groups[$partnerKey]['ValorNoProjeto'] += (float) ($marked['ValorNoProjeto'] ?? 0);
                $groups[$partnerKey]['ValorTotalNota'] += (float) ($marked['ValorTotalNota'] ?? 0);
            }
        }

        $list = array_values($groups);
        usort($list, static fn (array $a, array $b): int => strcasecmp((string) $a['Nome'], (string) $b['Nome']));
        foreach ($list as &$g) {
            $g['ValorNoProjeto'] = round((float) $g['ValorNoProjeto'], 2);
            $g['ValorTotalNota'] = round((float) $g['ValorTotalNota'], 2);
            usort(
                $g['documentos'],
                static function (array $a, array $b): int {
                    $da = (string) ($a['DataDocumento'] ?? '');
                    $db = (string) ($b['DataDocumento'] ?? '');

                    return $db <=> $da ?: ((int) ($b['NumDocumento'] ?? 0) <=> (int) ($a['NumDocumento'] ?? 0));
                }
            );
        }
        unset($g);

        return $list;
    }

    /**
     * @param list<array<string, mixed>> $parceiros
     * @return array{ValorNoProjeto: float, ValorTotalNota: float, QtdDocumentos: int, QtdParceiros: int}
     */
    private function fichaTotais(array $parceiros): array
    {
        $tot = ['ValorNoProjeto' => 0.0, 'ValorTotalNota' => 0.0, 'QtdDocumentos' => 0, 'QtdParceiros' => count($parceiros)];
        foreach ($parceiros as $p) {
            $tot['ValorNoProjeto'] += (float) ($p['ValorNoProjeto'] ?? 0);
            $tot['ValorTotalNota'] += (float) ($p['ValorTotalNota'] ?? 0);
            $tot['QtdDocumentos'] += (int) ($p['QtdDocumentos'] ?? 0);
        }
        $tot['ValorNoProjeto'] = round($tot['ValorNoProjeto'], 2);
        $tot['ValorTotalNota'] = round($tot['ValorTotalNota'], 2);

        return $tot;
    }

    /** @param array<string, mixed> $row */
    private function docGroupKey(array $row): string
    {
        $entry = (int) ($row['DocEntry'] ?? 0);
        if ($entry > 0) {
            return 'E' . $entry;
        }

        return 'N' . (int) ($row['NumDocumento'] ?? 0) . '-' . (int) ($row['NumNF'] ?? 0);
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function report(string $key, array $input): array
    {
        if (!isset(self::REPORTS[$key])) {
            throw new InvalidArgumentException('Relatório desconhecido.');
        }
        $started = microtime(true);
        $filters = $this->filtersForReport($key, $this->sql->normalizeFilters($input));

        $rows = match ($key) {
            'projetos' => $this->loadProjetoRows($filters),
            'documentos' => $this->loadDocumentRows($filters),
            'itens' => $this->itensRows($filters),
            'periodo' => $this->porPeriodo(
                $this->loadDocumentRows($filters),
                ($input['agrupar'] ?? 'mes') === 'ano' ? 'ano' : 'mes'
            ),
            'status' => $this->porStatus($this->loadProjetoRows($filters)),
            'ano_projeto' => $this->porAnoProjeto($this->loadProjetoRows($filters)),
        };

        $table = $this->table($key, $rows);
        $table['filters'] = $filters;
        $table['filters_text'] = $this->describeFilters($filters);
        $table['aviso'] = $key === 'itens' ? self::AVISO_ITENS : null;
        $table['warnings'] = $this->warnings;
        $table['generated_at'] = date('Y-m-d H:i:s');
        $table['execution_time'] = round(microtime(true) - $started, 2);

        return $table;
    }

    /**
     * @param array<string, int|string> $filters
     * @return list<string>
     */
    public function describeFilters(array $filters): array
    {
        $labels = [
            'projeto' => 'Projeto (chave)', 'status' => 'Status', 'tipo' => 'Tipo', 'cardcode' => 'Cliente',
            'filial' => 'Filial', 'ano_projeto' => 'Ano de início do projeto', 'data_de' => 'Documento de',
            'data_ate' => 'Documento até', 'fornecedor' => 'Fornecedor', 'item' => 'Item',
        ];
        $out = [];
        foreach ($filters as $name => $value) {
            $text = match ($name) {
                'status' => SapProjectsSql::label(SapProjectsSql::STATUS, $value),
                'tipo' => SapProjectsSql::label(SapProjectsSql::TIPO, $value),
                'data_de', 'data_ate' => $this->brDate((string) $value),
                default => (string) $value,
            };
            $out[] = ($labels[$name] ?? $name) . ': ' . $text;
        }

        return $out;
    }

    /**
     * Mantém só os filtros aplicáveis à consulta do relatório (seção 4 da especificação).
     *
     * @param array<string, int|string> $filters
     * @return array<string, int|string>
     */
    private function filtersForReport(string $key, array $filters): array
    {
        $scopes = self::REPORTS[$key]['scopes'];

        return array_filter(
            $filters,
            static fn (string $name): bool => in_array(SapProjectsSql::FILTERS[$name]['scope'], $scopes, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param array<string, int|string> $filters
     * @return list<array<string, mixed>>
     */
    private function loadProjetoRows(array $filters): array
    {
        $rows = $this->normalizeRows($this->fetchAll($this->sql->projetos($filters)), self::TYPES_PROJETOS);
        $rows = $this->collapseDuplicateProjetoRows($rows);
        foreach ($rows as &$row) {
            $row['Tipo'] = SapProjectsSql::label(SapProjectsSql::TIPO, $row['TipoCod'] ?? '');
            $row['Status'] = SapProjectsSql::label(SapProjectsSql::STATUS, $row['StatusCod'] ?? '');
            $row['NivelRisco'] = SapProjectsSql::label(SapProjectsSql::RISCO, $row['RiscoCod'] ?? '');
            $row['ComSubprojetos'] = (($row['ComSubprojetosCod'] ?? '') === 'Y') ? 'Sim' : 'Não';
            $row = $this->applyBudgetVarianceConvention($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * Se o JOIN de PMG8 ainda devolver mais de uma linha por AbsEntry, mantém uma e
     * preserva os maiores saldos C/P (evita ficha com faturado 0 e orçamento preenchido).
     *
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function collapseDuplicateProjetoRows(array $rows): array
    {
        $moneyKeys = [
            'Orcamento', 'ResumoAbertoCP', 'ResumoFaturadoCP', 'ResumoTotalCP', 'ResumoDesvioCP',
            'ValorPotencial', 'ResumoFaturadoCR', 'ResumoTotalCR', 'CustoRealProduto', 'DesvioTotalOT',
            'ValorVinculado', 'ValorNFEntrada', 'ValorOutrosVinculos', 'ValorPedidosCompra', 'ValorTotalNotas',
        ];
        $byChave = [];
        foreach ($rows as $row) {
            $chave = (int) ($row['Chave'] ?? 0);
            if ($chave < 1) {
                $byChave['_' . count($byChave)] = $row;
                continue;
            }
            if (!isset($byChave[$chave])) {
                $byChave[$chave] = $row;
                continue;
            }
            foreach ($moneyKeys as $key) {
                $byChave[$chave][$key] = max(
                    (float) ($byChave[$chave][$key] ?? 0),
                    (float) ($row[$key] ?? 0)
                );
            }
            if (($row['ResumoDesatualizado'] ?? '') === 'Não') {
                $byChave[$chave]['ResumoDesatualizado'] = 'Não';
            }
        }

        return array_values($byChave);
    }

    /**
     * @param array<string, mixed> $projeto
     * @return array<string, mixed>
     */
    private function mergeProjetoCadastro(array $projeto, int $projectId): array
    {
        try {
            $extra = $this->normalizeRows(
                $this->fetchAll($this->sql->projetoCadastro($projectId)),
                self::TYPES_PROJETO_CADASTRO
            );
        } catch (\Throwable $e) {
            $this->warnings[] = 'Cadastro do projeto: ' . mb_substr($e->getMessage(), 0, 180);

            return $projeto;
        }
        if ($extra === []) {
            return $projeto;
        }
        $cadastro = $extra[0];
        if ((int) ($cadastro['Chave'] ?? 0) !== $projectId) {
            return $projeto;
        }
        unset($cadastro['Chave']);

        return array_merge($projeto, $cadastro);
    }

    /**
     * Documentos PMG4 + cabeçalho do ObjType (consultas curtas; evita HTTP 414 no GET da API).
     *
     * @param array<string, int|string> $filters
     * @return list<array<string, mixed>>
     */
    private function loadDocumentRows(array $filters): array
    {
        $rows = $this->normalizeRows($this->fetchAll($this->sql->documentos($filters)), self::TYPES_DOCUMENTOS);
        foreach ($rows as &$row) {
            $row['TipoDocumento'] = SapProjectsSql::documentTypeLabel($row['ObjType'] ?? null);
            $row['StatusProjeto'] = SapProjectsSql::label(SapProjectsSql::STATUS, $row['StatusCod'] ?? '');
            // TipoProjeto vem do OPMG.TYP do projeto; a query ainda traz caseExpr no SQL antigo —
            // na query atual StatusProjeto/TipoProjeto usam caseExpr curto (STATUS/TIPO).
        }
        unset($row);
        $rows = $this->enrichDocumentRows($rows);
        if (isset($filters['fornecedor'])) {
            $code = (string) $filters['fornecedor'];
            $rows = array_values(array_filter(
                $rows,
                static fn (array $r): bool => (string) ($r['Fornecedor'] ?? '') === $code
            ));
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function enrichDocumentRows(array $rows): array
    {
        $byType = [];
        foreach ($rows as $i => $row) {
            $type = (int) ($row['ObjType'] ?? 0);
            $entry = (int) ($row['DocEntry'] ?? 0);
            if ($type > 0 && $entry > 0 && SapProjectsSql::documentSource($type) !== null) {
                $byType[$type][$entry][] = $i;
            }
        }

        foreach ($byType as $objType => $entries) {
            $ids = array_keys($entries);
            foreach (array_chunk($ids, 80) as $chunk) {
                try {
                    $headers = $this->fetchAll($this->sql->documentHeadersByEntries((int) $objType, $chunk));
                } catch (\Throwable $e) {
                    $this->warnings[] = 'Cabeçalho ObjType ' . $objType . ': ' . mb_substr($e->getMessage(), 0, 180);
                    continue;
                }
                $map = [];
                foreach ($headers as $raw) {
                    $map[$this->toInt($this->col($raw, 'DocEntry'))] = $raw;
                }
                foreach ($chunk as $entry) {
                    if (!isset($map[$entry])) {
                        continue;
                    }
                    $h = $map[$entry];
                    foreach ($entries[$entry] as $i) {
                        $rows[$i]['NumDocumento'] = $this->toInt($this->col($h, 'DocNum'));
                        $serial = $this->col($h, 'Serial');
                        $rows[$i]['NumNF'] = $serial === null || $serial === '' ? null : $this->toInt($serial);
                        $docDate = $this->toIsoDate($this->col($h, 'DocDate'));
                        if ($docDate !== null && $docDate !== '') {
                            $rows[$i]['DataDocumento'] = $docDate;
                            $rows[$i]['AnoDocumento'] = (int) substr($docDate, 0, 4);
                            $rows[$i]['MesDocumento'] = (int) substr($docDate, 5, 2);
                        }
                        $rows[$i]['Fornecedor'] = $this->toText($this->col($h, 'CardCode'));
                        $rows[$i]['NomeFornecedor'] = $this->toText($this->col($h, 'CardName'));
                        $status = $this->toText($this->col($h, 'DocStatus'));
                        $rows[$i]['StatusDocumento'] = $status !== ''
                            ? SapProjectsSql::label(SapProjectsSql::DOC_STATUS, $status)
                            : '';
                        $canceled = $this->toText($this->col($h, 'CANCELED'));
                        $rows[$i]['Cancelado'] = $canceled !== '' ? $canceled : 'N';
                        $rows[$i]['ValorTotalNota'] = $this->toFloat($this->col($h, 'DocTotal'));
                        $rows[$i]['DiferencaNotaProjeto'] = round(
                            (float) $rows[$i]['ValorTotalNota'] - (float) ($rows[$i]['ValorNoProjeto'] ?? 0),
                            2
                        );
                        $rows[$i]['ConsiderarNoTotal'] = in_array($rows[$i]['Cancelado'], ['Y', 'C'], true)
                            ? 'Não'
                            : 'Sim';
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * @param array<string, int|string> $filters
     * @param list<int>|null $objTypes null = todos os mapeados (relatórios); na ficha passe só os presentes
     * @return list<array<string, mixed>>
     */
    private function itensRows(array $filters, ?array $objTypes = null): array
    {
        if (!isset($filters['projeto']) && !isset($filters['data_de']) && !isset($filters['data_ate'])) {
            throw new InvalidArgumentException('Informe um projeto ou um período do documento para gerar o relatório de itens.');
        }

        $types = $objTypes ?? array_map('intval', array_keys(SapProjectsSql::DOC_SOURCES));
        $all = [];
        foreach ($types as $objType) {
            $objType = (int) $objType;
            if (SapProjectsSql::documentSource($objType) === null) {
                continue;
            }
            try {
                $chunk = $this->normalizeRows(
                    $this->fetchAll($this->sql->itens($filters, $objType)),
                    self::TYPES_ITENS
                );
                foreach ($chunk as $row) {
                    $all[] = $row;
                }
            } catch (\Throwable $e) {
                $this->warnings[] = 'Itens ObjType ' . $objType . ': ' . mb_substr($e->getMessage(), 0, 180);
            }
        }

        return $all;
    }

    /** @param array<string, int|string> $filters */
    private function hasDocumentFilters(array $filters): bool
    {
        foreach (array_keys($filters) as $name) {
            if (SapProjectsSql::FILTERS[$name]['scope'] !== SapProjectsSql::SCOPE_PROJECT) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{title: string, columns: list<array<string, string>>, rows: list<array<string, mixed>>, totals: array<string, float>, rows_count: int}
     */
    private function table(string $key, array $rows): array
    {
        $def = self::REPORTS[$key];
        $marked = array_map(fn (array $row): array => $this->markRow($row), $rows);
        $totals = [];
        foreach ($def['totals'] as $col) {
            $totals[$col] = 0.0;
        }
        foreach ($marked as $row) {
            if (!empty($row['_excluido'])) {
                continue;
            }
            foreach ($def['totals'] as $col) {
                $totals[$col] += (float) ($row[$col] ?? 0);
            }
        }

        return [
            'title' => $def['title'],
            'columns' => $def['columns'],
            'rows' => $marked,
            'totals' => array_map(static fn (float $v): float => round($v, 2), $totals),
            'rows_count' => count($marked),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function markRow(array $row): array
    {
        if (($row['ConsiderarNoTotal'] ?? 'Sim') === 'Não') {
            $row['_excluido'] = true;
        }
        if (str_starts_with((string) ($row['TipoDocumento'] ?? ''), 'Tipo não mapeado')) {
            $row['_aviso'] = true;
        }

        return $row;
    }

    /**
     * @param list<array<string, mixed>> $projetos
     * @return array<string, float|int>
     */
    private function kpis(array $projetos): array
    {
        $k = [
            'QtdProjetos' => count($projetos), 'QtdIniciados' => 0, 'QtdVencidos' => 0,
            'QtdResumoDesatualizado' => 0, 'QtdDocumentos' => 0, 'ValorVinculado' => 0.0,
            'ValorTotalNotas' => 0.0, 'Orcamento' => 0.0, 'ProblemasAbertos' => 0, 'AtividadesAbertas' => 0,
        ];
        foreach ($projetos as $p) {
            $k['QtdIniciados'] += $p['StatusCod'] === 'S' ? 1 : 0;
            $k['QtdVencidos'] += $p['DiasAtraso'] > 0 ? 1 : 0;
            $k['QtdResumoDesatualizado'] += $p['ResumoDesatualizado'] === 'Sim' ? 1 : 0;
            $k['QtdDocumentos'] += $p['QtdDocumentos'];
            $k['ValorVinculado'] += $p['ValorVinculado'];
            $k['ValorTotalNotas'] += $p['ValorTotalNotas'];
            $k['Orcamento'] += $p['Orcamento'];
            $k['ProblemasAbertos'] += $p['ProblemasAbertos'];
            $k['AtividadesAbertas'] += $p['AtividadesAbertas'];
        }
        foreach (['ValorVinculado', 'ValorTotalNotas', 'Orcamento'] as $money) {
            $k[$money] = round($k[$money], 2);
        }

        return $k;
    }

    /**
     * Q_POR_PERIODO: conta todos os documentos, soma só os que entram no total.
     *
     * @param list<array<string, mixed>> $documentos
     * @return list<array<string, mixed>>
     */
    private function porPeriodo(array $documentos, string $grain): array
    {
        $groups = [];
        foreach ($documentos as $d) {
            $ano = (int) ($d['AnoDocumento'] ?? 0);
            $mes = (int) ($d['MesDocumento'] ?? 0);
            if ($ano < 1) {
                $key = '9999-99';
                $label = 'Sem data';
            } elseif ($grain === 'ano') {
                $key = sprintf('%04d', $ano);
                $label = (string) $ano;
            } else {
                $key = sprintf('%04d-%02d', $ano, $mes);
                $label = (self::MESES[$mes] ?? (string) $mes) . '/' . $ano;
            }
            $groups[$key] ??= ['Periodo' => $label, 'QtdDocumentos' => 0, 'ValorNoProjeto' => 0.0, 'ValorTotalNotas' => 0.0];
            $groups[$key]['QtdDocumentos']++;
            if (($d['ConsiderarNoTotal'] ?? 'Sim') === 'Sim') {
                $groups[$key]['ValorNoProjeto'] += (float) $d['ValorNoProjeto'];
                $groups[$key]['ValorTotalNotas'] += (float) $d['ValorTotalNota'];
            }
        }
        ksort($groups);

        return array_values(array_map(static function (array $g): array {
            $g['ValorNoProjeto'] = round($g['ValorNoProjeto'], 2);
            $g['ValorTotalNotas'] = round($g['ValorTotalNotas'], 2);
            return $g;
        }, $groups));
    }

    /**
     * @param list<array<string, mixed>> $projetos
     * @return list<array<string, mixed>>
     */
    private function porStatus(array $projetos): array
    {
        $groups = [];
        foreach ($projetos as $p) {
            $key = $p['StatusCod'] !== '' ? $p['StatusCod'] : '-';
            $groups[$key] ??= [
                'StatusCod' => $key, 'Status' => $p['Status'] !== '' ? $p['Status'] : 'Sem status',
                'QtdProjetos' => 0, 'QtdDocumentos' => 0, 'ValorVinculado' => 0.0, 'Orcamento' => 0.0,
            ];
            $groups[$key]['QtdProjetos']++;
            $groups[$key]['QtdDocumentos'] += $p['QtdDocumentos'];
            $groups[$key]['ValorVinculado'] += $p['ValorVinculado'];
            $groups[$key]['Orcamento'] += $p['Orcamento'];
        }
        ksort($groups);

        return array_values($this->roundMoney($groups, ['ValorVinculado', 'Orcamento']));
    }

    /**
     * @param list<array<string, mixed>> $projetos
     * @return list<array<string, mixed>>
     */
    private function porAnoProjeto(array $projetos): array
    {
        $groups = [];
        foreach ($projetos as $p) {
            $ano = (int) $p['AnoInicio'];
            $key = $ano > 0 ? $ano : 9999;
            $groups[$key] ??= [
                'AnoInicio' => $ano > 0 ? (string) $ano : 'Sem data',
                'QtdProjetos' => 0, 'ValorVinculado' => 0.0, 'Orcamento' => 0.0,
            ];
            $groups[$key]['QtdProjetos']++;
            $groups[$key]['ValorVinculado'] += $p['ValorVinculado'];
            $groups[$key]['Orcamento'] += $p['Orcamento'];
        }
        ksort($groups);

        return array_values($this->roundMoney($groups, ['ValorVinculado', 'Orcamento']));
    }

    /**
     * @param array<array-key, array<string, mixed>> $groups
     * @param list<string> $columns
     * @return array<array-key, array<string, mixed>>
     */
    private function roundMoney(array $groups, array $columns): array
    {
        foreach ($groups as &$g) {
            foreach ($columns as $col) {
                $g[$col] = round((float) $g[$col], 2);
            }
        }
        unset($g);

        return $groups;
    }

    /**
     * Executa com paginação LIMIT/OFFSET (a API SAP corta respostas grandes).
     * As consultas base terminam em ORDER BY determinístico, então as páginas não se sobrepõem.
     *
     * @return list<array<string, mixed>>
     */
    private function fetchAll(string $sql): array
    {
        $sap = $this->sap ??= new SapReportApiService();
        $all = [];
        $offset = 0;
        $pages = 0;
        do {
            try {
                $result = $sap->execute($sql . ' LIMIT ' . self::PAGE_SIZE . ' OFFSET ' . $offset);
            } catch (Exception $e) {
                if ($offset > 0) {
                    throw $e;
                }
                $result = $sap->execute($sql);
                $page = is_array($result['data'] ?? null) ? $result['data'] : [];
                return array_values(array_filter($page, 'is_array'));
            }
            $page = is_array($result['data'] ?? null) ? $result['data'] : [];
            foreach ($page as $row) {
                if (is_array($row)) {
                    $all[] = $row;
                }
            }
            $offset += self::PAGE_SIZE;
            $pages++;
        } while (count($page) >= self::PAGE_SIZE && $pages < self::MAX_PAGES);

        if ($pages >= self::MAX_PAGES && count($page) >= self::PAGE_SIZE) {
            $this->warnings[] = 'Resultado limitado a ' . (self::PAGE_SIZE * self::MAX_PAGES)
                . ' linhas. Refine os filtros para ver tudo.';
        }

        return $all;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array<string, string> $types
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(array $rows, array $types): array
    {
        $out = [];
        foreach ($rows as $raw) {
            $row = [];
            foreach ($types as $name => $type) {
                $value = $this->col($raw, $name);
                $row[$name] = match ($type) {
                    'int' => $value === null || $value === '' ? null : $this->toInt($value),
                    'number', 'money' => round($this->toFloat($value), $type === 'money' ? 2 : 4),
                    'date' => $this->toIsoDate($value),
                    default => $this->toText($value),
                };
            }
            foreach (['DiasAtraso', 'QtdDocumentos', 'ProblemasAbertos', 'AtividadesAbertas', 'AnoInicio'] as $intCol) {
                if (array_key_exists($intCol, $row) && $row[$intCol] === null) {
                    $row[$intCol] = 0;
                }
            }
            $out[] = $row;
        }

        return $out;
    }

    /** @param array<string, mixed> $row */
    private function col(array $row, string $name): mixed
    {
        if (array_key_exists($name, $row)) {
            return $row[$name];
        }
        $lookup = strtolower($name);
        foreach ($row as $key => $value) {
            if (strtolower(trim((string) $key)) === $lookup) {
                return $value;
            }
        }

        return null;
    }

    private function toInt(mixed $value): int
    {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    }

    private function toFloat(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = str_replace(['.', ' '], '', (string) $value);
        $s = str_replace(',', '.', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function toText(mixed $value): string
    {
        return $value === null ? '' : trim((string) $value);
    }

    private function toIsoDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $s = substr(trim((string) $value), 0, 10);

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ? $s : null;
    }

    private function brDate(string $iso): string
    {
        $p = explode('-', $iso);

        return count($p) === 3 ? $p[2] . '/' . $p[1] . '/' . $p[0] : $iso;
    }
}
