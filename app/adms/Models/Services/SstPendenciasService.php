<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstCategoriaAsoHelper;
use App\adms\Models\Repository\SstAsoExamesRepository;
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
     * Pendências consolidadas de um colaborador.
     *
     * @return array{epis: array, exames: array, treinamentos: array, resumo: array}
     */
    public function getPendenciasPorUsuario(int $userId): array
    {
        $epis = $this->getPendenciasEpiPorUsuario($userId);
        $exames = array_merge(
            $this->getPendenciasExameComplementarPorUsuario($userId),
            $this->getPendenciasAsoEventoPorUsuario($userId)
        );
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
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasEpiPorUsuario(int $userId): array
    {
        $sql = "SELECT
                    n.adms_sst_epi_id,
                    ep.nome AS epi_nome,
                    ult.data_movimento AS ultima_entrega,
                    ult.data_prevista_troca,
                    CASE
                        WHEN ult.id IS NULL THEN 'nao_entregue'
                        WHEN ult.data_prevista_troca IS NOT NULL AND ult.data_prevista_troca < CURDATE() THEN 'troca_vencida'
                        WHEN ult.data_prevista_troca IS NOT NULL
                             AND ult.data_prevista_troca >= CURDATE()
                             AND ult.data_prevista_troca <= DATE_ADD(CURDATE(), INTERVAL :dias DAY) THEN 'troca_a_vencer'
                        ELSE NULL
                    END AS situacao
                FROM adms_users u
                INNER JOIN adms_sst_epi_necessidade n ON {$this->sqlRegraNecessidade('n', 'u')}
                INNER JOIN adms_sst_epis ep ON ep.id = n.adms_sst_epi_id AND ep.status = 'Ativo'
                LEFT JOIN (
                    SELECT e1.*
                    FROM adms_sst_epi_entregas e1
                    INNER JOIN (
                        SELECT adms_user_id, adms_sst_epi_id, MAX(data_movimento) AS max_data
                        FROM adms_sst_epi_entregas
                        WHERE tipo_movimento = 'Entrega' AND adms_user_id = :uid
                        GROUP BY adms_user_id, adms_sst_epi_id
                    ) em ON em.adms_user_id = e1.adms_user_id
                        AND em.adms_sst_epi_id = e1.adms_sst_epi_id
                        AND em.max_data = e1.data_movimento
                        AND e1.tipo_movimento = 'Entrega'
                ) ult ON ult.adms_user_id = u.id AND ult.adms_sst_epi_id = n.adms_sst_epi_id
                WHERE u.id = :uid
                  AND n.obrigatorio = 1
                HAVING situacao IS NOT NULL
                ORDER BY epi_nome";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':dias', self::DIAS_ALERTA, PDO::PARAM_INT);
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'epi');
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
        foreach ([SstCategoriaAsoHelper::PERIODICO] as $categoria) {
            $ultimo = $this->getUltimoAsoEvento($userId, $categoria);
            $periodicidade = $this->getPeriodicidadePadraoCategoria($userId, $categoria);
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

    /** @deprecated Use getPendenciasExameComplementarPorUsuario + getPendenciasAsoEventoPorUsuario */
    public function getPendenciasExamePorUsuario(int $userId): array
    {
        return array_merge(
            $this->getPendenciasExameComplementarPorUsuario($userId),
            $this->getPendenciasAsoEventoPorUsuario($userId)
        );
    }

    /**
     * Treinamentos obrigatórios do cargo sem vínculo válido ou com status pendente/vencido.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPendenciasTreinamentoPorUsuario(int $userId): array
    {
        if (!self::incluirTreinamentos()) {
            return [];
        }

        $sql = "SELECT
                    t.id AS adms_training_id,
                    t.nome AS treinamento_nome,
                    t.codigo AS treinamento_codigo,
                    tu.status AS treinamento_status,
                    CASE
                        WHEN tu.id IS NULL THEN 'sem_vinculo_treinamento'
                        WHEN tu.status = 'vencido' THEN 'treinamento_vencido'
                        WHEN tu.status IN ('pendente', 'agendado') THEN 'treinamento_pendente'
                        WHEN tu.status = 'proximo_vencimento' THEN 'treinamento_a_vencer'
                        ELSE NULL
                    END AS situacao
                FROM adms_users u
                INNER JOIN adms_training_positions tp
                    ON tp.adms_position_id = u.user_position_id AND tp.obrigatorio = 1
                INNER JOIN adms_trainings t ON t.id = tp.adms_training_id AND t.ativo = 1
                LEFT JOIN (
                    SELECT tu1.*
                    FROM adms_training_users tu1
                    INNER JOIN (
                        SELECT adms_user_id, adms_training_id, MAX(id) AS max_id
                        FROM adms_training_users
                        WHERE adms_user_id = :uid_tu
                        GROUP BY adms_user_id, adms_training_id
                    ) latest ON latest.max_id = tu1.id
                ) tu ON tu.adms_user_id = u.id AND tu.adms_training_id = t.id
                WHERE u.id = :uid
                HAVING situacao IS NOT NULL
                ORDER BY treinamento_nome";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_tu', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'treinamento');
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
            'afastamentos_ativos' => $this->getAfastamentosAtivos($filters),
            'acidentes_abertos' => $this->getAcidentesAbertos($filters),
        ];
    }

    public function countPendenciasCriticas(): int
    {
        $report = $this->getRelatorioCompleto();
        $criticas = self::SITUACOES_CRITICAS;

        $total = 0;
        $keys = ['epis_obrigatorios', 'exames_obrigatorios'];
        if (self::incluirTreinamentos()) {
            $keys[] = 'treinamentos_obrigatorios';
        }
        foreach ($keys as $key) {
            foreach ($report[$key] ?? [] as $row) {
                if (in_array($row['situacao'] ?? '', $criticas, true)) {
                    $total++;
                }
            }
        }

        return $total;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendenciasEpiGeral(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT
                    u.id AS adms_user_id,
                    u.name AS colaborador_nome,
                    dep.name AS departamento_nome,
                    pos.name AS cargo_nome,
                    n.adms_sst_epi_id,
                    ep.nome AS epi_nome,
                    ult.data_movimento AS ultima_entrega,
                    ult.data_prevista_troca,
                    CASE
                        WHEN ult.id IS NULL THEN 'nao_entregue'
                        WHEN ult.data_prevista_troca IS NOT NULL AND ult.data_prevista_troca < CURDATE() THEN 'troca_vencida'
                        WHEN ult.data_prevista_troca IS NOT NULL
                             AND ult.data_prevista_troca >= CURDATE()
                             AND ult.data_prevista_troca <= DATE_ADD(CURDATE(), INTERVAL :dias DAY) THEN 'troca_a_vencer'
                        ELSE NULL
                    END AS situacao
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                INNER JOIN adms_sst_epi_necessidade n ON {$this->sqlRegraNecessidade('n', 'u')}
                INNER JOIN adms_sst_epis ep ON ep.id = n.adms_sst_epi_id AND ep.status = 'Ativo'
                LEFT JOIN (
                    SELECT e1.*
                    FROM adms_sst_epi_entregas e1
                    INNER JOIN (
                        SELECT adms_user_id, adms_sst_epi_id, MAX(data_movimento) AS max_data
                        FROM adms_sst_epi_entregas
                        WHERE tipo_movimento = 'Entrega'
                        GROUP BY adms_user_id, adms_sst_epi_id
                    ) em ON em.adms_user_id = e1.adms_user_id
                        AND em.adms_sst_epi_id = e1.adms_sst_epi_id
                        AND em.max_data = e1.data_movimento
                        AND e1.tipo_movimento = 'Entrega'
                ) ult ON ult.adms_user_id = u.id AND ult.adms_sst_epi_id = n.adms_sst_epi_id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  AND n.obrigatorio = 1
                  {$extraWhere}
                HAVING situacao IS NOT NULL
                ORDER BY colaborador_nome, epi_nome
                LIMIT 1000";

        return $this->executarPendencias($sql, $params, 'epi');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getPendenciasExameGeral(array $filters = []): array
    {
        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');
        $sql = "SELECT u.id AS adms_user_id, u.name AS colaborador_nome,
                       dep.name AS departamento_nome, pos.name AS cargo_nome
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
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

        [$extraWhere, $params] = $this->buildUserFilters($filters, 'u');

        $sql = "SELECT
                    u.id AS adms_user_id,
                    u.name AS colaborador_nome,
                    dep.name AS departamento_nome,
                    pos.name AS cargo_nome,
                    t.id AS adms_training_id,
                    t.nome AS treinamento_nome,
                    t.codigo AS treinamento_codigo,
                    tu.status AS treinamento_status,
                    CASE
                        WHEN tu.id IS NULL THEN 'sem_vinculo_treinamento'
                        WHEN tu.status = 'vencido' THEN 'treinamento_vencido'
                        WHEN tu.status IN ('pendente', 'agendado') THEN 'treinamento_pendente'
                        WHEN tu.status = 'proximo_vencimento' THEN 'treinamento_a_vencer'
                        ELSE NULL
                    END AS situacao
                FROM adms_users u
                LEFT JOIN adms_departments dep ON dep.id = u.user_department_id
                LEFT JOIN adms_positions pos ON pos.id = u.user_position_id
                INNER JOIN adms_training_positions tp
                    ON tp.adms_position_id = u.user_position_id AND tp.obrigatorio = 1
                INNER JOIN adms_trainings t ON t.id = tp.adms_training_id AND t.ativo = 1
                LEFT JOIN (
                    SELECT tu1.*
                    FROM adms_training_users tu1
                    INNER JOIN (
                        SELECT adms_user_id, adms_training_id, MAX(id) AS max_id
                        FROM adms_training_users
                        GROUP BY adms_user_id, adms_training_id
                    ) latest ON latest.max_id = tu1.id
                ) tu ON tu.adms_user_id = u.id AND tu.adms_training_id = t.id
                WHERE u.status = 'Ativo'
                  AND (u.data_desligamento IS NULL)
                  {$extraWhere}
                HAVING situacao IS NOT NULL
                ORDER BY colaborador_nome, treinamento_nome
                LIMIT 1000";

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->enriquecerPendencias($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'treinamento');
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
    private function getAfastamentosAtivos(array $filters = []): array
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
        $sql = "SELECT id, tipo, data_realizacao, data_validade, resultado
                FROM adms_sst_asos
                WHERE adms_user_id = :uid AND tipo = :tipo
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
        $resolver = new SstExamesObrigatoriosResolver();
        $max = 0;
        foreach ($resolver->resolveForUser($userId, $categoria) as $regra) {
            $meses = (int) ($regra['periodicidade_meses'] ?? 0);
            if ($meses > $max) {
                $max = $meses;
            }
        }

        return $max > 0 ? $max : 12;
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
