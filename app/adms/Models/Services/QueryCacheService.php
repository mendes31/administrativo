<?php

namespace App\adms\Models\Services;

/**
 * Serviço de cache para queries frequentes (getAllTrainingsSelect, getAllUsersSelect, etc.)
 * 
 * Armazena resultados de queries em arquivos JSON com TTL (Time To Live)
 * para evitar queries repetitivas ao banco de dados.
 */
class QueryCacheService
{
    private string $cacheDir;
    private int $defaultTtl; // TTL padrão em segundos (5 minutos)

    public function __construct(?string $cacheDir = null, int $defaultTtl = 300)
    {
        $baseDir = $cacheDir ?? __DIR__ . '/../../../storage/cache/queries';
        $this->cacheDir = rtrim($baseDir, '/\\');
        $this->defaultTtl = $defaultTtl;

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    /**
     * Obtém dados do cache se ainda válidos
     * 
     * @param string $key Chave do cache
     * @param int|null $ttl TTL personalizado (opcional, usa default se null)
     * @return array|null Dados em cache ou null se expirado/inexistente
     */
    public function get(string $key, ?int $ttl = null): ?array
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $payload = json_decode($content, true);
        if (!is_array($payload) || !isset($payload['stored_at'], $payload['data'])) {
            return null;
        }

        // Verificar se o cache expirou
        $ttlToUse = $ttl ?? $this->defaultTtl;
        $age = time() - $payload['stored_at'];
        if ($age > $ttlToUse) {
            // Cache expirado, remover arquivo
            $this->forget($key);
            return null;
        }

        return $payload['data'];
    }

    /**
     * Armazena dados no cache
     * 
     * @param string $key Chave do cache
     * @param array $data Dados para cachear
     * @return bool Sucesso da operação
     */
    public function put(string $key, array $data): bool
    {
        $payload = [
            'stored_at' => time(),
            'data' => $data
        ];

        $file = $this->filePath($key);
        
        // Verificar se a pasta existe e é gravável
        $dir = dirname($file);
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0775, true)) {
                error_log("❌ ERRO: Não foi possível criar a pasta de cache: {$dir}");
                return false;
            }
        }
        
        if (!is_writable($dir)) {
            error_log("❌ ERRO: Pasta de cache não é gravável: {$dir}");
            return false;
        }
        
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            error_log("❌ ERRO: Falha ao codificar JSON para cache: " . json_last_error_msg());
            return false;
        }
        
        $result = @file_put_contents($file, $json);
        
        if ($result === false) {
            $error = error_get_last();
            error_log("❌ ERRO ao salvar cache: {$file} - " . ($error['message'] ?? 'Erro desconhecido'));
            return false;
        }
        
        // Verificar se o arquivo foi realmente criado
        if (!file_exists($file)) {
            error_log("❌ ERRO: Arquivo de cache não foi criado: {$file}");
            return false;
        }
        
        return true;
    }

    /**
     * Remove um item do cache
     * 
     * @param string $key Chave do cache
     * @return void
     */
    public function forget(string $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Limpa todo o cache de queries
     * 
     * @return int Número de arquivos removidos
     */
    public function clear(): int
    {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Limpa cache por prefixo (útil para limpar cache de uma tabela específica)
     * 
     * @param string $prefix Prefixo da chave (ex: 'trainings', 'users')
     * @return int Número de arquivos removidos
     */
    public function clearByPrefix(string $prefix): int
    {
        $count = 0;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '/' . $prefix . '_*.json');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Gera caminho do arquivo de cache
     * 
     * @param string $key Chave do cache
     * @return string Caminho completo do arquivo
     */
    private function filePath(string $key): string
    {
        // Sanitizar a chave para usar como nome de arquivo
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . '/' . $safeKey . '.json';
    }

    /**
     * Verifica se um item existe no cache (mesmo que expirado)
     * 
     * @param string $key Chave do cache
     * @return bool
     */
    public function has(string $key): bool
    {
        return file_exists($this->filePath($key));
    }
}

