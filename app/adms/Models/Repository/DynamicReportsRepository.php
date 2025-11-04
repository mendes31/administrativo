<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class DynamicReportsRepository extends DbConnection
{
    public function getUserReports(int $userId): array
    {
        $sql = "SELECT r.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM adms_report_favorites WHERE report_id = r.id) as favorite_count,
                       EXISTS(SELECT 1 FROM adms_report_favorites WHERE report_id = r.id AND user_id = :user_id) as is_favorite
                FROM adms_dynamic_reports r
                INNER JOIN adms_users u ON u.id = r.created_by
                WHERE (r.created_by = :user_id OR r.is_public = 1) AND r.is_active = 1
                ORDER BY r.updated_at DESC";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        }
        return $report ?: null;
    }

    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_dynamic_reports (name, description, created_by, is_public, data_source, custom_sql, query_mode, fields, filters, groupby, orderby, visualization_type, chart_config, refresh_interval, category, is_active)
                VALUES (:name, :description, :created_by, :is_public, :data_source, :custom_sql, :query_mode, :fields, :filters, :groupby, :orderby, :visualization_type, :chart_config, :refresh_interval, :category, :is_active)";
        
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
            ':is_active' => $data['is_active'] ?? 1
        ]);
        return (int) $this->getConnection()->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE adms_dynamic_reports SET name = :name, description = :description, is_public = :is_public, 
                data_source = :data_source, custom_sql = :custom_sql, query_mode = :query_mode, fields = :fields, filters = :filters, groupby = :groupby, orderby = :orderby,
                visualization_type = :visualization_type, chart_config = :chart_config, refresh_interval = :refresh_interval,
                category = :category, updated_at = NOW() WHERE id = :id";
        
        $stmt = $this->getConnection()->prepare($sql);
        return $stmt->execute([
            ':id' => $id, ':name' => $data['name'], ':description' => $data['description'] ?? null,
            ':is_public' => $data['is_public'] ?? 0, ':data_source' => $data['data_source'] ?? null,
            ':custom_sql' => $data['custom_sql'] ?? null, ':query_mode' => $data['query_mode'] ?? 'builder',
            ':fields' => json_encode($data['fields'] ?? []), ':filters' => json_encode($data['filters'] ?? []),
            ':groupby' => json_encode($data['groupby'] ?? []), ':orderby' => json_encode($data['orderby'] ?? []),
            ':visualization_type' => $data['visualization_type'] ?? 'table',
            ':chart_config' => json_encode($data['chart_config'] ?? []),
            ':refresh_interval' => $data['refresh_interval'] ?? null, ':category' => $data['category'] ?? null
        ]);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM adms_dynamic_reports WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        return $stmt->execute();
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

