<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstEquipamentoQrHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\SstEquipamentoCodigoService;
use PDO;
use Throwable;

class SstEquipamentosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT e.*, t.nome AS tipo_nome,
                       t.controla_recarga, t.validade_recarga_meses,
                       d.name AS departamento_nome, u.name AS responsavel_nome,
                       (SELECT COUNT(*) FROM adms_sst_equipamento_vistorias v
                        WHERE v.adms_sst_equipamento_id = e.id AND v.status IN ('Pendente','Em andamento','Vencida')) AS vistorias_pendentes
                FROM adms_sst_equipamentos e
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_departments d ON d.id = e.adms_department_id
                LEFT JOIN adms_users u ON u.id = e.responsavel_adms_user_id
                {$where}
                ORDER BY e.codigo ASC
                LIMIT :limit OFFSET :offset";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getTotal(array $filters = []): int
    {
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_equipamentos e
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT e.*, t.nome AS tipo_nome, t.codigo AS tipo_codigo,
                       t.controla_recarga, t.validade_recarga_meses, t.prefixo AS tipo_prefixo,
                       d.name AS departamento_nome, u.name AS responsavel_nome
                FROM adms_sst_equipamentos e
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_departments d ON d.id = e.adms_department_id
                LEFT JOIN adms_users u ON u.id = e.responsavel_adms_user_id
                WHERE e.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function getActiveForGeneration(): array
    {
        $sql = "SELECT e.*, t.nome AS tipo_nome
                FROM adms_sst_equipamentos e
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                WHERE e.status = 'Ativo' AND t.status = 'Ativo'
                  AND COALESCE(e.vistoria_automatica, 1) = 1
                ORDER BY e.id";
        $stmt = $this->getConnection()->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function create(array $data): int|false
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $tipoId = (int) ($data['adms_sst_equipamento_tipo_id'] ?? 0);
        if ($tipoId <= 0) {
            return false;
        }

        $pdo = $this->getConnection();
        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }

        try {
            $data['codigo'] = (new SstEquipamentoCodigoService())->allocateNextCodigo($tipoId, $pdo);

            $sql = 'INSERT INTO adms_sst_equipamentos (
                        codigo, patrimonio, adms_sst_equipamento_tipo_id, adms_department_id, localizacao,
                        fabricante, modelo, numero_serie, capacidade, data_fabricacao, data_recarga, data_proxima_recarga,
                        caracteristicas, periodicidade_meses, data_referencia_inspecao, dia_previsto_vistoria,
                        vistoria_automatica, responsavel_adms_user_id,
                        status, observacoes, created_by, updated_by, created_at, updated_at
                    ) VALUES (
                        :codigo, :patrimonio, :tipo_id, :dept_id, :localizacao,
                        :fabricante, :modelo, :numero_serie, :capacidade, :data_fabricacao, :data_recarga, :data_proxima_recarga,
                        :caracteristicas, :periodicidade_meses, :data_referencia_inspecao, :dia_previsto_vistoria,
                        :vistoria_automatica, :responsavel_id,
                        :status, :observacoes, :uid, :uid, NOW(), NOW()
                    )';
            $stmt = $pdo->prepare($sql);
            $this->bindEquipamento($stmt, $data, $uid);
            if (!$stmt->execute()) {
                if ($ownsTransaction && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                return false;
            }

            $newId = (int) $pdo->lastInsertId();
            if ($ownsTransaction) {
                $pdo->commit();
            }
            if ($newId > 0) {
                $this->ensureQrToken($newId);
            }

            return $newId;
        } catch (Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return false;
        }
    }

    public function getByQrToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        $sql = "SELECT e.*, t.nome AS tipo_nome, t.codigo AS tipo_codigo,
                       t.controla_recarga, t.validade_recarga_meses, t.prefixo AS tipo_prefixo,
                       d.name AS departamento_nome, u.name AS responsavel_nome
                FROM adms_sst_equipamentos e
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_departments d ON d.id = e.adms_department_id
                LEFT JOIN adms_users u ON u.id = e.responsavel_adms_user_id
                WHERE e.qr_token = :token LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':token', $token);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function ensureQrToken(int $id): ?string
    {
        $item = $this->getById($id);
        if (!$item) {
            return null;
        }
        $existing = trim((string) ($item['qr_token'] ?? ''));
        if ($existing !== '') {
            return $existing;
        }

        for ($i = 0; $i < 5; $i++) {
            $token = SstEquipamentoQrHelper::generateToken();
            $sql = 'UPDATE adms_sst_equipamentos SET qr_token = :token, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            if ($stmt->execute()) {
                return $token;
            }
        }

        return null;
    }

    public function update(int $id, array $data): bool
    {
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $existing = $this->getById($id);
        if (!$existing) {
            return false;
        }
        // Código é imutável após o cadastro (gerado automaticamente).
        $data['codigo'] = (string) ($existing['codigo'] ?? '');

        $sql = 'UPDATE adms_sst_equipamentos SET
                    codigo = :codigo, patrimonio = :patrimonio, adms_sst_equipamento_tipo_id = :tipo_id,
                    adms_department_id = :dept_id, localizacao = :localizacao,
                    fabricante = :fabricante, modelo = :modelo, numero_serie = :numero_serie, capacidade = :capacidade,
                    data_fabricacao = :data_fabricacao, data_recarga = :data_recarga, data_proxima_recarga = :data_proxima_recarga,
                    caracteristicas = :caracteristicas, periodicidade_meses = :periodicidade_meses,
                    data_referencia_inspecao = :data_referencia_inspecao, dia_previsto_vistoria = :dia_previsto_vistoria,
                    vistoria_automatica = :vistoria_automatica, responsavel_adms_user_id = :responsavel_id,
                    status = :status, observacoes = :observacoes, updated_by = :uid, updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindEquipamento($stmt, $data, $uid);

        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $sql = 'DELETE FROM adms_sst_equipamentos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /** @return list<array<string, mixed>> */
    public function getVistoriaHistorico(int $equipamentoId, int $limit = 24): array
    {
        $sql = "SELECT v.*, u.name AS executor_nome
                FROM adms_sst_equipamento_vistorias v
                LEFT JOIN adms_users u ON u.id = v.executor_adms_user_id
                WHERE v.adms_sst_equipamento_id = :id
                ORDER BY v.competencia DESC, v.id DESC
                LIMIT :limit";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $equipamentoId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function bindEquipamento(\PDOStatement $stmt, array $data, int $uid): void
    {
        $stmt->bindValue(':codigo', strtoupper(trim((string) ($data['codigo'] ?? ''))));
        $stmt->bindValue(':patrimonio', $data['patrimonio'] ?? null);
        $stmt->bindValue(':tipo_id', (int) ($data['adms_sst_equipamento_tipo_id'] ?? 0), PDO::PARAM_INT);
        $stmt->bindValue(':dept_id', !empty($data['adms_department_id']) ? (int) $data['adms_department_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':localizacao', $data['localizacao'] ?? null);
        $stmt->bindValue(':fabricante', $data['fabricante'] ?? null);
        $stmt->bindValue(':modelo', $data['modelo'] ?? null);
        $stmt->bindValue(':numero_serie', $data['numero_serie'] ?? null);
        $stmt->bindValue(':capacidade', $data['capacidade'] ?? null);
        $stmt->bindValue(':data_fabricacao', $data['data_fabricacao'] ?? null);
        $stmt->bindValue(':data_recarga', $data['data_recarga'] ?? null);
        $stmt->bindValue(':data_proxima_recarga', $data['data_proxima_recarga'] ?? null);
        $car = $data['caracteristicas'] ?? null;
        if (is_array($car)) {
            $car = json_encode($car, JSON_UNESCAPED_UNICODE);
        }
        $stmt->bindValue(':caracteristicas', $car ?: null);
        $stmt->bindValue(':periodicidade_meses', (int) ($data['periodicidade_meses'] ?? 1), PDO::PARAM_INT);
        $stmt->bindValue(':data_referencia_inspecao', $data['data_referencia_inspecao'] ?? null);
        $diaPrev = $data['dia_previsto_vistoria'] ?? null;
        if ($diaPrev === '' || $diaPrev === '0') {
            $diaPrev = null;
        }
        $stmt->bindValue(':dia_previsto_vistoria', $diaPrev !== null ? (int) $diaPrev : null, $diaPrev !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':vistoria_automatica', !empty($data['vistoria_automatica']) ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':responsavel_id', !empty($data['responsavel_adms_user_id']) ? (int) $data['responsavel_adms_user_id'] : null, PDO::PARAM_INT);
        $stmt->bindValue(':status', $data['status'] ?? 'Ativo');
        $stmt->bindValue(':observacoes', $data['observacoes'] ?? null);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = ['1=1'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(e.codigo LIKE :search OR e.patrimonio LIKE :search OR e.localizacao LIKE :search OR t.nome LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_sst_equipamento_tipo_id'])) {
            $where[] = 'e.adms_sst_equipamento_tipo_id = :tipo_id';
            $params[':tipo_id'] = (int) $filters['adms_sst_equipamento_tipo_id'];
        }
        if (!empty($filters['status'])) {
            $where[] = 'e.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['adms_department_id'])) {
            $where[] = 'e.adms_department_id = :dept_id';
            $params[':dept_id'] = (int) $filters['adms_department_id'];
        }
        if (!empty($filters['recarga_alerta'])) {
            $where[] = "t.controla_recarga = 1 AND (
                e.data_proxima_recarga IS NULL
                OR e.data_proxima_recarga < CURDATE()
                OR e.data_proxima_recarga <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            )";
        }

        return [' WHERE ' . implode(' AND ', $where), $params];
    }
}
