<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PoliciesRepository extends DbConnection
{
    /**
     * Listar políticas com paginação e filtros
     */
    public function getAllPolicies(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $whereConditions = [];
        $params = [];

        if (!empty($filters['categoria_id'])) {
            $whereConditions[] = 'p.categoria_id = :categoria_id';
            $params[':categoria_id'] = (int)$filters['categoria_id'];
        }
        if (!empty($filters['department_id'])) {
            $whereConditions[] = 'p.department_id = :department_id';
            $params[':department_id'] = (int)$filters['department_id'];
        }

        if (isset($filters['ativo']) && $filters['ativo'] !== '') {
            $whereConditions[] = 'p.ativo = :ativo';
            $params[':ativo'] = $filters['ativo'];
        }

        if (!empty($filters['urgente'])) {
            $whereConditions[] = 'p.urgente = :urgente';
            $params[':urgente'] = $filters['urgente'];
        }

        if (!empty($filters['data_inicio'])) {
            $whereConditions[] = 'DATE(p.created_at) >= :data_inicio';
            $params[':data_inicio'] = $filters['data_inicio'];
        }

        if (!empty($filters['data_fim'])) {
            $whereConditions[] = 'DATE(p.created_at) <= :data_fim';
            $params[':data_fim'] = $filters['data_fim'];
        }

        if (!empty($filters['busca'])) {
            $whereConditions[] = '(p.titulo LIKE :busca OR p.conteudo LIKE :busca OR p.resumo LIKE :busca)';
            $params[':busca'] = '%' . $filters['busca'] . '%';
        }

        // Filtro por janela de publicação (para usuários sem permissão de edição)
        if (!empty($filters['apenas_janela_publicacao'])) {
            $whereConditions[] = '(p.publish_at IS NULL OR p.publish_at <= NOW())';
            $whereConditions[] = '(p.expire_at IS NULL OR p.expire_at > NOW())';
        }

        $whereClause = '';
        if (!empty($whereConditions)) {
            $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
        }

        $sql = "SELECT p.*, u.name as usuario_nome, c.name as categoria_nome, d.name as department_name
                FROM adms_policies p
                LEFT JOIN adms_users u ON p.usuario_id = u.id
                LEFT JOIN adms_policies_categorias c ON c.id = p.categoria_id
                LEFT JOIN adms_departments d ON d.id = p.department_id
                {$whereClause}
                ORDER BY p.urgente DESC, COALESCE(p.publish_at, p.created_at) DESC
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
     * Contar total de políticas com filtros
     */
    public function getTotalPolicies(array $filters = []): int
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

        $sql = "SELECT COUNT(*) as total FROM adms_policies {$whereClause}";
        $stmt = $this->getConnection()->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $result['total'];
    }

    /**
     * Buscar política por ID
     */
    public function getPolicyById(int $id): ?array
    {
        $sql = "SELECT p.*, u.name as usuario_nome, c.name AS categoria_nome, d.name AS department_name
                FROM adms_policies p
                LEFT JOIN adms_users u ON p.usuario_id = u.id
                LEFT JOIN adms_policies_categorias c ON c.id = p.categoria_id
                LEFT JOIN adms_departments d ON d.id = p.department_id
                WHERE p.id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Marcar leitura (upsert) para o usuário
     */
    public function upsertRead(int $policyId, int $userId): void
    {
        // Atualiza TODAS as linhas existentes (evita duplicar em cenários sem UNIQUE adequado).
        $sqlUpdate = 'UPDATE adms_policies_reads
                      SET read_at = IF(read_at IS NULL, NOW(), read_at)
                      WHERE policy_id = :pol AND user_id = :usr';
        $stmt = $this->getConnection()->prepare($sqlUpdate);
        $stmt->bindValue(':pol', $policyId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() === 0) {
            $sqlExists = 'SELECT id
                           FROM adms_policies_reads
                           WHERE policy_id = :pol AND user_id = :usr
                           LIMIT 1';
            $stmtExists = $this->getConnection()->prepare($sqlExists);
            $stmtExists->bindValue(':pol', $policyId, PDO::PARAM_INT);
            $stmtExists->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmtExists->execute();

            if (!$stmtExists->fetch(PDO::FETCH_ASSOC)) {
                $sqlInsert = 'INSERT INTO adms_policies_reads (policy_id, user_id, read_at, created_at)
                               VALUES (:pol, :usr, NOW(), NOW())';
                $stmt2 = $this->getConnection()->prepare($sqlInsert);
                $stmt2->bindValue(':pol', $policyId, PDO::PARAM_INT);
                $stmt2->bindValue(':usr', $userId, PDO::PARAM_INT);
                $stmt2->execute();
            }
        }
    }

    /**
     * Registrar ciência (acknowledge)
     */
    public function acknowledge(int $policyId, int $userId): bool
    {
        try {
            // Atualiza TODAS as linhas existentes para garantir que a notificação some.
            $sqlUpdate = 'UPDATE adms_policies_reads
                          SET acknowledged = 1,
                              ack_at = NOW(),
                              read_at = IF(read_at IS NULL, NOW(), read_at)
                          WHERE policy_id = :pol AND user_id = :usr';
            $stmt = $this->getConnection()->prepare($sqlUpdate);
            $stmt->bindValue(':pol', $policyId, PDO::PARAM_INT);
            $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                return true;
            }

            // rowCount pode voltar 0 mesmo com linha existindo.
            // Checamos existência antes de inserir para não duplicar.
            $sqlExists = 'SELECT id
                           FROM adms_policies_reads
                           WHERE policy_id = :pol AND user_id = :usr
                           LIMIT 1';
            $stmtExists = $this->getConnection()->prepare($sqlExists);
            $stmtExists->bindValue(':pol', $policyId, PDO::PARAM_INT);
            $stmtExists->bindValue(':usr', $userId, PDO::PARAM_INT);
            $stmtExists->execute();

            if ($stmtExists->fetch(PDO::FETCH_ASSOC)) {
                return true;
            }

            // Se não existia linha alguma, cria.
            $sqlInsert = 'INSERT INTO adms_policies_reads (policy_id, user_id, read_at, acknowledged, ack_at, created_at)
                           VALUES (:pol, :usr, NOW(), 1, NOW(), NOW())';
            $stmt2 = $this->getConnection()->prepare($sqlInsert);
            $stmt2->bindValue(':pol', $policyId, PDO::PARAM_INT);
            $stmt2->bindValue(':usr', $userId, PDO::PARAM_INT);
            return $stmt2->execute();
        } catch (\Exception $e) {
            error_log("Erro ao registrar ciência de política: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obter leitura do usuário para uma política
     */
    public function getReadByUser(int $policyId, int $userId): ?array
    {
        $sql = 'SELECT id, read_at, acknowledged, ack_at
                FROM adms_policies_reads
                WHERE policy_id = :pol AND user_id = :usr
                ORDER BY acknowledged DESC, ack_at DESC, read_at DESC
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':pol', $policyId, PDO::PARAM_INT);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Usuários para relatório de políticas:
     * - Sempre inclui usuários com status "Ativo"
     * - Inclui também usuários inativos que já deram ciência (acknowledged = 1) para a política
     */
    public function getUsersForPolicyReport(int $policyId): array
    {
        $sql = 'SELECT DISTINCT 
                    u.id,
                    u.name,
                    u.email,
                    u.status
                FROM adms_users u
                LEFT JOIN adms_policies_reads r
                  ON r.user_id = u.id
                 AND r.policy_id = :policy_id
                WHERE u.status = "Ativo"
                   OR (r.acknowledged = 1)';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':policy_id', $policyId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    /**
     * Contar políticas não lidas para o usuário (dentro da janela)
     */
    public function countNaoLidos(int $userId): int
    {
        // NOT EXISTS para não “sofrer” com duplicidade de linhas em adms_policies_reads.
        // - requires_ack=1: só é não-lido se NÃO existir acknowledged=1.
        // - requires_ack=0: só é não-lido se NÃO existir read_at IS NOT NULL.
        $sql = 'SELECT COUNT(*) AS total
                FROM adms_policies p
                WHERE p.ativo = 1
                  AND (p.publish_at IS NULL OR p.publish_at <= NOW())
                  AND (p.expire_at IS NULL OR p.expire_at > NOW())
                  AND (
                        (p.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r
                            WHERE r.policy_id = p.id
                              AND r.user_id = :usr
                              AND r.acknowledged = 1
                        ))
                        OR ((p.requires_ack IS NULL OR p.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r2
                            WHERE r2.policy_id = p.id
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
     * Lista políticas ativas não lidas pelo usuário (para notificações)
     */
    public function getListNaoLidos(int $userId, int $limit = 15): array
    {
        $sql = 'SELECT p.id, p.titulo, p.resumo, p.urgente, p.created_at
                FROM adms_policies p
                WHERE p.ativo = 1
                  AND (p.publish_at IS NULL OR p.publish_at <= NOW())
                  AND (p.expire_at IS NULL OR p.expire_at > NOW())
                  AND (
                        (p.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r
                            WHERE r.policy_id = p.id
                              AND r.user_id = :usr
                              AND r.acknowledged = 1
                        ))
                        OR ((p.requires_ack IS NULL OR p.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r2
                            WHERE r2.policy_id = p.id
                              AND r2.user_id = :usr
                              AND r2.read_at IS NOT NULL
                        ))
                      )
                ORDER BY p.urgente DESC, p.created_at DESC
                LIMIT :limit';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':usr', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows ?: [];
    }

    /**
     * Retorna os IDs das políticas não lidas (mesma regra do sino), filtrando por uma lista de IDs.
     *
     * @param int   $userId
     * @param int[] $policyIds
     * @return int[]
     */
    public function getNaoLidosIdsByPolicyIds(int $userId, array $policyIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $policyIds), static fn ($v) => $v > 0)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT p.id
                FROM adms_policies p
                WHERE p.id IN ({$placeholders})
                  AND p.ativo = 1
                  AND (p.publish_at IS NULL OR p.publish_at <= NOW())
                  AND (p.expire_at IS NULL OR p.expire_at > NOW())
                  AND (
                        (p.requires_ack = 1 AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r
                            WHERE r.policy_id = p.id
                              AND r.user_id = ?
                              AND r.acknowledged = 1
                        ))
                        OR ((p.requires_ack IS NULL OR p.requires_ack = 0) AND NOT EXISTS (
                            SELECT 1
                            FROM adms_policies_reads r2
                            WHERE r2.policy_id = p.id
                              AND r2.user_id = ?
                              AND r2.read_at IS NOT NULL
                        ))
                      )";

        $stmt = $this->getConnection()->prepare($sql);
        $i = 1;
        // Ordem dos placeholders no SQL:
        // 1) IDs do p.id IN (...)
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
     * Atualiza o campo "ativo" com base em publish_at / expire_at.
     */
    public function updateActiveFromSchedule(): array
    {
        $conn = $this->getConnection();

        // Inativar políticas expiradas
        $sqlInativar = "UPDATE adms_policies
                        SET ativo = 0, updated_at = NOW()
                        WHERE ativo = 1
                          AND expire_at IS NOT NULL
                          AND expire_at <= NOW()";
        $stmtInativar = $conn->prepare($sqlInativar);
        $stmtInativar->execute();
        $inativados = (int) $stmtInativar->rowCount();

        // Ativar políticas já publicadas (que não expiraram)
        $sqlAtivar = "UPDATE adms_policies
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
     * Substitui os departamentos alvo de notificação de uma política.
     */
    public function replaceNotifyDepartments(int $policyId, array $departmentsIds): void
    {
        $conn = $this->getConnection();
        $conn->beginTransaction();
        try {
            $deleteSql = 'DELETE FROM adms_policies_notify_departments WHERE policy_id = :id';
            $stmtDel = $conn->prepare($deleteSql);
            $stmtDel->bindValue(':id', $policyId, PDO::PARAM_INT);
            $stmtDel->execute();

            $departmentsIds = array_values(array_unique(array_filter($departmentsIds, fn($v) => is_numeric($v) && (int)$v > 0)));
            if (!empty($departmentsIds)) {
                $insertSql = 'INSERT INTO adms_policies_notify_departments (policy_id, department_id, created_at)
                              VALUES (:policy_id, :department_id, NOW())';
                $stmtIns = $conn->prepare($insertSql);
                foreach ($departmentsIds as $depId) {
                    $stmtIns->bindValue(':policy_id', $policyId, PDO::PARAM_INT);
                    $stmtIns->bindValue(':department_id', (int)$depId, PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            }

            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            error_log('Erro ao atualizar departamentos de notificação da política: ' . $e->getMessage());
        }
    }

    /**
     * Retorna IDs de departamentos configurados para notificação de uma política.
     */
    public function getNotifyDepartmentsIds(int $policyId): array
    {
        $sql = 'SELECT department_id 
                FROM adms_policies_notify_departments
                WHERE policy_id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $policyId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows ?: []);
    }

    /**
     * Criar nova política
     */
    public function createPolicy(array $data): int
    {
        $sql = "INSERT INTO adms_policies (
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
     * Atualizar política
     */
    public function updatePolicy(int $id, array $data): bool
    {
        $sql = "UPDATE adms_policies 
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
     * Excluir política
     */
    public function deletePolicy(int $id): bool
    {
        $sql = "DELETE FROM adms_policies WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Listar categorias de políticas ativas
     */
    public function getCategorias(): array
    {
        $sql = "SELECT id, name FROM adms_policies_categorias WHERE ativo = 1 ORDER BY name";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCategoriaById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT id, name, ativo FROM adms_policies_categorias WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createCategoria(string $name, bool $ativo = true): int
    {
        $stmt = $this->getConnection()->prepare(
            'INSERT INTO adms_policies_categorias (name, ativo, created_at, updated_at)
             VALUES (:name, :ativo, NOW(), NOW())'
        );
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);
        $stmt->execute();

        return (int) $this->getConnection()->lastInsertId();
    }

    public function updateCategoria(int $id, string $name, bool $ativo): bool
    {
        $stmt = $this->getConnection()->prepare(
            'UPDATE adms_policies_categorias
             SET name = :name, ativo = :ativo, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':ativo', $ativo, PDO::PARAM_BOOL);

        return $stmt->execute();
    }

    public function deleteCategoria(int $id): bool
    {
        $stmt = $this->getConnection()->prepare('DELETE FROM adms_policies_categorias WHERE id = :id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Políticas para dashboard (mais recentes e ativas)
     */
    public function getPoliciesDashboard(int $limit = 5, ?int $categoriaId = null): array
    {
        $whereClause = 'WHERE p.ativo = 1 AND (p.publish_at IS NULL OR p.publish_at <= NOW()) AND (p.expire_at IS NULL OR p.expire_at > NOW())';
        $params = [];
        if ($categoriaId) {
            $whereClause .= ' AND p.categoria_id = :categoria_id';
            $params[':categoria_id'] = (int)$categoriaId;
        }

        $sql = "SELECT p.*, u.name as usuario_nome, c.name as categoria_nome, d.name as department_name
                FROM adms_policies p
                LEFT JOIN adms_users u ON p.usuario_id = u.id
                LEFT JOIN adms_policies_categorias c ON c.id = p.categoria_id
                LEFT JOIN adms_departments d ON d.id = p.department_id
                {$whereClause}
                ORDER BY p.urgente DESC, p.created_at DESC
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
     * Contar políticas urgentes ativas
     */
    public function countPoliciesUrgentes(): int
    {
        $sql = "SELECT COUNT(*) as total FROM adms_policies 
                WHERE urgente = 1 AND ativo = 1 
                AND (publish_at IS NULL OR publish_at <= NOW()) 
                AND (expire_at IS NULL OR expire_at > NOW())";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }
}

