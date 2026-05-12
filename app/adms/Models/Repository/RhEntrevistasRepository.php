<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class RhEntrevistasRepository extends DbConnection
{
    /**
     * Cria uma nova entrevista.
     */
    public function create(array $data): int|bool
    {
        try {
            $pdo = $this->getConnection();
            $candidatoId = (int)($data['rh_candidato_id'] ?? 0);
            if ($candidatoId <= 0) {
                throw new Exception('Candidato é obrigatório.');
            }

            $sql = 'INSERT INTO rh_entrevistas
                        (rh_candidato_id, rh_vaga_id, tipo, entrevistador_id, data_hora, local, observacoes, resultado, feedback, created_at)
                    VALUES
                        (:rh_candidato_id, :rh_vaga_id, :tipo, :entrevistador_id, :data_hora, :local, :observacoes, :resultado, :feedback, NOW())';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':rh_candidato_id', $candidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':rh_vaga_id', !empty($data['rh_vaga_id']) ? (int)$data['rh_vaga_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'] ?? 'presencial', PDO::PARAM_STR);
            $stmt->bindValue(':entrevistador_id', !empty($data['entrevistador_id']) ? (int)$data['entrevistador_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':data_hora', $data['data_hora'] ?? date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $local = $data['local'] ?? null;
            $observacoes = $data['observacoes'] ?? null;
            $resultado = $data['resultado'] ?? null;
            $feedback = $data['feedback'] ?? null;
            $stmt->bindValue(':local', $local, $local !== null && $local !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $observacoes, $observacoes !== null && $observacoes !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':resultado', $resultado, $resultado !== null && $resultado !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':feedback', $feedback, $feedback !== null && $feedback !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

            if (!$stmt->execute()) {
                return false;
            }
            $newId = (int) $pdo->lastInsertId();
            if ($newId > 0) {
                $row = $this->getById($newId);
                if (is_array($row)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'rh_entrevistas',
                        $newId,
                        $usuarioId,
                        'INSERT',
                        [],
                        $row
                    );
                }
            }

            return $newId;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao cadastrar entrevista.', [
                'rh_candidato_id' => $data['rh_candidato_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Atualiza uma entrevista existente.
     */
    public function update(int $id, array $data): bool
    {
        try {
            $pdo = $this->getConnection();
            $antes = $this->getById($id);
            if (!$antes) {
                throw new Exception('Entrevista não encontrada.');
            }

            $sql = 'UPDATE rh_entrevistas SET
                        rh_vaga_id = :rh_vaga_id,
                        tipo = :tipo,
                        entrevistador_id = :entrevistador_id,
                        data_hora = :data_hora,
                        local = :local,
                        observacoes = :observacoes,
                        resultado = :resultado,
                        feedback = :feedback,
                        updated_at = NOW()
                    WHERE id = :id';

            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':rh_vaga_id', !empty($data['rh_vaga_id']) ? (int)$data['rh_vaga_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'] ?? 'presencial', PDO::PARAM_STR);
            $stmt->bindValue(':entrevistador_id', !empty($data['entrevistador_id']) ? (int)$data['entrevistador_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':data_hora', $data['data_hora'] ?? $antes['data_hora'], PDO::PARAM_STR);
            $stmt->bindValue(':local', $data['local'] ?? null, ($data['local'] ?? null) !== null && ($data['local'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':observacoes', $data['observacoes'] ?? null, ($data['observacoes'] ?? null) !== null && ($data['observacoes'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':resultado', $data['resultado'] ?? null, ($data['resultado'] ?? null) !== null && ($data['resultado'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':feedback', $data['feedback'] ?? null, ($data['feedback'] ?? null) !== null && ($data['feedback'] ?? '') !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);

            $ok = $stmt->execute();
            if ($ok && is_array($antes)) {
                $depois = $this->getById($id);
                if (is_array($depois)) {
                    $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                    LogAlteracaoService::registrarAlteracao(
                        'rh_entrevistas',
                        $id,
                        $usuarioId,
                        'UPDATE',
                        $antes,
                        $depois
                    );
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar entrevista.', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Remove uma entrevista.
     */
    public function delete(int $id): bool
    {
        try {
            $pdo = $this->getConnection();
            $antes = $this->getById($id);
            $stmt = $pdo->prepare('DELETE FROM rh_entrevistas WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && is_array($antes)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'rh_entrevistas',
                    $id,
                    $usuarioId,
                    'DELETE',
                    $antes,
                    []
                );
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao excluir entrevista.', ['id' => $id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Busca entrevista por ID (com joins para candidato, vaga e entrevistador).
     */
    public function getById(int $id): ?array
    {
        $sql = 'SELECT e.*,
                    c.nome AS candidato_nome, c.email AS candidato_email,
                    v.titulo AS vaga_titulo,
                    u.name AS entrevistador_nome
                FROM rh_entrevistas e
                INNER JOIN rh_candidatos c ON c.id = e.rh_candidato_id
                LEFT JOIN rh_vagas v ON v.id = e.rh_vaga_id
                LEFT JOIN adms_users u ON u.id = e.entrevistador_id
                WHERE e.id = :id
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Lista entrevistas com filtros e paginação (para Dashboard e listagem).
     */
    public function getAll(array $filters, int $page, int $perPage): array
    {
        $pdo = $this->getConnection();
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['rh_candidato_id'])) {
            $where[] = 'e.rh_candidato_id = :rh_candidato_id';
            $params[':rh_candidato_id'] = (int) $filters['rh_candidato_id'];
        }
        if (!empty($filters['rh_vaga_id'])) {
            $where[] = 'e.rh_vaga_id = :rh_vaga_id';
            $params[':rh_vaga_id'] = (int) $filters['rh_vaga_id'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = 'e.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['resultado'])) {
            $where[] = 'e.resultado = :resultado';
            $params[':resultado'] = $filters['resultado'];
        }
        if (!empty($filters['data_de'])) {
            $where[] = 'e.data_hora >= :data_de';
            $params[':data_de'] = $filters['data_de'];
        }
        if (!empty($filters['data_ate'])) {
            $where[] = 'e.data_hora <= :data_ate';
            $params[':data_ate'] = $filters['data_ate'] . ' 23:59:59';
        }
        if (!empty($filters['entrevistador_id'])) {
            $where[] = 'e.entrevistador_id = :entrevistador_id';
            $params[':entrevistador_id'] = (int) $filters['entrevistador_id'];
        }

        $whereSql = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        $sqlCount = "SELECT COUNT(*) AS total FROM rh_entrevistas e WHERE $whereSql";
        $stmtCount = $pdo->prepare($sqlCount);
        foreach ($params as $k => $v) {
            $stmtCount->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmtCount->execute();
        $total = (int) ($stmtCount->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $sql = "SELECT e.*,
                    c.nome AS candidato_nome, c.email AS candidato_email,
                    v.titulo AS vaga_titulo,
                    u.name AS entrevistador_nome
                FROM rh_entrevistas e
                INNER JOIN rh_candidatos c ON c.id = e.rh_candidato_id
                LEFT JOIN rh_vagas v ON v.id = e.rh_vaga_id
                LEFT JOIN adms_users u ON u.id = e.entrevistador_id
                WHERE $whereSql
                ORDER BY e.data_hora DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return ['data' => $data, 'total' => $total];
    }

    /**
     * Verifica se já existe entrevista pendente para o par candidato+vaga (evita duplicar ao mover no pipeline).
     */
    public function existeEntrevistaPendenteParaVinculo(int $rhCandidatoId, int $rhVagaId): bool
    {
        $pdo = $this->getConnection();
        $stmt = $pdo->prepare('SELECT 1 FROM rh_entrevistas
                               WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id
                               AND (resultado IS NULL OR resultado = \'pendente\')
                               LIMIT 1');
        $stmt->bindValue(':candidato_id', $rhCandidatoId, PDO::PARAM_INT);
        $stmt->bindValue(':vaga_id', $rhVagaId, PDO::PARAM_INT);
        $stmt->execute();
        return (bool) $stmt->fetch();
    }

    /**
     * Cria uma entrevista "em aberto" (pendente) ao mover candidato para Em Entrevista no pipeline.
     * Usado por RhVagasRepository::atualizarStatusVinculo.
     */
    public function criarAoMoverParaEmEntrevista(int $rhCandidatoId, int $rhVagaId): ?int
    {
        if ($this->existeEntrevistaPendenteParaVinculo($rhCandidatoId, $rhVagaId)) {
            return null;
        }
        $id = $this->create([
            'rh_candidato_id' => $rhCandidatoId,
            'rh_vaga_id'      => $rhVagaId,
            'tipo'            => 'presencial',
            'data_hora'       => date('Y-m-d H:i:s'),
            'resultado'       => 'pendente',
            'observacoes'     => 'Criada automaticamente ao mover para "Em Entrevista" no pipeline. Edite para agendar data e preencher detalhes.',
        ]);
        return is_int($id) ? $id : null;
    }

    /**
     * Atualiza o resultado da(s) entrevista(s) do vínculo candidato-vaga.
     * Usado quando o pipeline é movido para Aprovado/Reprovado (reflete na entrevista).
     */
    public function atualizarResultadoPorCandidatoVaga(int $rhCandidatoId, int $rhVagaId, string $resultado): bool
    {
        if (!in_array($resultado, ['aprovado', 'reprovado'], true)) {
            return false;
        }
        try {
            $pdo = $this->getConnection();
            $stmtIds = $pdo->prepare(
                'SELECT id FROM rh_entrevistas WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id'
            );
            $stmtIds->bindValue(':candidato_id', $rhCandidatoId, PDO::PARAM_INT);
            $stmtIds->bindValue(':vaga_id', $rhVagaId, PDO::PARAM_INT);
            $stmtIds->execute();
            $idRows = $stmtIds->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $oldById = [];
            foreach ($idRows as $r) {
                $rid = (int) ($r['id'] ?? 0);
                if ($rid > 0) {
                    $row = $this->getById($rid);
                    if (is_array($row)) {
                        $oldById[$rid] = $row;
                    }
                }
            }

            $sql = 'UPDATE rh_entrevistas
                    SET resultado = :resultado, updated_at = NOW()
                    WHERE rh_candidato_id = :candidato_id AND rh_vaga_id = :vaga_id';
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':resultado', $resultado, PDO::PARAM_STR);
            $stmt->bindValue(':candidato_id', $rhCandidatoId, PDO::PARAM_INT);
            $stmt->bindValue(':vaga_id', $rhVagaId, PDO::PARAM_INT);
            $ok = $stmt->execute();
            if ($ok && $oldById !== []) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                foreach ($oldById as $rid => $oldRow) {
                    $newRow = $this->getById((int) $rid);
                    if (!is_array($newRow)) {
                        continue;
                    }
                    if (($oldRow['resultado'] ?? null) != ($newRow['resultado'] ?? null)) {
                        LogAlteracaoService::registrarAlteracao(
                            'rh_entrevistas',
                            (int) $rid,
                            $usuarioId,
                            'UPDATE',
                            $oldRow,
                            $newRow
                        );
                    }
                }
            }

            return $ok;
        } catch (Exception $e) {
            GenerateLog::generateLog('error', 'Erro ao atualizar resultado da entrevista por vínculo.', [
                'rh_candidato_id' => $rhCandidatoId,
                'rh_vaga_id'      => $rhVagaId,
                'resultado'       => $resultado,
                'error'           => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Lista entrevistas de um candidato (para histórico na view do candidato).
     */
    public function getByCandidato(int $rhCandidatoId): array
    {
        $result = $this->getAll(['rh_candidato_id' => $rhCandidatoId], 1, 500);
        return $result['data'] ?? [];
    }

    /**
     * Lista entrevistas de uma vaga.
     */
    public function getByVaga(int $rhVagaId): array
    {
        $result = $this->getAll(['rh_vaga_id' => $rhVagaId], 1, 500);
        return $result['data'] ?? [];
    }

    /**
     * Indicadores para Dashboard: totais e por resultado/período.
     */
    public function getStatsForDashboard(?string $dataDe = null, ?string $dataAte = null): array
    {
        $pdo = $this->getConnection();
        $where = ['1=1'];
        $params = [];
        if ($dataDe !== null && $dataDe !== '') {
            $where[] = 'data_hora >= :data_de';
            $params[':data_de'] = $dataDe;
        }
        if ($dataAte !== null && $dataAte !== '') {
            $where[] = 'data_hora <= :data_ate';
            $params[':data_ate'] = $dataAte . ' 23:59:59';
        }
        $whereSql = implode(' AND ', $where);

        $stmtTotal = $pdo->prepare("SELECT COUNT(*) AS total FROM rh_entrevistas WHERE $whereSql");
        foreach ($params as $k => $v) {
            $stmtTotal->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmtTotal->execute();
        $total = (int) ($stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $stmtResultado = $pdo->prepare("SELECT resultado, COUNT(*) AS total FROM rh_entrevistas WHERE $whereSql AND resultado IS NOT NULL GROUP BY resultado");
        foreach ($params as $k => $v) {
            $stmtResultado->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmtResultado->execute();
        $porResultado = [];
        while ($row = $stmtResultado->fetch(PDO::FETCH_ASSOC)) {
            $porResultado[$row['resultado'] ?? ''] = (int) $row['total'];
        }

        $stmtTipo = $pdo->prepare("SELECT tipo, COUNT(*) AS total FROM rh_entrevistas WHERE $whereSql GROUP BY tipo");
        foreach ($params as $k => $v) {
            $stmtTipo->bindValue($k, $v, PDO::PARAM_STR);
        }
        $stmtTipo->execute();
        $porTipo = [];
        while ($row = $stmtTipo->fetch(PDO::FETCH_ASSOC)) {
            $porTipo[$row['tipo'] ?? ''] = (int) $row['total'];
        }

        return [
            'total_entrevistas' => $total,
            'por_resultado'     => $porResultado,
            'por_tipo'          => $porTipo,
        ];
    }
}
