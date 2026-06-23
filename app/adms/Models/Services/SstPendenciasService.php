<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\SstAsoExamesRepository;
use App\adms\Models\Repository\SstTreinamentosRepository;
use App\adms\Models\Repository\SstTreinamentoVinculosRepository;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Calcula pendências SST cruzando vínculos (necessidades) com registros (entregas/ASOs).
 */
class SstPendenciasService extends DbConnection
{
    private const DIAS_ALERTA = 30;

    public const SITUACOES_CRITICAS = [
        'nao_entregue',
        'troca_vencida',
        'sem_aso',
        'aso_vencido',
        'aso_evento_sem_registro',
        'aso_evento_vencido',
    ];

    public static function incluirTreinamentos(): bool
    {
        return \App\adms\Models\Services\NotificationSettingsService::isEnabled('sst_pendencias_incluir_treinamentos');
    }

    /**
     * Resumo leve para o dashboard SST (uma consulta consolidada + cache curto).
     *
     * @return array{criticas_count: int, epis_amostra: array, exames_amostra: array}
     */
    public function getDashboardResumo(int $amostra = 5, int $cacheTtlSeconds = 90): array
    {
        $cached = $this->readDashboardCache($cacheTtlSeconds);
        if ($cached !== null) {
            return $cached;
        }

        $data = [
            'criticas_count' => $this->countPendenciasCriticas(),
            'epis_amostra' => $this->getPendenciasEpiGeral(['_limit' => $amostra]),
            'exames_amostra' => $this->getPendenciasExameGeral(['_limit' => $amostra]),
        ];
        if (self::incluirTreinamentos()) {
            $data['treinamentos_amostra'] = $this->getPendenciasTreinamentoGeral(['_limit' => $amostra]);
        }
        $this->writeDashboardCache($data);

        return $data;
    }

    /** Invalida cache do dashboard (após cadastro de ASO/EPI/vínculo). */
    public static function invalidateDashboardCache(): void
    {
        $file = dirname(__DIR__, 3) . '/storage/cache/sst/dashboard_pendencias.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * Pendências consolidadas de um colaborador.
     *
     * @return array{epis: array, exames: array, treinamentos: array, resumo: array}
     */
    public function getPendenciasPorUsuario(int $userId): array
    {
        $epis = $this->getPendenciasEpiPorUsuario($userId);
        $exames = $this->getPendenciasExameConsolidadoPorUsuario($userId);
        $treinamentos = self::incluirTreinamentos()
            ? $this->getPendenciasTreinamentoPorUsuario($userId)
            : [];

        return [
            'epis' => $epis,
            'exames' => $exames,
            'treinamentos' => $treinamentos,
            'resumo' => [
                'total' => count($epis) + count($exames) + count($treinamentos),
                'epis' => count($epis),
                'exames' => count($exames),
                'treinamentos' => count($treinamentos),
            ],
        ];
    }

    /**
     * Pendências de exame consolidadas: prioriza o evento ASO (ex. Periódico) e evita
     * duplicar exames complementares que já fazem parte do pacote desse ASO.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasExameConsolidadoPorUsuario(int $userId): array
    {
        $eventos = $this->getPendenciasAsoEventoPorUsuario($userId);
        $complementares = $this->getPendenciasExameComplementarPorUsuario($userId);

        $examesNoPacoteAso = $this->coletarExamesIdsPacoteEventosPendentes($userId, $eventos);

        $vistosExame = [];
        $complementaresFiltrados = [];
        foreach ($complementares as $comp) {
            $exameId = (int) ($comp['adms_sst_exame_id'] ?? 0);
            if ($exameId > 0 && isset($examesNoPacoteAso[$exameId])) {
                continue;
            }
            if ($exameId > 0 && isset($vistosExame[$exameId])) {
                continue;
            }
            if ($exameId > 0) {
                $vistosExame[$exameId] = true;
            }
            $complementaresFiltrados[] = $comp;
        }

        return array_merge($eventos, $complementaresFiltrados);
    }

    /**
     * IDs de exames que já fazem parte de um evento ASO pendente ou aguardando resultados.
     *
     * @param array<int, array<string, mixed>> $eventosPendentes
     * @return array<int, true>
     */
    private function coletarExamesIdsPacoteEventosPendentes(int $userId, array $eventosPendentes): array
    {
        $ids = [];
        $resolver = new SstExamesObrigatoriosResolver();
        $categorias = [];

        foreach ($eventosPendentes as $ev) {
            $cat = $ev['categoria_aso'] ?? null;
            if (is_string($cat) && $cat !== '') {
                $categorias[$cat] = true;
            }
        }

        foreach (array_keys($categorias) as $categoria) {
            $pacote = $resolver->resolvePacoteCompleto($userId, $categoria);
            foreach (array_merge($pacote['obrigatorios'] ?? [], $pacote['recomendados'] ?? []) as $regra) {
                $eid = (int) ($regra['adms_sst_exame_id'] ?? 0);
                if ($eid > 0) {
                    $ids[$eid] = true;
                }
            }
        }

        $asoRepo = new SstAsosRepository();
        foreach ($asoRepo->findAllAguardandoPorUsuario($userId) as $aso) {
            $cat = (string) ($aso['tipo'] ?? '');
            if ($cat === '' || isset($categorias[$cat])) {
                continue;
            }
            $pacote = $resolver->resolvePacoteCompleto($userId, $cat);
            foreach (array_merge($pacote['obrigatorios'] ?? [], $pacote['recomendados'] ?? []) as $regra) {
                $eid = (int) ($regra['adms_sst_exame_id'] ?? 0);
                if ($eid > 0) {
                    $ids[$eid] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @return list<int>
     */
    public function getUserIdsAtivosComObrigacaoExame(): array
    {
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoExame('u');
        $sql = "SELECT u.id
                FROM adms_users u
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                ORDER BY u.id
                LIMIT 500";
        $stmt = $this->getConnection()->query($sql);
        $out = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * Complementar da matriz já está no pacote do evento ASO pendente?
     *
     * @param array<string, true> $categoriasEventoPendentes
     * @deprecated Substituído por coletarExamesIdsPacoteEventosPendentes
     */
    private function complementarCobertoPorEventoAso(?string $categoriaComplementar, array $categoriasEventoPendentes): bool
    {
        if ($categoriasEventoPendentes === []) {
            return false;
        }
        if ($categoriaComplementar !== null && $categoriaComplementar !== '') {
            return isset($categoriasEventoPendentes[$categoriaComplementar]);
        }

        return isset($categoriasEventoPendentes[SstCategoriaAsoHelper::PERIODICO]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasEpiPorUsuario(int $userId): array
    {
        $regras = (new SstEpisObrigatoriosResolver())->resolveForUser($userId);
        $pendencias = [];

        foreach ($regras as $regra) {
            $epiId = (int) ($regra['adms_sst_epi_id'] ?? 0);
            if ($epiId <= 0) {
                continue;
            }
            $ult = $this->getUltimaEntregaEpi($userId, $epiId);
            $situacao = $this->avaliarSituacaoEpi($ult);
            if ($situacao === null) {
                continue;
            }
            $pendencias[] = [
                'adms_sst_epi_id' => $epiId,
                'epi_nome' => (string) ($regra['epi_nome'] ?? ''),
                'origem' => (string) ($regra['origem'] ?? ''),
                'ultima_entrega' => $ult['data_movimento'] ?? null,
                'data_prevista_troca' => $ult['data_prevista_troca'] ?? null,
                'situacao' => $situacao,
            ];
        }

        return $this->enriquecerPendencias($pendencias, 'epi');
    }

    /**
     * Pendências de exames complementares (resolver Cargo → Risco → Exame + regras diretas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasExameComplementarPorUsuario(int $userId): array
    {
        $resolver = new SstExamesObrigatoriosResolver();
        $asoExamesRepo = new SstAsoExamesRepository();
        $regras = $resolver->resolveForUser($userId);
        $pendencias = [];

        foreach ($regras as $regra) {
            $exameId = (int) ($regra['adms_sst_exame_id'] ?? 0);
            if ($exameId <= 0) {
                continue;
            }
            $categoria = $regra['categoria_aso'] ?? null;
            $ultima = $asoExamesRepo->getUltimaRealizacao($userId, $exameId, $categoria);
            $periodicidade = isset($regra['periodicidade_meses']) ? (int) $regra['periodicidade_meses'] : null;
            $situacao = $this->avaliarSituacaoExame($ultima, $periodicidade);
            if ($situacao === null) {
                continue;
            }
            $pendencias[] = [
                'adms_sst_exame_id' => $exameId,
                'exame_nome' => (string) ($regra['exame_nome'] ?? ''),
                'categoria_aso' => $categoria,
                'periodicidade_meses' => $periodicidade,
                'origem' => (string) ($regra['origem'] ?? ''),
                'tipo_pendencia' => 'exame_complementar',
                'ultimo_aso' => $ultima['data_realizacao'] ?? $ultima['aso_data_realizacao'] ?? null,
                'data_validade' => $ultima['data_validade'] ?? null,
                'resultado' => $ultima['resultado'] ?? null,
                'situacao' => $situacao,
            ];
        }

        return $this->enriquecerPendencias($pendencias, 'exame');
    }

    /**
     * Pendência do evento ASO (cabeçalho) para categorias com monitoramento periódico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasAsoEventoPorUsuario(int $userId): array
    {
        $pendencias = [];
        $resolver = new SstExamesObrigatoriosResolver();
        $asoRepo = new SstAsosRepository();
        foreach ([SstCategoriaAsoHelper::PERIODICO] as $categoria) {
            $regras = $resolver->resolveForUser($userId, $categoria);
            if ($regras === []) {
                continue;
            }
            $aguardando = $asoRepo->findAguardando($userId, $categoria);
            if ($aguardando !== null) {
                $pendencias[] = [
                    'categoria_aso' => $categoria,
                    'exame_nome' => 'ASO ' . $categoria,
                    'tipo_pendencia' => 'evento_aso',
                    'aso_aguardando_id' => (int) $aguardando['id'],
                    'ultimo_aso' => null,
                    'data_validade' => null,
                    'resultado' => null,
                    'periodicidade_meses' => $this->maxPeriodicidadeMeses($regras),
                    'situacao' => 'aso_aguardando_resultados',
                ];
                continue;
            }
            $ultimo = $this->getUltimoAsoEvento($userId, $categoria);
            $periodicidade = $this->maxPeriodicidadeMeses($regras);
            $situacao = $this->avaliarSituacaoEventoAso($ultimo, $periodicidade);
            if ($situacao === null) {
                continue;
            }
            $pendencias[] = [
                'categoria_aso' => $categoria,
                'exame_nome' => 'ASO ' . $categoria,
                'tipo_pendencia' => 'evento_aso',
                'ultimo_aso' => $ultimo['data_realizacao'] ?? null,
                'data_validade' => $ultimo['data_validade'] ?? null,
                'resultado' => $ultimo['resultado'] ?? null,
                'periodicidade_meses' => $periodicidade,
                'situacao' => $situacao,
            ];
        }

        return $this->enriquecerPendencias($pendencias, 'exame');
    }

    /** @deprecated Use getPendenciasExameConsolidadoPorUsuario */
    public function getPendenciasExamePorUsuario(int $userId): array
    {
        return $this->getPendenciasExameConsolidadoPorUsuario($userId);
    }

    /**
     * Treinamentos obrigatórios do cargo sem vínculo válido ou com status pendente/vencido.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasTreinamentoPorUsuario(int $userId, bool $ignorarFlagIncluir = false): array
    {
        if (!$ignorarFlagIncluir && !self::incluirTreinamentos()) {
            return [];
        }
        if (\App\adms\Helpers\InstitutionalSystemUserHelper::isExemptFromAcknowledgment($userId)) {
            return [];
        }
        if (!$this->hasTable('adms_sst_treinamentos')) {
            return [];
        }

        $resolver = new SstTreinamentosObrigatoriosResolver();
        $vinculoRepo = new SstTreinamentoVinculosRepository();
        $treinamentoRepo = new SstTreinamentosRepository();
        $rows = [];
        foreach ($resolver->resolveForUser($userId) as $obrigatorio) {
            $treinamentoId = (int) ($obrigatorio['adms_sst_treinamento_id'] ?? 0);
            if ($treinamentoId <= 0) {
                continue;
            }
            $vinculo = $vinculoRepo->getByUserAndTreinamento($userId, $treinamentoId);
            $situacao = $this->avaliarSituacaoTreinamentoSst($vinculo);
            if ($situacao === null) {
                continue;
            }
            $catalogo = $treinamentoRepo->getById($treinamentoId);
            $rows[] = [
                'adms_sst_treinamento_id' => $treinamentoId,
                'treinamento_nome' => (string) ($obrigatorio['treinamento_nome'] ?? $catalogo['nome'] ?? ''),
                'treinamento_codigo' => $catalogo['codigo'] ?? null,
                'treinamento_status' => $vinculo['status'] ?? null,
                'situacao' => $situacao,
            ];
        }

        return $this->enriquecerPendencias($rows, 'treinamento');
    }

    /**
     * Relatório geral de pendências por vínculos + vencimentos operacionais.
     *
     * @return array<string, mixed>
     */
    public function getRelatorioCompleto(array $filters = []): array
    {
        return [
            'epis_obrigatorios' => $this->getPendenciasEpiGeral($filters),
            'exames_obrigatorios' => $this->getPendenciasExameGeral($filters),
            'treinamentos_obrigatorios' => self::incluirTreinamentos()
                ? $this->getPendenciasTreinamentoGeral($filters)
                : [],
            'asos_vencidos' => $this->getAsosVencidosSemRegra($filters),
            'epis_troca_vencida' => $this->getEpisTrocaVencidaSemRegra($filters),
            'afastamentos_ativos' => $this->getAfastamentosAtivosRelatorio($filters),
            'acidentes_abertos' => $this->getAcidentesAbertos($filters),
        ];
    }

    public function countPendenciasCriticas(): int
    {
        return $this->countPendenciasEpiCriticas()
            + $this->countPendenciasExameCriticas()
            + (self::incluirTreinamentos() ? $this->countPendenciasTreinamentoCriticas() : 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendenciasEpiGeral(array $filters = []): array
    {
        $limit = isset($filters['_limit']) ? (int) $filters['_limit'] : null;
        unset($filters['_limit']);
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoEpi('u');
        $sql = "SELECT u.id AS adms_user_id, u.name AS colaborador_nome,
                       dep.name AS departamento_nome, pos.name AS cargo_nome
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  {$extraWhere}
                ORDER BY colaborador_nome
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasEpiPorUsuario($uid) as $row) {
                $out[] = array_merge($user, $row);
                if ($limit !== null && count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendenciasExameGeral(array $filters = []): array
    {
        $limit = isset($filters['_limit']) ? (int) $filters['_limit'] : null;
        unset($filters['_limit']);
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoExame('u');
        $sql = "SELECT u.id AS adms_user_id, u.name AS colaborador_nome,
                       dep.name AS departamento_nome, pos.name AS cargo_nome
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  {$extraWhere}
                ORDER BY colaborador_nome
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasExamePorUsuario($uid) as $row) {
                $out[] = array_merge($user, $row);
                if ($limit !== null && count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendenciasTreinamentoGeral(array $filters = []): array
    {
        if (!self::incluirTreinamentos()) {
            return [];
        }

        $limit = isset($filters['_limit']) ? (int) $filters['_limit'] : null;
        unset($filters['_limit']);
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoTreinamento('u');
        $sql = "SELECT u.id AS adms_user_id, u.name AS colaborador_nome,
                       dep.name AS departamento_nome, pos.name AS cargo_nome
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  AND " . \App\adms\Helpers\InstitutionalSystemUserHelper::sqlExcludeUserIdColumn('u.id') . "
                  {$extraWhere}
                ORDER BY colaborador_nome
                LIMIT 500";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasTreinamentoPorUsuario($uid) as $row) {
                $out[] = array_merge($user, $row);
                if ($limit !== null && count($out) >= $limit) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /**
     * Pendências agrupadas por colaborador para envio de alertas (e-mail/notificação).
     *
     * @return array<int, array{user_id: int, user_name: string, user_email: string, itens: array<int, array<string, mixed>>}>
     */
    public function getPendenciasParaNotificacao(): array
    {
        $filters = [];
        $rows = array_merge(
            $this->getPendenciasEpiGeral($filters),
            $this->getPendenciasExameGeral($filters)
        );
        if (self::incluirTreinamentos()) {
            $rows = array_merge($rows, $this->getPendenciasTreinamentoGeral($filters));
        }

        $alertaveis = array_merge(self::SITUACOES_CRITICAS, [
            'troca_a_vencer',
            'aso_a_vencer',
        ]);
        if (self::incluirTreinamentos()) {
            $alertaveis[] = 'treinamento_a_vencer';
        }

        $grouped = [];
        foreach ($rows as $row) {
            $sit = (string) ($row['situacao'] ?? '');
            if (!in_array($sit, $alertaveis, true)) {
                continue;
            }
            $userId = (int) ($row['adms_user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            if (!isset($grouped[$userId])) {
                $grouped[$userId] = [
                    'user_id' => $userId,
                    'user_name' => (string) ($row['colaborador_nome'] ?? ''),
                    'user_email' => '',
                    'itens' => [],
                ];
            }
            $grouped[$userId]['itens'][] = $row;
        }

        if ($grouped === []) {
            return [];
        }

        $ids = implode(',', array_map('intval', array_keys($grouped)));
        $stmt = $this->getConnection()->query(
            "SELECT id, name, email FROM adms_users WHERE id IN ({$ids}) AND status = 'Ativo'"
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $user) {
            $uid = (int) $user['id'];
            if (isset($grouped[$uid])) {
                $grouped[$uid]['user_name'] = (string) ($user['name'] ?? $grouped[$uid]['user_name']);
                $grouped[$uid]['user_email'] = trim((string) ($user['email'] ?? ''));
            }
        }

        return array_values(array_filter($grouped, static fn(array $g): bool => $g['itens'] !== []));
    }

    /**
     * ASOs vencidos mesmo sem regra cadastrada (controle operacional puro).
     *
     * @return array<int, array<string, mixed>>
     */
    private function getAsosVencidosSemRegra(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT a.*, u.name AS colaborador_nome, ex.nome AS exame_nome, 'aso_vencido' AS situacao
                FROM adms_sst_asos a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                LEFT JOIN adms_sst_exames ex ON ex.id = a.adms_sst_exame_id
                WHERE a.data_validade IS NOT NULL
                  AND a.data_validade < CURDATE()
                  AND (a.status IS NULL OR a.status = 'Concluído')
                  AND u.status = 'Ativo'
                  {$extraWhere}
                ORDER BY a.data_validade ASC
                LIMIT 500";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'exame');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getEpisTrocaVencidaSemRegra(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT e.*, u.name AS colaborador_nome, ep.nome AS epi_nome, 'troca_vencida' AS situacao
                FROM adms_sst_epi_entregas e
                INNER JOIN adms_users u ON u.id = e.adms_user_id
                INNER JOIN adms_sst_epis ep ON ep.id = e.adms_sst_epi_id
                WHERE e.tipo_movimento = 'Entrega'
                  AND e.data_prevista_troca IS NOT NULL
                  AND e.data_prevista_troca < CURDATE()
                  AND u.status = 'Ativo'
                  {$extraWhere}
                ORDER BY e.data_prevista_troca ASC
                LIMIT 500";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'epi');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAfastamentosAtivosRelatorio(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_afastamentos a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.status = 'Ativo'
                  {$extraWhere}
                ORDER BY a.data_inicio DESC
                LIMIT 200";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getAcidentesAbertos(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT a.*, u.name AS colaborador_nome
                FROM adms_sst_acidentes a
                INNER JOIN adms_users u ON u.id = a.adms_user_id
                WHERE a.status IN ('Aberto', 'Em investigação')
                  {$extraWhere}
                ORDER BY a.data_ocorrencia DESC
                LIMIT 200";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function sqlRegraNecessidade(string $aliasNecessidade, string $aliasUser): string
    {
        return "({$aliasNecessidade}.adms_position_id IS NULL OR {$aliasNecessidade}.adms_position_id = {$aliasUser}.user_position_id)
                AND ({$aliasNecessidade}.adms_department_id IS NULL OR {$aliasNecessidade}.adms_department_id = {$aliasUser}.user_department_id)";
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function buildUserFilters(array $filters, string $userAlias): array
    {
        $where = '';
        $params = [];

        if (!empty($filters['adms_user_id'])) {
            $where .= " AND {$userAlias}.id = :filtro_user_id";
            $params[':filtro_user_id'] = (int) $filters['adms_user_id'];
        }
        if (!empty($filters['adms_department_id'])) {
            $where .= " AND {$userAlias}.user_department_id = :filtro_dep_id";
            $params[':filtro_dep_id'] = (int) $filters['adms_department_id'];
        }
        if (!empty($filters['situacao'])) {
            // aplicado via HAVING no caller quando possível — filtro pós-query em PHP se necessário
        }

        return [$where, $params];
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function executarPendencias(string $sql, array $params, string $tipo): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':dias', self::DIAS_ALERTA, PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], $tipo);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    private function enriquecerPendencias(array $rows, string $tipo): array
    {
        $labels = [
            'nao_entregue' => 'EPI não entregue',
            'troca_vencida' => 'Troca de EPI vencida',
            'troca_a_vencer' => 'Troca de EPI a vencer',
            'sem_aso' => 'Sem ASO/exame',
            'aso_vencido' => 'ASO/exame vencido',
            'aso_a_vencer' => 'ASO/exame a vencer',
            'aso_evento_sem_registro' => 'ASO não realizado',
            'aso_evento_vencido' => 'ASO vencido',
            'aso_evento_a_vencer' => 'ASO a vencer',
            'aso_aguardando_resultados' => 'ASO aguardando resultados',
            'sem_vinculo_treinamento' => 'Treinamento sem vínculo',
            'treinamento_vencido' => 'Treinamento vencido',
            'treinamento_pendente' => 'Treinamento pendente',
            'treinamento_a_vencer' => 'Treinamento a vencer',
        ];
        $badges = [
            'nao_entregue' => 'danger',
            'troca_vencida' => 'danger',
            'troca_a_vencer' => 'warning',
            'sem_aso' => 'danger',
            'aso_vencido' => 'danger',
            'aso_a_vencer' => 'warning',
            'aso_evento_sem_registro' => 'danger',
            'aso_evento_vencido' => 'danger',
            'aso_evento_a_vencer' => 'warning',
            'aso_aguardando_resultados' => 'info',
            'sem_vinculo_treinamento' => 'danger',
            'treinamento_vencido' => 'danger',
            'treinamento_pendente' => 'warning',
            'treinamento_a_vencer' => 'warning',
        ];

        foreach ($rows as &$row) {
            $sit = (string) ($row['situacao'] ?? '');
            $row['situacao_label'] = $labels[$sit] ?? $sit;
            $row['situacao_badge'] = $badges[$sit] ?? 'secondary';
            $row['tipo_pendencia'] = $row['tipo_pendencia'] ?? $tipo;
        }
        unset($row);

        return $rows;
    }

  /** @return array<string, mixed>|null */
    private function getUltimoAsoEvento(int $userId, string $categoria): ?array
    {
        $sql = "SELECT id, tipo, data_realizacao, data_validade, resultado, status
                FROM adms_sst_asos
                WHERE adms_user_id = :uid AND tipo = :tipo
                  AND (status IS NULL OR status = 'Concluído')
                ORDER BY data_realizacao DESC, id DESC
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', $categoria, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    private function getPeriodicidadePadraoCategoria(int $userId, string $categoria): int
    {
        $regras = (new SstExamesObrigatoriosResolver())->resolveForUser($userId, $categoria);

        return $this->maxPeriodicidadeMeses($regras);
    }

    /**
     * @param list<array<string, mixed>> $regras
     */
    private function maxPeriodicidadeMeses(array $regras): int
    {
        $max = 0;
        foreach ($regras as $regra) {
            $meses = (int) ($regra['periodicidade_meses'] ?? 0);
            if ($meses > $max) {
                $max = $meses;
            }
        }

        return $max > 0 ? $max : 12;
    }

    private function countPendenciasEpiCriticas(array $filters = []): int
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoEpi('u');
        $sql = "SELECT u.id AS adms_user_id
                FROM adms_users u
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  {$extraWhere}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $criticas = ['nao_entregue', 'troca_vencida'];
        $total = 0;
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasEpiPorUsuario($uid) as $row) {
                if (in_array($row['situacao'] ?? '', $criticas, true)) {
                    $total++;
                }
            }
        }

        return $total;
    }

    private function countPendenciasTreinamentoCriticas(array $filters = []): int
    {
        if (!self::incluirTreinamentos()) {
            return 0;
        }

        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoTreinamento('u');
        $sql = "SELECT u.id AS adms_user_id
                FROM adms_users u
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  AND " . \App\adms\Helpers\InstitutionalSystemUserHelper::sqlExcludeUserIdColumn('u.id') . "
                  {$extraWhere}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $criticas = ['sem_vinculo_treinamento', 'treinamento_vencido'];
        $total = 0;
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasTreinamentoPorUsuario($uid) as $row) {
                if (in_array($row['situacao'] ?? '', $criticas, true)) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /** @param array<string, mixed>|null $vinculo */
    private function avaliarSituacaoTreinamentoSst(?array $vinculo): ?string
    {
        if ($vinculo === null) {
            return 'sem_vinculo_treinamento';
        }
        $status = (string) ($vinculo['status'] ?? '');

        return match ($status) {
            'vencido' => 'treinamento_vencido',
            'pendente', 'agendado' => 'treinamento_pendente',
            'proximo_vencimento' => 'treinamento_a_vencer',
            default => null,
        };
    }

    private function sqlUsuarioComObrigacaoTreinamento(string $aliasUser = 'u'): string
    {
        $parts = [];
        if ($this->hasTable('adms_sst_treinamento_necessidade')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM adms_sst_treinamento_necessidade n
                INNER JOIN adms_sst_treinamentos tr ON tr.id = n.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE n.obrigatorio = 1
                  AND {$this->sqlRegraNecessidade('n', $aliasUser)}
                  AND (
                    n.adms_sst_risco_id IS NULL
                    OR EXISTS (
                        SELECT 1 FROM adms_sst_riscos_cargo rc2
                        WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                          AND (rc2.adms_position_id IS NULL OR rc2.adms_position_id = {$aliasUser}.user_position_id)
                          AND (rc2.adms_department_id IS NULL OR rc2.adms_department_id = {$aliasUser}.user_department_id)
                    )
                  )
            )";
        }
        if ($this->hasTable('adms_sst_risco_treinamento')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM adms_sst_riscos_cargo rc
                INNER JOIN adms_sst_risco_treinamento rt ON rt.adms_sst_risco_id = rc.adms_sst_risco_id AND rt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = rt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE (rc.adms_position_id IS NULL OR rc.adms_position_id = {$aliasUser}.user_position_id)
                  AND (rc.adms_department_id IS NULL OR rc.adms_department_id = {$aliasUser}.user_department_id)
            )";
        }
        if ($this->hasTable('adms_sst_ghe_colaboradores') && $this->hasTable('adms_sst_ghe_treinamentos')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM adms_sst_ghe_colaboradores gc
                INNER JOIN adms_sst_ghe g ON g.id = gc.adms_sst_ghe_id AND g.status = 'Ativo'
                INNER JOIN adms_sst_ghe_treinamentos gt ON gt.adms_sst_ghe_id = g.id AND gt.obrigatorio = 1
                INNER JOIN adms_sst_treinamentos tr ON tr.id = gt.adms_sst_treinamento_id AND tr.status = 'Ativo'
                WHERE gc.adms_user_id = {$aliasUser}.id AND gc.data_fim IS NULL
            )";
        }

        return $parts === [] ? '0' : '(' . implode(' OR ', $parts) . ')';
    }

    private function countPendenciasExameCriticas(array $filters = []): int
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $obrigacaoSql = $this->sqlUsuarioComObrigacaoExame('u');
        $sql = "SELECT u.id AS adms_user_id
                FROM adms_users u
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND {$obrigacaoSql}
                  {$extraWhere}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $criticas = self::SITUACOES_CRITICAS;
        $total = 0;
        foreach ($users as $user) {
            $uid = (int) ($user['adms_user_id'] ?? 0);
            if ($uid <= 0) {
                continue;
            }
            foreach ($this->getPendenciasExamePorUsuario($uid) as $row) {
                if (in_array($row['situacao'] ?? '', $criticas, true)) {
                    $total++;
                }
            }
        }

        return $total;
    }

    private function sqlUsuarioComObrigacaoExame(string $aliasUser = 'u'): string
    {
        $parts = [
            "EXISTS (
                SELECT 1 FROM adms_sst_exame_necessidade n
                INNER JOIN adms_sst_exames ex ON ex.id = n.adms_sst_exame_id AND ex.status = 'Ativo'
                WHERE n.obrigatorio = 1
                  AND {$this->sqlRegraNecessidade('n', $aliasUser)}
                  AND (
                    n.adms_sst_risco_id IS NULL
                    OR EXISTS (
                        SELECT 1 FROM adms_sst_riscos_cargo rc2
                        WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                          AND (rc2.adms_position_id IS NULL OR rc2.adms_position_id = {$aliasUser}.user_position_id)
                          AND (rc2.adms_department_id IS NULL OR rc2.adms_department_id = {$aliasUser}.user_department_id)
                    )
                  )
            )",
        ];

        if ($this->hasTable('adms_sst_risco_exame')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM adms_sst_riscos_cargo rc
                INNER JOIN adms_sst_risco_exame re ON re.adms_sst_risco_id = rc.adms_sst_risco_id AND re.obrigatorio = 1
                INNER JOIN adms_sst_exames ex ON ex.id = re.adms_sst_exame_id AND ex.status = 'Ativo'
                WHERE (rc.adms_position_id IS NULL OR rc.adms_position_id = {$aliasUser}.user_position_id)
                  AND (rc.adms_department_id IS NULL OR rc.adms_department_id = {$aliasUser}.user_department_id)
            )";
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    private function sqlUsuarioComObrigacaoEpi(string $aliasUser = 'u'): string
    {
        $parts = [
            "EXISTS (
                SELECT 1 FROM adms_sst_epi_necessidade n
                INNER JOIN adms_sst_epis ep ON ep.id = n.adms_sst_epi_id AND ep.status = 'Ativo'
                WHERE n.obrigatorio = 1
                  AND {$this->sqlRegraNecessidade('n', $aliasUser)}
                  AND (
                    n.adms_sst_risco_id IS NULL
                    OR EXISTS (
                        SELECT 1 FROM adms_sst_riscos_cargo rc2
                        WHERE rc2.adms_sst_risco_id = n.adms_sst_risco_id
                          AND (rc2.adms_position_id IS NULL OR rc2.adms_position_id = {$aliasUser}.user_position_id)
                          AND (rc2.adms_department_id IS NULL OR rc2.adms_department_id = {$aliasUser}.user_department_id)
                    )
                  )
            )",
        ];

        if ($this->hasTable('adms_sst_risco_epi')) {
            $parts[] = "EXISTS (
                SELECT 1 FROM adms_sst_riscos_cargo rc
                INNER JOIN adms_sst_risco_epi re ON re.adms_sst_risco_id = rc.adms_sst_risco_id AND re.obrigatorio = 1
                INNER JOIN adms_sst_epis ep ON ep.id = re.adms_sst_epi_id AND ep.status = 'Ativo'
                WHERE (rc.adms_position_id IS NULL OR rc.adms_position_id = {$aliasUser}.user_position_id)
                  AND (rc.adms_department_id IS NULL OR rc.adms_department_id = {$aliasUser}.user_department_id)
            )";
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /** @return array<string, mixed>|null */
    private function getUltimaEntregaEpi(int $userId, int $epiId): ?array
    {
        $sql = "SELECT e1.*
                FROM adms_sst_epi_entregas e1
                INNER JOIN (
                    SELECT adms_user_id, adms_sst_epi_id, MAX(data_movimento) AS max_data
                    FROM adms_sst_epi_entregas
                    WHERE tipo_movimento = 'Entrega' AND adms_user_id = :uid AND adms_sst_epi_id = :eid
                    GROUP BY adms_user_id, adms_sst_epi_id
                ) em ON em.adms_user_id = e1.adms_user_id
                    AND em.adms_sst_epi_id = e1.adms_sst_epi_id
                    AND em.max_data = e1.data_movimento
                    AND e1.tipo_movimento = 'Entrega'
                WHERE e1.adms_user_id = :uid2 AND e1.adms_sst_epi_id = :eid2
                LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':eid', $epiId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':eid2', $epiId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    /** @param array<string, mixed>|null $ultima */
    private function avaliarSituacaoEpi(?array $ultima): ?string
    {
        if ($ultima === null) {
            return 'nao_entregue';
        }
        $troca = $ultima['data_prevista_troca'] ?? null;
        if ($troca === null || $troca === '') {
            return null;
        }
        $hoje = date('Y-m-d');
        if ($troca < $hoje) {
            return 'troca_vencida';
        }
        $limite = date('Y-m-d', strtotime('+' . self::DIAS_ALERTA . ' days'));
        if ($troca <= $limite) {
            return 'troca_a_vencer';
        }

        return null;
    }

    private function hasTable(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :t LIMIT 1'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return array{criticas_count: int, epis_amostra: array, exames_amostra: array}|null
     */
    private function readDashboardCache(int $ttlSeconds): ?array
    {
        $file = dirname(__DIR__, 3) . '/storage/cache/sst/dashboard_pendencias.json';
        if (!is_readable($file)) {
            return null;
        }
        $payload = json_decode((string) file_get_contents($file), true);
        if (!is_array($payload) || !isset($payload['stored_at'], $payload['data'])) {
            return null;
        }
        if (time() - (int) $payload['stored_at'] > $ttlSeconds) {
            return null;
        }

        return is_array($payload['data']) ? $payload['data'] : null;
    }

    /**
     * @param array{criticas_count: int, epis_amostra: array, exames_amostra: array} $data
     */
    private function writeDashboardCache(array $data): void
    {
        $dir = dirname(__DIR__, 3) . '/storage/cache/sst';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $file = $dir . '/dashboard_pendencias.json';
        file_put_contents($file, json_encode([
            'stored_at' => time(),
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE));
    }

  /** @param array<string, mixed>|null $ultima */
    private function avaliarSituacaoExame(?array $ultima, ?int $periodicidadeMeses): ?string
    {
        if ($ultima === null) {
            return 'sem_aso';
        }
        $dataReal = (string) ($ultima['data_realizacao'] ?? $ultima['aso_data_realizacao'] ?? '');
        $dataVal = (string) ($ultima['data_validade'] ?? '');

        return $this->avaliarDatasVencimento($dataReal, $dataVal, $periodicidadeMeses, 'sem_aso', 'aso_vencido', 'aso_a_vencer');
    }

  /** @param array<string, mixed>|null $ultimo */
    private function avaliarSituacaoEventoAso(?array $ultimo, int $periodicidadeMeses): ?string
    {
        if ($ultimo === null) {
            return 'aso_evento_sem_registro';
        }
        $dataReal = (string) ($ultimo['data_realizacao'] ?? '');
        $dataVal = (string) ($ultimo['data_validade'] ?? '');

        return $this->avaliarDatasVencimento(
            $dataReal,
            $dataVal,
            $periodicidadeMeses,
            'aso_evento_sem_registro',
            'aso_evento_vencido',
            'aso_evento_a_vencer'
        );
    }

    private function avaliarDatasVencimento(
        string $dataRealizacao,
        string $dataValidade,
        ?int $periodicidadeMeses,
        string $semRegistro,
        string $vencido,
        string $aVencer
    ): ?string {
        $hoje = new \DateTimeImmutable('today');
        $validade = null;
        if ($dataValidade !== '') {
            $validade = \DateTimeImmutable::createFromFormat('Y-m-d', substr($dataValidade, 0, 10)) ?: null;
        } elseif ($dataRealizacao !== '' && $periodicidadeMeses !== null && $periodicidadeMeses > 0) {
            $real = \DateTimeImmutable::createFromFormat('Y-m-d', substr($dataRealizacao, 0, 10));
            if ($real) {
                $validade = $real->modify('+' . $periodicidadeMeses . ' months');
            }
        }
        if ($validade === null) {
            return $dataRealizacao === '' ? $semRegistro : null;
        }
        if ($validade < $hoje) {
            return $vencido;
        }
        $limiteAlerta = $hoje->modify('+' . self::DIAS_ALERTA . ' days');
        if ($validade <= $limiteAlerta) {
            return $aVencer;
        }

        return null;
    }

    public static function situacoesFiltro(): array
    {
        $filtros = [
            '' => 'Todas',
            'nao_entregue' => 'EPI não entregue',
            'troca_vencida' => 'Troca EPI vencida',
            'troca_a_vencer' => 'Troca EPI a vencer',
            'sem_aso' => 'Sem ASO',
            'aso_vencido' => 'ASO vencido',
            'aso_a_vencer' => 'ASO a vencer',
            'aso_evento_sem_registro' => 'ASO (evento) não realizado',
            'aso_evento_vencido' => 'ASO (evento) vencido',
            'aso_evento_a_vencer' => 'ASO (evento) a vencer',
        ];
        if (self::incluirTreinamentos()) {
            $filtros['sem_vinculo_treinamento'] = 'Treinamento sem vínculo';
            $filtros['treinamento_vencido'] = 'Treinamento vencido';
            $filtros['treinamento_pendente'] = 'Treinamento pendente';
            $filtros['treinamento_a_vencer'] = 'Treinamento a vencer';
        }

        return $filtros;
    }
}
