<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\TextEncodingHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class InformativosRepository extends DbConnection
{
    /**
     * Listar informativos com paginação e filtros
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function getAllInformativos(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        // Garantir que a página seja sempre pelo menos 1
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        
        $whereConditions = [];
        $params = [];

        // Listagem para quem pode criar/editar: ativos de qualquer autor + todos os próprios (qualquer status)
        if (!empty($filters['editor_list_scope_user_id'])) {
            $whereConditions[] = '(i.usuario_id = :editor_list_uid OR i.ativo = 1)';
            $params[':editor_list_uid'] = (int) $filters['editor_list_scope_user_id'];
        }

        if (!empty($filters['categoria_id'])) {
            $whereConditions[] = 'i.categoria_id = :categoria_id';
            $params[':categoria_id'] = (int)$filters['categoria_id'];
        }
        if (!empty($filters['department_id'])) {
            $whereConditions[] = 'i.department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }
        
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $whereConditions[] = 'i.ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }
        
        if (!empty($filters['urgente'])) {
            $whereConditions[] = 'i.urgente = :urgente';
            $params[':urgente'] = $filters['urgente'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $whereConditions[] = 'DATE(i.created_at) >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $whereConditions[] = 'DATE(i.created_at) <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'];
        }
        
        if (!empty($filters['busca'])) {
            $whereConditions[] = '(i.titulo LIKE :busca OR i.conteudo LIKE :busca OR i.resumo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }
        
        // Filtro por janela de publicação (apenas para usuários sem permissão de edição)
        if (!empty($filters['apenas_janela_publicacao'])) {
            $whereConditions[] = '(i.publish_at IS NULL OR i.publish_at <= NOW())';
            $whereConditions[] = '(i.expire_at IS NULL OR i.expire_at > NOW())';
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT i.*, u.name as usuario_nome, c.name as categoria_nome, d.name as department_name
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                LEFT JOIN adms_informativos_categorias c ON c.id = i.categoria_id
                LEFT JOIN adms_departments d ON d.id = i.department_id
                {$whereClause}
                ORDER BY i.ativo DESC, i.urgente DESC, COALESCE(i.publish_at, i.created_at) DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
    
    /**
     * Contar total de informativos com filtros
     * @param array $filters
     * @return int
     */
    public function getTotalInformativos(array $filters = []): int
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['editor_list_scope_user_id'])) {
            $whereConditions[] = '(usuario_id = :editor_list_uid OR ativo = 1)';
            $params[':editor_list_uid'] = (int) $filters['editor_list_scope_user_id'];
        }

        if (!empty($filters['categoria_id'])) {
            $whereConditions[] = 'categoria_id = :categoria_id';
            $params[':categoria_id'] = (int)$filters['categoria_id'];
        }
        if (!empty($filters['department_id'])) {
            $whereConditions[] = 'department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }
        
        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $whereConditions[] = 'ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }
        
        if (!empty($filters['urgente'])) {
            $whereConditions[] = 'urgente = :urgente';
            $params[':urgente'] = $filters['urgente'];
        }
        
        if (!empty($filters['data_inicio'])) {
            $whereConditions[] = 'DATE(created_at) >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'];
        }
        
        if (!empty($filters['data_fim'])) {
            $whereConditions[] = 'DATE(created_at) <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'];
        }
        
        if (!empty($filters['busca'])) {
            $whereConditions[] = '(titulo LIKE :busca OR conteudo LIKE :busca OR resumo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        if (!empty($filters['apenas_janela_publicacao'])) {
            $whereConditions[] = '(publish_at IS NULL OR publish_at <= NOW())';
            $whereConditions[] = '(expire_at IS NULL OR expire_at > NOW())';
        }
        
        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }
        
        $sql = "SELECT COUNT(*) as total FROM adms_informativos {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['total'];
    }
    
    /**
     * Buscar informativo por ID
     * @param int $id
     * @return array|null
     */
    public function getInformativoById(int $id): ?array
    {
        $sql = "SELECT i.*, u.name as usuario_nome, c.name AS categoria_nome, d.name AS department_name
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                LEFT JOIN adms_informativos_categorias c ON c.id = i.categoria_id
                LEFT JOIN adms_departments d ON d.id = i.department_id
                WHERE i.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->normalizeRow($result) : null;
    }

    /**
     * Marcar leitura (upsert) para o usuário
     */
    public function upsertRead(int $informativoId, int $userId): void
    {
        // Atualiza TODAS as linhas existentes (evita duplicar em cenários sem UNIQUE adequado).
        $sqlUpdate = 'UPDATE adms_informativos_reads
                      SET read_at = IF(read_at IS NULL, NOW(), read_at)
                      WHERE informativo_id = :inf AND user_id = :usr';
        $stmt = $this->getConnection()->prepare($sqlUpdate);
        $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Se não existia linha alguma, cria.
        if ($stmt->rowCount() === 0) {
            $sqlExists = 'SELECT id
                           FROM adms_informativos_reads
                           WHERE informativo_id = :inf AND user_id = :usr
                           LIMIT 1';
            $stmtExists = $this->getConnection()->prepare($sqlExists);
            $stmtExists->bindValue(':inf', $informativoId, PDO::PARAM_INT);
            $stmtExists->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmtExists->execute();

            if (!$stmtExists->fetch(PDO::FETCH_ASSOC)) {
                $sqlInsert = 'INSERT INTO adms_informativos_reads (informativo_id, user_id, read_at, created_at)
                               VALUES (:inf, :usr, NOW(), NOW())';
                $stmt2 = $this->getConnection()->prepare($sqlInsert);
                $stmt2->bindValue(':inf', $informativoId, PDO::PARAM_INT);
                $stmt2->bindValue(':usr', $userId, PDO::PARAM_INT);
                $stmt2->execute();
            }
        }
    }

    /**
     * Registrar ciência (acknowledge)
     */
    public function acknowledge(int $informativoId, int $userId): bool
    {
        try {
            // Atualiza TODAS as linhas existentes para não deixar "lixo" com acknowledged != 1.
            $sqlUpdate = 'UPDATE adms_informativos_reads
                           SET acknowledged = 1,
                               ack_at = NOW(),
                               read_at = IF(read_at IS NULL, NOW(), read_at)
                           WHERE informativo_id = :inf AND user_id = :usr';
            $stmt = $this->getConnection()->prepare($sqlUpdate);
            $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
            $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return true;
            }

            // rowCount pode voltar 0 mesmo com linha existindo (mesmo valor).
            // Checamos existência antes de inserir para não duplicar.
            $sqlExists = 'SELECT id
                           FROM adms_informativos_reads
                           WHERE informativo_id = :inf AND user_id = :usr
                           LIMIT 1';
            $stmtExists = $this->getConnection()->prepare($sqlExists);
            $stmtExists->bindValue(':inf', $informativoId, PDO::PARAM_INT);
            $stmtExists->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmtExists->execute();

            if ($stmtExists->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }

            // Se não existia linha alguma, cria.
            $sqlInsert = 'INSERT INTO adms_informativos_reads (informativo_id, user_id, read_at, acknowledged, ack_at, created_at)
                           VALUES (:inf, :usr, NOW(), 1, NOW(), NOW())';
            $stmt2 = $this->getConnection()->prepare($sqlInsert);
            $stmt2->bindValue(':inf', $informativoId, PDO::PARAM_INT);
            $stmt2->bindValue(':usr', $userId, PDO::PARAM_INT);
            return $stmt2->execute();
        } catch (\Exception $e) {
            // Log do erro
            error_log("Erro ao registrar ciência: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter leitura do usuário para um informativo
     */
    public function getReadByUser(int $informativoId, int $userId): ?array
    {
        $sql = 'SELECT id, read_at, acknowledged, ack_at
                FROM adms_informativos_reads
                WHERE informativo_id = :inf AND user_id = :usr
                ORDER BY acknowledged DESC, ack_at DESC, read_at DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }

    /**
     * Contar não lidos para o usuário (dentro da janela)
     */
    public function countNaoLidos(int $userId): int
    {
        // NOT EXISTS para não “sofrer” com duplicidade de linhas em adms_informativos_reads.
        // - requires_ack=1: só é não-lido se NÃO existir acknowledged=1 para o usuário.
        // - requires_ack=0: só é não-lido se NÃO existir read_at IS NOT NULL para o usuário.
        $sql = 'SELECT COUNT(*) AS total
                FROM adms_informativos i
                WHERE i.ativo = 1
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expire_at IS NULL OR i.expire_at > NOW())
                  AND (
                        (i.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r
                            WHERE r.informativo_id = i.id
                              AND r.user_id = :usr
                              AND r.acknowledged = 1
                        ))
                        OR ((i.requires_ack IS NULL OR i.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r2
                            WHERE r2.informativo_id = i.id
                              AND r2.user_id = :usr
                              AND r2.read_at IS NOT NULL
                        ))
                      )';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Contar urgentes não lidos
     */
    public function countUrgentesNaoLidos(int $userId): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM adms_informativos i
                LEFT JOIN adms_informativos_reads r
                  ON r.informativo_id = i.id AND r.user_id = :usr
                WHERE i.ativo = 1 AND i.urgente = 1
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expire_at IS NULL OR i.expire_at > NOW())
                  AND r.id IS NULL';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }

    /**
     * Lista informativos ativos não lidos pelo usuário (para notificações/sino).
     * Considera janela de publicação (publish_at / expire_at).
     *
     * @param int $userId
     * @param int $limit
     * @return array<int, array>
     */
    public function getListNaoLidos(int $userId, int $limit = 15): array
    {
        $sql = 'SELECT i.id, i.titulo, i.resumo, i.urgente, i.created_at
                FROM adms_informativos i
                WHERE i.ativo = 1
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expire_at IS NULL OR i.expire_at > NOW())
                  AND (
                        (i.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r
                            WHERE r.informativo_id = i.id
                              AND r.user_id = :usr
                              AND r.acknowledged = 1
                        ))
                        OR ((i.requires_ack IS NULL OR i.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r2
                            WHERE r2.informativo_id = i.id
                              AND r2.user_id = :usr
                              AND r2.read_at IS NOT NULL
                        ))
                      )
                ORDER BY i.urgente DESC, i.created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $this->normalizeRows($rows ?: []);
    }

    /**
     * Retorna os IDs dos informativos não lidos (mesma regra do sino),
     * filtrando apenas os IDs presentes na lista da tela.
     *
     * @param int   $userId
     * @param int[] $informativoIds
     * @return int[]
     */
    public function getNaoLidosIdsByInformativoIds(int $userId, array $informativoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $informativoIds), static fn ($v) => $v > 0)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $sql = "SELECT i.id
                FROM adms_informativos i
                WHERE i.id IN ({$placeholders})
                  AND i.ativo = 1
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expire_at IS NULL OR i.expire_at > NOW())
                  AND (
                        (i.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r
                            WHERE r.informativo_id = i.id
                              AND r.user_id = ?
                              AND r.acknowledged = 1
                        ))
                        OR ((i.requires_ack IS NULL OR i.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_informativos_reads r2
                            WHERE r2.informativo_id = i.id
                              AND r2.user_id = ?
                              AND r2.read_at IS NOT NULL
                        ))
                      )";

        $stmt = $this->getConnection()->prepare($sql);
        $i = 1;
        // Ordem dos placeholders no SQL:
        // 1) IDs do i.id IN (...)
        // 2) user_id do NOT EXISTS (acknowledged=1)
        // 3) user_id do NOT EXISTS (read_at IS NOT NULL)
        foreach ($ids as $id) {
            $stmt->bindValue($i++, $id, PDO::PARAM_INT);
        }
        $stmt->bindValue($i++, $userId, PDO::PARAM_INT);
        $stmt->bindValue($i++, $userId, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(static fn ($row) => (int) $row['id'], $rows);
    }

    /**
     * Último registro de leitura/ciência do usuário por informativo (mesma prioridade de getReadByUser).
     *
     * @param int   $userId
     * @param int[] $informativoIds
     * @return array<int, array<string, mixed>> mapa informativo_id => linha normalizada
     */
    public function getReadsMapForUser(int $userId, array $informativoIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $informativoIds), static fn ($v) => $v > 0)));
        if ($userId <= 0 || $ids === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT informativo_id, read_at, acknowledged, ack_at
                FROM adms_informativos_reads
                WHERE user_id = ?
                  AND informativo_id IN ({$placeholders})";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $i = 2;
        foreach ($ids as $id) {
            $stmt->bindValue($i++, $id, PDO::PARAM_INT);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byInf = [];
        foreach ($rows as $row) {
            $infId = (int) ($row['informativo_id'] ?? 0);
            if ($infId <= 0) {
                continue;
            }
            $byInf[$infId][] = $row;
        }

        $map = [];
        foreach ($byInf as $infId => $group) {
            usort($group, static function (array $a, array $b): int {
                $aAck = !empty($a['acknowledged']) ? 1 : 0;
                $bAck = !empty($b['acknowledged']) ? 1 : 0;
                if ($aAck !== $bAck) {
                    return $bAck <=> $aAck;
                }
                $aAckAt = strtotime((string) ($a['ack_at'] ?? '')) ?: 0;
                $bAckAt = strtotime((string) ($b['ack_at'] ?? '')) ?: 0;
                if ($aAckAt !== $bAckAt) {
                    return $bAckAt <=> $aAckAt;
                }
                $aRead = strtotime((string) ($a['read_at'] ?? '')) ?: 0;
                $bRead = strtotime((string) ($b['read_at'] ?? '')) ?: 0;

                return $bRead <=> $aRead;
            });
            $map[$infId] = $this->normalizeRow($group[0]);
        }

        return $map;
    }
    
    /**
     * Atualiza o campo "ativo" com base em publish_at / expire_at.
     *
     * - Inativa informativos expirados (expire_at <= agora)
     * - Ativa informativos publicados (publish_at <= agora) que ainda não expiraram
     *
     * Retorna array com contagem de registros afetados.
     *
     * @return array{inativados:int,ativados:int}
     */
    public function updateActiveFromSchedule(): array
    {
        $conn = $this->getConnection();

        // Inativar informativos expirados
        $sqlInativar = "UPDATE adms_informativos
                        SET ativo = 0, updated_at = NOW()
                        WHERE ativo = 1
                          AND expire_at IS NOT NULL
                          AND expire_at <= NOW()";
        $stmtInativar = $conn->prepare($sqlInativar);
        $stmtInativar->execute();
        $inativados = (int) $stmtInativar->rowCount();

        // Ativar informativos já publicados (que não expiraram)
        $sqlAtivar = "UPDATE adms_informativos
                      SET ativo = 1, updated_at = NOW()
                      WHERE ativo = 0
                        AND (publish_at IS NULL OR publish_at <= NOW())
                        AND (expire_at IS NULL OR expire_at > NOW())";
        $stmtAtivar = $conn->prepare($sqlAtivar);
        $stmtAtivar->execute();
        $ativados = (int) $stmtAtivar->rowCount();

        return [
            'inativados' => $inativados,
            'ativados'   => $ativados,
        ];
    }

    /**
     * Substitui (replace) os departamentos alvo de notificação de um informativo.
     * Se o array estiver vazio, entende-se que a notificação é para TODOS os departamentos.
     *
     * @param int   $informativoId
     * @param int[] $departmentsIds
     * @return void
     */
    public function replaceNotifyDepartments(int $informativoId, array $departmentsIds): void
    {
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            $deleteSql = 'DELETE FROM adms_informativos_notify_departments WHERE informativo_id = :id';
            $stmtDel = $conn->prepare($deleteSql);
            $stmtDel->bindValue(':id', $informativoId, PDO::PARAM_INT);
            $stmtDel->execute();

            $departmentsIds = array_values(array_unique(array_filter($departmentsIds, fn($v) => is_numeric($v) && (int)$v > 0)));
            if (!empty($departmentsIds)) {
                $insertSql = 'INSERT INTO adms_informativos_notify_departments (informativo_id, department_id, created_at)
                              VALUES (:informativo_id, :department_id, NOW())';
                $stmtIns = $conn->prepare($insertSql);
                foreach ($departmentsIds as $depId) {
                    $stmtIns->bindValue(':informativo_id', $informativoId, PDO::PARAM_INT);
                    $stmtIns->bindValue(':department_id', (int)$depId, PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('Erro ao atualizar departamentos de notificação do informativo: ' . $e->getMessage());
        }
    }

    /**
     * Retorna apenas os IDs de departamentos configurados para notificação
     * de um determinado informativo.
     *
     * @param int $informativoId
     * @return int[]
     */
    public function getNotifyDepartmentsIds(int $informativoId): array
    {
        $sql = 'SELECT department_id 
                FROM adms_informativos_notify_departments
                WHERE informativo_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $informativoId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows ?: []);
    }
    
    /**
     * Criar novo informativo
     * @param array $data
     * @return int
     */
    public function createInformativo(array $data): int
    {
        $sql = "INSERT INTO adms_informativos (
                    titulo, conteudo, resumo, categoria, categoria_id, department_id,
                    imagem, anexo, urgente, notificar, requires_ack, ativo, publish_at, expire_at, usuario_id, created_at, updated_at
                )
                VALUES (
                    :titulo, :conteudo, :resumo, :categoria, :categoria_id, :department_id,
                    :imagem, :anexo, :urgente, :notificar, :requires_ack, :ativo, :publish_at, :expire_at, :usuario_id, NOW(), NOW()
                )";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':resumo', $data['resumo'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $data['categoria'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria_id', $data['categoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindValue(':imagem', $data['imagem'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':anexo', $data['anexo'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':urgente', $data['urgente'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':notificar', $data['notificar'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':requires_ack', $data['requires_ack'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':ativo', $data['ativo'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':publish_at', $data['publish_at'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':expire_at', $data['expire_at'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':usuario_id', $data['usuario_id'], PDO::PARAM_INT);
        
        $stmt->execute();
        
        return (int) $this->getConnection()->lastInsertId();
    }
    
    /**
     * Atualizar informativo
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateInformativo(int $id, array $data): bool
    {
        $sql = "UPDATE adms_informativos 
                SET titulo = :titulo, conteudo = :conteudo, resumo = :resumo, categoria = :categoria,
                    categoria_id = :categoria_id, department_id = :department_id,
                    imagem = :imagem, anexo = :anexo, urgente = :urgente, notificar = :notificar, requires_ack = :requires_ack, ativo = :ativo,
                    publish_at = :publish_at, expire_at = :expire_at,
                    updated_at = NOW()
                WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':titulo', $data['titulo'], PDO::PARAM_STR);
        $stmt->bindValue(':conteudo', $data['conteudo'], PDO::PARAM_STR);
        $stmt->bindValue(':resumo', $data['resumo'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria', $data['categoria'], PDO::PARAM_STR);
        $stmt->bindValue(':categoria_id', $data['categoria_id'], PDO::PARAM_INT);
        $stmt->bindValue(':department_id', $data['department_id'], PDO::PARAM_INT);
        $stmt->bindValue(':imagem', $data['imagem'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':anexo', $data['anexo'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':urgente', $data['urgente'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':notificar', $data['notificar'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':requires_ack', $data['requires_ack'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindValue(':ativo', $data['ativo'] ?? true, PDO::PARAM_BOOL);
        $stmt->bindValue(':publish_at', $data['publish_at'] ?? null, PDO::PARAM_STR);
        $stmt->bindValue(':expire_at', $data['expire_at'] ?? null, PDO::PARAM_STR);
        
        return $stmt->execute();
    }
    
    /**
     * Excluir informativo
     * @param int $id
     * @return bool
     */
    public function deleteInformativo(int $id): bool
    {
        $sql = "DELETE FROM adms_informativos WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Listar categorias disponíveis
     * @return array
     */
    public function getCategorias(): array
    {
        $sql = "SELECT id, name FROM adms_informativos_categorias WHERE ativo = 1 ORDER BY name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    public function getCategoriaById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name FROM adms_informativos_categorias WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeRow($row) : null;
    }
    
    /**
     * Listar informativos para o dashboard (mais recentes e ativos)
     * @param int $limit
     * @param string|null $categoria
     * @return array
     */
    public function getInformativosDashboard(int $limit = 5, ?int $categoriaId = null): array
    {
        $whereClause = 'WHERE i.ativo = 1 AND (i.publish_at IS NULL OR i.publish_at <= NOW()) AND (i.expire_at IS NULL OR i.expire_at > NOW())';
        $params = [];
        if ($categoriaId) {
            $whereClause .= ' AND i.categoria_id = :categoria_id';
            $params[':categoria_id'] = (int)$categoriaId;
        }
        
        $sql = "SELECT i.*, u.name as usuario_nome, c.name as categoria_nome, d.name as department_name
                FROM adms_informativos i
                LEFT JOIN adms_users u ON i.usuario_id = u.id
                LEFT JOIN adms_informativos_categorias c ON c.id = i.categoria_id
                LEFT JOIN adms_departments d ON d.id = i.department_id
                {$whereClause}
                ORDER BY i.urgente DESC, i.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
    
    /**
     * Contar informativos urgentes ativos
     * @return int
     */
    public function countInformativosUrgentes(): int
    {
        $sql = "SELECT COUNT(*) as total FROM adms_informativos 
                WHERE urgente = 1 AND ativo = 1 
                AND (publish_at IS NULL OR publish_at <= NOW()) 
                AND (expire_at IS NULL OR expire_at > NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row)) {
                $row = $this->normalizeRow($row);
            }
        }
        unset($row);
        return $rows;
    }

    private function normalizeRow(array $row): array
    {
        foreach (['titulo', 'conteudo', 'resumo', 'categoria', 'categoria_nome', 'usuario_nome', 'department_name'] as $field) {
            if (array_key_exists($field, $row) && is_string($row[$field])) {
                $row[$field] = TextEncodingHelper::decodeEntities($row[$field]);
            }
        }
        return $row;
    }
} 