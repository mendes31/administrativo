<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

final class UserEducationsRepository extends DbConnection
{
    /** @return list<array<string, mixed>> */
    public function getByUserId(int $userId): array
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_user_educations
             WHERE adms_user_id = :user_id
             ORDER BY COALESCE(data_conclusao, data_inicio) DESC, id DESC'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<string, mixed>|false */
    public function getByIdForUser(int $id, int $userId): array|false
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_user_educations WHERE id = :id AND adms_user_id = :user_id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /** @return array<string, mixed>|false */
    public function getById(int $id): array|false
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT * FROM adms_user_educations WHERE id = :id LIMIT 1'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
    }

    /** @return list<array<string, mixed>> */
    public function getByUserIds(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $id): bool => $id > 0)));
        if ($userIds === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $stmt = $this->getConnection()->prepare(
            "SELECT * FROM adms_user_educations
             WHERE adms_user_id IN ({$placeholders})
             ORDER BY adms_user_id, COALESCE(data_conclusao, data_inicio) DESC, id DESC"
        );
        foreach ($userIds as $index => $id) {
            $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data, ?int $actorId = null): int
    {
        $sql = 'INSERT INTO adms_user_educations
                (adms_user_id, tipo, curso, instituicao, situacao, data_inicio, data_conclusao,
                 carga_horaria, observacoes, comprovante_path, comprovante_nome_original,
                 comprovante_mime, comprovante_tamanho, created_by, updated_by, created_at, updated_at)
                VALUES
                (:user_id, :tipo, :curso, :instituicao, :situacao, :data_inicio, :data_conclusao,
                 :carga_horaria, :observacoes, :comprovante_path, :comprovante_nome_original,
                 :comprovante_mime, :comprovante_tamanho, :created_by, :updated_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindData($stmt, $userId, $data, $actorId);
        $stmt->execute();
        $id = (int) $this->getConnection()->lastInsertId();
        $after = $this->getById($id);
        if ($id > 0 && is_array($after)) {
            $this->log($id, 'INSERT', [], $after, $actorId);
        }

        return $id;
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, int $userId, array $data, ?int $actorId = null): bool
    {
        $before = $this->getByIdForUser($id, $userId);
        if (!is_array($before)) {
            return false;
        }
        $sql = 'UPDATE adms_user_educations SET
                    tipo = :tipo,
                    curso = :curso,
                    instituicao = :instituicao,
                    situacao = :situacao,
                    data_inicio = :data_inicio,
                    data_conclusao = :data_conclusao,
                    carga_horaria = :carga_horaria,
                    observacoes = :observacoes,
                    comprovante_path = :comprovante_path,
                    comprovante_nome_original = :comprovante_nome_original,
                    comprovante_mime = :comprovante_mime,
                    comprovante_tamanho = :comprovante_tamanho,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id AND adms_user_id = :user_id';
        $stmt = $this->getConnection()->prepare($sql);
        $this->bindData($stmt, $userId, $data, $actorId, false);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        $after = $this->getByIdForUser($id, $userId);
        if ($ok && is_array($after) && $before !== $after) {
            $this->log($id, 'UPDATE', $before, $after, $actorId);
        }

        return $ok;
    }

    /** @return array<string, mixed>|false Registro removido, para limpeza do arquivo. */
    public function delete(int $id, int $userId, ?int $actorId = null): array|false
    {
        $before = $this->getByIdForUser($id, $userId);
        if (!is_array($before)) {
            return false;
        }
        $stmt = $this->getConnection()->prepare(
            'DELETE FROM adms_user_educations WHERE id = :id AND adms_user_id = :user_id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $this->log($id, 'DELETE', $before, [], $actorId);

        return $before;
    }

    /**
     * Importação idempotente: usa o ID quando informado; sem ID, reutiliza
     * uma formação equivalente do mesmo usuário.
     *
     * @param array<string, mixed> $data
     * @return array{action: string, id: int}
     */
    public function upsertImported(int $userId, array $data, ?int $educationId, ?int $actorId = null): array
    {
        if ($educationId !== null && $educationId > 0) {
            if (!$this->getByIdForUser($educationId, $userId)) {
                throw new \RuntimeException('Formação não encontrada para o usuário informado.');
            }
            $this->update($educationId, $userId, $data, $actorId);

            return ['action' => 'updated', 'id' => $educationId];
        }

        $equivalentId = $this->findEquivalentId($userId, $data);
        if ($equivalentId !== null) {
            $existing = $this->getByIdForUser($equivalentId, $userId);
            if (is_array($existing)) {
                foreach (['comprovante_path', 'comprovante_nome_original', 'comprovante_mime', 'comprovante_tamanho'] as $field) {
                    if (($data[$field] ?? null) === null) {
                        $data[$field] = $existing[$field] ?? null;
                    }
                }
            }
            $this->update($equivalentId, $userId, $data, $actorId);

            return ['action' => 'updated', 'id' => $equivalentId];
        }

        return ['action' => 'created', 'id' => $this->create($userId, $data, $actorId)];
    }

    /** @param array<string, mixed> $data */
    private function findEquivalentId(int $userId, array $data): ?int
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT id FROM adms_user_educations
             WHERE adms_user_id = :user_id
               AND tipo = :tipo
               AND LOWER(TRIM(curso)) = LOWER(TRIM(:curso))
               AND LOWER(TRIM(COALESCE(instituicao, \'\'))) = LOWER(TRIM(:instituicao))
             ORDER BY id DESC LIMIT 1'
        );
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':curso', (string) ($data['curso'] ?? ''), PDO::PARAM_STR);
        $stmt->bindValue(':instituicao', (string) ($data['instituicao'] ?? ''), PDO::PARAM_STR);
        $stmt->execute();
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /** @param array<string, mixed> $data */
    private function bindData(\PDOStatement $stmt, int $userId, array $data, ?int $actorId, bool $includeCreatedBy = true): void
    {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        foreach (['tipo', 'curso', 'instituicao', 'situacao', 'data_inicio', 'data_conclusao', 'observacoes',
                  'comprovante_path', 'comprovante_nome_original', 'comprovante_mime'] as $field) {
            $value = $data[$field] ?? null;
            $stmt->bindValue(':' . $field, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        }
        foreach (['carga_horaria', 'comprovante_tamanho'] as $field) {
            $value = isset($data[$field]) && $data[$field] !== '' ? (int) $data[$field] : null;
            $stmt->bindValue(':' . $field, $value, $value === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }
        if ($includeCreatedBy) {
            $stmt->bindValue(':created_by', $actorId, $actorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        }
        $stmt->bindValue(':updated_by', $actorId, $actorId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    }

    /** @param array<string, mixed> $before @param array<string, mixed> $after */
    private function log(int $id, string $operation, array $before, array $after, ?int $actorId): void
    {
        $actorId ??= (int) ($_SESSION['user_id'] ?? 0);
        if ($actorId <= 0) {
            return;
        }
        LogAlteracaoService::registrarAlteracao(
            'adms_user_educations',
            $id,
            $actorId,
            $operation,
            $before,
            $after
        );
    }
}
