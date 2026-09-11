<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

abstract class AbstractSstCatalogImportProfile implements ImportProfileInterface
{
    public function permission(): string
    {
        return 'ImportCenterSst';
    }

    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array
    {
        $keyField = (string) ($mapped['_key_field'] ?? $this->defaultKeyField());
        $keyRaw = $this->resolveKeyRaw($mapped, $keyField);
        if ($keyRaw === '') {
            return ['action' => 'error', 'message' => 'Chave vazia.', 'key' => ''];
        }

        try {
            $existing = $this->findExisting($mapped, $keyField);
            if ($existing === null) {
                if ($operation === 'update') {
                    return ['action' => 'skipped', 'message' => 'Registro não encontrado.', 'key' => $keyRaw];
                }
                $payload = $this->buildCreatePayload($mapped);
                if ($dryRun) {
                    return ['action' => 'would_create', 'message' => 'Seria criado.', 'key' => $keyRaw];
                }
                $ok = $this->createRow($payload);
                if (!$ok) {
                    return ['action' => 'error', 'message' => 'Falha ao criar.', 'key' => $keyRaw];
                }

                return ['action' => 'created', 'message' => 'Criado.', 'key' => $keyRaw];
            }

            if ($operation === 'insert') {
                return ['action' => 'skipped', 'message' => 'Já existe.', 'key' => $keyRaw];
            }
            $payload = $this->buildUpdatePayload($mapped, $existing, $emptyPolicy);
            if ($dryRun) {
                return ['action' => 'would_update', 'message' => 'Seria atualizado.', 'key' => $keyRaw];
            }
            $ok = $this->updateRow((int) $existing['id'], $payload);
            if (!$ok) {
                return ['action' => 'error', 'message' => 'Falha ao atualizar.', 'key' => $keyRaw];
            }
        } catch (\Throwable $e) {
            return ['action' => 'error', 'message' => $e->getMessage(), 'key' => $keyRaw];
        }

        return ['action' => 'updated', 'message' => 'Atualizado.', 'key' => $keyRaw];
    }

    /**
     * @param array<string, string> $mapped
     */
    protected function resolveKeyRaw(array $mapped, string $keyField): string
    {
        $keyRaw = SstImportValues::v($mapped, $keyField);
        if ($keyRaw === '' && $keyField !== 'nome' && $keyField !== 'name') {
            $keyRaw = SstImportValues::v($mapped, 'nome');
        }

        return $keyRaw;
    }

    /** @param array<string, string> $mapped */
    abstract protected function findExisting(array $mapped, string $keyField): ?array;

    /**
     * @param array<string, string> $mapped
     * @return array<string, mixed>
     */
    abstract protected function buildCreatePayload(array $mapped): array;

    /**
     * @param array<string, string> $mapped
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    abstract protected function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array;

    /** @param array<string, mixed> $payload */
    abstract protected function createRow(array $payload): int|false;

    /** @param array<string, mixed> $payload */
    abstract protected function updateRow(int $id, array $payload): bool;
}
