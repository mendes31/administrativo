<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repository para Configuração de WhatsApp
 *
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class AdmsWhatsAppConfigRepository extends DbConnection
{
    /**
     * Buscar configuração ativa do WhatsApp
     */
    public function getConfig(): array
    {
        try {
            $sql = 'SELECT * FROM adms_whatsapp_config WHERE is_active = 1 ORDER BY id DESC LIMIT 1';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute();
            $config = $stmt->fetch(PDO::FETCH_ASSOC);
            return $config ?: [];
        } catch (\Exception $e) {
            error_log("Erro ao buscar config WhatsApp: " . $e->getMessage());
            // Se a tabela não existir, retornar array vazio
            return [];
        }
    }

    /**
     * Salvar configuração do WhatsApp
     */
    public function saveConfig(array $data): bool
    {
        try {
            error_log("=== REPOSITORY saveConfig ===");
            
            // Verificar se já existe configuração
            $config = $this->getConfig();
            
            error_log("Config existente: " . print_r($config, true));
            
            if ($config && !empty($config['id'])) {
                // UPDATE
                error_log("Modo: UPDATE (ID: " . $config['id'] . ")");
                
                $sql = 'UPDATE adms_whatsapp_config SET
                            api_provider = :api_provider,
                            api_url = :api_url,
                            api_key = :api_key,
                            api_token = :api_token,
                            instance_name = :instance_name,
                            phone_number = :phone_number,
                            webhook_url = :webhook_url,
                            is_active = :is_active,
                            updated_at = NOW()
                        WHERE id = :id';
                
                $stmt = $this->getConnection()->prepare($sql);
                $stmt->bindValue(':id', $config['id'], PDO::PARAM_INT);
            } else {
                // INSERT
                error_log("Modo: INSERT (primeira configuração)");
                
                $sql = 'INSERT INTO adms_whatsapp_config (
                            api_provider, api_url, api_key, api_token, instance_name, 
                            phone_number, webhook_url, is_active, created_at, updated_at
                        ) VALUES (
                            :api_provider, :api_url, :api_key, :api_token, :instance_name,
                            :phone_number, :webhook_url, :is_active, NOW(), NOW()
                        )';
                
                $stmt = $this->getConnection()->prepare($sql);
            }

            $stmt->bindValue(':api_provider', $data['api_provider']);
            $stmt->bindValue(':api_url', $data['api_url']);
            $stmt->bindValue(':api_key', $data['api_key']);
            $stmt->bindValue(':api_token', $data['api_token'] ?? null);
            $stmt->bindValue(':instance_name', $data['instance_name'] ?? null);
            $stmt->bindValue(':phone_number', $data['phone_number'] ?? null);
            $stmt->bindValue(':webhook_url', $data['webhook_url'] ?? null);
            $stmt->bindValue(':is_active', $data['is_active'] ?? 1, PDO::PARAM_INT);

            $result = $stmt->execute();
            
            error_log("Execute Result: " . ($result ? 'TRUE' : 'FALSE'));
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                error_log("PDO Error Info: " . print_r($errorInfo, true));
            }
            
            return $result;
        } catch (\Exception $e) {
            error_log("EXCEPTION ao salvar config WhatsApp: " . $e->getMessage());
            error_log("Stack Trace: " . $e->getTraceAsString());
            return false;
        }
    }
}

