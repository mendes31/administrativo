<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;

/**
 * Repository para gerenciar planilhas
 */
class SpreadsheetsRepository extends DbConnection
{
    /**
     * Criar nova planilha
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO adms_spreadsheets 
                (name, description, file_name, file_path, file_type, file_size, 
                 sheet_name, header_row, data_start_row, columns_config, 
                 total_rows, total_columns, created_by, is_public, category, status)
                VALUES 
                (:name, :description, :file_name, :file_path, :file_type, :file_size,
                 :sheet_name, :header_row, :data_start_row, :columns_config,
                 :total_rows, :total_columns, :created_by, :is_public, :category, :status)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':name', $data['name']);
        $stmt->bindValue(':description', $data['description'] ?? null);
        $stmt->bindValue(':file_name', $data['file_name']);
        $stmt->bindValue(':file_path', $data['file_path']);
        $stmt->bindValue(':file_type', $data['file_type']);
        $stmt->bindValue(':file_size', $data['file_size'], \PDO::PARAM_INT);
        $stmt->bindValue(':sheet_name', $data['sheet_name'] ?? null);
        $stmt->bindValue(':header_row', $data['header_row'] ?? 1, \PDO::PARAM_INT);
        $stmt->bindValue(':data_start_row', $data['data_start_row'] ?? 2, \PDO::PARAM_INT);
        $stmt->bindValue(':columns_config', json_encode($data['columns_config'] ?? []));
        $stmt->bindValue(':total_rows', $data['total_rows'] ?? 0, \PDO::PARAM_INT);
        $stmt->bindValue(':total_columns', $data['total_columns'] ?? 0, \PDO::PARAM_INT);
        $stmt->bindValue(':created_by', $data['created_by'], \PDO::PARAM_INT);
        $stmt->bindValue(':is_public', $data['is_public'] ?? false, \PDO::PARAM_BOOL);
        $stmt->bindValue(':category', $data['category'] ?? null);
        $stmt->bindValue(':status', $data['status'] ?? true, \PDO::PARAM_BOOL);
        
        $stmt->execute();

        $newId = (int) $this->getConnection()->lastInsertId();
        if ($newId > 0) {
            $row = $this->getRawRowById($newId);
            if (is_array($row)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_spreadsheets',
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

    /**
     * Buscar por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT * FROM adms_spreadsheets WHERE id = :id AND status = 1";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result && isset($result['columns_config'])) {
            $result['columns_config'] = json_decode($result['columns_config'], true) ?? [];
        }
        
        return $result ?: null;
    }

    /**
     * Listar planilhas do usuário
     */
    public function getUserSpreadsheets(int $userId, bool $includePublic = true): array
    {
        // Super administrador (nível 1) tem acesso a todas as planilhas
        $isSuperAdmin = \App\adms\Helpers\UserAccessHelper::hasFullSystemAccess();
        
        if ($isSuperAdmin) {
            // Super admin vê todas as planilhas ativas
            $sql = "SELECT * FROM adms_spreadsheets 
                    WHERE COALESCE(status, 1) = 1 
                    ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
        } else {
            // Usuários normais: apenas planilhas próprias ou públicas
            $publicCondition = $includePublic ? "OR COALESCE(is_public, 0) = 1" : "";
            
            $sql = "SELECT * FROM adms_spreadsheets 
                    WHERE COALESCE(status, 1) = 1 
                    AND (created_by = :user_id {$publicCondition})
                    ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
        }
        
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        foreach ($results as &$result) {
            if (isset($result['columns_config'])) {
                $result['columns_config'] = json_decode($result['columns_config'], true) ?? [];
            }
        }
        
        return $results;
    }

    /**
     * Atualizar planilha
     */
    public function update(int $id, array $data): bool
    {
        $oldRow = $this->getRawRowById($id);
        $fields = [];
        $values = [];
        
        $allowedFields = ['name', 'description', 'sheet_name', 'header_row', 'data_start_row', 
                         'columns_config', 'category', 'is_public', 'status'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = :{$field}";
                $values[":{$field}"] = $field === 'columns_config' 
                    ? json_encode($data[$field]) 
                    : $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $values[':id'] = $id;
        $fields[] = "updated_at = NOW()";
        
        $sql = "UPDATE adms_spreadsheets SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        
        foreach ($values as $key => $value) {
            $type = \PDO::PARAM_STR;
            if (in_array($key, [':header_row', ':data_start_row', ':id'])) {
                $type = \PDO::PARAM_INT;
            } elseif (in_array($key, [':is_public', ':status'])) {
                $type = \PDO::PARAM_BOOL;
            }
            $stmt->bindValue($key, $value, $type);
        }

        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $newRow = $this->getRawRowById($id);
            if (is_array($newRow)) {
                $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
                LogAlteracaoService::registrarAlteracao(
                    'adms_spreadsheets',
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
     * Deletar planilha (soft delete)
     */
    public function delete(int $id): bool
    {
        $oldRow = $this->getRawRowById($id);
        $sql = "UPDATE adms_spreadsheets SET status = 0, updated_at = NOW() WHERE id = :id";
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $ok = $stmt->execute();
        if ($ok && is_array($oldRow)) {
            $usuarioId = (int) ($_SESSION['user_id'] ?? 1);
            LogAlteracaoService::registrarAlteracao(
                'adms_spreadsheets',
                $id,
                $usuarioId,
                'DELETE',
                $oldRow,
                []
            );
        }

        return $ok;
    }

    /**
     * Verificar se usuário pode acessar planilha
     */
    public function canAccess(int $id, int $userId): bool
    {
        $sql = "SELECT COUNT(*) FROM adms_spreadsheets 
                WHERE id = :id AND status = 1 
                AND (created_by = :user_id OR is_public = 1)";
        
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getRawRowById(int $id): ?array
    {
        $sql = 'SELECT * FROM adms_spreadsheets WHERE id = :id LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}


