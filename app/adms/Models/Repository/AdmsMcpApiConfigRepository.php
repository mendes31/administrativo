<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class AdmsMcpApiConfigRepository extends DbConnection
{
    public function getConfig(): array
    {
        $sql = 'SELECT * FROM adms_mcp_api_config ORDER BY id DESC LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute();

        $config = $stmt->fetch(PDO::FETCH_ASSOC);
        return $config ?: [];
    }

    public function saveConfig(array $data): bool
    {
        $oldData = $this->getConfig();
        $isUpdate = $oldData && !empty($oldData['id']);
        $hasOllamaCols = $this->hasOllamaColumns();

        if ($isUpdate) {
            $sql = 'UPDATE adms_mcp_api_config SET
                        base_url = :base_url,
                        is_active = :is_active';
            if ($hasOllamaCols) {
                $sql .= ',
                        ollama_model = :ollama_model,
                        ollama_models_fallback = :ollama_models_fallback';
            }
            $sql .= ',
                        updated_at = NOW()
                    WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $oldData['id'], PDO::PARAM_INT);
        } else {
            if ($hasOllamaCols) {
                $sql = 'INSERT INTO adms_mcp_api_config (
                            base_url, is_active, ollama_model, ollama_models_fallback, created_at, updated_at
                        ) VALUES (
                            :base_url, :is_active, :ollama_model, :ollama_models_fallback, NOW(), NOW()
                        )';
            } else {
                $sql = 'INSERT INTO adms_mcp_api_config (
                            base_url, is_active, created_at, updated_at
                        ) VALUES (
                            :base_url, :is_active, NOW(), NOW()
                        )';
            }
            $stmt = $this->getConnection()->prepare($sql);
        }

        $stmt->bindValue(':base_url', $data['base_url']);
        $stmt->bindValue(':is_active', $data['is_active'], PDO::PARAM_INT);
        if ($hasOllamaCols) {
            $model = trim((string) ($data['ollama_model'] ?? ''));
            $fallback = trim((string) ($data['ollama_models_fallback'] ?? ''));
            $stmt->bindValue(':ollama_model', $model !== '' ? $model : null, $model !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ollama_models_fallback', $fallback !== '' ? $fallback : null, $fallback !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
        }

        $result = $stmt->execute();

        if ($result) {
            $usuarioId = $_SESSION['user_id'] ?? 1;
            $newData = $this->getConfig();
            if ($isUpdate && $oldData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_mcp_api_config',
                    (int) $oldData['id'],
                    $usuarioId,
                    'UPDATE',
                    $oldData,
                    $newData
                );
            } elseif (!$isUpdate && $newData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_mcp_api_config',
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

    /**
     * Modelo Ollama preferencial: banco → .env → llama3.2
     *
     * @return list<string>
     */
    public function resolveOllamaModelChain(): array
    {
        $config = $this->getConfig();
        $primary = trim((string) ($config['ollama_model'] ?? ''));
        if ($primary === '') {
            $primary = trim((string) ($_ENV['OLLAMA_MODEL'] ?? 'llama3.2'));
        }
        if ($primary === '') {
            $primary = 'llama3.2';
        }

        $fallbacksRaw = trim((string) ($config['ollama_models_fallback'] ?? ''));
        if ($fallbacksRaw === '') {
            $fallbacksRaw = trim((string) ($_ENV['OLLAMA_MODELS_FALLBACK'] ?? ''));
        }

        $chain = [$primary];
        if ($fallbacksRaw !== '') {
            foreach (preg_split('/\s*,\s*/', $fallbacksRaw) ?: [] as $model) {
                $model = trim((string) $model);
                if ($model !== '' && !in_array($model, $chain, true)) {
                    $chain[] = $model;
                }
            }
        }

        return $chain;
    }

    private function hasOllamaColumns(): bool
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        try {
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_mcp_api_config LIKE 'ollama_model'");
            $cached = (bool) ($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (\Throwable) {
            $cached = false;
        }

        return $cached;
    }
}
