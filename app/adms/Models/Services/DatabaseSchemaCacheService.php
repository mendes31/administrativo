<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Cache em disco do catálogo de tabelas (evita INFORMATION_SCHEMA a cada acesso).
 */
class DatabaseSchemaCacheService
{
    private string $baseDir;

    public function __construct(?string $databaseName = null)
    {
        $databaseName = $databaseName ?? (string) ($_ENV['DB_NAME'] ?? 'default');
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $databaseName) ?: 'default';
        $this->baseDir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'storage'
            . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'database_schema'
            . DIRECTORY_SEPARATOR . $safeKey;

        if (!is_dir($this->baseDir)) {
            @mkdir($this->baseDir, 0775, true);
        }
    }

    /**
     * @return array{updated_at: string, tables: list<array<string, mixed>>, modules: list<string>}|null
     */
    public function getCatalog(): ?array
    {
        $payload = $this->readJson($this->baseDir . DIRECTORY_SEPARATOR . 'catalog.json');
        if (!is_array($payload) || !isset($payload['tables']) || !is_array($payload['tables'])) {
            return null;
        }

        return $payload;
    }

    /**
     * @param list<array<string, mixed>> $tables
     * @param list<string> $modules
     */
    public function putCatalog(array $tables, array $modules): void
    {
        $this->writeJson($this->baseDir . DIRECTORY_SEPARATOR . 'catalog.json', [
            'updated_at' => date('Y-m-d H:i:s'),
            'tables' => $tables,
            'modules' => $modules,
        ]);
    }

    public function clearCatalog(): void
    {
        $file = $this->baseDir . DIRECTORY_SEPARATOR . 'catalog.json';
        if (is_file($file)) {
            @unlink($file);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getTableDetail(string $tableName): ?array
    {
        $file = $this->tableDetailPath($tableName);
        if (!is_file($file)) {
            return null;
        }

        $payload = $this->readJson($file);

        return is_array($payload) ? $payload : null;
    }

    /**
     * @param array<string, mixed> $detail
     */
    public function putTableDetail(string $tableName, array $detail): void
    {
        $tablesDir = $this->baseDir . DIRECTORY_SEPARATOR . 'tables';
        if (!is_dir($tablesDir)) {
            @mkdir($tablesDir, 0775, true);
        }

        $detail['updated_at'] = date('Y-m-d H:i:s');
        $this->writeJson($this->tableDetailPath($tableName), $detail);
    }

    public function clearTableDetail(string $tableName): void
    {
        $file = $this->tableDetailPath($tableName);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function clearAllTableDetails(): void
    {
        $tablesDir = $this->baseDir . DIRECTORY_SEPARATOR . 'tables';
        if (!is_dir($tablesDir)) {
            return;
        }
        foreach (glob($tablesDir . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    public function clearAll(): void
    {
        $this->clearCatalog();
        $this->clearAllTableDetails();
    }

    private function tableDetailPath(string $tableName): string
    {
        $safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tableName) ?: 'table';

        return $this->baseDir . DIRECTORY_SEPARATOR . 'tables' . DIRECTORY_SEPARATOR . $safe . '.json';
    }

  /**
     * @return array<string, mixed>|null
     */
    private function readJson(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return null;
        }
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $path, array $data): void
    {
        $dir = dirname($path);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}
