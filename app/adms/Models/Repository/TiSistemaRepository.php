<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use Exception;
use PDO;
use PDOException;

class TiSistemaRepository extends DbConnection
{
    public const TIPOS = ['embarcado', 'local', 'rede', 'saas', 'outro'];
    public const STATUS_ATIVO = 'ativo';
    public const STATUS_INATIVO = 'inativo';

    /**
     * @return list<array<string, mixed>>
     */
    public function getAll(int $page = 1, int $limit = 20, string $filterNome = '', string $filterStatus = ''): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = ['1=1'];
        $params = [];

        if ($filterNome !== '') {
            $where[] = '(s.nome LIKE :nome OR s.codigo LIKE :nome OR s.localizacao LIKE :nome)';
            $params[':nome'] = '%' . $filterNome . '%';
        }
        if ($filterStatus !== '' && in_array($filterStatus, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $where[] = 's.status = :status';
            $params[':status'] = $filterStatus;
        }

        $sql = 'SELECT s.*,
                       (SELECT COUNT(*) FROM ti_acessos a
                         WHERE a.ti_sistema_id = s.id AND a.status = \'ativo\') AS acessos_ativos
                FROM ti_sistemas s
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.nome ASC
                LIMIT :limit OFFSET :offset';

        try {
            $stmt = $this->getConnection()->prepare($sql);
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function countAll(string $filterNome = '', string $filterStatus = ''): int
    {
        $where = ['1=1'];
        $params = [];
        if ($filterNome !== '') {
            $where[] = '(nome LIKE :nome OR codigo LIKE :nome OR localizacao LIKE :nome)';
            $params[':nome'] = '%' . $filterNome . '%';
        }
        if ($filterStatus !== '' && in_array($filterStatus, [self::STATUS_ATIVO, self::STATUS_INATIVO], true)) {
            $where[] = 'status = :status';
            $params[':status'] = $filterStatus;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT COUNT(*) FROM ti_sistemas WHERE ' . implode(' AND ', $where)
            );
            foreach ($params as $k => $v) {
                $stmt->bindValue($k, $v, PDO::PARAM_STR);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::countAll', ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        try {
            $stmt = $this->getConnection()->prepare('SELECT * FROM ti_sistemas WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * @return list<array{id:int,nome:string}>
     */
    public function getAtivosSelect(): array
    {
        try {
            $stmt = $this->getConnection()->query(
                "SELECT id, nome FROM ti_sistemas WHERE status = 'ativo' ORDER BY nome ASC"
            );
            return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (PDOException $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::getAtivosSelect', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data, int $actorId): int|false
    {
        try {
            $sql = 'INSERT INTO ti_sistemas
                        (codigo, nome, descricao, tipo, localizacao, observacoes, status, created_by_user_id, created_at)
                    VALUES
                        (:codigo, :nome, :descricao, :tipo, :localizacao, :observacoes, :status, :created_by, NOW())';
            $stmt = $this->getConnection()->prepare($sql);
            $codigo = trim((string) ($data['codigo'] ?? ''));
            $stmt->bindValue(':codigo', $codigo !== '' ? $codigo : null, $codigo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nome', trim((string) ($data['nome'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')) ?: null);
            $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'outro'), PDO::PARAM_STR);
            $stmt->bindValue(':localizacao', trim((string) ($data['localizacao'] ?? '')) ?: null);
            $stmt->bindValue(':observacoes', trim((string) ($data['observacoes'] ?? '')) ?: null);
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_ATIVO), PDO::PARAM_STR);
            $stmt->bindValue(':created_by', $actorId > 0 ? $actorId : null, $actorId > 0 ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            if ($id > 0) {
                LogAlteracaoService::registrarAlteracao('ti_sistemas', $id, $actorId > 0 ? $actorId : 1, 'INSERT', [], $data);
            }
            return $id > 0 ? $id : false;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data, int $actorId): bool
    {
        $antes = $this->getById($id);
        if ($antes === null) {
            return false;
        }
        try {
            $sql = 'UPDATE ti_sistemas SET
                        codigo = :codigo,
                        nome = :nome,
                        descricao = :descricao,
                        tipo = :tipo,
                        localizacao = :localizacao,
                        observacoes = :observacoes,
                        status = :status
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $codigo = trim((string) ($data['codigo'] ?? ''));
            $stmt->bindValue(':codigo', $codigo !== '' ? $codigo : null, $codigo !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':nome', trim((string) ($data['nome'] ?? '')), PDO::PARAM_STR);
            $stmt->bindValue(':descricao', trim((string) ($data['descricao'] ?? '')) ?: null);
            $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'outro'), PDO::PARAM_STR);
            $stmt->bindValue(':localizacao', trim((string) ($data['localizacao'] ?? '')) ?: null);
            $stmt->bindValue(':observacoes', trim((string) ($data['observacoes'] ?? '')) ?: null);
            $stmt->bindValue(':status', (string) ($data['status'] ?? self::STATUS_ATIVO), PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok) {
                LogAlteracaoService::registrarAlteracao(
                    'ti_sistemas',
                    $id,
                    $actorId > 0 ? $actorId : 1,
                    'UPDATE',
                    $antes,
                    $data
                );
            }
            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'TiSistemaRepository::update', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
