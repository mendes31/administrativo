<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

final class PortariaPontosRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getAll(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT p.*, COALESCE(NULLIF(b.nome_fantasia, ''), b.name) AS filial_nome
                 FROM portaria_pontos_controle p
                 LEFT JOIN adms_branches b ON b.id = p.adms_branch_id
                 ORDER BY p.ativo DESC, p.nome ASC"
            );
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaPontosRepository::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare(
                "SELECT p.*, COALESCE(NULLIF(b.nome_fantasia, ''), b.name) AS filial_nome
                 FROM portaria_pontos_controle p
                 LEFT JOIN adms_branches b ON b.id = p.adms_branch_id
                 WHERE p.id = :id LIMIT 1"
            );
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaPontosRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_pontos_controle (nome, codigo, adms_branch_id, ativo)
                 VALUES (:nome, :codigo, :branch_id, :ativo)'
            );
            $this->bindFields($stmt, $data);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            return $id > 0 ? $id : false;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaPontosRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE portaria_pontos_controle
                 SET nome = :nome, codigo = :codigo, adms_branch_id = :branch_id, ativo = :ativo
                 WHERE id = :id'
            );
            $this->bindFields($stmt, $data);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaPontosRepository::update', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @return list<array<string, mixed>> */
    public function listAtivos(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                'SELECT id, nome, codigo FROM portaria_pontos_controle WHERE ativo = 1 ORDER BY nome'
            );
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaPontosRepository::listAtivos', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @param array<string, mixed> $data */
    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        $codigo = trim((string) ($data['codigo'] ?? ''));
        $branchId = (int) ($data['adms_branch_id'] ?? 0);
        $stmt->bindValue(':nome', trim((string) ($data['nome'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':codigo', $codigo !== '' ? $codigo : null, $codigo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':branch_id', $branchId > 0 ? $branchId : null, $branchId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ativo', !empty($data['ativo']) ? 1 : 0, PDO::PARAM_INT);
    }
}
