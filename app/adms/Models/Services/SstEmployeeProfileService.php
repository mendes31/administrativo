<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SstRiscoCargoMatch;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Dados consolidados do perfil SST ocupacional do colaborador.
 */
class SstEmployeeProfileService extends DbConnection
{
    /**
     * @return array<string, mixed>
     */
    public function getResumo(int $userId): array
    {
        $ultimoAso = $this->fetchOne(
            'SELECT a.*, ex.nome AS exame_nome
             FROM adms_sst_asos a
             LEFT JOIN adms_sst_exames ex ON ex.id = a.adms_sst_exame_id
             WHERE a.adms_user_id = :uid
             ORDER BY a.data_realizacao DESC, a.id DESC
             LIMIT 1',
            [':uid' => $userId]
        );

        $afastamentoAtivo = $this->fetchOne(
            'SELECT a.*, c.codigo AS cid_codigo, c.descricao AS cid_descricao
             FROM adms_sst_afastamentos a
             LEFT JOIN adms_sst_cids c ON c.id = a.adms_sst_cid_id
             WHERE a.adms_user_id = :uid AND a.status = :status
             ORDER BY a.data_inicio DESC
             LIMIT 1',
            [':uid' => $userId, ':status' => 'Ativo']
        );

        $proximosExames = $this->getProximosExames($userId);
        $pendencias = (new SstPendenciasService())->getPendenciasPorUsuario($userId);

        return [
            'ultimo_aso' => $ultimoAso,
            'afastamento_ativo' => $afastamentoAtivo,
            'proximos_exames' => $proximosExames,
            'pendencias_total' => (int) ($pendencias['resumo']['total'] ?? 0),
            'pendencias_criticas' => $this->countCriticas($pendencias),
        ];
    }

    /**
     * Riscos vinculados ao cargo/departamento do colaborador.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRiscosVinculados(int $userId): array
    {
        $sql = "SELECT rc.id, rc.nivel, rc.observacoes,
                       r.nome AS risco_nome,
                       COALESCE(r.grupo_risco, r.tipo) AS risco_tipo,
                       p.name AS cargo_regra, d.name AS departamento_regra
                FROM adms_users u
                INNER JOIN adms_sst_riscos_cargo rc ON {$this->sqlRegraCargoDep('rc', 'u')}
                INNER JOIN adms_sst_riscos r ON r.id = rc.adms_sst_risco_id AND r.status = 'Ativo'
                LEFT JOIN adms_positions p ON p.id = rc.adms_position_id
                LEFT JOIN adms_departments d ON d.id = rc.adms_department_id
                WHERE u.id = :uid
                ORDER BY r.nome";

        return $this->fetchAll($sql, [':uid' => $userId]);
    }

    /**
     * EPIs (cargo + GHE, sem duplicar o mesmo item) e exames por cargo/departamento.
     *
     * @return array{epis: array, exames: array}
     */
    public function getObrigatoriedades(int $userId): array
    {
        $episRegras = (new SstEpisObrigatoriosResolver())->resolveForUser($userId);
        $epis = [];
        foreach ($episRegras as $regra) {
            $epis[] = [
                'adms_sst_epi_id' => (int) ($regra['adms_sst_epi_id'] ?? 0),
                'obrigatorio' => 1,
                'epi_nome' => (string) ($regra['epi_nome'] ?? ''),
                'origem' => (string) ($regra['origem'] ?? ''),
                'cargo_regra' => null,
                'departamento_regra' => null,
                'observacoes' => null,
            ];
        }

        $examesSql = "SELECT n.id, n.obrigatorio, n.periodicidade_meses, n.observacoes,
                             ex.nome AS exame_nome, ex.periodicidade_meses AS exame_periodicidade_padrao,
                             p.name AS cargo_regra, d.name AS departamento_regra
                      FROM adms_users u
                      INNER JOIN adms_sst_exame_necessidade n ON {$this->sqlRegraCargoDep('n', 'u')}
                      INNER JOIN adms_sst_exames ex ON ex.id = n.adms_sst_exame_id AND ex.status = 'Ativo'
                      LEFT JOIN adms_positions p ON p.id = n.adms_position_id
                      LEFT JOIN adms_departments d ON d.id = n.adms_department_id
                      WHERE u.id = :uid AND n.obrigatorio = 1
                      ORDER BY ex.nome";

        return [
            'epis' => $epis,
            'exames' => $this->fetchAll($examesSql, [':uid' => $userId]),
        ];
    }

    /**
     * Linha do tempo unificada (ASO, EPI, afastamento, acidente).
     *
     * @param array<int, array<string, mixed>> $asos
     * @param array<int, array<string, mixed>> $epiEntregas
     * @param array<int, array<string, mixed>> $afastamentos
     * @param array<int, array<string, mixed>> $acidentes
     * @return array<int, array<string, mixed>>
     */
    public function buildTimeline(array $asos, array $epiEntregas, array $afastamentos, array $acidentes): array
    {
        $events = [];

        foreach ($asos as $row) {
            $events[] = [
                'data' => (string) ($row['data_realizacao'] ?? ''),
                'tipo' => 'aso',
                'icone' => 'fa-file-medical',
                'cor' => 'primary',
                'titulo' => 'ASO — ' . ($row['tipo'] ?? ''),
                'detalhe' => trim(($row['exame_nome'] ?? '') . ' · ' . ($row['resultado'] ?? '')),
                'id' => (int) ($row['id'] ?? 0),
                'url' => 'sst-view-aso/',
            ];
        }

        foreach ($epiEntregas as $row) {
            $fichaId = (int) ($row['adms_sst_epi_ficha_id'] ?? 0);
            if ($fichaId <= 0) {
                continue;
            }
            $events[] = [
                'data' => (string) ($row['data_movimento'] ?? ''),
                'tipo' => 'epi',
                'icone' => 'fa-hard-hat',
                'cor' => 'warning',
                'titulo' => 'EPI — ' . ($row['tipo_movimento'] ?? 'Movimento'),
                'detalhe' => (string) ($row['epi_nome'] ?? ''),
                'id' => $fichaId,
                'url' => 'sst-view-epi-ficha/',
            ];
        }

        foreach ($afastamentos as $row) {
            $events[] = [
                'data' => (string) ($row['data_inicio'] ?? ''),
                'tipo' => 'afastamento',
                'icone' => 'fa-procedures',
                'cor' => 'info',
                'titulo' => 'Afastamento — ' . ($row['tipo'] ?? ''),
                'detalhe' => (string) ($row['status'] ?? ''),
                'id' => (int) ($row['id'] ?? 0),
                'url' => 'sst-view-afastamento/',
            ];
        }

        foreach ($acidentes as $row) {
            $data = (string) ($row['data_ocorrencia'] ?? '');
            if (strlen($data) > 10) {
                $data = substr($data, 0, 10);
            }
            $events[] = [
                'data' => $data,
                'tipo' => 'acidente',
                'icone' => 'fa-ambulance',
                'cor' => 'danger',
                'titulo' => ($row['tipo'] ?? 'Acidente') . ' — ' . ($row['status'] ?? ''),
                'detalhe' => !empty($row['descricao']) ? mb_substr((string) $row['descricao'], 0, 80) : '',
                'id' => (int) ($row['id'] ?? 0),
                'url' => 'sst-view-acidente/',
            ];
        }

        usort($events, static function (array $a, array $b): int {
            return strcmp($b['data'] ?? '', $a['data'] ?? '');
        });

        return $events;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getProximosExames(int $userId): array
    {
        $sql = "SELECT
                    ex.nome AS exame_nome,
                    COALESCE(n.periodicidade_meses, ex.periodicidade_meses) AS periodicidade_meses,
                    ult.data_realizacao AS ultimo_aso,
                    ult.data_validade,
                    CASE
                        WHEN ult.data_validade IS NOT NULL THEN ult.data_validade
                        WHEN ult.data_realizacao IS NOT NULL
                             AND COALESCE(n.periodicidade_meses, ex.periodicidade_meses) IS NOT NULL
                        THEN DATE_ADD(ult.data_realizacao, INTERVAL COALESCE(n.periodicidade_meses, ex.periodicidade_meses) MONTH)
                        ELSE NULL
                    END AS proxima_data
                FROM adms_users u
                INNER JOIN adms_sst_exame_necessidade n ON {$this->sqlRegraCargoDep('n', 'u')}
                INNER JOIN adms_sst_exames ex ON ex.id = n.adms_sst_exame_id AND ex.status = 'Ativo'
                LEFT JOIN (
                    SELECT a1.*
                    FROM adms_sst_asos a1
                    INNER JOIN (
                        SELECT adms_sst_exame_id, MAX(data_realizacao) AS max_data
                        FROM adms_sst_asos
                        WHERE adms_user_id = :uid_aso
                        GROUP BY adms_sst_exame_id
                    ) am ON am.adms_sst_exame_id <=> a1.adms_sst_exame_id
                        AND am.max_data = a1.data_realizacao
                        AND a1.adms_user_id = :uid_aso2
                ) ult ON ult.adms_sst_exame_id = n.adms_sst_exame_id
                WHERE u.id = :uid AND n.obrigatorio = 1
                ORDER BY proxima_data IS NULL, proxima_data ASC, ex.nome";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_aso', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid_aso2', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $pendencias
     */
    private function countCriticas(array $pendencias): int
    {
        $criticas = SstPendenciasService::SITUACOES_CRITICAS;
        $total = 0;
        foreach (['epis', 'exames', 'treinamentos'] as $key) {
            foreach ($pendencias[$key] ?? [] as $row) {
                if (in_array($row['situacao'] ?? '', $criticas, true)) {
                    $total++;
                }
            }
        }
        return $total;
    }

    private function sqlRegraCargoDep(string $alias, string $userAlias): string
    {
        return SstRiscoCargoMatch::sqlUsuario($alias, $userAlias);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    private function fetchOne(string $sql, array $params): ?array
    {
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql, array $params): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
