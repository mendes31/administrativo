<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LgpdConsentimentosRepository extends DbConnection
{
    /**
     * Obtém todos os consentimentos
     *
     * @return array
     */
    public function getAllConsentimentos(): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at,
                        updated_at
                      FROM lgpd_consentimentos 
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar consentimentos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém consentimento por ID
     *
     * @param int $id
     * @return array|null
     */
    public function getConsentimentoById(int $id): ?array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at,
                        updated_at
                      FROM lgpd_consentimentos 
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            error_log("Erro ao buscar consentimento por ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Cria um novo consentimento
     *
     * @param array $data
     * @return bool
     */
    public function create(array $data): bool
    {
        try {
            $query = "INSERT INTO lgpd_consentimentos 
                      (titular_nome, titular_email, finalidade, canal, data_consentimento, status) 
                      VALUES (:titular_nome, :titular_email, :finalidade, :canal, :data_consentimento, :status)";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':titular_nome', $data['titular_nome'], PDO::PARAM_STR);
            $stmt->bindParam(':titular_email', $data['titular_email'], PDO::PARAM_STR);
            $stmt->bindParam(':finalidade', $data['finalidade'], PDO::PARAM_STR);
            $stmt->bindParam(':canal', $data['canal'], PDO::PARAM_STR);
            $stmt->bindParam(':data_consentimento', $data['data_consentimento'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao criar consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um consentimento
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        try {
            $query = "UPDATE lgpd_consentimentos 
                      SET titular_nome = :titular_nome,
                          titular_email = :titular_email,
                          finalidade = :finalidade,
                          canal = :canal,
                          data_consentimento = :data_consentimento,
                          status = :status,
                          updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':titular_nome', $data['titular_nome'], PDO::PARAM_STR);
            $stmt->bindParam(':titular_email', $data['titular_email'], PDO::PARAM_STR);
            $stmt->bindParam(':finalidade', $data['finalidade'], PDO::PARAM_STR);
            $stmt->bindParam(':canal', $data['canal'], PDO::PARAM_STR);
            $stmt->bindParam(':data_consentimento', $data['data_consentimento'], PDO::PARAM_STR);
            $stmt->bindParam(':status', $data['status'], PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao atualizar consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Exclui um consentimento
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        try {
            $query = "DELETE FROM lgpd_consentimentos WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao excluir consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Revoga um consentimento
     *
     * @param int $id
     * @return bool
     */
    public function revogarConsentimento(int $id): bool
    {
        try {
            $query = "UPDATE lgpd_consentimentos 
                      SET status = 'Revogado', updated_at = NOW()
                      WHERE id = :id";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Erro ao revogar consentimento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém estatísticas dos consentimentos
     *
     * @return array
     */
    public function getEstatisticas(): array
    {
        try {
            $query = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'Ativo' THEN 1 ELSE 0 END) as ativos,
                        SUM(CASE WHEN status = 'Revogado' THEN 1 ELSE 0 END) as revogados,
                        SUM(CASE WHEN status = 'Expirado' THEN 1 ELSE 0 END) as expirados,
                        SUM(CASE WHEN data_consentimento >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as ultimos_30_dias
                      FROM lgpd_consentimentos";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [
                'total' => 0,
                'ativos' => 0,
                'revogados' => 0,
                'expirados' => 0,
                'ultimos_30_dias' => 0
            ];
        }
    }

    /**
     * Obtém consentimentos por status
     *
     * @param string $status
     * @return array
     */
    public function getConsentimentosPorStatus(string $status): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE status = :status
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar consentimentos por status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtém consentimentos expirados
     *
     * @return array
     */
    public function getConsentimentosExpirados(): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE data_consentimento < DATE_SUB(NOW(), INTERVAL 1 YEAR)
                      AND status = 'Ativo'
                      ORDER BY data_consentimento ASC";
            
            $stmt = $this->getConnection()->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar consentimentos expirados: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca consentimentos por termo
     *
     * @param string $termo
     * @return array
     */
    public function buscarPorTermo(string $termo): array
    {
        try {
            $query = "SELECT 
                        id,
                        titular_nome,
                        titular_email,
                        finalidade,
                        canal,
                        data_consentimento,
                        status,
                        created_at
                      FROM lgpd_consentimentos 
                      WHERE titular_nome LIKE :termo 
                         OR titular_email LIKE :termo 
                         OR finalidade LIKE :termo
                      ORDER BY created_at DESC";
            
            $stmt = $this->getConnection()->prepare($query);
            $termo = "%{$termo}%";
            $stmt->bindParam(':termo', $termo, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erro ao buscar consentimentos por termo: " . $e->getMessage());
            return [];
        }
    }
}
