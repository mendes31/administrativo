<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Helpers\GenerateLog;
use PDO;
use Exception;

class TrainingsRepository extends DbConnection
{
    private function existsCode(string $codigo, ?int $excludeId = null): bool
    {
        $codigo = trim($codigo);
        if ($codigo === '') {
            return false;
        }

        $sql = 'SELECT id
                FROM adms_trainings
                WHERE TRIM(codigo) = :codigo';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isCodeAlreadyRegistered(string $codigo, ?int $excludeId = null): bool
    {
        return $this->existsCode($codigo, $excludeId);
    }

    private function existsCodeVersion(string $codigo, ?string $versao, ?int $excludeId = null): bool
    {
        $codigo = trim($codigo);
        $versao = trim((string)($versao ?? ''));

        $sql = 'SELECT id
                FROM adms_trainings
                WHERE TRIM(codigo) = :codigo
                  AND COALESCE(TRIM(versao), "") = :versao';
        if ($excludeId !== null) {
            $sql .= ' AND id <> :exclude_id';
        }
        $sql .= ' LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->bindValue(':versao', $versao, PDO::PARAM_STR);
        if ($excludeId !== null) {
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }

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
        // OTIMIZADO: Incluir contagens de colaboradores e cargos vinculados em uma única query (resolve N+1)
        $sql = 'SELECT 
                t.*, 
                u.name as user_name, 
                dep_resp.name as area_responsavel_nome, 
                dep_elab.name as area_elaborador_nome,
                -- Contagem de colaboradores vinculados (otimização N+1)
                (SELECT COUNT(DISTINCT u2.id)
                 FROM adms_users u2
                 INNER JOIN adms_training_users tu2 ON tu2.adms_user_id = u2.id
                 LEFT JOIN adms_training_positions tp2 ON tp2.adms_training_id = tu2.adms_training_id 
                     AND tp2.adms_position_id = u2.user_position_id 
                     AND tp2.obrigatorio = 1
                 WHERE tu2.adms_training_id = t.id
                   AND u2.status = "Ativo"
                   AND (tu2.tipo_vinculo = "individual" OR tp2.id IS NOT NULL)
                ) as colaboradores_vinculados,
                -- Contagem de cargos vinculados (otimização N+1)
                (SELECT COUNT(*)
                 FROM adms_training_positions tp3
                 WHERE tp3.adms_training_id = t.id
                   AND tp3.obrigatorio = 1
                ) as cargos_vinculados
                FROM adms_trainings t
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
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Converter contagens para inteiros
        foreach ($results as &$result) {
            $result['colaboradores_vinculados'] = (int)($result['colaboradores_vinculados'] ?? 0);
            $result['cargos_vinculados'] = (int)($result['cargos_vinculados'] ?? 0);
        }
        unset($result);
        
        return $results;
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
            $codigo = trim((string)($data['codigo'] ?? ''));
            $versao = trim((string)($data['versao'] ?? ''));
            if ($codigo === '') {
                throw new Exception('O campo "Código" é obrigatório.');
            }
            if ($versao === '') {
                throw new Exception('O campo "Versão" é obrigatório.');
            }
            $isVersioning = !empty($data['parent_training_id']);
            if (!$isVersioning && $this->existsCode($codigo)) {
                throw new Exception('Código já existe na base e somente pode ser versionado, através da opção Criar nova versão disponibilizada na tela de visualização do treinamento.');
            }
            if ($this->existsCodeVersion($codigo, $versao)) {
                throw new Exception('Já existe um treinamento com o mesmo código e versão.');
            }

            // Validação do prazo de treinamento
            $prazoTreinamento = (int)($data['prazo_treinamento'] ?? 0);
            if ($prazoTreinamento <= 0) {
                throw new Exception('O campo "Prazo de treinamento (dias)" é obrigatório e deve ser maior que 0.');
            }
            
            $familyKey = trim((string)($data['training_family_key'] ?? $codigo));
            if ($familyKey === '') {
                $familyKey = $codigo;
            }
            $sql = 'INSERT INTO adms_trainings (nome, codigo, training_family_key, parent_training_id, versao, prazo_treinamento, tipo, instrutor, carga_horaria, ativo, is_current_version, change_summary, require_retraining, created_at, instructor_user_id, instructor_email, instructor_name, reciclagem, reciclagem_periodo, area_responsavel_id, area_elaborador_id, tipo_obrigatoriedade) VALUES (:nome, :codigo, :training_family_key, :parent_training_id, :versao, :prazo_treinamento, :tipo, :instrutor, :carga_horaria, :ativo, :is_current_version, :change_summary, :require_retraining, NOW(), :instructor_user_id, :instructor_email, :instructor_name, :reciclagem, :reciclagem_periodo, :area_responsavel_id, :area_elaborador_id, :tipo_obrigatoriedade)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':nome', $data['nome'], PDO::PARAM_STR);
            $stmt->bindValue(':codigo', $data['codigo'], PDO::PARAM_STR);
            $stmt->bindValue(':training_family_key', $familyKey, PDO::PARAM_STR);
            $stmt->bindValue(':parent_training_id', $data['parent_training_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':versao', $data['versao'], PDO::PARAM_STR);
            $stmt->bindValue(':prazo_treinamento', $prazoTreinamento, PDO::PARAM_INT);
            $stmt->bindValue(':tipo', $data['tipo'], PDO::PARAM_STR);
            $stmt->bindValue(':instrutor', $data['instrutor'], PDO::PARAM_STR);
            $stmt->bindValue(':carga_horaria', $data['carga_horaria'] !== '' ? $data['carga_horaria'] : null, PDO::PARAM_STR);
            $stmt->bindValue(':ativo', $data['ativo'] ?? 1, PDO::PARAM_BOOL);
            $stmt->bindValue(':is_current_version', isset($data['is_current_version']) ? (int)$data['is_current_version'] : 1, PDO::PARAM_INT);
            $stmt->bindValue(':change_summary', $data['change_summary'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':require_retraining', isset($data['require_retraining']) ? (int)$data['require_retraining'] : 1, PDO::PARAM_INT);
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
                    'training_family_key' => $familyKey,
                    'parent_training_id' => $data['parent_training_id'] ?? null,
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
                    'is_current_version' => isset($data['is_current_version']) ? (int)$data['is_current_version'] : 1,
                    'change_summary' => $data['change_summary'] ?? null,
                    'require_retraining' => isset($data['require_retraining']) ? (int)$data['require_retraining'] : 1,
                ];
                \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                    'adms_trainings',
                    $novoId,
                    $_SESSION['user_id'] ?? 0,
                    'insert',
                    [],
                    $dadosDepois
                );
                
                // Invalidar cache de getAllTrainingsSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('trainings_select_all');
            }
            return $novoId;
        } catch (Exception $e) {
            // Log detalhado para análise em produção
            GenerateLog::generateLog("error", "Treinamento não cadastrado.", [
                'nome'   => $data['nome']   ?? '',
                'codigo' => $data['codigo'] ?? '',
                'error'  => $e->getMessage(),
            ]);

            // Expor mensagem técnica temporariamente para facilitar diagnóstico
            // (pode ser simplificada depois que o problema for identificado)
            $_SESSION['error'] = 'Erro ao cadastrar treinamento: ' . $e->getMessage();

            return false;
        }
    }

    public function updateTraining(int|string $id, array $data): bool
    {
        try {
            $codigo = trim((string)($data['codigo'] ?? ''));
            $versao = trim((string)($data['versao'] ?? ''));
            if ($codigo === '') {
                throw new Exception('O campo "Código" é obrigatório.');
            }
            if ($versao === '') {
                throw new Exception('O campo "Versão" é obrigatório.');
            }
            if ($this->existsCodeVersion($codigo, $versao, (int)$id)) {
                throw new Exception('Já existe outro treinamento com o mesmo código e versão.');
            }

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
                
                // Invalidar cache de getAllTrainingsSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('trainings_select_all');
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
                
                // Invalidar cache de getAllTrainingsSelect
                $cacheService = new \App\adms\Models\Services\QueryCacheService();
                $cacheService->forget('trainings_select_all');
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

    /**
     * Cria nova versão de treinamento de forma transacional.
     * - Inativa a versão de origem
     * - Cria a nova versão como atual
     * - Migra vínculos ativos
     * - Com "sem retreinamento", herda conclusões/aplicações da versão anterior
     */
    public function createNewVersion(int $sourceTrainingId, array $data, int $actorUserId = 0): bool|int
    {
        try {
            $source = $this->getTraining($sourceTrainingId);
            if (!$source) {
                throw new Exception('Treinamento de origem não encontrado.');
            }

            $codigo = trim((string)($source['codigo'] ?? ''));
            $versaoRaw = trim((string)($data['versao'] ?? ''));
            if ($versaoRaw === '' || !ctype_digit($versaoRaw)) {
                throw new Exception('A nova versão deve ser um número inteiro (1, 2, 3...).');
            }
            $versaoInt = (int)$versaoRaw;
            if ($versaoInt < 1) {
                throw new Exception('A nova versão deve ser maior que zero.');
            }
            $versao = (string)$versaoInt;
            if ($codigo === '' || $versao === '') {
                throw new Exception('Código e nova versão são obrigatórios.');
            }
            if ($this->existsCodeVersion($codigo, $versao)) {
                throw new Exception('Já existe treinamento com este código e versão.');
            }

            $changeSummary = trim((string)($data['change_summary'] ?? ''));
            if ($changeSummary === '') {
                throw new Exception('Resumo das alterações é obrigatório para nova versão.');
            }

            $requireRetraining = !empty($data['require_retraining']) ? 1 : 0;
            $familyKey = trim((string)($source['training_family_key'] ?? $codigo));
            if ($familyKey === '') {
                $familyKey = $codigo;
            }

            $sqlMaxVersion = 'SELECT COALESCE(MAX(CAST(versao AS UNSIGNED)), 0) AS max_version
                              FROM adms_trainings
                              WHERE (training_family_key = :family_key OR codigo = :family_key)
                                AND versao REGEXP "^[0-9]+$"';
            $stmtMaxVersion = $this->getConnection()->prepare($sqlMaxVersion);
            $stmtMaxVersion->bindValue(':family_key', $familyKey, PDO::PARAM_STR);
            $stmtMaxVersion->execute();
            $maxVersion = (int)($stmtMaxVersion->fetch(PDO::FETCH_ASSOC)['max_version'] ?? 0);
            if ($versaoInt !== ($maxVersion + 1)) {
                throw new Exception('A nova versão deve ser sequencial: ' . ($maxVersion + 1) . '.');
            }

            $conn = $this->getConnection();
            $conn->beginTransaction();

            $insertData = [
                'nome' => $data['nome'] ?? $source['nome'],
                'codigo' => $codigo,
                'training_family_key' => $familyKey,
                'parent_training_id' => $sourceTrainingId,
                'versao' => $versao,
                'prazo_treinamento' => (int)($data['prazo_treinamento'] ?? $source['prazo_treinamento'] ?? 0),
                'tipo' => $data['tipo'] ?? $source['tipo'] ?? '',
                'instrutor' => $data['instrutor'] ?? $source['instrutor'] ?? '',
                'carga_horaria' => $data['carga_horaria'] ?? $source['carga_horaria'] ?? null,
                'ativo' => 1,
                'is_current_version' => 1,
                'change_summary' => $changeSummary,
                'require_retraining' => $requireRetraining,
                'instructor_user_id' => $data['instructor_user_id'] ?? ($source['instructor_user_id'] ?? null),
                'instructor_email' => $data['instructor_email'] ?? ($source['instructor_email'] ?? null),
                'instructor_name' => $data['instructor_name'] ?? ($source['instructor_name'] ?? null),
                'reciclagem' => $data['reciclagem'] ?? ($source['reciclagem'] ?? 0),
                'reciclagem_periodo' => $data['reciclagem_periodo'] ?? ($source['reciclagem_periodo'] ?? null),
                'area_responsavel_id' => $data['area_responsavel_id'] ?? ($source['area_responsavel_id'] ?? null),
                'area_elaborador_id' => $data['area_elaborador_id'] ?? ($source['area_elaborador_id'] ?? null),
                'tipo_obrigatoriedade' => $data['tipo_obrigatoriedade'] ?? ($source['tipo_obrigatoriedade'] ?? null),
            ];

            $newTrainingId = $this->createTraining($insertData);
            if (!$newTrainingId) {
                throw new Exception('Falha ao criar nova versão do treinamento.');
            }

            $sqlUnsetCurrent = 'UPDATE adms_trainings
                                SET is_current_version = 0, updated_at = NOW()
                                WHERE training_family_key = :family_key
                                  AND id <> :new_id';
            $stmtUnsetCurrent = $conn->prepare($sqlUnsetCurrent);
            $stmtUnsetCurrent->bindValue(':family_key', $familyKey, PDO::PARAM_STR);
            $stmtUnsetCurrent->bindValue(':new_id', $newTrainingId, PDO::PARAM_INT);
            $stmtUnsetCurrent->execute();

            $sqlInactivateSource = 'UPDATE adms_trainings
                                    SET ativo = 0,
                                        is_current_version = 0,
                                        updated_at = NOW()
                                    WHERE id = :id';
            $stmtInactivateSource = $conn->prepare($sqlInactivateSource);
            $stmtInactivateSource->bindValue(':id', $sourceTrainingId, PDO::PARAM_INT);
            $stmtInactivateSource->execute();

            // Copiar matriz de cargos da versão anterior para a nova versão.
            // Isso mantém a estrutura de obrigatoriedade e permite ajustes na versão atual.
            $sqlCopyPositions = 'INSERT INTO adms_training_positions
                                 (adms_training_id, adms_position_id, obrigatorio, tipo_treinamento, reciclagem_periodo, created_at, updated_at)
                                 SELECT
                                    :new_training_id,
                                    tp.adms_position_id,
                                    tp.obrigatorio,
                                    tp.tipo_treinamento,
                                    tp.reciclagem_periodo,
                                    NOW(),
                                    NOW()
                                 FROM adms_training_positions tp
                                 WHERE tp.adms_training_id = :source_training_id
                                   AND NOT EXISTS (
                                       SELECT 1
                                       FROM adms_training_positions tp_exists
                                       WHERE tp_exists.adms_training_id = :new_training_id_2
                                         AND tp_exists.adms_position_id = tp.adms_position_id
                                   )';
            $stmtCopyPositions = $conn->prepare($sqlCopyPositions);
            $stmtCopyPositions->bindValue(':new_training_id', $newTrainingId, PDO::PARAM_INT);
            $stmtCopyPositions->bindValue(':new_training_id_2', $newTrainingId, PDO::PARAM_INT);
            $stmtCopyPositions->bindValue(':source_training_id', $sourceTrainingId, PDO::PARAM_INT);
            $stmtCopyPositions->execute();

            $sqlMigrateActive = 'INSERT INTO adms_training_users
                                 (adms_training_id, adms_user_id, data_realizacao, data_agendada, status, nota, certificado, created_at, updated_at, data_limite_primeiro_treinamento, tipo_vinculo, motivo, last_notification_expiring, last_notification_expired, observacoes)
                                 SELECT
                                    :new_training_id,
                                    tu.adms_user_id,
                                    NULL,
                                    NULL,
                                    CASE WHEN :require_retraining = 1 THEN "dentro_do_prazo" ELSE tu.status END,
                                    CASE WHEN :require_retraining = 1 THEN NULL ELSE tu.nota END,
                                    CASE WHEN :require_retraining = 1 THEN NULL ELSE tu.certificado END,
                                    NOW(),
                                    NOW(),
                                    tu.data_limite_primeiro_treinamento,
                                    tu.tipo_vinculo,
                                    CASE WHEN :require_retraining = 1 THEN "nova_versao_retreinamento" ELSE "nova_versao_migracao" END,
                                    NULL,
                                    NULL,
                                    tu.observacoes
                                 FROM adms_training_users tu
                                 WHERE tu.adms_training_id = :source_training_id
                                   AND tu.status <> "concluido"';
            $stmtMigrateActive = $conn->prepare($sqlMigrateActive);
            $stmtMigrateActive->bindValue(':new_training_id', $newTrainingId, PDO::PARAM_INT);
            $stmtMigrateActive->bindValue(':source_training_id', $sourceTrainingId, PDO::PARAM_INT);
            $stmtMigrateActive->bindValue(':require_retraining', $requireRetraining, PDO::PARAM_INT);
            $stmtMigrateActive->execute();

            if ($requireRetraining === 0) {
                $sqlCopyConcludedApps = 'INSERT INTO adms_training_applications
                                         (adms_user_id, adms_training_id, data_realizacao, data_avaliacao, data_agendada, instrutor_nome, instrutor_email, aplicado_por, nota, observacoes, status, created_at, updated_at)
                                         SELECT
                                            ta.adms_user_id,
                                            :new_training_id,
                                            ta.data_realizacao,
                                            ta.data_avaliacao,
                                            ta.data_agendada,
                                            ta.instrutor_nome,
                                            ta.instrutor_email,
                                            ta.aplicado_por,
                                            ta.nota,
                                            ta.observacoes,
                                            ta.status,
                                            NOW(),
                                            NOW()
                                         FROM adms_training_applications ta
                                         WHERE ta.adms_training_id = :source_training_id
                                           AND ta.status = "concluido"';
                $stmtCopyConcludedApps = $conn->prepare($sqlCopyConcludedApps);
                $stmtCopyConcludedApps->bindValue(':new_training_id', $newTrainingId, PDO::PARAM_INT);
                $stmtCopyConcludedApps->bindValue(':source_training_id', $sourceTrainingId, PDO::PARAM_INT);
                $stmtCopyConcludedApps->execute();

                $sqlCreateConcludedLinks = 'INSERT INTO adms_training_users
                                            (adms_training_id, adms_user_id, data_realizacao, data_agendada, status, nota, certificado, created_at, updated_at, data_limite_primeiro_treinamento, tipo_vinculo, motivo, last_notification_expiring, last_notification_expired, observacoes)
                                            SELECT
                                                :new_training_id,
                                                x.adms_user_id,
                                                x.data_realizacao,
                                                NULL,
                                                "concluido",
                                                x.nota,
                                                NULL,
                                                NOW(),
                                                NOW(),
                                                x.data_limite,
                                                "cargo",
                                                "nova_versao_sem_retreinamento",
                                                NULL,
                                                NULL,
                                                x.observacoes
                                            FROM (
                                                SELECT
                                                    ta.adms_user_id,
                                                    MAX(ta.data_realizacao) AS data_realizacao,
                                                    MAX(ta.nota) AS nota,
                                                    MAX(ta.observacoes) AS observacoes,
                                                    DATE_ADD(CURDATE(), INTERVAL 90 DAY) AS data_limite
                                                FROM adms_training_applications ta
                                                WHERE ta.adms_training_id = :source_training_id_2
                                                  AND ta.status = "concluido"
                                                GROUP BY ta.adms_user_id
                                            ) x
                                            LEFT JOIN adms_training_users tu_exists
                                                   ON tu_exists.adms_training_id = :new_training_id_2
                                                  AND tu_exists.adms_user_id = x.adms_user_id
                                            WHERE tu_exists.id IS NULL';
                $stmtCreateConcludedLinks = $conn->prepare($sqlCreateConcludedLinks);
                $stmtCreateConcludedLinks->bindValue(':new_training_id', $newTrainingId, PDO::PARAM_INT);
                $stmtCreateConcludedLinks->bindValue(':new_training_id_2', $newTrainingId, PDO::PARAM_INT);
                $stmtCreateConcludedLinks->bindValue(':source_training_id_2', $sourceTrainingId, PDO::PARAM_INT);
                $stmtCreateConcludedLinks->execute();
            }

            // Remove vínculos da versão anterior: a matriz passa a ser da versão nova.
            // Histórico de aplicações permanece em adms_training_applications (source_training_id).
            $sqlCleanupSource = 'DELETE FROM adms_training_users WHERE adms_training_id = :source_training_id';
            $stmtCleanupSource = $conn->prepare($sqlCleanupSource);
            $stmtCleanupSource->bindValue(':source_training_id', $sourceTrainingId, PDO::PARAM_INT);
            $stmtCleanupSource->execute();

            $conn->commit();

            \App\adms\Models\Services\LogAlteracaoService::registrarAlteracao(
                'adms_trainings',
                (int)$newTrainingId,
                $actorUserId,
                'version_create',
                $source,
                [
                    'new_training_id' => $newTrainingId,
                    'source_training_id' => $sourceTrainingId,
                    'version' => $versao,
                    'require_retraining' => $requireRetraining,
                    'change_summary' => $changeSummary,
                ]
            );

            return (int)$newTrainingId;
        } catch (Exception $e) {
            if ($this->getConnection()->inTransaction()) {
                $this->getConnection()->rollBack();
            }
            GenerateLog::generateLog('error', 'Falha ao criar nova versão de treinamento.', [
                'source_training_id' => $sourceTrainingId,
                'error' => $e->getMessage(),
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
        // Cache para queries frequentes (TTL: 5 minutos)
        $cacheService = new \App\adms\Models\Services\QueryCacheService(null, 300);
        // Stamp para renovar cache automaticamente quando houver mudanças na tabela
        $stampSql = 'SELECT COUNT(*) AS total_rows, COALESCE(MAX(id), 0) AS max_id FROM adms_trainings';
        $stampStmt = $this->getConnection()->prepare($stampSql);
        $stampStmt->execute();
        $stamp = $stampStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_rows' => 0, 'max_id' => 0];
        $cacheKey = 'trainings_select_all_' . (int)$stamp['total_rows'] . '_' . (int)$stamp['max_id'];
        
        // Tentar obter do cache
        $cached = $cacheService->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Se não estiver em cache, buscar do banco
        $sql = 'SELECT id, nome as name FROM adms_trainings ORDER BY nome ASC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Armazenar no cache
        $cacheService->put($cacheKey, $result);
        
        return $result;
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
     * Total de famílias de treinamento ativas (DISTINCT codigo), ignorando versões.
     */
    public function countActiveDistinctCodigos(): int
    {
        $sql = "SELECT COUNT(DISTINCT t.codigo) AS total
                FROM adms_trainings t
                WHERE t.ativo = 1
                  AND t.codigo IS NOT NULL
                  AND TRIM(t.codigo) <> ''";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        return (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);
    }

    /**
     * Retorna o total de colaboradores vinculados ao treinamento (direto ou por cargo obrigatório, sem duplicidade)
     */
    public function getTotalColaboradoresVinculados(int $trainingId): int
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

    /**
     * Lista versões da mesma família (mais nova primeiro).
     */
    public function getVersionsByFamily(string $familyKey): array
    {
        $familyKey = trim($familyKey);
        if ($familyKey === '') {
            return [];
        }

        $sql = 'SELECT id, nome, codigo, versao, ativo, is_current_version, parent_training_id, change_summary, require_retraining, created_at, updated_at
                FROM adms_trainings
                WHERE training_family_key = :family_key OR codigo = :family_key
                ORDER BY id DESC';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':family_key', $familyKey, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna linhas da auditoria de versionamento com filtro por família/código.
     *
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function getVersionAuditRows(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['family'])) {
            $where[] = 'COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) LIKE :family';
            $params[':family'] = '%' . trim((string)$filters['family']) . '%';
        }
        if (!empty($filters['codigo'])) {
            $where[] = 'TRIM(t.codigo) LIKE :codigo';
            $params[':codigo'] = '%' . trim((string)$filters['codigo']) . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $sql = 'SELECT
                    COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) AS familia,
                    t.codigo,
                    t.id AS training_id,
                    t.versao,
                    t.ativo,
                    t.is_current_version,
                    t.parent_training_id AS versao_origem_id,
                    t.change_summary AS resumo_alteracoes,
                    t.created_at,
                    t.updated_at
                FROM adms_trainings t
                ' . $whereSql . '
                ORDER BY familia ASC, CAST(t.versao AS UNSIGNED) DESC, t.id DESC';

        $stmt = $this->getConnection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Retorna alertas de inconsistência para versionamento.
     *
     * @param array<string, mixed> $filters
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getVersionAuditAlerts(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['family'])) {
            $where[] = 'COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) LIKE :family';
            $params[':family'] = '%' . trim((string)$filters['family']) . '%';
        }
        if (!empty($filters['codigo'])) {
            $where[] = 'TRIM(t.codigo) LIKE :codigo';
            $params[':codigo'] = '%' . trim((string)$filters['codigo']) . '%';
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $alerts = [
            'families_with_invalid_current_count' => [],
            'families_with_invalid_active_count' => [],
            'current_versions_not_active' => [],
        ];

        $sqlCurrentCount = 'SELECT
                                COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) AS familia,
                                COUNT(*) AS total_versoes,
                                SUM(CASE WHEN t.is_current_version = 1 THEN 1 ELSE 0 END) AS qtd_atuais
                            FROM adms_trainings t
                            ' . $whereSql . '
                            GROUP BY familia
                            HAVING SUM(CASE WHEN t.is_current_version = 1 THEN 1 ELSE 0 END) <> 1
                            ORDER BY familia ASC';
        $stmtCurrentCount = $this->getConnection()->prepare($sqlCurrentCount);
        foreach ($params as $key => $value) {
            $stmtCurrentCount->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtCurrentCount->execute();
        $alerts['families_with_invalid_current_count'] = $stmtCurrentCount->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlActiveCount = 'SELECT
                                COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) AS familia,
                                COUNT(*) AS total_versoes,
                                SUM(CASE WHEN t.ativo = 1 THEN 1 ELSE 0 END) AS qtd_ativas
                           FROM adms_trainings t
                           ' . $whereSql . '
                           GROUP BY familia
                           HAVING SUM(CASE WHEN t.ativo = 1 THEN 1 ELSE 0 END) <> 1
                           ORDER BY familia ASC';
        $stmtActiveCount = $this->getConnection()->prepare($sqlActiveCount);
        foreach ($params as $key => $value) {
            $stmtActiveCount->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtActiveCount->execute();
        $alerts['families_with_invalid_active_count'] = $stmtActiveCount->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $sqlCurrentInactive = 'SELECT
                                    COALESCE(NULLIF(TRIM(t.training_family_key), ""), TRIM(t.codigo)) AS familia,
                                    t.id AS training_id,
                                    t.codigo,
                                    t.versao,
                                    t.ativo,
                                    t.is_current_version
                               FROM adms_trainings t
                               ' . $whereSql . ($whereSql === '' ? ' WHERE ' : ' AND ') . 't.is_current_version = 1 AND t.ativo <> 1
                               ORDER BY familia ASC, t.id DESC';
        $stmtCurrentInactive = $this->getConnection()->prepare($sqlCurrentInactive);
        foreach ($params as $key => $value) {
            $stmtCurrentInactive->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmtCurrentInactive->execute();
        $alerts['current_versions_not_active'] = $stmtCurrentInactive->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return $alerts;
    }
} 