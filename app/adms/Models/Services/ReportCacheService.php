<?php

namespace App\adms\Models\Services;

/**
 * Armazena resultados de relatórios em cache (arquivo) para evitar reprocessamentos.
 */
class ReportCacheService
{
    private string $cacheDir;

    public function __construct(?string $cacheDir = null)
    {
        $baseDir = $cacheDir ?? __DIR__ . '/../../../storage/cache/reports';
        $this->cacheDir = rtrim($baseDir, '/\\');

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0775, true);
        }
    }

    public function get(string $key): ?array
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

        return $payload;
    }

    public function put(string $key, array $data): void
    {
        $payload = [
            'stored_at' => time(),
            'data' => $data
        ];

        file_put_contents($this->filePath($key), json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    public function forget(string $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    private function filePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.json';
    }
}

