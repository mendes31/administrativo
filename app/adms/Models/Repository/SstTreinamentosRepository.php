<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\SstCatalogCodigoHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstTreinamentosRepository extends DbConnection
{
    public function getAll(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT t.*
                FROM adms_sst_treinamentos t
                {$whereClause}
                ORDER BY t.nome ASC, t.id DESC
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
        [$whereClause, $params] = $this->buildWhere($filters);
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_treinamentos t {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT t.* FROM adms_sst_treinamentos t WHERE t.id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return $this->hydrateRow($row);
    }

    /** @param array<string, mixed> $row */
    private function hydrateRow(array $row): array
    {
        $momentos = \App\adms\Helpers\SstTreinamentoAplicacaoHelper::decodeFromDb($row['aplicacao_momentos'] ?? null);
        if ($momentos === [] && !empty($row['tipo'])) {
            $momentos = \App\adms\Helpers\SstTreinamentoAplicacaoHelper::fromLegacyTipo((string) $row['tipo']);
        }
        $row['aplicacao_momentos'] = $momentos;

        return $row;
    }

    public function existsCodigo(string $codigo, ?int $excludeId = null): bool
    {
        if ($codigo === '') {
            return false;
        }

        $sql = 'SELECT id FROM adms_sst_treinamentos WHERE codigo = :codigo';
        $params = [':codigo' => strtoupper($codigo)];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getProximoCodigo(): string
    {
        $stmt = $this->getConnection()->query(
            "SELECT codigo FROM adms_sst_treinamentos WHERE codigo IS NOT NULL AND codigo <> ''"
        );
        $codigos = array_column($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [], 'codigo');

        return SstCatalogCodigoHelper::proximo(
            'TR',
            4,
            $codigos,
            fn (string $c) => $this->existsCodigo($c)
        );
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_treinamentos (
                    codigo, nome, descricao, nr_referencia, tipo, aplicacao_momentos, modalidade,
                    carga_horaria_minutos, validade_meses, prazo_primeiro_dias,
                    status, created_by, updated_by, created_at, updated_at
                ) VALUES (
                    :codigo, :nome, :descricao, :nr_referencia, :tipo, :aplicacao_momentos, :modalidade,
                    :carga_horaria_minutos, :validade_meses, :prazo_primeiro_dias,
                    :status, :created_by, :updated_by, NOW(), NOW()
                )';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindCatalogFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $newData = $this->getById($newId);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamentos', $newId, $uid, 'INSERT', [], $newData);
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldData = $this->getById($id);
        $sql = 'UPDATE adms_sst_treinamentos SET
                    codigo = :codigo,
                    nome = :nome,
                    descricao = :descricao,
                    nr_referencia = :nr_referencia,
                    tipo = :tipo,
                    aplicacao_momentos = :aplicacao_momentos,
                    modalidade = :modalidade,
                    carga_horaria_minutos = :carga_horaria_minutos,
                    validade_meses = :validade_meses,
                    prazo_primeiro_dias = :prazo_primeiro_dias,
                    status = :status,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $this->bindCatalogFields($stmt, $data);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && $oldData) {
            $newData = $this->getById($id);
            if ($newData) {
                LogAlteracaoService::registrarAlteracao('adms_sst_treinamentos', $id, $uid, 'UPDATE', $oldData, $newData);
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldData = $this->getById($id);
        $sql = 'DELETE FROM adms_sst_treinamentos WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $deleted = $stmt->rowCount() > 0;
        if ($deleted && $oldData) {
            $uid = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao('adms_sst_treinamentos', $id, $uid, 'DELETE', $oldData, []);
        }

        return $deleted;
    }

    /** @param array<string, mixed> $data */
    private function bindCatalogFields(\PDOStatement $stmt, array $data): void
    {
        $this->bindField($stmt, ':codigo', $data['codigo'] ?? null);
        $this->bindField($stmt, ':nome', $data['nome'] ?? null);
        $this->bindField($stmt, ':descricao', $data['descricao'] ?? null);
        $this->bindField($stmt, ':nr_referencia', $data['nr_referencia'] ?? null);
        $this->bindField($stmt, ':tipo', $data['tipo'] ?? 'Ambos');
        $aplicacaoJson = \App\adms\Helpers\SstTreinamentoAplicacaoHelper::encodeForDb(
            is_array($data['aplicacao_momentos'] ?? null) ? $data['aplicacao_momentos'] : []
        );
        $this->bindField($stmt, ':aplicacao_momentos', $aplicacaoJson);
        $this->bindField($stmt, ':modalidade', $data['modalidade'] ?? 'Presencial');
        $this->bindField($stmt, ':carga_horaria_minutos', $data['carga_horaria_minutos'] ?? null);
        $this->bindField($stmt, ':validade_meses', $data['validade_meses'] ?? null);
        $this->bindField($stmt, ':prazo_primeiro_dias', $data['prazo_primeiro_dias'] ?? null);
        $this->bindField($stmt, ':status', $data['status'] ?? 'Ativo');
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = [];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(t.nome LIKE :search OR t.codigo LIKE :search OR t.nr_referencia LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['status'])) {
            $where[] = 't.status = :status';
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['nr_referencia'])) {
            $where[] = 't.nr_referencia = :nr_referencia';
            $params[':nr_referencia'] = $filters['nr_referencia'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [$whereClause, $params];
    }

    private function bindField(\PDOStatement $stmt, string $param, mixed $value): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);

            return;
        }
        if (is_bool($value)) {
            $stmt->bindValue($param, $value ? 1 : 0, PDO::PARAM_INT);

            return;
        }
        if (is_int($value)) {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);

            return;
        }
        $stmt->bindValue($param, (string) $value, PDO::PARAM_STR);
    }
}
