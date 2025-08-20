<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class TrainingsRepository extends DbConnection
{
    public function getAllTrainings(int $page = 1, int $limit = 20, array $filters = []): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $where = [];
        $params = [];
        if (!empty($filters['nome'])) {
            $where[] = 't.nome LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $where[] = 't.ativo = :ativo';
            $params[':ativo'] = (int)$filters['ativo'];
        }
        if (!empty($filters['instrutor'])) {
            $where[] = '(u.name LIKE :instrutor OR t.instrutor LIKE :instrutor)';
            $params[':instrutor'] = '%' . $filters['instrutor'] . '%';
        }
        if (!empty($filters['tipo'])) {
            $where[] = 't.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['codigo'])) {
            $where[] = 't.codigo LIKE :codigo';
            $params[':codigo'] = '%' . $filters['codigo'] . '%';
        }
        if (!empty($filters['reciclagem'])) {
            $where[] = 't.reciclagem_periodo = :reciclagem';
            $params[':reciclagem'] = (int)$filters['reciclagem'];
        }
        if (!empty($filters['area_responsavel_id'])) {
            $where[] = 't.area_responsavel_id = :area_responsavel_id';
            $params[':area_responsavel_id'] = (int)$filters['area_responsavel_id'];
        }
        if (!empty($filters['area_elaborador_id'])) {
            $where[] = 't.area_elaborador_id = :area_elaborador_id';
            $params[':area_elaborador_id'] = (int)$filters['area_elaborador_id'];
        }
        if (!empty($filters['tipo_obrigatoriedade'])) {
            $where[] = 't.tipo_obrigatoriedade = :tipo_obrigatoriedade';
            $params[':tipo_obrigatoriedade'] = $filters['tipo_obrigatoriedade'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT t.*, u.name as user_name, dep_resp.name as area_responsavel_nome, dep_elab.name as area_elaborador_nome FROM adms_trainings t
                LEFT JOIN adms_users u ON u.id = t.instructor_user_id
                LEFT JOIN adms_departments dep_resp ON dep_resp.id = t.area_responsavel_id
                LEFT JOIN adms_departments dep_elab ON dep_elab.id = t.area_elaborador_id
                ' . $whereSql . '
                ORDER BY t.id DESC LIMIT :limit OFFSET :offset';
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTraining(int|string $id): array|bool
    {
        $sql = 'SELECT * FROM adms_trainings WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createTraining(array $data): bool|int
    {
        try {
            // Validação do prazo de treinamento
            $prazoTreinamento = (int)($data['prazo_treinamento'] ?? 0);
            if ($prazoTreinamento <= 0) {
                throw new Exception('O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.');
            }
            
            $sql = 'INSERT INTO adms_trainings (nome, codigo, versao, prazo_treinamento, tipo, instrutor, carga_horaria, ativo, created_at, instructor_user_id, instructor_email, instructor_name, reciclagem, reciclagem_periodo, area_responsavel_id, area_elaborador_id, tipo_obrigatoriedade) VALUES (:nome, :codigo, :versao, :prazo_treinamento, :tipo, :instrutor, :carga_horaria, :ativo, NOW(), :instructor_user_id, :instructor_email, :instructor_name, :reciclagem, :reciclagem_periodo, :area_responsavel_id, :area_elaborador_id, :tipo_obrigatoriedade)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':codigo', $data['codigo'], PDO::PARAM_STR);
            $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
            $stmt->bindValue(':prazo_treinamento', $prazoTreinamento, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':instrutor', $data['instrutor'], PDO::PARAM_STR);
            $stmt->bindValue(':carga_horaria', $data['carga_horaria'] !== '' ? $data['carga_horaria'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_BOOL);
            $stmt->bindValue(':instructor_user_id', $data['instructor_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':instructor_email', $data['instructor_email'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':instructor_name', $data['instructor_name'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':reciclagem', $data['reciclagem'] ?? 0, PDO::PARAM_BOOL);
            $stmt->bindValue(':reciclagem_periodo', $data['reciclagem_periodo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':area_responsavel_id', $data['area_responsavel_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':area_elaborador_id', $data['area_elaborador_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_obrigatoriedade', $data['tipo_obrigatoriedade'] ?? null, PDO::PARAM_STR);
            $stmt->execute();
            $novoId = $this->getConnection()->lastInsertId();
            
            // Log de inserção
            if ($novoId) {
                $dadosDepois = [
                    'id' => $novoId,
                    'nome' => $data['nome'],
                    'codigo' => $data['codigo'],
                    'versao' => $data['versao'],
                    'prazo_treinamento' => $prazoTreinamento,
                    'tipo' => $data['tipo'],
                    'instrutor' => $data['instrutor'],
                    'carga_horaria' => $data['carga_horaria'],
                    'ativo' => $data['ativo'] ?? 1,
                    'instructor_user_id' => $data['instructor_user_id'] ?? null,
                    'instructor_email' => $data['instructor_email'] ?? null,
                    'instructor_name' => $data['instructor_name'] ?? null,
                    'reciclagem' => $data['reciclagem'] ?? 0,
                    'reciclagem_periodo' => $data['reciclagem_periodo'] ?? null,
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_trainings',
                    $novoId,
                    $_SESSION['user_id'] ?? 0,
                    'insert',
                    [],
                    $dadosDepois
                );
            }
            return $novoId;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Treinamento não cadastrado.", [
                'nome' => $data['nome'] ?? '',
                'codigo' => $data['codigo'] ?? '',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function updateTraining(int|string $id, array $data): bool
    {
        try {
            // Validação do prazo de treinamento
            $prazoTreinamento = (int)($data['prazo_treinamento'] ?? 0);
            if ($prazoTreinamento <= 0) {
                throw new Exception('O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.');
            }
            
            // Captura os dados antigos antes da alteração
            $dadosAntes = $this->getTraining($id);
            
            $sql = 'UPDATE adms_trainings SET nome = :nome, codigo = :codigo, versao = :versao, prazo_treinamento = :prazo_treinamento, tipo = :tipo, instrutor = :instrutor, carga_horaria = :carga_horaria, ativo = :ativo, instructor_user_id = :instructor_user_id, instructor_email = :instructor_email, instructor_name = :instructor_name, reciclagem = :reciclagem, reciclagem_periodo = :reciclagem_periodo, area_responsavel_id = :area_responsavel_id, area_elaborador_id = :area_elaborador_id, tipo_obrigatoriedade = :tipo_obrigatoriedade, updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':codigo', $data['codigo'], PDO::PARAM_STR);
            $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
            $stmt->bindValue(':prazo_treinamento', $prazoTreinamento, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':instrutor', $data['instrutor'], PDO::PARAM_STR);
            $stmt->bindValue(':carga_horaria', $data['carga_horaria'] !== '' ? $data['carga_horaria'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_BOOL);
            $stmt->bindValue(':instructor_user_id', $data['instructor_user_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':instructor_email', $data['instructor_email'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':instructor_name', $data['instructor_name'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':reciclagem', $data['reciclagem'] ?? 0, PDO::PARAM_BOOL);
            $stmt->bindValue(':reciclagem_periodo', $data['reciclagem_periodo'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':area_responsavel_id', $data['area_responsavel_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':area_elaborador_id', $data['area_elaborador_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':tipo_obrigatoriedade', $data['tipo_obrigatoriedade'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Se atualização bem-sucedida, registra o log de alteração
            if ($result) {
                $dadosDepois = [
                    'id' => $id,
                    'nome' => $data['nome'],
                    'codigo' => $data['codigo'],
                    'versao' => $data['versao'],
                    'prazo_treinamento' => $prazoTreinamento,
                    'tipo' => $data['tipo'],
                    'instrutor' => $data['instrutor'],
                    'carga_horaria' => $data['carga_horaria'],
                    'ativo' => $data['ativo'] ?? 1,
                    'instructor_user_id' => $data['instructor_user_id'] ?? null,
                    'instructor_email' => $data['instructor_email'] ?? null,
                    'instructor_name' => $data['instructor_name'] ?? null,
                    'reciclagem' => $data['reciclagem'] ?? 0,
                    'reciclagem_periodo' => $data['reciclagem_periodo'] ?? null,
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_trainings',
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    'update',
                    $dadosAntes ?: [],
                    $dadosDepois
                );
            }
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Treinamento não editado.", [
                'id' => $id,
                'nome' => $data['nome'] ?? '',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function deleteTraining(int|string $id): bool
    {
        try {
            // Captura os dados antigos antes da exclusão
            $dadosAntes = $this->getTraining($id);
            
            $sql = 'DELETE FROM adms_trainings WHERE id = :id LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            // Se exclusão bem-sucedida, registra o log de alteração
            if ($result) {
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_trainings',
                    $id,
                    $_SESSION['user_id'] ?? 0,
                    'delete',
                    $dadosAntes ?: [],
                    []
                );
            }
            return $result;
        } catch (Exception $e) {
            GenerateLog::generateLog("error", "Treinamento não apagado.", [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getLinkedPositionsCount(int $trainingId): int
    {
        $sql = 'SELECT COUNT(*) FROM adms_training_positions WHERE adms_training_id = :training_id AND obrigatorio = 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, PDO::PARAM_INT);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public function getAllTrainingsSelect(): array
    {
        $sql = 'SELECT id, nome as name FROM adms_trainings ORDER BY nome ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna o total de treinamentos
     */
    public function getTotalTrainings(array $filters = []): int
    {
        $where = [];
        $params = [];
        if (!empty($filters['nome'])) {
            $where[] = 't.nome LIKE :nome';
            $params[':nome'] = '%' . $filters['nome'] . '%';
        }
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $where[] = 't.ativo = :ativo';
            $params[':ativo'] = (int)$filters['ativo'];
        }
        if (!empty($filters['instrutor'])) {
            $where[] = '(u.name LIKE :instrutor OR t.instrutor LIKE :instrutor)';
            $params[':instrutor'] = '%' . $filters['instrutor'] . '%';
        }
        if (!empty($filters['tipo'])) {
            $where[] = 't.tipo = :tipo';
            $params[':tipo'] = $filters['tipo'];
        }
        if (!empty($filters['codigo'])) {
            $where[] = 't.codigo LIKE :codigo';
            $params[':codigo'] = '%' . $filters['codigo'] . '%';
        }
        if (!empty($filters['reciclagem'])) {
            $where[] = 't.reciclagem_periodo = :reciclagem';
            $params[':reciclagem'] = (int)$filters['reciclagem'];
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $sql = 'SELECT COUNT(*) as total FROM adms_trainings t
                LEFT JOIN adms_users u ON u.id = t.instructor_user_id
                ' . $whereSql;
        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    }

    /**
     * Retorna o total de colaboradores vinculados ao treinamento (direto ou por cargo obrigatório, sem duplicidade)
     */
    public function getTotalColaboradoresVinculados($trainingId): int
    {
        $sql = "SELECT COUNT(DISTINCT u.id) as total
                FROM adms_users u
                INNER JOIN adms_training_users tu ON tu.adms_user_id = u.id
                LEFT JOIN adms_training_positions tp ON tp.adms_training_id = tu.adms_training_id AND tp.adms_position_id = u.user_position_id AND tp.obrigatorio = 1
                WHERE tu.adms_training_id = :training_id
                  AND u.status = 'Ativo'
                  AND (
                        tu.tipo_vinculo = 'individual'
                        OR tp.id IS NOT NULL
                      )";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':training_id', $trainingId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
} 