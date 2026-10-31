<?php

declare(strict_types=1);

namespace App\adms\Models\Services\SapProjects;

use InvalidArgumentException;

/**
 * Consultas do Gerenciamento de Projetos SAP B1 (OPMG e filhas) — somente SELECT.
 *
 * A API SAP do Portal recebe SQL pronto (sem bind de parâmetros). Por isso os filtros
 * vêm de uma lista branca e cada valor é validado pelo tipo e renderizado como literal
 * seguro (inteiro, data TO_DATE, código restrito a [A-Za-z0-9._-/]).
 *
 * Regras de cálculo seguem docs/dashboard-projetos-sap-b1.md (seção 3); não alterar sem
 * reconferir os valores da seção 8.
 */
final class SapProjectsSql
{
    /** Dicionários de domínio: único lugar para incluir novos códigos (pendências 2, 3 e 6). */
    /** OPMG.STATUS (SDK SAP B1): S Started, P Paused, T Stopped, F Finished, N Canceled. */
    public const STATUS = [
        'S' => 'Iniciado',
        'P' => 'Pausado',
        'T' => 'Parado',
        'F' => 'Finalizado',
        'N' => 'Cancelado',
    ];
    public const TIPO = ['E' => 'Externo', 'I' => 'Interno'];
    public const RISCO = ['L' => 'Baixo', 'M' => 'Médio', 'H' => 'Alto'];
    public const DOC_STATUS = ['O' => 'Aberto', 'C' => 'Fechado'];

    /**
     * Rótulos de P4.TYP (ObjType) — lista do vínculo de documentos no Gerenciamento de Projetos.
     * Tipos sem entrada em DOC_SOURCES continuam com nome, mas sem enriquecimento de cabeçalho/itens.
     *
     * @var array<int, string>
     */
    public const DOC_TIPOS = [
        30 => 'Lançamento contábil manual',
        23 => 'Cotação de vendas',
        17 => 'Pedido de venda',
        15 => 'Entrega',
        16 => 'Devolução',
        203 => 'Adiantamento de cliente',
        13 => 'Nota Fiscal de Saída',
        14 => 'Dev. Nota Fiscal Saída',
        165 => 'NF de Saída de correção',
        166 => 'NF de Saída de correção: estorno',
        1470000113 => 'Solicitação de compra',
        540000006 => 'Oferta de compra',
        22 => 'Pedido de compra',
        20 => 'Recebimento de mercadorias',
        21 => 'Devolução de mercadorias',
        204 => 'Adiantamento para fornecedor',
        18 => 'Nota Fiscal de Entrada',
        19 => 'Dev. Nota Fiscal Entrada',
        163 => 'NF de Entrada de correção',
        164 => 'NF de Entrada de correção: estorno',
        191 => 'Chamado de serviço',
        59 => 'Entrada de mercadoria',
        60 => 'Saída de mercadorias',
        234000032 => 'Pedido de devolução de mercadorias',
        234000031 => 'Pedido de devolução',
    ];

    /**
     * Tabelas de consulta por ObjType (cabeçalho + linhas) para documentos com schema de marketing doc.
     * has_serial: campo Serial (nº NF) existe tipicamente em NFs.
     *
     * @var array<int, array{header: string, lines: string, has_serial: bool, side: string}>
     */
    public const DOC_SOURCES = [
        // Compra (principais)
        1470000113 => ['header' => 'OPRQ', 'lines' => 'PRQ1', 'has_serial' => false, 'side' => 'compra'],
        540000006 => ['header' => 'OPQT', 'lines' => 'PQT1', 'has_serial' => false, 'side' => 'compra'],
        22 => ['header' => 'OPOR', 'lines' => 'POR1', 'has_serial' => false, 'side' => 'compra'],
        20 => ['header' => 'OPDN', 'lines' => 'PDN1', 'has_serial' => false, 'side' => 'compra'],
        21 => ['header' => 'ORPD', 'lines' => 'RPD1', 'has_serial' => false, 'side' => 'compra'],
        204 => ['header' => 'ODPO', 'lines' => 'DPO1', 'has_serial' => true, 'side' => 'compra'],
        18 => ['header' => 'OPCH', 'lines' => 'PCH1', 'has_serial' => true, 'side' => 'compra'],
        19 => ['header' => 'ORPC', 'lines' => 'RPC1', 'has_serial' => true, 'side' => 'compra'],
        // Vendas (principais)
        23 => ['header' => 'OQUT', 'lines' => 'QUT1', 'has_serial' => false, 'side' => 'venda'],
        17 => ['header' => 'ORDR', 'lines' => 'RDR1', 'has_serial' => false, 'side' => 'venda'],
        15 => ['header' => 'ODLN', 'lines' => 'DLN1', 'has_serial' => false, 'side' => 'venda'],
        16 => ['header' => 'ORDN', 'lines' => 'RDN1', 'has_serial' => false, 'side' => 'venda'],
        203 => ['header' => 'ODPI', 'lines' => 'DPI1', 'has_serial' => true, 'side' => 'venda'],
        13 => ['header' => 'OINV', 'lines' => 'INV1', 'has_serial' => true, 'side' => 'venda'],
        14 => ['header' => 'ORIN', 'lines' => 'RIN1', 'has_serial' => true, 'side' => 'venda'],
        // Estoque
        59 => ['header' => 'OIGN', 'lines' => 'IGN1', 'has_serial' => false, 'side' => 'estoque'],
        60 => ['header' => 'OIGE', 'lines' => 'IGE1', 'has_serial' => false, 'side' => 'estoque'],
    ];

    public const SCOPE_PROJECT = 'project';
    public const SCOPE_DOCUMENT = 'document';
    public const SCOPE_ITEM = 'item';

    /**
     * Lista branca de filtros. {DOCDATE} é trocado pela expressão de data de cada consulta.
     *
     * @var array<string, array{type: string, scope: string, expr: string}>
     */
    public const FILTERS = [
        'projeto' => ['type' => 'int', 'scope' => self::SCOPE_PROJECT, 'expr' => 'T0."AbsEntry" = %s'],
        'status' => ['type' => 'char1', 'scope' => self::SCOPE_PROJECT, 'expr' => 'T0."STATUS" = %s'],
        'tipo' => ['type' => 'char1', 'scope' => self::SCOPE_PROJECT, 'expr' => 'T0."TYP" = %s'],
        'cardcode' => ['type' => 'code', 'scope' => self::SCOPE_PROJECT, 'expr' => 'T0."CARDCODE" = %s'],
        'filial' => ['type' => 'int', 'scope' => self::SCOPE_PROJECT, 'expr' => 'T0."BPLid" = %s'],
        'ano_projeto' => ['type' => 'year', 'scope' => self::SCOPE_PROJECT, 'expr' => 'YEAR(T0."START") = %s'],
        'data_de' => ['type' => 'date', 'scope' => self::SCOPE_DOCUMENT, 'expr' => '{DOCDATE} >= %s'],
        'data_ate' => ['type' => 'date', 'scope' => self::SCOPE_DOCUMENT, 'expr' => '{DOCDATE} <= %s'],
        'fornecedor' => ['type' => 'code', 'scope' => self::SCOPE_DOCUMENT, 'expr' => 'OP."CardCode" = %s'],
        'item' => ['type' => 'code', 'scope' => self::SCOPE_ITEM, 'expr' => 'L."ItemCode" = %s'],
    ];

    /** Data do vínculo em PMG4 (cabeçalho do doc é enriquecido depois no PHP — evita SQL gigante no GET). */
    private const DOCDATE_DOCUMENTOS = 'CAST(P4."DocDate" AS DATE)';
    /** Q_ITENS: data do cabeçalho do documento (OP alias). */
    private const DOCDATE_ITENS = 'CAST(OP."DocDate" AS DATE)';

    private string $schema;

    /**
     * Em produção o schema fica vazio: as tabelas vão sem prefixo e a API SAP
     * usa a base de dados já configurada nela. Só passe $schema em testes unitários.
     */
    public function __construct(?string $schema = null)
    {
        $schema = trim((string) ($schema ?? ''));
        if ($schema !== '' && !preg_match('/^[A-Za-z0-9_]{1,64}$/', $schema)) {
            throw new InvalidArgumentException('Schema SAP inválido.');
        }
        $this->schema = $schema;
    }

    /**
     * Valida e normaliza os filtros recebidos da tela. Vazios são ignorados.
     *
     * @param array<string, mixed> $input
     * @return array<string, int|string>
     */
    public function normalizeFilters(array $input): array
    {
        $out = [];
        foreach (self::FILTERS as $name => $def) {
            $raw = $input[$name] ?? null;
            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                continue;
            }
            if (!is_scalar($raw)) {
                throw new InvalidArgumentException('Filtro inválido: ' . $name . '.');
            }
            $out[$name] = $this->normalizeValue($name, $def['type'], trim((string) $raw));
        }
        if (isset($out['data_de'], $out['data_ate']) && $out['data_de'] > $out['data_ate']) {
            [$out['data_de'], $out['data_ate']] = [$out['data_ate'], $out['data_de']];
        }

        return $out;
    }

    /**
     * Listagem/dashboard/relatório — SQL curto (GET /query).
     * Rótulos Tipo/Status/Risco e cadastro completo da ficha são resolvidos no PHP.
     *
     * @param array<string, int|string> $filters
     */
    public function projetos(array $filters): string
    {
        $t = fn (string $name): string => $this->table($name);
        $where = $this->where($filters, [self::SCOPE_PROJECT], self::DOCDATE_DOCUMENTOS);

        return <<<SQL
SELECT
    T0."AbsEntry" AS "Chave",
    T0."DocNum" AS "NumProjeto",
    T0."NAME" AS "NomeProjeto",
    T0."TYP" AS "TipoCod",
    T0."STATUS" AS "StatusCod",
    T0."RISK" AS "RiscoCod",
    T0."WithPhases" AS "ComSubprojetosCod",
    T0."CARDCODE" AS "CodigoPN",
    T0."CARDNAME" AS "NomePN",
    T0."BPLid" AS "FilialId",
    BP."BPLName" AS "Filial",
    T0."FIPROJECT" AS "ProjetoFinanceiro",
    NULLIF(TRIM(COALESCE(EM."firstName",'')||' '||COALESCE(EM."lastName",'')),'') AS "Titular",
    CAST(T0."START" AS DATE) AS "DataInicio",
    YEAR(T0."START") AS "AnoInicio",
    CAST(T0."DUEDATE" AS DATE) AS "DataVencimento",
    CAST(T0."CLOSING" AS DATE) AS "DataEncerramento",
    T0."FINISHED" AS "PercConcluido",
    CASE WHEN T0."CLOSING" IS NULL AND T0."DUEDATE" IS NOT NULL AND CAST(T0."DUEDATE" AS DATE)<CURRENT_DATE
         THEN DAYS_BETWEEN(CAST(T0."DUEDATE" AS DATE),CURRENT_DATE) ELSE 0 END AS "DiasAtraso",
    IFNULL(V."QtdDocumentos",0) AS "QtdDocumentos",
    IFNULL(V."QtdDocCancelados",0) AS "QtdDocCancelados",
    IFNULL(V."ValorVinculado",0) AS "ValorVinculado",
    IFNULL(V."ValorNFEntrada",0) AS "ValorNFEntrada",
    IFNULL(V."ValorOutrosVinculos",0) AS "ValorOutrosVinculos",
    IFNULL(V."ValorPedidosCompra",0) AS "ValorPedidosCompra",
    IFNULL(TN."ValorTotalNotas",0) AS "ValorTotalNotas",
    IFNULL(NULLIF(R."PhBudget",0),IFNULL(R."AccPhBudg",0)) AS "Orcamento",
    IFNULL(R."OpenAmtAP",0) AS "ResumoAbertoCP",
    IFNULL(NULLIF(R."InvoicedAP",0),IFNULL(R."AccInvAP",0)) AS "ResumoFaturadoCP",
    IFNULL(NULLIF(R."TotalAP",0),IFNULL(R."AccTotAP",0)) AS "ResumoTotalCP",
    IFNULL(R."TotVarAP",0) AS "ResumoDesvioCP",
    IFNULL(R."PoPhAmt",0) AS "ValorPotencial",
    IFNULL(NULLIF(R."InvoicedAR",0),IFNULL(R."AccInvAR",0)) AS "ResumoFaturadoCR",
    IFNULL(NULLIF(R."TotalAR",0),IFNULL(R."AccTotAR",0)) AS "ResumoTotalCR",
    IFNULL(R."ActPrCost",0) AS "CustoRealProduto",
    IFNULL(R."TotalVar",0) AS "DesvioTotalOT",
    CASE WHEN ROUND(IFNULL(NULLIF(R."InvoicedAP",0),IFNULL(R."AccInvAP",0)),2)
              <>ROUND(IFNULL(V."ValorNFEntrada",0),2) THEN 'Sim' ELSE 'Não' END AS "ResumoDesatualizado"
FROM {$t('OPMG')} T0
LEFT OUTER JOIN {$t('OBPL')} BP ON BP."BPLId"=T0."BPLid"
LEFT OUTER JOIN {$t('OHEM')} EM ON EM."empID"=T0."OWNER"
LEFT OUTER JOIN (
    /* Uma linha por projeto (MIN LineID). PK do PMG8 é AbsEntry+LineID — JOIN solto zerava C/P. */
    SELECT P.*
    FROM {$t('PMG8')} P
    INNER JOIN (
        SELECT "AbsEntry", MIN("LineID") AS "LineID"
        FROM {$t('PMG8')}
        GROUP BY "AbsEntry"
    ) X ON X."AbsEntry"=P."AbsEntry" AND X."LineID"=P."LineID"
) R ON R."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT P4."AbsEntry",
        COUNT(*) AS "QtdDocumentos",
        SUM(CASE WHEN P4."TYP"=18 AND OP."CANCELED" IN ('Y','C') THEN 1 ELSE 0 END) AS "QtdDocCancelados",
        SUM(CASE WHEN P4."TYP"=18 AND OP."CANCELED" IN ('Y','C') THEN 0 ELSE IFNULL(P4."Total",0) END) AS "ValorVinculado",
        SUM(CASE WHEN P4."TYP"=18 AND IFNULL(OP."CANCELED",'N') NOT IN ('Y','C') THEN IFNULL(P4."Total",0) ELSE 0 END) AS "ValorNFEntrada",
        SUM(CASE WHEN P4."TYP"<>18 THEN IFNULL(P4."Total",0) ELSE 0 END) AS "ValorOutrosVinculos",
        SUM(CASE WHEN P4."TYP"=22 THEN IFNULL(P4."Total",0) ELSE 0 END) AS "ValorPedidosCompra"
    FROM {$t('PMG4')} P4
    LEFT OUTER JOIN {$t('OPCH')} OP ON P4."TYP"=18 AND OP."DocEntry"=P4."DocEntry"
    GROUP BY P4."AbsEntry"
) V ON V."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT X."AbsEntry", SUM(X."DocTotal") AS "ValorTotalNotas"
    FROM (
        SELECT P4."AbsEntry", P4."DocEntry", MAX(OP."DocTotal") AS "DocTotal"
        FROM {$t('PMG4')} P4
        INNER JOIN {$t('OPCH')} OP ON P4."TYP"=18 AND OP."DocEntry"=P4."DocEntry"
            AND IFNULL(OP."CANCELED",'N') NOT IN ('Y','C')
        GROUP BY P4."AbsEntry", P4."DocEntry"
    ) X
    GROUP BY X."AbsEntry"
) TN ON TN."AbsEntry"=T0."AbsEntry"
WHERE 1=1{$where}
ORDER BY T0."DocNum",T0."AbsEntry"
SQL;
    }

    /** Complemento da ficha (cadastro) — consulta curta por AbsEntry. */
    public function projetoCadastro(int $absEntry): string
    {
        if ($absEntry < 1) {
            throw new InvalidArgumentException('Projeto inválido.');
        }
        $t = fn (string $name): string => $this->table($name);

        return <<<SQL
SELECT
    T0."AbsEntry" AS "Chave",
    CP."Name" AS "PessoaContato",
    TE."descript" AS "Territorio",
    SL."SlpName" AS "Vendedor",
    ID."IndName" AS "SetorIndustrial",
    PR."PrjName" AS "NomeProjetoFinanceiro",
    IFNULL(E."QtdEtapas",0) AS "QtdEtapas",
    IFNULL(E."EtapasConcluidas",0) AS "EtapasConcluidas",
    IFNULL(E."CustoPlanejado",0) AS "CustoPlanejadoEtapas",
    IFNULL(P."QtdProblemas",0) AS "QtdProblemas",
    IFNULL(P."ProblemasAbertos",0) AS "ProblemasAbertos",
    IFNULL(A."QtdAtividades",0) AS "QtdAtividades",
    IFNULL(A."AtividadesAbertas",0) AS "AtividadesAbertas",
    IFNULL(W."QtdOrdensTrabalho",0) AS "QtdOrdensTrabalho",
    IFNULL(S."QtdSubprojetos",0) AS "QtdSubprojetos",
    IFNULL(X."QtdAnexos",0) AS "QtdAnexos",
    TO_NVARCHAR(T0."REASON") AS "ObservacoesVisaoGeral",
    TO_NVARCHAR(T0."Free_Text") AS "ObservacoesAba"
FROM {$t('OPMG')} T0
LEFT OUTER JOIN {$t('OCPR')} CP ON CP."CntctCode"=T0."CONTACT"
LEFT OUTER JOIN {$t('OTER')} TE ON TE."territryID"=T0."TERRITORY"
LEFT OUTER JOIN {$t('OSLP')} SL ON SL."SlpCode"=T0."EMPLOYEE"
LEFT OUTER JOIN {$t('OOND')} ID ON ID."IndCode"=T0."INDUSTRY"
LEFT OUTER JOIN {$t('OPRJ')} PR ON PR."PrjCode"=T0."FIPROJECT"
LEFT OUTER JOIN (
    SELECT "AbsEntry", COUNT(*) AS "QtdEtapas",
        SUM(CASE WHEN "FINISH"='Y' THEN 1 ELSE 0 END) AS "EtapasConcluidas",
        SUM(IFNULL("EXPCOSTS",0)) AS "CustoPlanejado"
    FROM {$t('PMG1')} GROUP BY "AbsEntry"
) E ON E."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT "AbsEntry", COUNT(*) AS "QtdProblemas",
        SUM(CASE WHEN IFNULL("CLOSED",'N')<>'Y' THEN 1 ELSE 0 END) AS "ProblemasAbertos"
    FROM {$t('PMG2')} GROUP BY "AbsEntry"
) P ON P."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT A6."AbsEntry", COUNT(*) AS "QtdAtividades",
        SUM(CASE WHEN CL."Closed"='N' THEN 1 ELSE 0 END) AS "AtividadesAbertas"
    FROM {$t('PMG6')} A6
    LEFT OUTER JOIN {$t('OCLG')} CL ON CL."ClgCode"=A6."ACTIVITYID"
    GROUP BY A6."AbsEntry"
) A ON A."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT "AbsEntry", COUNT(*) AS "QtdOrdensTrabalho" FROM {$t('PMG7')} GROUP BY "AbsEntry"
) W ON W."AbsEntry"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT "ProjectID", COUNT(*) AS "QtdSubprojetos" FROM {$t('OPHA')} GROUP BY "ProjectID"
) S ON S."ProjectID"=T0."AbsEntry"
LEFT OUTER JOIN (
    SELECT "AbsEntry", COUNT(*) AS "QtdAnexos" FROM {$t('ATC1')} GROUP BY "AbsEntry"
) X ON X."AbsEntry"=T0."AtcEntry"
WHERE T0."AbsEntry"={$absEntry}
SQL;
    }

    /**
     * Vínculos PMG4 + projeto (sem JOIN de todos os ObjTypes — enriquecimento no PHP).
     * Filtro fornecedor é aplicado após enriquecer o cabeçalho.
     *
     * @param array<string, int|string> $filters
     */
    public function documentos(array $filters): string
    {
        $t = fn (string $name): string => $this->table($name);
        $sqlFilters = $filters;
        unset($sqlFilters['fornecedor']);
        $where = $this->where(
            $sqlFilters,
            [self::SCOPE_PROJECT, self::SCOPE_DOCUMENT],
            self::DOCDATE_DOCUMENTOS
        );

        return <<<SQL
SELECT
    T0."AbsEntry" AS "Chave",
    T0."DocNum" AS "NumProjeto",
    T0."NAME" AS "NomeProjeto",
    T0."STATUS" AS "StatusCod",
    {$this->caseExpr('T0."STATUS"', self::STATUS)} AS "StatusProjeto",
    {$this->caseExpr('T0."TYP"', self::TIPO)} AS "TipoProjeto",
    T0."CARDCODE" AS "CodigoPN",
    T0."CARDNAME" AS "NomePN",
    YEAR(T0."START") AS "AnoInicioProjeto",
    P4."LineID" AS "LinhaVinculo",
    P4."StageID" AS "Etapa",
    P4."TYP" AS "ObjType",
    P4."DocEntry" AS "DocEntry",
    P4."DOCNUM" AS "NumDocumento",
    CAST(NULL AS INTEGER) AS "NumNF",
    CAST(P4."DocDate" AS DATE) AS "DataDocumento",
    YEAR(P4."DocDate") AS "AnoDocumento",
    MONTH(P4."DocDate") AS "MesDocumento",
    CAST(NULL AS NVARCHAR(50)) AS "Fornecedor",
    CAST(NULL AS NVARCHAR(200)) AS "NomeFornecedor",
    CAST(NULL AS NVARCHAR(20)) AS "StatusDocumento",
    CAST(NULL AS NVARCHAR(1)) AS "Cancelado",
    P4."Total" AS "ValorNoProjeto",
    CAST(NULL AS DECIMAL) AS "ValorTotalNota",
    CAST(0 AS DECIMAL) AS "DiferencaNotaProjeto",
    'Sim' AS "ConsiderarNoTotal"
FROM {$t('PMG4')} P4
INNER JOIN {$t('OPMG')} T0 ON T0."AbsEntry" = P4."AbsEntry"
WHERE 1 = 1{$where}
ORDER BY T0."DocNum", P4."AbsEntry", P4."LineID"
SQL;
    }

    /**
     * Itens de um único ObjType (consulta curta para GET /query).
     *
     * @param array<string, int|string> $filters
     */
    public function itens(array $filters, ?int $objType = null): string
    {
        $objType ??= 18;
        $src = self::DOC_SOURCES[$objType] ?? null;
        if ($src === null) {
            throw new InvalidArgumentException('ObjType sem tabelas de itens mapeadas: ' . $objType);
        }
        $where = $this->where(
            $filters,
            [self::SCOPE_PROJECT, self::SCOPE_DOCUMENT, self::SCOPE_ITEM],
            self::DOCDATE_ITENS
        );

        return $this->itensSelectForType($objType, $src, $where)
            . "\nORDER BY T0.\"DocNum\", OP.\"DocNum\", L.\"LineNum\"";
    }

    /**
     * Cabeçalhos de um ObjType por lista de DocEntry (enriquecimento pós-consulta).
     *
     * @param list<int> $docEntries
     */
    public function documentHeadersByEntries(int $objType, array $docEntries): string
    {
        $src = self::DOC_SOURCES[$objType] ?? null;
        if ($src === null) {
            throw new InvalidArgumentException('ObjType sem tabela de cabeçalho: ' . $objType);
        }
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($v): int => (int) $v, $docEntries),
            static fn (int $v): bool => $v > 0
        )));
        if ($ids === []) {
            throw new InvalidArgumentException('Nenhum DocEntry para enriquecer.');
        }
        $serial = !empty($src['has_serial']) ? '"Serial"' : 'CAST(NULL AS NVARCHAR(32))';
        $inList = implode(',', $ids);

        return 'SELECT "DocEntry", "DocNum", ' . $serial . ' AS "Serial",'
            . ' "DocDate", "CardCode", "CardName", "DocStatus", "CANCELED", "DocTotal"'
            . ' FROM ' . $this->table($src['header'])
            . ' WHERE "DocEntry" IN (' . $inList . ')';
    }

    /** Combos de projeto, status, cliente, filial e ano de início numa única ida ao SAP. */
    public function opcoesProjetos(): string
    {
        return 'SELECT T0."AbsEntry" AS "Chave", T0."DocNum" AS "NumProjeto", T0."NAME" AS "NomeProjeto",'
            . ' T0."STATUS" AS "StatusCod", T0."TYP" AS "TipoCod",'
            . ' T0."CARDCODE" AS "CodigoPN", T0."CARDNAME" AS "NomePN",'
            . ' T0."BPLid" AS "FilialId", BP."BPLName" AS "Filial", YEAR(T0."START") AS "AnoInicio"'
            . ' FROM ' . $this->table('OPMG') . ' T0'
            . ' LEFT OUTER JOIN ' . $this->table('OBPL') . ' BP ON BP."BPLId" = T0."BPLid"'
            . ' ORDER BY T0."DocNum"';
    }

    /**
     * Combos de parceiro/ano a partir de um ObjType (chamadas curtas no PHP agregam os tipos).
     */
    public function opcoesDocumentos(int $objType = 18): string
    {
        $src = self::DOC_SOURCES[$objType] ?? null;
        if ($src === null) {
            throw new InvalidArgumentException('ObjType sem tabela para opções: ' . $objType);
        }

        return 'SELECT OP."CardCode" AS "Fornecedor", MAX(OP."CardName") AS "NomeFornecedor",'
            . ' YEAR(OP."DocDate") AS "Ano"'
            . ' FROM ' . $this->table('PMG4') . ' P4'
            . ' INNER JOIN ' . $this->table($src['header']) . ' OP'
            . ' ON P4."TYP" = ' . (int) $objType . ' AND OP."DocEntry" = P4."DocEntry"'
            . ' WHERE OP."CardCode" IS NOT NULL'
            . ' GROUP BY OP."CardCode", YEAR(OP."DocDate")'
            . ' ORDER BY 2';
    }

    /**
     * Resolve cabeçalho/linhas a partir do ObjType (P4.TYP).
     *
     * @return array{header: string, lines: string, has_serial: bool, side: string}|null
     */
    public static function documentSource(int $objType): ?array
    {
        return self::DOC_SOURCES[$objType] ?? null;
    }

    /** @return list<int> */
    public static function mappedObjTypes(): array
    {
        return array_map('intval', array_keys(self::DOC_SOURCES));
    }

    public static function label(array $dictionary, mixed $code): string
    {
        $key = is_string($code) ? trim($code) : $code;
        if ($key === null || $key === '') {
            return '';
        }

        return (string) ($dictionary[$key] ?? $key);
    }

    public static function documentTypeLabel(int|string|null $objType): string
    {
        if ($objType === null || $objType === '') {
            return '';
        }
        $code = (int) $objType;

        return self::DOC_TIPOS[$code] ?? ('Tipo não mapeado (' . $code . ')');
    }

    /**
     * @param array<string, int|string> $filters
     * @param list<string> $scopes
     */
    private function where(array $filters, array $scopes, string $docDateExpr): string
    {
        $parts = [];
        foreach ($filters as $name => $value) {
            $def = self::FILTERS[$name] ?? null;
            if ($def === null || !in_array($def['scope'], $scopes, true)) {
                continue;
            }
            $expr = str_replace('{DOCDATE}', $docDateExpr, $def['expr']);
            $parts[] = sprintf($expr, $this->literal($def['type'], $value));
        }

        return $parts === [] ? '' : "\n  AND " . implode("\n  AND ", $parts);
    }

    private function normalizeValue(string $name, string $type, string $raw): int|string
    {
        $invalid = static fn (): InvalidArgumentException
            => new InvalidArgumentException('Valor inválido para o filtro ' . $name . '.');

        switch ($type) {
            case 'int':
                if (!preg_match('/^\d{1,9}$/', $raw)) {
                    throw $invalid();
                }
                return (int) $raw;
            case 'year':
                if (!preg_match('/^\d{4}$/', $raw) || (int) $raw < 1990 || (int) $raw > 2100) {
                    throw $invalid();
                }
                return (int) $raw;
            case 'char1':
                if (!preg_match('/^[A-Za-z]$/', $raw)) {
                    throw $invalid();
                }
                return strtoupper($raw);
            case 'code':
                if (!preg_match('~^[A-Za-z0-9._/\-]{1,50}$~', $raw)) {
                    throw $invalid();
                }
                return $raw;
            case 'date':
                $dt = \DateTimeImmutable::createFromFormat('!Y-m-d', $raw);
                if (!$dt instanceof \DateTimeImmutable || $dt->format('Y-m-d') !== $raw) {
                    throw $invalid();
                }
                return $raw;
        }

        throw $invalid();
    }

    private function literal(string $type, int|string $value): string
    {
        return match ($type) {
            'int', 'year' => (string) (int) $value,
            'date' => "TO_DATE('" . $value . "', 'YYYY-MM-DD')",
            default => "'" . str_replace("'", "''", (string) $value) . "'",
        };
    }

    /** @param array<string|int, string> $dictionary */
    private function caseExpr(string $column, array $dictionary): string
    {
        $sql = 'CASE ' . $column;
        foreach ($dictionary as $code => $label) {
            $sql .= " WHEN '" . $code . "' THEN '" . str_replace("'", "''", $label) . "'";
        }

        return $sql . ' ELSE ' . $column . ' END';
    }

    private function docTypeExpr(string $column): string
    {
        $sql = 'CASE ' . $column;
        foreach (self::DOC_TIPOS as $code => $label) {
            $sql .= ' WHEN ' . (int) $code . " THEN '" . str_replace("'", "''", $label) . "'";
        }

        return $sql . " ELSE 'Tipo não mapeado (' || TO_NVARCHAR(" . $column . ") || ')' END";
    }

    /**
     * @param array{header: string, lines: string, has_serial: bool, side: string} $src
     */
    private function itensSelectForType(int $objType, array $src, string $where): string
    {
        $t = fn (string $name): string => $this->table($name);
        $serial = !empty($src['has_serial']) ? 'OP."Serial"' : 'CAST(NULL AS NVARCHAR(32))';
        $tipoLabel = str_replace("'", "''", self::DOC_TIPOS[$objType] ?? ('ObjType ' . $objType));

        return <<<SQL
SELECT
    T0."AbsEntry" AS "Chave",
    T0."DocNum" AS "NumProjeto",
    T0."NAME" AS "NomeProjeto",
    {$this->caseExpr('T0."STATUS"', self::STATUS)} AS "StatusProjeto",
    {$objType} AS "ObjType",
    '{$tipoLabel}' AS "TipoDocumento",
    OP."DocEntry" AS "DocEntry",
    OP."DocNum" AS "NumDocumento",
    {$serial} AS "NumNF",
    CAST(OP."DocDate" AS DATE) AS "DataDocumento",
    YEAR(OP."DocDate") AS "AnoDocumento",
    OP."CardCode" AS "Fornecedor",
    OP."CardName" AS "NomeFornecedor",
    OP."CANCELED" AS "Cancelado",
    L."LineNum" AS "LinhaNota",
    L."ItemCode" AS "CodigoItem",
    L."Dscription" AS "DescricaoItem",
    L."Quantity" AS "Quantidade",
    L."LineTotal" AS "TotalLinha",
    L."Project" AS "ProjetoFinanceiroLinha",
    CASE
        WHEN EXISTS (
            SELECT 1 FROM {$t('PMG4')} X4
            WHERE X4."AbsEntry" = VN."AbsEntry"
              AND X4."DocEntry" = VN."DocEntry"
              AND X4."TYP" = {$objType}
              AND X4."LineNum" = L."LineNum"
        ) THEN 'Sim'
        ELSE 'Não'
    END AS "LinhaVinculada",
    CASE WHEN OP."CANCELED" IN ('Y', 'C') THEN 'Não' ELSE 'Sim' END AS "ConsiderarNoTotal"
FROM (
    SELECT "AbsEntry", "DocEntry"
    FROM {$t('PMG4')}
    WHERE "TYP" = {$objType}
    GROUP BY "AbsEntry", "DocEntry"
) VN
INNER JOIN {$t('OPMG')} T0 ON T0."AbsEntry" = VN."AbsEntry"
INNER JOIN {$t($src['header'])} OP ON OP."DocEntry" = VN."DocEntry"
INNER JOIN {$t($src['lines'])} L ON L."DocEntry" = OP."DocEntry"
WHERE 1 = 1{$where}
SQL;
    }

    private function table(string $name): string
    {
        return $this->schema === ''
            ? '"' . $name . '"'
            : '"' . $this->schema . '"."' . $name . '"';
    }
}
