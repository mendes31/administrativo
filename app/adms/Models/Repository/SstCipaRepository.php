<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class SstCipaRepository extends DbConnection
{
    public function getMandatos(int $page, int $perPage, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'm.status = :status';
            $params[':status'] = $filters['status'];
        }
        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT m.*,
                       (SELECT COUNT(*) FROM adms_sst_cipa_membros mb WHERE mb.adms_sst_cipa_mandato_id = m.id AND mb.ativo = 1) AS total_membros,
                       (SELECT COUNT(*) FROM adms_sst_cipa_reunioes r WHERE r.adms_sst_cipa_mandato_id = m.id) AS total_reunioes
                FROM adms_sst_cipa_mandatos m
                {$whereClause}
                ORDER BY m.data_inicio DESC
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

    public function getMandatosTotal(array $filters = []): int
    {
        $where = !empty($filters['status']) ? 'WHERE status = :status' : '';
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_cipa_mandatos {$where}";
        $stmt = $this->getConnection()->prepare($sql);
        if (!empty($filters['status'])) {
            $stmt->bindValue(':status', $filters['status']);
        }
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function getMandatoById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_sst_cipa_mandatos WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getMandatoAtivo(): ?array
    {
        $sql = "SELECT * FROM adms_sst_cipa_mandatos WHERE status = 'Ativo' ORDER BY data_inicio DESC LIMIT 1";
        $row = $this->getConnection()->query($sql)->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function createMandato(array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_cipa_mandatos (titulo, data_inicio, data_fim, status, observacoes, created_by, updated_by, created_at, updated_at)
                VALUES (:titulo, :data_inicio, :data_fim, :status, :observacoes, :uid, :uid, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':titulo', (string) ($data['titulo'] ?? ''));
        $stmt->bindValue(':data_inicio', (string) ($data['data_inicio'] ?? date('Y-m-d')));
        $this->bindNull($stmt, ':data_fim', $data['data_fim'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Ativo'));
        $this->bindNull($stmt, ':observacoes', $data['observacoes'] ?? null);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);

        return $stmt->execute() ? (int) $this->getConnection()->lastInsertId() : false;
    }

    public function updateMandato(int $id, array $data): bool
    {
        $sql = "UPDATE adms_sst_cipa_mandatos SET titulo = :titulo, data_inicio = :data_inicio, data_fim = :data_fim,
                status = :status, observacoes = :observacoes, updated_by = :uid, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', (string) ($data['titulo'] ?? ''));
        $stmt->bindValue(':data_inicio', (string) ($data['data_inicio'] ?? date('Y-m-d')));
        $this->bindNull($stmt, ':data_fim', $data['data_fim'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Ativo'));
        $this->bindNull($stmt, ':observacoes', $data['observacoes'] ?? null);
        $stmt->bindValue(':uid', (int) ($_SESSION['user_id'] ?? 1), PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function deleteMandato(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_cipa_mandatos WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getMembros(int $mandatoId): array
    {
        $sql = "SELECT m.*, u.name AS colaborador_nome, p.name AS cargo_nome
                FROM adms_sst_cipa_membros m
                LEFT JOIN adms_users u ON u.id = m.adms_user_id
                LEFT JOIN adms_positions p ON p.id = m.adms_position_id
                WHERE m.adms_sst_cipa_mandato_id = :mid
                ORDER BY FIELD(m.cargo, 'Presidente', 'Vice-presidente', 'Secretário', 'Titular', 'Suplente'), u.name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':mid', $mandatoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function addMembro(int $mandatoId, array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_cipa_membros (adms_sst_cipa_mandato_id, adms_user_id, cargo, adms_position_id, ativo, created_at)
                VALUES (:mid, :uid, :cargo, :pos, :ativo, NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':mid', $mandatoId, PDO::PARAM_INT);
        $stmt->bindValue(':uid', (int) $data['adms_user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':cargo', (string) ($data['cargo'] ?? 'Titular'));
        $this->bindNull($stmt, ':pos', $data['adms_position_id'] ?? null, true);
        $stmt->bindValue(':ativo', !empty($data['ativo']) ? 1 : 0, PDO::PARAM_INT);

        return $stmt->execute() ? (int) $this->getConnection()->lastInsertId() : false;
    }

    public function deleteMembro(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_cipa_membros WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getReunioes(int $mandatoId): array
    {
        $sql = 'SELECT * FROM adms_sst_cipa_reunioes WHERE adms_sst_cipa_mandato_id = :mid ORDER BY data_reuniao DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':mid', $mandatoId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getReuniaoById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_sst_cipa_reunioes WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function addReuniao(int $mandatoId, array $data): int|false
    {
        $sql = "INSERT INTO adms_sst_cipa_reunioes (adms_sst_cipa_mandato_id, data_reuniao, tipo, pauta, ata, status, created_by, created_at, updated_at)
                VALUES (:mid, :data_reuniao, :tipo, :pauta, :ata, :status, :uid, NOW(), NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':mid', $mandatoId, PDO::PARAM_INT);
        $stmt->bindValue(':data_reuniao', (string) ($data['data_reuniao'] ?? date('Y-m-d H:i:s')));
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'Ordinária'));
        $this->bindNull($stmt, ':pauta', $data['pauta'] ?? null);
        $this->bindNull($stmt, ':ata', $data['ata'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Agendada'));
        $stmt->bindValue(':uid', (int) ($_SESSION['user_id'] ?? 1), PDO::PARAM_INT);

        return $stmt->execute() ? (int) $this->getConnection()->lastInsertId() : false;
    }

    public function updateReuniao(int $id, array $data): bool
    {
        $sql = "UPDATE adms_sst_cipa_reunioes SET data_reuniao = :data_reuniao, tipo = :tipo, pauta = :pauta, ata = :ata, status = :status, updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':data_reuniao', (string) ($data['data_reuniao'] ?? date('Y-m-d H:i:s')));
        $stmt->bindValue(':tipo', (string) ($data['tipo'] ?? 'Ordinária'));
        $this->bindNull($stmt, ':pauta', $data['pauta'] ?? null);
        $this->bindNull($stmt, ':ata', $data['ata'] ?? null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Agendada'));

        return $stmt->execute();
    }

    public function deleteReuniao(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_sst_cipa_reunioes WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    private function bindNull(\PDOStatement $stmt, string $param, mixed $value, bool $int = false): void
    {
        if ($value === null || $value === '') {
            $stmt->bindValue($param, null, PDO::PARAM_NULL);
            return;
        }
        $stmt->bindValue($param, $int ? (int) $value : (string) $value, $int ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
}
