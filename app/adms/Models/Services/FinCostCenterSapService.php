<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Indicadores de centros de custo SAP B1 (Dimensão 1).
 *
 * Consulta JDT1/OACT (contas 4. e 5.) com rateio OOCR/OCR1/OPRC.
 * Agrega no PHP o relatório hierárquico (unidade → centro → conta).
 * Orçamento ainda não é cadastrado: a coluna fica vazia para evolução futura.
 */
class FinCostCenterSapService
{
    public const MAX_DAYS = 366;

    public const PERIODOS = [
        'mes_atual' => 'Mês atual',
        'mes_anterior' => 'Mês anterior',
        '3' => 'Últimos 3 meses',
        '6' => 'Últimos 6 meses',
        'ano_atual' => 'Ano atual (YTD)',
        'personalizado' => 'Personalizado…',
    ];

    private const MESES_PT = [
        1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
        7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
    ];

    /** Rótulos da visão Equipes (sufixo do centro, cruzando unidades). */
    private const EQUIPE_NOMES = [
        'TI' => 'Tecnologia da Informação',
        'CMP' => 'Compras',
        'DIR' => 'Diretoria',
        'CQ' => 'Controle de Qualidade',
        'AR' => 'Assuntos Regulatórios',
        'COM' => 'Comercial',
        'FIN' => 'Financeiro',
        'RH' => 'Recursos Humanos',
        'PRD' => 'Produção',
        'DA' => 'Administrativo',
        'LOG' => 'Logística',
        'MKT' => 'Marketing',
        'PCP' => 'PCP',
        'ENG' => 'Engenharia',
        'MAN' => 'Manutenção',
        'ALM' => 'Almoxarifado',
        'FIS' => 'Fiscal',
        'CON' => 'Contábil',
        'JUR' => 'Jurídico',
        'EXP' => 'Expedição',
        'QLD' => 'Qualidade',
        'REVISAR' => 'Pendente / revisar',
        'OUTROS' => 'Outros',
    ];

    private SapReportApiService $sap;

    public function __construct(?SapReportApiService $sap = null)
    {
        $this->sap = $sap ?? new SapReportApiService();
    }

    /**
     * @param array<string, mixed> $get
     * @return array{chave:string,from:string,to:string,aviso:?string}
     */
    public function resolvePeriod(array $get): array
    {
        $chave = (string) ($get['periodo'] ?? '3');
        if (!isset(self::PERIODOS[$chave])) {
            $chave = '3';
        }
        $today = new DateTimeImmutable('today');
        $aviso = null;

        switch ($chave) {
            case 'mes_atual':
                $from = $today->modify('first day of this month');
                $to = $today;
                break;
            case 'mes_anterior':
                $from = $today->modify('first day of last month');
                $to = $today->modify('last day of last month');
                break;
            case '3':
                $to = $today;
                $from = $today->modify('-2 months')->modify('first day of this month');
                break;
            case '6':
                $to = $today;
                $from = $today->modify('-5 months')->modify('first day of this month');
                break;
            case 'ano_atual':
                $from = $today->setDate((int) $today->format('Y'), 1, 1);
                $to = $today;
                break;
            case 'personalizado':
                $from = $this->parseDate((string) ($get['data_inicio'] ?? ''));
                $to = $this->parseDate((string) ($get['data_fim'] ?? ''));
                if ($from === null || $to === null) {
                    $chave = '3';
                    $from = $today->modify('-2 months')->modify('first day of this month');
                    $to = $today;
                    $aviso = 'Datas personalizadas inválidas. Ajustado para os últimos 3 meses.';
                }
                break;
            default:
                $to = $today;
                $from = $today->modify('-2 months')->modify('first day of this month');
                break;
        }

        if ($to < $from) {
            [$from, $to] = [$to, $from];
        }

        $days = (int) $from->diff($to)->format('%a') + 1;
        if ($days > self::MAX_DAYS) {
            $to = $from->add(new DateInterval('P' . (self::MAX_DAYS - 1) . 'D'));
            $aviso = 'A consulta ao SAP está limitada a ' . self::MAX_DAYS
                . ' dias. O fim do período foi ajustado para ' . $to->format('d/m/Y') . '.';
        }

        return [
            'chave' => $chave,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'aviso' => $aviso,
        ];
    }

    /**
     * @return array{
     *   tree: list<array<string, mixed>>,
     *   arvore: list<array<string, mixed>>,
     *   kpis: array<string, mixed>,
     *   unidades: list<string>,
     *   centros: list<array{codigo:string,nome:string,unidade:string}>,
     *   chart: array{labels: list<string>, valores: list<float>},
     *   serie_mensal: array{labels: list<string>, total: list<float>, alocado: list<float>, pendente: list<float>, chaves: list<string>},
     *   stacked_unidades: array{labels: list<string>, datasets: list<array{label:string,data:list<float>}>},
     *   ranking: list<array<string, mixed>>,
     *   ranking_equipes: list<array<string, mixed>>,
     *   arvore_equipes: list<array<string, mixed>>,
     *   matrix: array{meses: list<array{chave:string,label:string}>, linhas: list<array<string, mixed>>, totais: list<float>},
     *   lancamentos: list<array<string, mixed>>,
     *   unidade: string,
     *   centro: string,
     *   execution_time: float,
     *   rows_count: int
     * }
     */
    public function getDashboardData(string $from, string $to, string $unidade, string $centro, bool $includeDetails): array
    {
        $fromDt = $this->parseDate($from);
        $toDt = $this->parseDate($to);
        if ($fromDt === null || $toDt === null) {
            throw new InvalidArgumentException('Período inválido.');
        }

        $execTime = 0.0;
        $grain = [];
        foreach ($this->monthWindows($fromDt, $toDt) as [$chunkFrom, $chunkTo]) {
            $result = $this->sap->execute($this->sqlResumo($chunkFrom, $chunkTo));
            $execTime += (float) ($result['execution_time'] ?? 0);
            $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
            $grain = array_merge($grain, $this->normalizeGrain($raw, $chunkFrom->format('Y-m')));
        }
        $grain = $this->mergeGrain($grain);
        $unidades = $this->uniqueUnidades($grain);
        $unidade = trim($unidade);
        $centro = trim($centro);
        if ($unidade !== '' && !in_array($unidade, $unidades, true)) {
            $unidade = '';
        }
        if ($unidade !== '') {
            $grain = array_values(array_filter(
                $grain,
                static fn(array $row): bool => $row['unidade'] === $unidade
            ));
        }
        $centros = $this->uniqueCentros($grain);
        $codigos = array_column($centros, 'codigo');
        if ($centro !== '' && !in_array($centro, $codigos, true)) {
            $centro = '';
        }
        if ($centro !== '') {
            $grain = array_values(array_filter(
                $grain,
                static fn(array $row): bool => $row['centro'] === $centro
            ));
        }

        $meses = $this->monthKeys($fromDt, $toDt);
        $tree = $this->buildTree($grain);
        $arvore = $this->buildArvore($grain);
        $kpis = $this->buildKpis($grain, $meses);
        $chart = $this->buildChart($grain);
        $serie = $this->buildSerieMensal($grain, $meses);
        $stacked = $this->buildStackedUnidades($grain, $meses);
        $ranking = $this->buildRanking($grain);
        $rankingEquipes = $this->buildRankingEquipes($grain);
        $arvoreEquipes = $this->buildArvoreEquipes($grain);
        $matrix = $this->buildMatrix($grain, $meses);

        $lancamentos = [];
        if ($includeDetails) {
            foreach ($this->monthWindows($fromDt, $toDt) as [$chunkFrom, $chunkTo]) {
                $detailResult = $this->sap->execute($this->sqlLancamentos($chunkFrom, $chunkTo));
                $execTime += (float) ($detailResult['execution_time'] ?? 0);
                $lancamentos = array_merge(
                    $lancamentos,
                    $this->normalizeLancamentos(
                        is_array($detailResult['data'] ?? null) ? $detailResult['data'] : [],
                        $unidade,
                        $centro
                    )
                );
            }
        }

        return [
            'tree' => $tree,
            'arvore' => $arvore,
            'kpis' => $kpis,
            'unidades' => $unidades,
            'centros' => $centros,
            'chart' => $chart,
            'serie_mensal' => $serie,
            'stacked_unidades' => $stacked,
            'ranking' => $ranking,
            'ranking_equipes' => $rankingEquipes,
            'arvore_equipes' => $arvoreEquipes,
            'matrix' => $matrix,
            'lancamentos' => $lancamentos,
            'unidade' => $unidade,
            'centro' => $centro,
            'execution_time' => $execTime,
            'rows_count' => count($grain),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeGrain(array $rows, string $anoMes): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $status = (string) $this->col($row, 'Status');
            $regra = (string) $this->col($row, 'Regra');
            $debito = $this->num($this->col($row, 'Debito'));
            $credito = $this->num($this->col($row, 'Credito'));
            $out[] = [
                'ano_mes' => $anoMes,
                'unidade' => trim((string) $this->col($row, 'Unidade')),
                'departamento' => trim((string) $this->col($row, 'Departamento')),
                'centro' => trim((string) $this->col($row, 'Centro')),
                'nome_centro' => (string) $this->col($row, 'NomeCentro'),
                'account' => (string) $this->col($row, 'Account'),
                'acct_name' => (string) $this->col($row, 'AcctName'),
                'regra' => $regra,
                'status' => $status,
                'regra_revisar' => $status === 'Pendente' ? $regra : '',
                'debito' => $debito,
                'credito' => $credito,
                'valor' => round($debito - $credito, 2),
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function normalizeLancamentos(array $rows, string $unidade, string $centro = ''): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $unid = trim((string) $this->col($row, 'Unidade'));
            if ($unidade !== '' && $unid !== $unidade) {
                continue;
            }
            $centroRow = trim((string) $this->col($row, 'Centro'));
            if ($centro !== '' && $centroRow !== $centro) {
                continue;
            }
            $debito = $this->num($this->col($row, 'Debito atribuido', 'Debito'));
            $credito = $this->num($this->col($row, 'Credito atribuido', 'Credito'));
            $out[] = [
                'unidade' => $unid,
                'departamento' => (string) $this->col($row, 'Departamento'),
                'centro' => (string) $this->col($row, 'Centro'),
                'nome_centro' => (string) $this->col($row, 'Nome do centro', 'NomeCentro'),
                'ref_date' => $this->formatDate($this->col($row, 'Data de lancamento', 'RefDate')),
                'account' => (string) $this->col($row, 'Conta contabil', 'Account'),
                'natureza' => (string) $this->col($row, 'Natureza', 'AcctName'),
                'trans_id' => $this->docNum($this->col($row, 'Numero da transacao', 'TransId')),
                'line_id' => $this->docNum($this->col($row, 'Linha contabil', 'Line_ID')),
                'trans_type' => $this->docNum($this->col($row, 'Tipo origem', 'TransType')),
                'base_ref' => $this->docNum($this->col($row, 'Referencia origem', 'BaseRef')),
                'historico' => (string) $this->col($row, 'Historico', 'LineMemo'),
                'regra' => (string) $this->col($row, 'Regra informada', 'Regra'),
                'debito' => $debito,
                'credito' => $credito,
                'valor' => round($debito - $credito, 2),
                'status' => (string) $this->col($row, 'Status'),
            ];
        }
        return $out;
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function mergeGrain(array $grain): array
    {
        $merged = [];
        foreach ($grain as $row) {
            $key = implode("\0", [
                $row['ano_mes'] ?? '',
                $row['unidade'],
                $row['departamento'],
                $row['centro'],
                $row['nome_centro'],
                $row['account'],
                $row['acct_name'],
                $row['regra'],
                $row['status'],
            ]);
            if (!isset($merged[$key])) {
                $merged[$key] = $row;
                continue;
            }
            $merged[$key]['debito'] += $row['debito'];
            $merged[$key]['credito'] += $row['credito'];
            $merged[$key]['valor'] = round($merged[$key]['debito'] - $merged[$key]['credito'], 2);
        }
        return array_values($merged);
    }

    /**
     * @return list<array{0: DateTimeImmutable, 1: DateTimeImmutable}>
     */
    private function monthWindows(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $windows = [];
        $cursor = $from;
        while ($cursor <= $to) {
            $endOfMonth = $cursor->modify('last day of this month');
            $chunkEnd = $endOfMonth < $to ? $endOfMonth : $to;
            $windows[] = [$cursor, $chunkEnd];
            $cursor = $chunkEnd->add(new DateInterval('P1D'));
        }
        return $windows;
    }

    /**
     * Hierarquia equivalente ao GROUPING SETS: total, unidade, centro, conta (se houver mais de uma).
     *
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function buildTree(array $grain): array
    {
        $total = 0.0;
        $byUnit = [];
        $byCentro = [];
        $byAccount = [];

        foreach ($grain as $row) {
            $valor = (float) $row['valor'];
            $total += $valor;
            $unid = $row['unidade'];
            $ckey = $unid . "\0" . $row['centro'] . "\0" . $row['regra_revisar'];
            $akey = $ckey . "\0" . $row['account'];

            if (!isset($byUnit[$unid])) {
                $byUnit[$unid] = 0.0;
            }
            $byUnit[$unid] += $valor;

            if (!isset($byCentro[$ckey])) {
                $byCentro[$ckey] = [
                    'unidade' => $unid,
                    'centro' => $row['centro'],
                    'nome_centro' => $row['nome_centro'],
                    'regra_revisar' => $row['regra_revisar'],
                    'status' => $row['status'],
                    'valor' => 0.0,
                    'contas' => [],
                ];
            }
            $byCentro[$ckey]['valor'] += $valor;
            $byCentro[$ckey]['contas'][$row['account']] = true;

            if (!isset($byAccount[$akey])) {
                $byAccount[$akey] = [
                    'unidade' => $unid,
                    'centro' => $row['centro'],
                    'regra_revisar' => $row['regra_revisar'],
                    'account' => $row['account'],
                    'acct_name' => $row['acct_name'],
                    'valor' => 0.0,
                ];
            }
            $byAccount[$akey]['valor'] += $valor;
        }

        $unidades = array_keys($byUnit);
        usort($unidades, [$this, 'cmpUnidade']);

        $tree = [];
        foreach ($unidades as $unid) {
            $tree[] = [
                'nivel' => 1,
                'descricao' => $unid,
                'natureza' => '',
                'valor' => round($byUnit[$unid], 2),
                'unidade' => $unid,
                'status' => '',
            ];

            $centrosUnid = [];
            foreach ($byCentro as $ckey => $centro) {
                if ($centro['unidade'] === $unid) {
                    $centrosUnid[$ckey] = $centro;
                }
            }
            uasort($centrosUnid, static function (array $a, array $b): int {
                return [$a['centro'], $a['regra_revisar']] <=> [$b['centro'], $b['regra_revisar']];
            });

            foreach ($centrosUnid as $ckey => $centro) {
                $qtdContas = count($centro['contas']);
                $contaUnica = '';
                $naturezaUnica = '';
                if ($qtdContas === 1) {
                    $firstAcc = array_key_first($centro['contas']);
                    $contaUnica = (string) $firstAcc;
                    foreach ($byAccount as $acc) {
                        if ($acc['unidade'] === $unid
                            && $acc['centro'] === $centro['centro']
                            && $acc['regra_revisar'] === $centro['regra_revisar']
                            && $acc['account'] === $contaUnica
                        ) {
                            $naturezaUnica = $acc['acct_name'];
                            break;
                        }
                    }
                }

                $nome = $centro['nome_centro'];
                $prefix = $unid . ' - ';
                if ($unid !== '' && str_starts_with($nome, $prefix)) {
                    $nome = substr($nome, strlen($prefix));
                }
                $descricao = '    ' . $centro['centro'] . ' - ' . $nome;
                if ($centro['regra_revisar'] !== '') {
                    $descricao .= ' [Regra: ' . $centro['regra_revisar'] . ']';
                }

                $natureza = '';
                if ($qtdContas === 1) {
                    $natureza = $contaUnica . ' - ' . $naturezaUnica;
                }

                $tree[] = [
                    'nivel' => 2,
                    'descricao' => $descricao,
                    'natureza' => $natureza,
                    'valor' => round((float) $centro['valor'], 2),
                    'unidade' => $unid,
                    'status' => (string) $centro['status'],
                ];

                if ($qtdContas > 1) {
                    $contas = [];
                    foreach ($byAccount as $akey => $acc) {
                        if ($acc['unidade'] === $unid
                            && $acc['centro'] === $centro['centro']
                            && $acc['regra_revisar'] === $centro['regra_revisar']
                        ) {
                            $contas[$akey] = $acc;
                        }
                    }
                    uasort($contas, static fn(array $a, array $b): int => strcmp($a['account'], $b['account']));
                    foreach ($contas as $acc) {
                        $tree[] = [
                            'nivel' => 3,
                            'descricao' => '        Conta ' . $acc['account'],
                            'natureza' => $acc['acct_name'],
                            'valor' => round((float) $acc['valor'], 2),
                            'unidade' => $unid,
                            'status' => '',
                        ];
                    }
                }
            }
        }

        $tree[] = [
            'nivel' => 0,
            'descricao' => 'TOTAL GERAL',
            'natureza' => '',
            'valor' => round($total, 2),
            'unidade' => '',
            'status' => '',
        ];

        return $tree;
    }

    /**
     * Árvore aninhada para expandir/recolher unidade e centro de forma independente.
     *
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function buildArvore(array $grain): array
    {
        $byUnit = [];
        foreach ($grain as $row) {
            $unid = (string) $row['unidade'];
            $ckey = $unid . "\0" . $row['centro'] . "\0" . $row['regra_revisar'];
            $akey = $ckey . "\0" . $row['account'];
            $valor = (float) $row['valor'];

            if (!isset($byUnit[$unid])) {
                $byUnit[$unid] = ['unidade' => $unid, 'valor' => 0.0, 'centros' => []];
            }
            $byUnit[$unid]['valor'] += $valor;

            if (!isset($byUnit[$unid]['centros'][$ckey])) {
                $byUnit[$unid]['centros'][$ckey] = [
                    'centro' => (string) $row['centro'],
                    'nome' => $this->nomeCurto($unid, (string) $row['nome_centro']),
                    'regra_revisar' => (string) $row['regra_revisar'],
                    'status' => (string) $row['status'],
                    'valor' => 0.0,
                    'contas' => [],
                ];
            }
            $byUnit[$unid]['centros'][$ckey]['valor'] += $valor;

            if (!isset($byUnit[$unid]['centros'][$ckey]['contas'][$akey])) {
                $byUnit[$unid]['centros'][$ckey]['contas'][$akey] = [
                    'account' => (string) $row['account'],
                    'acct_name' => (string) $row['acct_name'],
                    'valor' => 0.0,
                ];
            }
            $byUnit[$unid]['centros'][$ckey]['contas'][$akey]['valor'] += $valor;
        }

        $unidades = array_keys($byUnit);
        usort($unidades, [$this, 'cmpUnidade']);

        $out = [];
        $ui = 0;
        foreach ($unidades as $unid) {
            $ui++;
            $centros = array_values($byUnit[$unid]['centros']);
            usort($centros, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
            $centrosOut = [];
            $ci = 0;
            foreach ($centros as $centro) {
                $ci++;
                $contas = array_values($centro['contas']);
                usort($contas, static fn(array $a, array $b): int => strcmp($a['account'], $b['account']));
                foreach ($contas as &$conta) {
                    $conta['valor'] = round((float) $conta['valor'], 2);
                }
                unset($conta);
                $centrosOut[] = [
                    'id' => 'u' . $ui . '-c' . $ci,
                    'centro' => $centro['centro'],
                    'nome' => $centro['nome'],
                    'status' => $centro['status'],
                    'regra_revisar' => $centro['regra_revisar'],
                    'valor' => round((float) $centro['valor'], 2),
                    'contas' => $contas,
                ];
            }
            $out[] = [
                'id' => 'u' . $ui,
                'unidade' => $unid,
                'valor' => round((float) $byUnit[$unid]['valor'], 2),
                'qtd_centros' => count($centrosOut),
                'centros' => $centrosOut,
            ];
        }
        return $out;
    }

    private function cmpUnidade(string $a, string $b): int
    {
        $peso = static function (string $u): int {
            return match ($u) {
                'REVISAR' => 2,
                'OUTROS / LEGADO' => 1,
                default => 0,
            };
        };
        return [$peso($a), $a] <=> [$peso($b), $b];
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return array<string, mixed>
     */
    private function buildKpis(array $grain, array $meses): array
    {
        $total = 0.0;
        $alocado = 0.0;
        $pendente = 0.0;
        $centrosAloc = [];
        $centrosTodos = [];
        $porMes = [];
        $porCentro = [];
        foreach ($grain as $row) {
            $valor = (float) $row['valor'];
            $total += $valor;
            $mes = (string) ($row['ano_mes'] ?? '');
            if ($mes !== '') {
                $porMes[$mes] = ($porMes[$mes] ?? 0.0) + $valor;
            }
            $code = (string) $row['centro'];
            if ($code !== '') {
                $centrosTodos[$code] = true;
                $porCentro[$code] = ($porCentro[$code] ?? 0.0) + $valor;
            }
            if ($row['status'] === 'Alocado') {
                $alocado += $valor;
                if ($code !== '') {
                    $centrosAloc[$code] = true;
                }
            } else {
                $pendente += $valor;
            }
        }

        $qtdMeses = max(1, count($meses));
        $media = $total / $qtdMeses;
        $maiorMes = '';
        $maiorValor = null;
        foreach ($meses as $chave) {
            $v = round((float) ($porMes[$chave] ?? 0), 2);
            if ($maiorValor === null || $v > $maiorValor) {
                $maiorValor = $v;
                $maiorMes = $chave;
            }
        }
        arsort($porCentro, SORT_NUMERIC);
        $topCodigo = (string) (array_key_first($porCentro) ?? '');
        $topValor = $topCodigo !== '' ? (float) $porCentro[$topCodigo] : 0.0;
        $i = 0;
        $top3 = 0.0;
        foreach ($porCentro as $valorCentro) {
            $i++;
            if ($i > 3) {
                break;
            }
            $top3 += (float) $valorCentro;
        }
        $qtdTodos = count($centrosTodos);

        return [
            'total' => round($total, 2),
            'alocado' => round($alocado, 2),
            'pendente' => round($pendente, 2),
            'qtd_centros' => count($centrosAloc),
            'qtd_centros_total' => $qtdTodos,
            'qtd_meses' => count($meses),
            'media_mensal' => round($media, 2),
            'ticket_medio' => $qtdTodos > 0 ? round($total / $qtdTodos, 2) : 0.0,
            'maior_mes' => $maiorMes,
            'maior_mes_label' => $this->labelMes($maiorMes),
            'maior_mes_valor' => round((float) ($maiorValor ?? 0), 2),
            'top_centro' => $topCodigo,
            'top_centro_valor' => round($topValor, 2),
            'top_centro_pct' => $total != 0.0 ? round(($topValor / $total) * 100, 1) : 0.0,
            'pct_alocado' => $total != 0.0 ? round(($alocado / $total) * 100, 1) : 0.0,
            'pct_pendente' => $total != 0.0 ? round(($pendente / $total) * 100, 1) : 0.0,
            'pct_top3' => $total != 0.0 ? round(($top3 / $total) * 100, 1) : 0.0,
            'orcamento' => null,
        ];
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return list<string>
     */
    private function uniqueUnidades(array $grain): array
    {
        $set = [];
        foreach ($grain as $row) {
            if ($row['unidade'] !== '') {
                $set[$row['unidade']] = true;
            }
        }
        $list = array_keys($set);
        usort($list, [$this, 'cmpUnidade']);
        return $list;
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return array{labels: list<string>, valores: list<float>}
     */
    private function buildChart(array $grain): array
    {
        $byUnit = [];
        foreach ($grain as $row) {
            $unid = $row['unidade'] !== '' ? $row['unidade'] : '(sem unidade)';
            if (!isset($byUnit[$unid])) {
                $byUnit[$unid] = 0.0;
            }
            $byUnit[$unid] += (float) $row['valor'];
        }
        ksort($byUnit, SORT_STRING);
        $labels = [];
        $valores = [];
        foreach ($byUnit as $label => $valor) {
            $labels[] = $label;
            $valores[] = round($valor, 2);
        }
        return ['labels' => $labels, 'valores' => $valores];
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return list<array{codigo:string,nome:string,unidade:string}>
     */
    private function uniqueCentros(array $grain): array
    {
        $set = [];
        foreach ($grain as $row) {
            $code = (string) $row['centro'];
            if ($code === '' || isset($set[$code])) {
                continue;
            }
            $set[$code] = [
                'codigo' => $code,
                'nome' => $this->nomeCurto((string) $row['unidade'], (string) $row['nome_centro']),
                'unidade' => (string) $row['unidade'],
            ];
        }
        $list = array_values($set);
        usort($list, static fn(array $a, array $b): int => strcmp($a['codigo'], $b['codigo']));
        return $list;
    }

    /**
     * @return list<string>
     */
    private function monthKeys(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $keys = [];
        $cursor = $from->modify('first day of this month');
        $end = $to->modify('first day of this month');
        while ($cursor <= $end) {
            $keys[] = $cursor->format('Y-m');
            $cursor = $cursor->modify('+1 month');
        }
        return $keys;
    }

    private function labelMes(string $anoMes): string
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $anoMes, $m)) {
            return $anoMes;
        }
        $mes = (int) $m[2];
        $rotulo = self::MESES_PT[$mes] ?? $m[2];
        return $rotulo . '/' . substr($m[1], 2, 2);
    }

    private function nomeCurto(string $unidade, string $nome): string
    {
        $prefix = $unidade . ' - ';
        if ($unidade !== '' && str_starts_with($nome, $prefix)) {
            return substr($nome, strlen($prefix));
        }
        return $nome;
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @param list<string> $meses
     * @return array{labels: list<string>, total: list<float>, alocado: list<float>, pendente: list<float>, chaves: list<string>}
     */
    private function buildSerieMensal(array $grain, array $meses): array
    {
        $total = [];
        $alocado = [];
        $pendente = [];
        foreach ($meses as $chave) {
            $total[$chave] = 0.0;
            $alocado[$chave] = 0.0;
            $pendente[$chave] = 0.0;
        }
        foreach ($grain as $row) {
            $chave = (string) ($row['ano_mes'] ?? '');
            if (!isset($total[$chave])) {
                continue;
            }
            $valor = (float) $row['valor'];
            $total[$chave] += $valor;
            if ($row['status'] === 'Alocado') {
                $alocado[$chave] += $valor;
            } else {
                $pendente[$chave] += $valor;
            }
        }
        $labels = [];
        $tot = [];
        $alo = [];
        $pen = [];
        foreach ($meses as $chave) {
            $labels[] = $this->labelMes($chave);
            $tot[] = round($total[$chave], 2);
            $alo[] = round($alocado[$chave], 2);
            $pen[] = round($pendente[$chave], 2);
        }
        return [
            'labels' => $labels,
            'total' => $tot,
            'alocado' => $alo,
            'pendente' => $pen,
            'chaves' => $meses,
        ];
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @param list<string> $meses
     * @return array{labels: list<string>, datasets: list<array{label:string,data:list<float>}>}
     */
    private function buildStackedUnidades(array $grain, array $meses): array
    {
        $unidades = $this->uniqueUnidades($grain);
        $grid = [];
        foreach ($unidades as $unid) {
            foreach ($meses as $chave) {
                $grid[$unid][$chave] = 0.0;
            }
        }
        foreach ($grain as $row) {
            $unid = (string) $row['unidade'];
            $chave = (string) ($row['ano_mes'] ?? '');
            if ($unid === '' || !isset($grid[$unid][$chave])) {
                continue;
            }
            $grid[$unid][$chave] += (float) $row['valor'];
        }
        $labels = [];
        foreach ($meses as $chave) {
            $labels[] = $this->labelMes($chave);
        }
        $datasets = [];
        foreach ($unidades as $unid) {
            $data = [];
            foreach ($meses as $chave) {
                $data[] = round((float) ($grid[$unid][$chave] ?? 0), 2);
            }
            $datasets[] = ['label' => $unid, 'data' => $data];
        }
        return ['labels' => $labels, 'datasets' => $datasets];
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function buildRanking(array $grain): array
    {
        $byCentro = [];
        $total = 0.0;
        foreach ($grain as $row) {
            $valor = (float) $row['valor'];
            $total += $valor;
            $code = (string) $row['centro'];
            if ($code === '') {
                continue;
            }
            if (!isset($byCentro[$code])) {
                $byCentro[$code] = [
                    'centro' => $code,
                    'nome' => $this->nomeCurto((string) $row['unidade'], (string) $row['nome_centro']),
                    'unidade' => (string) $row['unidade'],
                    'valor' => 0.0,
                    'status' => (string) $row['status'],
                ];
            }
            $byCentro[$code]['valor'] += $valor;
        }
        uasort($byCentro, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
        $out = [];
        $i = 0;
        foreach ($byCentro as $row) {
            $i++;
            $valor = round((float) $row['valor'], 2);
            $out[] = [
                'pos' => $i,
                'centro' => $row['centro'],
                'nome' => $row['nome'],
                'unidade' => $row['unidade'],
                'valor' => $valor,
                'pct' => $total != 0.0 ? round(($valor / $total) * 100, 1) : 0.0,
                'status' => $row['status'],
            ];
        }
        return $out;
    }

    /**
     * Consolida o sufixo do centro entre unidades (AFR_TI + BIO_TI + TIA_TI = TI).
     *
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function buildRankingEquipes(array $grain): array
    {
        $by = [];
        $total = 0.0;
        foreach ($grain as $row) {
            $valor = (float) $row['valor'];
            $total += $valor;
            $key = $this->equipeDe($row);
            $unid = (string) ($row['unidade'] !== '' ? $row['unidade'] : '(sem unidade)');
            $nomeCentro = $this->nomeCurto($unid, (string) $row['nome_centro']);
            if (!isset($by[$key])) {
                $by[$key] = [
                    'equipe' => $key,
                    'nome' => $this->nomeEquipe($key, $nomeCentro),
                    'valor' => 0.0,
                    'unidades' => [],
                    'centros' => [],
                ];
            }
            $by[$key]['valor'] += $valor;
            $by[$key]['unidades'][$unid] = ($by[$key]['unidades'][$unid] ?? 0.0) + $valor;
            $code = (string) $row['centro'];
            if ($code === '') {
                continue;
            }
            if (!isset($by[$key]['centros'][$code])) {
                $by[$key]['centros'][$code] = [
                    'centro' => $code,
                    'unidade' => $unid,
                    'nome' => $nomeCentro,
                    'valor' => 0.0,
                ];
            }
            $by[$key]['centros'][$code]['valor'] += $valor;
        }
        uasort($by, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
        $out = [];
        $i = 0;
        foreach ($by as $row) {
            $i++;
            $valor = round((float) $row['valor'], 2);
            $centros = array_values($row['centros']);
            usort($centros, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
            foreach ($centros as &$c) {
                $c['valor'] = round((float) $c['valor'], 2);
            }
            unset($c);
            $unidades = $row['unidades'];
            arsort($unidades, SORT_NUMERIC);
            $unidOut = [];
            foreach ($unidades as $label => $uVal) {
                $unidOut[] = ['unidade' => $label, 'valor' => round((float) $uVal, 2)];
            }
            $out[] = [
                'pos' => $i,
                'equipe' => $row['equipe'],
                'nome' => $row['nome'],
                'valor' => $valor,
                'pct' => $total != 0.0 ? round(($valor / $total) * 100, 1) : 0.0,
                'qtd_centros' => count($centros),
                'qtd_unidades' => count($unidOut),
                'unidades' => $unidOut,
                'centros' => $centros,
            ];
        }
        return $out;
    }

    /**
     * Árvore equipe → unidade → centro (visão consolidada).
     *
     * @param list<array<string, mixed>> $grain
     * @return list<array<string, mixed>>
     */
    private function buildArvoreEquipes(array $grain): array
    {
        $byEq = [];
        foreach ($grain as $row) {
            $key = $this->equipeDe($row);
            $unid = (string) ($row['unidade'] !== '' ? $row['unidade'] : '(sem unidade)');
            $code = (string) $row['centro'];
            $valor = (float) $row['valor'];
            if (!isset($byEq[$key])) {
                $byEq[$key] = [
                    'equipe' => $key,
                    'nome' => $this->nomeEquipe($key, $this->nomeCurto($unid, (string) $row['nome_centro'])),
                    'valor' => 0.0,
                    'unidades' => [],
                ];
            }
            $byEq[$key]['valor'] += $valor;
            if (!isset($byEq[$key]['unidades'][$unid])) {
                $byEq[$key]['unidades'][$unid] = ['unidade' => $unid, 'valor' => 0.0, 'centros' => []];
            }
            $byEq[$key]['unidades'][$unid]['valor'] += $valor;
            if ($code === '') {
                continue;
            }
            if (!isset($byEq[$key]['unidades'][$unid]['centros'][$code])) {
                $byEq[$key]['unidades'][$unid]['centros'][$code] = [
                    'centro' => $code,
                    'nome' => $this->nomeCurto($unid, (string) $row['nome_centro']),
                    'valor' => 0.0,
                ];
            }
            $byEq[$key]['unidades'][$unid]['centros'][$code]['valor'] += $valor;
        }
        uasort($byEq, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
        $out = [];
        foreach ($byEq as $eq) {
            $unids = $eq['unidades'];
            uasort($unids, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
            $unidOut = [];
            foreach ($unids as $unid) {
                $centros = array_values($unid['centros']);
                usort($centros, static fn(array $a, array $b): int => $b['valor'] <=> $a['valor']);
                foreach ($centros as &$c) {
                    $c['valor'] = round((float) $c['valor'], 2);
                    $c['id'] = 'eqc-' . $eq['equipe'] . '-' . $c['centro'];
                }
                unset($c);
                $unidOut[] = [
                    'id' => 'equ-' . $eq['equipe'] . '-' . $unid['unidade'],
                    'unidade' => $unid['unidade'],
                    'valor' => round((float) $unid['valor'], 2),
                    'qtd_centros' => count($centros),
                    'centros' => $centros,
                ];
            }
            $out[] = [
                'id' => 'eq-' . $eq['equipe'],
                'equipe' => $eq['equipe'],
                'nome' => $eq['nome'],
                'valor' => round((float) $eq['valor'], 2),
                'qtd_unidades' => count($unidOut),
                'unidades' => $unidOut,
            ];
        }
        return $out;
    }

    private function equipeDe(array $row): string
    {
        $unid = (string) ($row['unidade'] ?? '');
        $centro = trim((string) ($row['centro'] ?? ''));
        $dep = trim((string) ($row['departamento'] ?? ''));
        if ($unid === 'REVISAR' || str_starts_with($centro, '[')) {
            return 'REVISAR';
        }
        if ($dep !== '' && !str_starts_with($dep, '[')) {
            return mb_strtoupper($dep);
        }
        if (preg_match('/^[A-Z]{3}_(.+)$/i', $centro, $m)) {
            return mb_strtoupper($m[1]);
        }
        return $centro !== '' ? mb_strtoupper($centro) : 'OUTROS';
    }

    private function nomeEquipe(string $codigo, string $fallback = ''): string
    {
        if (isset(self::EQUIPE_NOMES[$codigo])) {
            return self::EQUIPE_NOMES[$codigo];
        }
        $fb = trim($fallback);
        return $fb !== '' ? $fb : $codigo;
    }

    /**
     * @param list<array<string, mixed>> $grain
     * @param list<string> $meses
     * @return array{meses: list<array{chave:string,label:string}>, linhas: list<array<string, mixed>>, totais: list<float>}
     */
    private function buildMatrix(array $grain, array $meses): array
    {
        $linhasMap = [];
        $totais = array_fill_keys($meses, 0.0);
        foreach ($grain as $row) {
            $code = (string) $row['centro'];
            if ($code === '') {
                continue;
            }
            $chave = (string) ($row['ano_mes'] ?? '');
            if (!isset($linhasMap[$code])) {
                $linhasMap[$code] = [
                    'centro' => $code,
                    'nome' => $this->nomeCurto((string) $row['unidade'], (string) $row['nome_centro']),
                    'unidade' => (string) $row['unidade'],
                    'status' => (string) $row['status'],
                    'meses' => array_fill_keys($meses, 0.0),
                    'total' => 0.0,
                ];
            }
            $valor = (float) $row['valor'];
            if (isset($linhasMap[$code]['meses'][$chave])) {
                $linhasMap[$code]['meses'][$chave] += $valor;
            }
            $linhasMap[$code]['total'] += $valor;
            if (isset($totais[$chave])) {
                $totais[$chave] += $valor;
            }
        }
        uasort($linhasMap, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
        $linhas = [];
        foreach ($linhasMap as $linha) {
            $vals = [];
            foreach ($meses as $chave) {
                $vals[] = round((float) ($linha['meses'][$chave] ?? 0), 2);
            }
            $linhas[] = [
                'centro' => $linha['centro'],
                'nome' => $linha['nome'],
                'unidade' => $linha['unidade'],
                'status' => $linha['status'],
                'valores' => $vals,
                'total' => round((float) $linha['total'], 2),
            ];
        }
        $mesesOut = [];
        $totaisOut = [];
        foreach ($meses as $chave) {
            $mesesOut[] = ['chave' => $chave, 'label' => $this->labelMes($chave)];
            $totaisOut[] = round((float) $totais[$chave], 2);
        }
        return [
            'meses' => $mesesOut,
            'linhas' => $linhas,
            'totais' => $totaisOut,
        ];
    }

    private function sqlResumo(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $inner = $this->sqlAlocacao($from, $to);
        return 'SELECT "Unidade", "Departamento", "Centro", "NomeCentro", "Account", "AcctName", "Regra", "Status",'
            . ' SUM("Debito") AS "Debito", SUM("Credito") AS "Credito"'
            . ' FROM (' . $inner . ') M'
            . ' GROUP BY "Unidade", "Departamento", "Centro", "NomeCentro", "Account", "AcctName", "Regra", "Status"';
    }

    private function sqlLancamentos(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $inner = $this->sqlAlocacao($from, $to);
        return 'SELECT "Unidade", "Departamento", "Centro",'
            . ' "NomeCentro" AS "Nome do centro", "RefDate" AS "Data de lancamento",'
            . ' "Account" AS "Conta contabil", "AcctName" AS "Natureza",'
            . ' "TransId" AS "Numero da transacao", "Line_ID" AS "Linha contabil",'
            . ' "TransType" AS "Tipo origem", "BaseRef" AS "Referencia origem",'
            . ' "LineMemo" AS "Historico", "Regra" AS "Regra informada",'
            . ' "Debito" AS "Debito atribuido", "Credito" AS "Credito atribuido",'
            . ' "Debito"-"Credito" AS "Valor liquido atribuido", "Status"'
            . ' FROM (' . $inner . ') M'
            . ' ORDER BY "Unidade", "Departamento", "Centro", "Account", "RefDate", "TransId", "Line_ID"';
    }

    private function sqlAlocacao(DateTimeImmutable $from, DateTimeImmutable $to): string
    {
        $inicio = $this->quoteDate($from);
        $fim = $this->quoteDate($to);

        return <<<SQL
SELECT AL.*,
 CASE WHEN LEFT("Centro",4)='TIA_' THEN 'TIARAJU'
      WHEN LEFT("Centro",4)='BIO_' THEN 'BIOTICS'
      WHEN LEFT("Centro",4)='AFR_' THEN 'AFRA'
      WHEN LEFT("Centro",4)='GER_' THEN 'GERAL'
      WHEN "Centro" IN ('ACL','REFAR','UNITA') THEN 'HOLDINGS'
      WHEN "Status" <> 'Alocado' THEN 'REVISAR'
      ELSE 'OUTROS / LEGADO' END AS "Unidade",
 CASE WHEN LEFT("Centro",4) IN ('TIA_','BIO_','AFR_','GER_')
      THEN SUBSTRING("Centro",5) ELSE "Centro" END AS "Departamento"
FROM (
    SELECT "TransId", "Line_ID", "TransType", "BaseRef", "LineMemo", "RefDate", "Account", "AcctName", "Inicio", "Regra",
           CASE WHEN "Resolvido" = 1 THEN "CentroCandidato"
                WHEN "Regra" = '' THEN '[SEM CENTRO]' ELSE '[PENDENTE]' END AS "Centro",
           CASE WHEN "Resolvido" = 1 THEN "PrcName"
                WHEN "Regra" = '' THEN 'Conta de custo/despesa sem dimensao 1'
                ELSE 'Revisar regra, vigencia, valor fixo ou total dos fatores' END AS "NomeCentro",
           CASE WHEN "Resolvido" = 1 THEN 'Alocado'
                WHEN "Regra" = '' THEN 'Sem classificacao' ELSE 'Pendente' END AS "Status",
           "Debit" * CASE WHEN "Resolvido" = 1 THEN "Fator" ELSE 1 END AS "Debito",
           "Credit" * CASE WHEN "Resolvido" = 1 THEN "Fator" ELSE 1 END AS "Credito"
    FROM (
    SELECT V.*,
           CASE WHEN V."TemErro" = 0 AND V."MaiorRepeticao" = 1
                     AND ABS(V."SomaFatores" - 1) < 0.000001
                THEN 1 ELSE 0 END AS "Resolvido"
    FROM (
    SELECT X.*,
           SUM(X."Fator") OVER (PARTITION BY X."TransId", X."Line_ID") AS "SomaFatores",
           MAX(X."Erro") OVER (PARTITION BY X."TransId", X."Line_ID") AS "TemErro",
           MAX(X."RepeticoesCentro") OVER (PARTITION BY X."TransId", X."Line_ID") AS "MaiorRepeticao"
    FROM (
    SELECT B.*, R."PrcCode" AS "CentroCandidato", C."PrcName",
           CASE WHEN R."OcrTotal" > 0
                THEN R."PrcAmount" / NULLIF(R."OcrTotal", 0) ELSE NULL END AS "Fator",
           CASE WHEN C."PrcCode" IS NULL OR R."OcrTotal" IS NULL
                     OR R."OcrTotal" <= 0 OR R."PrcAmount" IS NULL
                     OR R."PrcAmount" < 0 THEN 1 ELSE 0 END AS "Erro",
           COUNT(*) OVER (PARTITION BY B."TransId", B."Line_ID", R."PrcCode") AS "RepeticoesCentro",
           ROW_NUMBER() OVER (
               PARTITION BY B."TransId", B."Line_ID"
               ORDER BY R."PrcCode", R."ValidFrom"
           ) AS "Seq"
    FROM (
    SELECT L."TransId", L."Line_ID", L."TransType", L."BaseRef", L."LineMemo", L."RefDate", L."Account",
           A."AcctName", L."Debit", L."Credit",
           COALESCE(TRIM(L."ProfitCode"), '') AS "Regra",
           P."Inicio", P."Fim"
    FROM "JDT1" L
    INNER JOIN "OACT" A ON A."AcctCode" = L."Account"
    CROSS JOIN (
    SELECT {$inicio} AS "Inicio", {$fim} AS "Fim" FROM DUMMY
) P
    WHERE L."RefDate" BETWEEN P."Inicio" AND P."Fim"
      AND (A."AcctCode" LIKE '4.%' OR A."AcctCode" LIKE '5.%')
) B
    LEFT JOIN "OOCR" H ON H."OcrCode" = B."Regra"
        AND H."DimCode" = 1 AND COALESCE(H."IsFixedAmt", 'N') = 'N'
    LEFT JOIN "OCR1" R ON R."OcrCode" = H."OcrCode"
        AND B."RefDate" >= COALESCE(R."ValidFrom", TO_DATE('1900-01-01'))
        AND (R."ValidTo" IS NULL OR B."RefDate" <= R."ValidTo")
    LEFT JOIN "OPRC" C ON C."PrcCode" = R."PrcCode" AND C."DimCode" = 1
) X
) V
) CC
    WHERE "Resolvido" = 1 OR "Seq" = 1
) AL
SQL;
    }

    private function quoteDate(DateTimeImmutable $d): string
    {
        return "TO_DATE('" . $d->format('Y-m-d') . "', 'YYYY-MM-DD')";
    }

    private function parseDate(string $value): ?DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $dt instanceof DateTimeImmutable ? $dt : null;
    }

    private function col(array $row, string ...$names): mixed
    {
        $map = [];
        foreach ($row as $key => $value) {
            $map[strtolower(trim((string) $key))] = $value;
        }
        foreach ($names as $name) {
            $lookup = strtolower(trim($name));
            if (array_key_exists($lookup, $map)) {
                return $map[$lookup];
            }
        }
        return null;
    }

    private function num(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $s = str_replace(['.', ' '], ['', ''], (string) $value);
        $s = str_replace(',', '.', $s);
        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function formatDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $s = substr((string) $value, 0, 10);
        $dt = $this->parseDate($s);
        return $dt !== null ? $dt->format('d/m/Y') : (string) $value;
    }

    private function docNum(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return (string) (int) round((float) $value);
        }
        return trim((string) $value);
    }
}
