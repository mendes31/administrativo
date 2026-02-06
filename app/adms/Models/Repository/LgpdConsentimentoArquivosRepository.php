<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;
use Exception;

class LgpdConsentimentoArquivosRepository extends DbConnection
{
    /**
    * Salva um arquivo vinculado a um consentimento
    */
    public function create(array $data): bool
    {
        try {
            $sql = "INSERT INTO lgpd_consentimento_arquivos
                        (consentimento_id, nome_original, arquivo_path, mime_type, tamanho_bytes, created_at)
                    VALUES
                        (:consentimento_id, :nome_original, :arquivo_path, :mime_type, :tamanho_bytes, NOW())";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':consentimento_id', $data['consentimento_id'], PDO::PARAM_INT);
            $stmt->bindValue(':nome_original', $data['nome_original'], PDO::PARAM_STR);
            $stmt->bindValue(':arquivo_path', $data['arquivo_path'], PDO::PARAM_STR);
            $stmt->bindValue(':mime_type', $data['mime_type'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':tamanho_bytes', $data['tamanho_bytes'] ?? null, PDO::PARAM_INT);

            return $stmt->execute();
        } catch (\Exception $e) {
            error_log('Erro ao salvar arquivo de consentimento: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
    * Lista arquivos de um consentimento
    */
    public function getByConsentimentoId(int $consentimentoId): array
    {
        try {
            $sql = "SELECT * FROM lgpd_consentimento_arquivos
                    WHERE consentimento_id = :id
                    ORDER BY created_at ASC, id ASC";

            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $consentimentoId, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            error_log('Erro ao buscar arquivos de consentimento: ' . $e->getMessage());
            return [];
        }
    }

    /**
    * Busca um arquivo por ID
    */
    public function getById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM lgpd_consentimento_arquivos WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (\Exception $e) {
            error_log('Erro ao buscar arquivo por ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
    * Remove um arquivo (do banco e do sistema de arquivos)
    */
    public function delete(int $id): bool
    {
        try {
            // Buscar informações do arquivo antes de deletar
            $arquivo = $this->getById($id);
            if (!$arquivo) {
                return false;
            }

            // Deletar do banco
            $sql = "DELETE FROM lgpd_consentimento_arquivos WHERE id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();

            // Se deletou do banco, tentar remover o arquivo físico
            if ($result && !empty($arquivo['arquivo_path'])) {
                // dirname(__DIR__, 4) = raiz do projeto (de app/adms/Models/Repository para raiz)
                $filePath = dirname(__DIR__, 4) . '/' . $arquivo['arquivo_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            return $result;
        } catch (\Exception $e) {
            error_log('Erro ao deletar arquivo de consentimento: ' . $e->getMessage());
            return false;
        }
    }
}


