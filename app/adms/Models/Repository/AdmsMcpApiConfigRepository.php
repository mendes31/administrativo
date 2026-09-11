<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use App\adms\Models\Services\LogAlteracaoService;
use PDO;

class AdmsMcpApiConfigRepository extends DbConnection
{
    private const SECRET_FIELDS = [
        'llm_groq_api_key',
        'llm_gemini_api_key',
        'llm_openai_api_key',
        'llm_anthropic_api_key',
    ];

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
        $hasOllamaCols = $this->hasColumn('ollama_model');
        $hasLlmCols = $this->hasColumn('llm_provider');

        $data = $this->mergeSecrets($data, $oldData);

        $fields = ['base_url', 'is_active'];
        if ($hasOllamaCols) {
            $fields[] = 'ollama_model';
            $fields[] = 'ollama_models_fallback';
        }
        if ($hasLlmCols) {
            $fields = array_merge($fields, [
                'llm_provider',
                'llm_groq_api_key',
                'llm_groq_model',
                'llm_gemini_api_key',
                'llm_gemini_model',
                'llm_openai_api_key',
                'llm_openai_base_url',
                'llm_openai_model',
                'llm_anthropic_api_key',
                'llm_anthropic_model',
                'llm_ollama_url',
            ]);
        }

        if ($isUpdate) {
            $sets = [];
            foreach ($fields as $field) {
                $sets[] = "{$field} = :{$field}";
            }
            $sql = 'UPDATE adms_mcp_api_config SET ' . implode(', ', $sets) . ', updated_at = NOW() WHERE id = :id';
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->bindValue(':id', $oldData['id'], PDO::PARAM_INT);
        } else {
            $cols = implode(', ', $fields);
            $placeholders = implode(', ', array_map(static fn (string $f): string => ':' . $f, $fields));
            $sql = "INSERT INTO adms_mcp_api_config ({$cols}, created_at, updated_at) VALUES ({$placeholders}, NOW(), NOW())";
            $stmt = $this->getConnection()->prepare($sql);
        }

        $this->bindConfigFields($stmt, $fields, $data);

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
                    $this->maskSecrets($oldData),
                    $this->maskSecrets($newData)
                );
            } elseif (!$isUpdate && $newData) {
                LogAlteracaoService::registrarAlteracao(
                    'adms_mcp_api_config',
                    (int) ($newData['id'] ?? 0),
                    $usuarioId,
                    'INSERT',
                    [],
                    $this->maskSecrets($newData)
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

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $oldData
     * @return array<string, mixed>
     */
    private function mergeSecrets(array $data, array $oldData): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (!empty($data['clear_' . $field])) {
                $data[$field] = null;
                continue;
            }
            $incoming = trim((string) ($data[$field] ?? ''));
            if ($incoming === '') {
                $data[$field] = $oldData[$field] ?? null;
            } else {
                $data[$field] = $incoming;
            }
        }

        return $data;
    }

    /**
     * @param list<string> $fields
     * @param array<string, mixed> $data
     */
    private function bindConfigFields(\PDOStatement $stmt, array $fields, array $data): void
    {
        foreach ($fields as $field) {
            if ($field === 'is_active') {
                $stmt->bindValue(':is_active', (int) ($data['is_active'] ?? 0), PDO::PARAM_INT);
                continue;
            }
            if ($field === 'llm_provider') {
                $provider = strtolower(trim((string) ($data['llm_provider'] ?? 'auto')));
                if ($provider === '') {
                    $provider = 'auto';
                }
                $stmt->bindValue(':llm_provider', $provider, PDO::PARAM_STR);
                continue;
            }

            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') {
                $stmt->bindValue(':' . $field, null, PDO::PARAM_NULL);
            } else {
                $stmt->bindValue(':' . $field, $value, PDO::PARAM_STR);
            }
        }
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function maskSecrets(array $row): array
    {
        foreach (self::SECRET_FIELDS as $field) {
            if (!empty($row[$field])) {
                $row[$field] = '********';
            }
        }

        return $row;
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $safe = preg_replace('/[^a-z0-9_]/i', '', $column) ?? '';
            if ($safe === '') {
                $cache[$column] = false;
                return false;
            }
            $stmt = $this->getConnection()->query("SHOW COLUMNS FROM adms_mcp_api_config LIKE '{$safe}'");
            $cache[$column] = (bool) ($stmt && $stmt->fetch(PDO::FETCH_ASSOC));
        } catch (\Throwable) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }
}
