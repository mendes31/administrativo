<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Helpers\GenerateLog;
use App\adms\Models\Services\DbConnection;
use PDO;
use Throwable;

final class PortariaVisitantesRepository extends DbConnection
{
    /** @param array<string, mixed> $filters @return list<array<string, mixed>> */
    public function getAll(array $filters = []): array
    {
        $where = ['1 = 1'];
        $params = [];
        $busca = trim((string) ($filters['busca'] ?? ''));
        if ($busca !== '') {
            $where[] = '(v.nome LIKE :busca OR v.documento LIKE :busca OR v.empresa LIKE :busca OR v.telefone LIKE :busca)';
            $params[':busca'] = '%' . $busca . '%';
        }
        if (($filters['ativo'] ?? '') !== '') {
            $where[] = 'v.ativo = :ativo';
            $params[':ativo'] = !empty($filters['ativo']) ? 1 : 0;
        }

        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT v.* FROM portaria_visitantes v WHERE ' . implode(' AND ', $where) . ' ORDER BY v.nome'
            );
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as &$row) {
                $row['termo'] = $this->getTermoStatus((int) $row['id']);
                $row['termo_status'] = $row['termo']['status'];
            }
            unset($row);
            return $rows;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaVisitantesRepository::getAll', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /** @return array<string, mixed>|null */
    public function getById(int $id): ?array
    {
        try {
            $stmt = $this->getConnection()->prepare('SELECT * FROM portaria_visitantes WHERE id = :id LIMIT 1');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            $row['termo'] = $this->getTermoStatus($id);
            return $row;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaVisitantesRepository::getById', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int|false
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'INSERT INTO portaria_visitantes (nome, documento, telefone, empresa, email, observacoes, ativo)
                 VALUES (:nome, :documento, :telefone, :empresa, :email, :observacoes, :ativo)'
            );
            $this->bindFields($stmt, $data);
            $stmt->execute();
            $id = (int) $this->getConnection()->lastInsertId();
            return $id > 0 ? $id : false;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaVisitantesRepository::create', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        try {
            $stmt = $this->getConnection()->prepare(
                'UPDATE portaria_visitantes SET nome = :nome, documento = :documento, telefone = :telefone,
                 empresa = :empresa, email = :email, observacoes = :observacoes, ativo = :ativo WHERE id = :id'
            );
            $this->bindFields($stmt, $data);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaVisitantesRepository::update', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Retorna o estado e a evidência de aceite mais recente.
     * @return array<string, mixed>
     */
    public function getTermoStatus(int $visitanteId): array
    {
        try {
            $join = $this->tableExists('lgpd_termos')
                ? 'LEFT JOIN lgpd_termos t ON t.id = a.lgpd_termo_id'
                : '';
            $termFields = $this->tableExists('lgpd_termos')
                ? ', t.titulo AS termo_titulo, t.versao AS termo_versao'
                : ", NULL AS termo_titulo, NULL AS termo_versao";
            $stmt = $this->getConnection()->prepare(
                "SELECT a.* {$termFields}
                 FROM portaria_termo_aceites a {$join}
                 WHERE a.visitante_id = :visitante_id
                 ORDER BY a.aceito_em DESC, a.id DESC LIMIT 1"
            );
            $stmt->bindValue(':visitante_id', $visitanteId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return ['status' => 'ausente'];
            }
            $row['status'] = empty($row['revogado_em']) && strtotime((string) $row['valido_ate']) >= time()
                ? 'vigente'
                : 'vencido';
            return $row;
        } catch (Throwable $e) {
            GenerateLog::generateLog('error', 'PortariaVisitantesRepository::getTermoStatus', ['error' => $e->getMessage()]);
            return ['status' => 'ausente'];
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->bindValue(':table', $table, PDO::PARAM_STR);
        $stmt->execute();
        return (bool) $stmt->fetchColumn();
    }

    /** @param array<string, mixed> $data */
    private function bindFields(\PDOStatement $stmt, array $data): void
    {
        foreach (['documento', 'telefone', 'empresa', 'email', 'observacoes'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            $stmt->bindValue(':' . $field, $value !== '' ? $value : null, $value !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        }
        $stmt->bindValue(':nome', trim((string) ($data['nome'] ?? '')), PDO::PARAM_STR);
        $stmt->bindValue(':ativo', !empty($data['ativo']) ? 1 : 0, PDO::PARAM_INT);
    }
}
