<?php

namespace App\adms\Models\Repository;

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
                ORDER BY i.urgente DESC, COALESCE(i.publish_at, i.created_at) DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        return $result ?: null;
    }

    /**
     * Marcar leitura (upsert) para o usuário
     */
    public function upsertRead(int $informativoId, int $userId): void
    {
        $sql = 'INSERT INTO adms_informativos_reads (informativo_id, user_id, read_at, created_at)
                VALUES (:inf, :usr, NOW(), NOW())
                ON DUPLICATE KEY UPDATE read_at = IF(read_at IS NULL, VALUES(read_at), read_at)';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Registrar ciência (acknowledge)
     */
    public function acknowledge(int $informativoId, int $userId): bool
    {
        try {
            $sql = 'INSERT INTO adms_informativos_reads (informativo_id, user_id, read_at, acknowledged, ack_at, created_at)
                    VALUES (:inf, :usr, NOW(), 1, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE acknowledged = 1, ack_at = NOW(), read_at = IF(read_at IS NULL, NOW(), read_at)';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
            $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            return $result;
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
                WHERE informativo_id = :inf AND user_id = :usr LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':inf', $informativoId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Contar não lidos para o usuário (dentro da janela)
     */
    public function countNaoLidos(int $userId): int
    {
        $sql = 'SELECT COUNT(*) AS total
                FROM adms_informativos i
                LEFT JOIN adms_informativos_reads r
                  ON r.informativo_id = i.id AND r.user_id = :usr
                WHERE i.ativo = 1
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
                LEFT JOIN adms_informativos_reads r
                  ON r.informativo_id = i.id AND r.user_id = :usr
                WHERE i.ativo = 1
                  AND (i.publish_at IS NULL OR i.publish_at <= NOW())
                  AND (i.expire_at IS NULL OR i.expire_at > NOW())
                  AND r.id IS NULL
                ORDER BY i.urgente DESC, i.created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoriaById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name FROM adms_informativos_categorias WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
} 