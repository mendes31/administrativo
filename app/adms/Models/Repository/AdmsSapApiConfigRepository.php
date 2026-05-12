<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class AdmsSapApiConfigRepository extends DbConnection
{
    public function getConfig(): array
    {
        $sql = 'SELECT * FROM adms_sap_api_config ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: [];
    }

    public function saveConfig(array $data): bool
    {
        $oldData = $this->getConfig();
        $isUpdate = $oldData && !empty($oldData['id']);

        if ($isUpdate) {
            $sql = 'UPDATE adms_sap_api_config SET 
                        base_url = :base_url,
                        api_token = :api_token,
                        timeout_ms = :timeout_ms,
                        page_size = :page_size,
                        health_endpoint = :health_endpoint,
                        is_active = :is_active,
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $oldData['id'], PDO::PARAM_INT);
        } else {
            $sql = 'INSERT INTO adms_sap_api_config (
                        base_url, api_token, timeout_ms, page_size, health_endpoint, is_active, created_at, updated_at
                    ) VALUES (
                        :base_url, :api_token, :timeout_ms, :page_size, :health_endpoint, :is_active, NOW(), NOW()
                    )';
            $stmt = $this->getConnection()->prepare($sql);
        }

        $stmt->bindValue(':base_url', $data['base_url']);
        $stmt->bindValue(':api_token', $data['api_token']);
        $stmt->bindValue(':timeout_ms', $data['timeout_ms'], PDO::PARAM_INT);
        $stmt->bindValue(':page_size', $data['page_size'], PDO::PARAM_INT);
        $stmt->bindValue(':health_endpoint', $data['health_endpoint']);
        $stmt->bindValue(':is_active', $data['is_active'], PDO::PARAM_INT);

        $result = $stmt->execute();

        if ($result) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            $newData = $this->getConfig();
            if ($isUpdate && $oldData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_sap_api_config',
                    (int) $oldData['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            } elseif (!$isUpdate && $newData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_sap_api_config',
                    (int) ($newData['id'] ?? 0),
                    $usuarioId,
                    'INSERT',
                    [],
                    $newData
                );
            }
        }

        return $result;
    }
}







