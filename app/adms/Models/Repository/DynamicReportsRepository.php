<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class DynamicReportsRepository extends DbConnection
{
    /**
     * Relatórios dinâmicos visíveis para o utilizador.
     *
     * @param bool $includeAllReports Quando true (ex.: super utilizador / acesso total), lista todos os relatórios ativos,
     *                                independentemente do criador ou da flag is_public.
     */
    public function getUserReports(int $userId, bool $includeAllReports = false): array
    {
        // Super administrador / flag super usuário: todos os relatórios ativos (sem JOIN à tabela de partilhas —
        // evita Erro 004 se a migração `adms_dynamic_report_shared_users` ainda não existir).
        if ($includeAllReports) {
            $sql = "SELECT r.*, u.name as creator_name,
                           (SELECT COUNT(*) FROM adms_report_favorites WHERE report_id = r.id) as favorite_count,
                           EXISTS(SELECT 1 FROM adms_report_favorites WHERE report_id = r.id AND user_id = :user_id) as is_favorite,
                           0 AS access_via_share
                    FROM adms_dynamic_reports r
                    INNER JOIN adms_users u ON u.id = r.created_by
                    WHERE r.is_active = 1
                    ORDER BY r.updated_at DESC";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        try {
            return $this->fetchUserReportsForUserWithShareTable($userId);
        } catch (\Throwable $e) {
            error_log('DynamicReportsRepository::getUserReports (fallback sem partilhas): ' . $e->getMessage());

            return $this->fetchUserReportsForUserWithoutShareTable($userId);
        }
    }

    /**
     * Listagem com partilha por utilizador (requer tabela adms_dynamic_report_shared_users).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchUserReportsForUserWithShareTable(int $userId): array
    {
        $sql = "SELECT r.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM adms_report_favorites WHERE report_id = r.id) as favorite_count,
                       EXISTS(SELECT 1 FROM adms_report_favorites WHERE report_id = r.id AND user_id = :user_id) as is_favorite,
                       EXISTS(
                           SELECT 1 FROM adms_dynamic_report_shared_users sh
                           WHERE sh.report_id = r.id AND sh.user_id = :user_id2
                       ) as access_via_share
                FROM adms_dynamic_reports r
                INNER JOIN adms_users u ON u.id = r.created_by
                WHERE (r.created_by = :user_id OR r.is_public = 1 OR EXISTS (
                    SELECT 1 FROM adms_dynamic_report_shared_users sh2
                    WHERE sh2.report_id = r.id AND sh2.user_id = :user_id3
                )) AND r.is_active = 1
                ORDER BY r.updated_at DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id3', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Listagem sem tabela de partilhas (comportamento anterior à partilha por utilizador).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchUserReportsForUserWithoutShareTable(int $userId): array
    {
        $sql = "SELECT r.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM adms_report_favorites WHERE report_id = r.id) as favorite_count,
                       EXISTS(SELECT 1 FROM adms_report_favorites WHERE report_id = r.id AND user_id = :user_id) as is_favorite,
                       0 AS access_via_share
                FROM adms_dynamic_reports r
                INNER JOIN adms_users u ON u.id = r.created_by
                WHERE (r.created_by = :user_id OR r.is_public = 1) AND r.is_active = 1
                ORDER BY r.updated_at DESC";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Utilizadores ativos para multiselect de partilha (exclui o próprio utilizador).
     *
     * @return list<array{id: int, name: string, email: string}>
     */
    public function listUsersForReportShare(int $excludeUserId, int $limit = 2000): array
    {
        $sql = "SELECT id, name, email FROM adms_users
                WHERE status = 'Ativo'
                  AND id != :exclude
                  AND (bloqueado IS NULL OR bloqueado IN ('Não', 'Nao', 'NÃO', 'não', '0', 0))
                ORDER BY name ASC
                LIMIT " . max(1, min($limit, 5000));

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':exclude', $excludeUserId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * IDs dos utilizadores com acesso explícito ao relatório (além do criador / público).
     *
     * @return int[]
     */
    public function getSharedUserIds(int $reportId): array
    {
        if ($reportId < 1) {
            return [];
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT user_id FROM adms_dynamic_report_shared_users WHERE report_id = :rid ORDER BY user_id'
            );
            $stmt->bindValue(':rid', $reportId, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return array_values(array_map('intval', $rows ?: []));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Substitui a lista de utilizadores com partilha explícita. Apenas quem pode editar o relatório deve chamar.
     *
     * @param int[] $userIds
     */
    public function setSharedUsers(int $reportId, array $userIds, int $actingUserId): void
    {
        if ($reportId < 1 || $actingUserId < 1) {
            return;
        }
        $report = $this->getById($reportId);
        if (!$report || !$this->userCanEditReport($report, $actingUserId)) {
            return;
        }
        $creatorId = (int) ($report['created_by'] ?? 0);
        $clean = [];
        foreach ($userIds as $uid) {
            $uid = (int) $uid;
            if ($uid > 0 && $uid !== $creatorId) {
                $clean[$uid] = $uid;
            }
        }

        $conn = $this->getConnection();
        $oldShareSnap = json_encode($this->getSharedUserIds($reportId));
        $conn->beginTransaction();
        try {
            $del = $conn->prepare('DELETE FROM adms_dynamic_report_shared_users WHERE report_id = :rid');
            $del->execute([':rid' => $reportId]);
            if ($clean !== []) {
                $ins = $conn->prepare(
                    'INSERT INTO adms_dynamic_report_shared_users (report_id, user_id) VALUES (:rid, :uid)'
                );
                foreach ($clean as $uid) {
                    $ins->execute([':rid' => $reportId, ':uid' => $uid]);
                }
            }
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }

        $newShareSnap = json_encode($this->getSharedUserIds($reportId));
        if ($oldShareSnap !== $newShareSnap) {
            $usuarioId = (int) $actingUserId;
            LogAlteracaoService::registrarAlteracao(
                'adms_dynamic_report_shared_users',
                $reportId,
                $usuarioId,
                'UPDATE',
                ['shared_user_ids' => $oldShareSnap],
                ['shared_user_ids' => $newShareSnap]
            );
        }
    }

    private function userHasExplicitShare(int $reportId, int $userId): bool
    {
        if ($reportId < 1 || $userId < 1) {
            return false;
        }
        try {
            $stmt = $this->getConnection()->prepare(
                'SELECT 1 FROM adms_dynamic_report_shared_users WHERE report_id = :rid AND user_id = :uid LIMIT 1'
            );
            $stmt->execute([':rid' => $reportId, ':uid' => $userId]);

            return (bool) $stmt->fetchColumn();
        } catch (\Throwable) {
            // Tabela ainda não migrada: comportamento legado (sem partilhas)
            return false;
        }
    }

    /**
     * Ver / executar / exportar / usar em dashboard.
     */
    public function userCanViewReport(array $report, int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        if ((int) ($report['is_public'] ?? 0) === 1) {
            return true;
        }
        if ((int) ($report['created_by'] ?? 0) === $userId) {
            return true;
        }
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        return $this->userHasExplicitShare((int) ($report['id'] ?? 0), $userId);
    }

    /**
     * Alterar definições ou apagar relatório.
     */
    public function userCanEditReport(array $report, int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }
        if ((int) ($report['created_by'] ?? 0) === $userId) {
            return true;
        }

        return UserAccessHelper::hasFullSystemAccess();
    }

    /**
     * @deprecated Use userCanViewReport() ou userCanEditReport()
     */
    public function userCanAccessReport(array $report, int $userId): bool
    {
        return $this->userCanViewReport($report, $userId);
    }

    public function getById(int $id): ?array
    {
        $sql = "SELECT r.*, u.name as creator_name FROM adms_dynamic_reports r
                INNER JOIN adms_users u ON u.id = r.created_by WHERE r.id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($report) {
            $report['fields'] = json_decode($report['fields'] ?? '[]', true);
            $report['filters'] = json_decode($report['filters'] ?? '[]', true);
            $report['groupby'] = json_decode($report['groupby'] ?? '[]', true);
            $report['orderby'] = json_decode($report['orderby'] ?? '[]', true);
            $report['chart_config'] = json_decode($report['chart_config'] ?? '{}', true);
            $examples = json_decode($report['chat_example_prompts'] ?? '[]', true);
            $report['chat_example_prompts'] = is_array($examples) ? $examples : [];
            $report['chat_enabled'] = (int) ($report['chat_enabled'] ?? 0);
        }
        return $report ?: null;
    }

    /**
     * Relatórios ativos para administração do catálogo do chat (inclui chat_enabled=0).
     *
     * @return list<array<string, mixed>>
     */
    public function getReportsForChatAdmin(): array
    {
        try {
            $sql = "SELECT r.id, r.name, r.description, r.category, r.data_source, r.query_mode,
                           r.visualization_type, r.is_public, r.is_active, r.chat_enabled,
                           r.chat_tool_name, r.chat_description, r.chat_example_prompts,
                           r.updated_at, u.name AS creator_name
                    FROM adms_dynamic_reports r
                    INNER JOIN adms_users u ON u.id = r.created_by
                    WHERE r.is_active = 1
                    ORDER BY r.chat_enabled DESC, r.name ASC";
            $stmt = $this->getConnection()->query($sql);
            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            error_log('getReportsForChatAdmin: ' . $e->getMessage());

            return [];
        }

        foreach ($rows as &$report) {
            $examples = json_decode($report['chat_example_prompts'] ?? '[]', true);
            $report['chat_example_prompts'] = is_array($examples) ? $examples : [];
            $report['chat_enabled'] = (int) ($report['chat_enabled'] ?? 0);
        }
        unset($report);

        return $rows;
    }

    /**
     * Atualiza apenas metadados do chat (tela Tools do assistente).
     *
     * @param array{chat_enabled?:int|bool, chat_tool_name?:?string, chat_description?:?string, chat_example_prompts?:list<string>} $data
     */
    public function updateChatMetadata(int $id, array $data): bool
    {
        if ($id < 1) {
            return false;
        }
        $oldRow = $this->getRawReportRowById($id);
        if ($oldRow === null) {
            return false;
        }

        $sql = 'UPDATE adms_dynamic_reports SET
                    chat_enabled = :chat_enabled,
                    chat_tool_name = :chat_tool_name,
                    chat_description = :chat_description,
                    chat_example_prompts = :chat_example_prompts,
                    updated_at = NOW()
                WHERE id = :id';
        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':id' => $id,
            ':chat_enabled' => !empty($data['chat_enabled']) ? 1 : 0,
            ':chat_tool_name' => $data['chat_tool_name'] ?? null,
            ':chat_description' => $data['chat_description'] ?? null,
            ':chat_example_prompts' => json_encode($data['chat_example_prompts'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        if ($ok) {
            $newRow = $this->getRawReportRowById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_dynamic_reports',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    /**
     * Relatórios ativos marcados para o Assistente MCP.
     *
     * @return list<array<string, mixed>>
     */
    public function getChatEnabledReports(): array
    {
        try {
            $sql = "SELECT r.*, u.name as creator_name
                    FROM adms_dynamic_reports r
                    INNER JOIN adms_users u ON u.id = r.created_by
                    WHERE r.is_active = 1 AND r.chat_enabled = 1
                    ORDER BY r.name ASC";
            $stmt = $this->getConnection()->query($sql);
            $rows = $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (\Throwable $e) {
            error_log('getChatEnabledReports: ' . $e->getMessage());

            return [];
        }

        foreach ($rows as &$report) {
            $report['fields'] = json_decode($report['fields'] ?? '[]', true);
            $report['filters'] = json_decode($report['filters'] ?? '[]', true);
            $report['groupby'] = json_decode($report['groupby'] ?? '[]', true);
            $report['orderby'] = json_decode($report['orderby'] ?? '[]', true);
            $report['chart_config'] = json_decode($report['chart_config'] ?? '{}', true);
            $examples = json_decode($report['chat_example_prompts'] ?? '[]', true);
            $report['chat_example_prompts'] = is_array($examples) ? $examples : [];
            $report['chat_enabled'] = (int) ($report['chat_enabled'] ?? 0);
        }
        unset($report);

        return $rows;
    }

    /**
     * Linha bruta de adms_dynamic_reports (sem decodificar JSON) para auditoria.
     *
     * @return array<string, mixed>|null
     */
    private function getRawReportRowById(int $id): ?array
    {
        $stmt = $this->getConnection()->prepare('SELECT * FROM adms_dynamic_reports WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_dynamic_reports (name, description, created_by, is_public, data_source, custom_sql, query_mode, fields, filters, groupby, orderby, visualization_type, chart_config, refresh_interval, category, is_active, chat_enabled, chat_tool_name, chat_description, chat_example_prompts)
                VALUES (:name, :description, :created_by, :is_public, :data_source, :custom_sql, :query_mode, :fields, :filters, :groupby, :orderby, :visualization_type, :chart_config, :refresh_interval, :category, :is_active, :chat_enabled, :chat_tool_name, :chat_description, :chat_example_prompts)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':created_by' => $data['created_by'],
            ':is_public' => $data['is_public'] ?? 0,
            ':data_source' => $data['data_source'] ?? null,
            ':custom_sql' => $data['custom_sql'] ?? null,
            ':query_mode' => $data['query_mode'] ?? 'builder',
            ':fields' => json_encode($data['fields'] ?? []),
            ':filters' => json_encode($data['filters'] ?? []),
            ':groupby' => json_encode($data['groupby'] ?? []),
            ':orderby' => json_encode($data['orderby'] ?? []),
            ':visualization_type' => $data['visualization_type'] ?? 'table',
            ':chart_config' => json_encode($data['chart_config'] ?? []),
            ':refresh_interval' => $data['refresh_interval'] ?? null,
            ':category' => $data['category'] ?? null,
            ':is_active' => $data['is_active'] ?? 1,
            ':chat_enabled' => !empty($data['chat_enabled']) ? 1 : 0,
            ':chat_tool_name' => $data['chat_tool_name'] ?? null,
            ':chat_description' => $data['chat_description'] ?? null,
            ':chat_example_prompts' => json_encode($data['chat_example_prompts'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawReportRowById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_dynamic_reports',
                    $newId,
                    $usuarioId,
                    'INSERT',
                    [],
                    $row
                );
            }
        }

        return $newId;
    }

    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getRawReportRowById($id);
        $sql = "UPDATE adms_dynamic_reports SET name = :name, description = :description, is_public = :is_public, 
                data_source = :data_source, custom_sql = :custom_sql, query_mode = :query_mode, fields = :fields, filters = :filters, groupby = :groupby, orderby = :orderby,
                visualization_type = :visualization_type, chart_config = :chart_config, refresh_interval = :refresh_interval,
                category = :category, chat_enabled = :chat_enabled, chat_tool_name = :chat_tool_name,
                chat_description = :chat_description, chat_example_prompts = :chat_example_prompts,
                updated_at = NOW() WHERE id = :id";

        $stmt = $this->getConnection()->prepare($sql);
        $ok = $stmt->execute([
            ':id' => $id, ':name' => $data['name'], ':description' => $data['description'] ?? null,
            ':is_public' => $data['is_public'] ?? 0, ':data_source' => $data['data_source'] ?? null,
            ':custom_sql' => $data['custom_sql'] ?? null, ':query_mode' => $data['query_mode'] ?? 'builder',
            ':fields' => json_encode($data['fields'] ?? []), ':filters' => json_encode($data['filters'] ?? []),
            ':groupby' => json_encode($data['groupby'] ?? []), ':orderby' => json_encode($data['orderby'] ?? []),
            ':visualization_type' => $data['visualization_type'] ?? 'table',
            ':chart_config' => json_encode($data['chart_config'] ?? []),
            ':refresh_interval' => $data['refresh_interval'] ?? null, ':category' => $data['category'] ?? null,
            ':chat_enabled' => !empty($data['chat_enabled']) ? 1 : 0,
            ':chat_tool_name' => $data['chat_tool_name'] ?? null,
            ':chat_description' => $data['chat_description'] ?? null,
            ':chat_example_prompts' => json_encode($data['chat_example_prompts'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawReportRowById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_dynamic_reports',
                    $id,
                    $usuarioId,
                    'UPDATE',
                    $oldRow,
                    $newRow
                );
            }
        }

        return $ok;
    }

    public function delete(int $id): bool
    {
        $oldRow = $this->getRawReportRowById($id);
        $sql = "DELETE FROM adms_dynamic_reports WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_dynamic_reports',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    public function logExecution(int $reportId, int $userId, float $executionTime, int $rowsReturned): void
    {
        $sql = "INSERT INTO adms_report_executions (report_id, user_id, execution_time, rows_returned)
                VALUES (:report_id, :user_id, :execution_time, :rows_returned)";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute([':report_id' => $reportId, ':user_id' => $userId, 
                       ':execution_time' => $executionTime, ':rows_returned' => $rowsReturned]);
    }

    public function getAvailableTables(): array
    {
        return array_merge($this->getAllLocalTables(), $this->getSapB1Tables());
    }

    /**
     * Buscar TODAS as tabelas do banco de dados automaticamente
     */
    private function getAllLocalTables(): array
    {
        try {
            $sql = "SELECT TABLE_NAME, TABLE_COMMENT 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_SCHEMA = :database 
                    AND TABLE_TYPE = 'BASE TABLE'
                    ORDER BY TABLE_NAME";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':database', $_ENV['DB_NAME'], \PDO::PARAM_STR);
            $stmt->execute();
            
            $tables = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $result = [];
            
            foreach ($tables as $table) {
                $tableName = $table['TABLE_NAME'];
                $tableComment = $table['TABLE_COMMENT'] ?: $tableName;
                
                // Buscar campos da tabela
                $fields = $this->getTableColumns($tableName);
                
                $result[$tableName] = [
                    'label' => $tableComment ?: $tableName,
                    'connection' => 'local',
                    'fields' => $fields
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("Erro ao buscar tabelas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Buscar colunas de uma tabela
     */
    private function getTableColumns(string $tableName): array
    {
        try {
            $sql = "SELECT COLUMN_NAME, COLUMN_COMMENT, DATA_TYPE 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = :database 
                    AND TABLE_NAME = :table
                    ORDER BY ORDINAL_POSITION";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':database', $_ENV['DB_NAME'], \PDO::PARAM_STR);
            $stmt->bindValue(':table', $tableName, \PDO::PARAM_STR);
            $stmt->execute();
            
            $columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $result = [];
            
            foreach ($columns as $column) {
                $colName = $column['COLUMN_NAME'];
                $colComment = $column['COLUMN_COMMENT'] ?: $colName;
                $result[$colName] = $colComment;
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function getSapB1Tables(): array
    {
        // SAP B1: Não listar tabelas (usuário usa SQL personalizado)
        // Retornar array vazio - tabelas SAP B1 serão acessadas via SQL livre
        return [];
    }
}

