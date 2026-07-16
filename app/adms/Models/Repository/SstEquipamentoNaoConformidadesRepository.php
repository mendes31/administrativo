<?php

declare(strict_types=1);

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class SstEquipamentoNaoConformidadesRepository extends DbConnection
{
    public function getAll(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $perPage);
        [$where, $params] = $this->buildWhere($filters);
        $sql = "SELECT nc.*, e.codigo AS equipamento_codigo, e.localizacao, e.status AS equipamento_status,
                       v.competencia, v.resultado AS vistoria_resultado,
                       u.name AS responsavel_encerramento_nome,
                       ac.codigo AS acao_encerramento_codigo
                FROM adms_sst_equipamento_nao_conformidades nc
                INNER JOIN adms_sst_equipamentos e ON e.id = nc.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_vistorias v ON v.id = nc.adms_sst_equipamento_vistoria_id
                LEFT JOIN adms_users u ON u.id = nc.encerrada_por_adms_user_id
                LEFT JOIN adms_sst_equipamento_acoes_corretivas ac ON ac.id = nc.encerrada_por_acao_id
                {$where}
                ORDER BY FIELD(nc.status, 'Aberta', 'Em tratamento', 'Encerrada', 'Cancelada'), nc.id DESC
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
        $sql = "SELECT COUNT(*) AS total
                FROM adms_sst_equipamento_nao_conformidades nc
                INNER JOIN adms_sst_equipamentos e ON e.id = nc.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_vistorias v ON v.id = nc.adms_sst_equipamento_vistoria_id
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
        $sql = "SELECT nc.*, e.codigo AS equipamento_codigo, e.localizacao, e.status AS equipamento_status,
                       e.empresa_contratante, t.nome AS tipo_nome,
                       v.competencia, v.resultado AS vistoria_resultado, v.status AS vistoria_status,
                       u.name AS responsavel_encerramento_nome,
                       ac.codigo AS acao_encerramento_codigo, ac.titulo AS acao_encerramento_titulo
                FROM adms_sst_equipamento_nao_conformidades nc
                INNER JOIN adms_sst_equipamentos e ON e.id = nc.adms_sst_equipamento_id
                INNER JOIN adms_sst_equipamento_vistorias v ON v.id = nc.adms_sst_equipamento_vistoria_id
                INNER JOIN adms_sst_equipamento_tipos t ON t.id = e.adms_sst_equipamento_tipo_id
                LEFT JOIN adms_users u ON u.id = nc.encerrada_por_adms_user_id
                LEFT JOIN adms_sst_equipamento_acoes_corretivas ac ON ac.id = nc.encerrada_por_acao_id
                WHERE nc.id = :id LIMIT 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return list<array<string, mixed>> */
    public function getByVistoriaId(int $vistoriaId): array
    {
        $sql = "SELECT nc.*,
                       ac.codigo AS acao_encerramento_codigo,
                       ac.titulo AS acao_encerramento_titulo,
                       ac.descricao AS acao_encerramento_descricao,
                       ac.data_conclusao AS acao_encerramento_data,
                       ac.observacoes AS acao_encerramento_obs
                FROM adms_sst_equipamento_nao_conformidades nc
                LEFT JOIN adms_sst_equipamento_acoes_corretivas ac ON ac.id = nc.encerrada_por_acao_id
                WHERE nc.adms_sst_equipamento_vistoria_id = :vid
                ORDER BY nc.id ASC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':vid', $vistoriaId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAbertasByEquipamento(int $equipamentoId): int
    {
        $sql = "SELECT COUNT(*) AS total FROM adms_sst_equipamento_nao_conformidades
                WHERE adms_sst_equipamento_id = :eq AND status IN ('Aberta', 'Em tratamento')";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':eq', $equipamentoId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) ($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    public function existsForResposta(int $respostaId): bool
    {
        $sql = 'SELECT 1 FROM adms_sst_equipamento_nao_conformidades
                WHERE adms_sst_equipamento_vistoria_resposta_id = :rid LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':rid', $respostaId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
    }

    public function create(array $data): int|false
    {
        $sql = 'INSERT INTO adms_sst_equipamento_nao_conformidades
                (codigo, adms_sst_equipamento_vistoria_id, adms_sst_equipamento_id,
                 adms_sst_equipamento_vistoria_resposta_id, descricao, observacao, status,
                 created_by, updated_by, created_at, updated_at)
                VALUES (:codigo, :vistoria_id, :equipamento_id, :resposta_id, :descricao, :observacao, :status,
                        :created_by, :updated_by, NOW(), NOW())';
        $stmt = $this->getConnection()->prepare($sql);
        $uid = (int) ($_SESSION['user_id'] ?? 1);
        $tempCodigo = 'TMP-' . uniqid('', true);
        $stmt->bindValue(':codigo', $tempCodigo);
        $stmt->bindValue(':vistoria_id', (int) $data['adms_sst_equipamento_vistoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':equipamento_id', (int) $data['adms_sst_equipamento_id'], PDO::PARAM_INT);
        $respId = !empty($data['adms_sst_equipamento_vistoria_resposta_id'])
            ? (int) $data['adms_sst_equipamento_vistoria_resposta_id'] : null;
        if ($respId === null) {
            $stmt->bindValue(':resposta_id', null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(':resposta_id', $respId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':descricao', (string) $data['descricao']);
        $obs = $data['observacao'] ?? null;
        $stmt->bindValue(':observacao', $obs !== null && $obs !== '' ? (string) $obs : null);
        $stmt->bindValue(':status', (string) ($data['status'] ?? 'Aberta'));
        $stmt->bindValue(':created_by', $uid, PDO::PARAM_INT);
        $stmt->bindValue(':updated_by', $uid, PDO::PARAM_INT);
        if (!$stmt->execute()) {
            return false;
        }
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId <= 0) {
            return false;
        }
        $codigo = 'NC-' . str_pad((string) $newId, 6, '0', STR_PAD_LEFT);
        $upd = $this->getConnection()->prepare(
            'UPDATE adms_sst_equipamento_nao_conformidades SET codigo = :c WHERE id = :id'
        );
        $upd->bindValue(':c', $codigo);
        $upd->bindValue(':id', $newId, PDO::PARAM_INT);
        $upd->execute();

        $newData = $this->getById($newId);
        if ($newData) {
            LogAlteracaoService::registrarAlteracao('adms_sst_equipamento_nao_conformidades', $newId, $uid, 'INSERT', [], $newData);
        }

        return $newId;
    }

    public function markEmTratamento(int $id): void
    {
        $sql = "UPDATE adms_sst_equipamento_nao_conformidades
                SET status = 'Em tratamento', updated_at = NOW()
                WHERE id = :id AND status = 'Aberta'";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public function encerrar(int $id, int $userId, int $acaoId): bool
    {
        $old = $this->getById($id);
        if (!$old || !in_array($old['status'] ?? '', ['Aberta', 'Em tratamento'], true)) {
            return false;
        }
        $sql = "UPDATE adms_sst_equipamento_nao_conformidades SET
                    status = 'Encerrada',
                    encerrada_em = NOW(),
                    encerrada_por_adms_user_id = :uid,
                    encerrada_por_acao_id = :acao,
                    updated_by = :uid,
                    updated_at = NOW()
                WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':acao', $acaoId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        if (!$stmt->execute() || $stmt->rowCount() < 1) {
            return false;
        }
        $new = $this->getById($id);
        if ($old && $new) {
            LogAlteracaoService::registrarAlteracao('adms_sst_equipamento_nao_conformidades', $id, $userId, 'UPDATE', $old, $new);
        }

        return true;
    }

    /** @return array{0: string, 1: array<string, mixed>} */
    private function buildWhere(array $filters): array
    {
        $where = ['WHERE 1=1'];
        $params = [];
        if (!empty($filters['status'])) {
            $where[] = 'nc.status = :status';
            $params[':status'] = (string) $filters['status'];
        }
        if (!empty($filters['status_open'])) {
            $where[] = "nc.status IN ('Aberta', 'Em tratamento')";
        }
        if (!empty($filters['search'])) {
            $where[] = '(nc.codigo LIKE :search OR nc.descricao LIKE :search OR e.codigo LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (!empty($filters['adms_sst_equipamento_id'])) {
            $where[] = 'nc.adms_sst_equipamento_id = :eq';
            $params[':eq'] = (int) $filters['adms_sst_equipamento_id'];
        }

        return [implode(' ', $where), $params];
    }
}
