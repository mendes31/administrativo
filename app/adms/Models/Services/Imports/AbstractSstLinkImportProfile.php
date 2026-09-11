<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

abstract class AbstractSstLinkImportProfile implements ImportProfileInterface
{
    public function permission(): string
    {
        return 'ImportCenterSst';
    }

    public function keyFields(): array
    {
        return ['id'];
    }

    public function defaultKeyField(): string
    {
        return 'id';
    }

    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array
    {
        try {
            $resolved = $this->resolve($mapped, $emptyPolicy);
        } catch (\Throwable $e) {
            return ['action' => 'error', 'message' => $e->getMessage(), 'key' => ''];
        }
        $key = (string) ($resolved['key'] ?? '');
        $existing = $resolved['existing'] ?? null;
        $payload = $resolved['payload'] ?? [];

        if ($existing === null) {
            if ($operation === 'update') {
                return ['action' => 'skipped', 'message' => 'Vínculo não encontrado.', 'key' => $key];
            }
            if ($dryRun) {
                return ['action' => 'would_create', 'message' => 'Seria criado.', 'key' => $key];
            }
            $ok = $this->createRow($payload);
            if (!$ok) {
                return ['action' => 'error', 'message' => 'Falha ao criar vínculo.', 'key' => $key];
            }

            return ['action' => 'created', 'message' => 'Vínculo criado.', 'key' => $key];
        }

        if ($operation === 'insert') {
            return ['action' => 'skipped', 'message' => 'Vínculo já existe.', 'key' => $key];
        }
        if ($dryRun) {
            return ['action' => 'would_update', 'message' => 'Seria atualizado.', 'key' => $key];
        }
        $ok = $this->updateRow((int) $existing['id'], $payload);
        if (!$ok) {
            return ['action' => 'error', 'message' => 'Falha ao atualizar vínculo.', 'key' => $key];
        }

        return ['action' => 'updated', 'message' => 'Vínculo atualizado.', 'key' => $key];
    }

    /**
     * @param array<string, string> $mapped
     * @return array{existing: ?array, payload: array<string, mixed>, key: string}
     */
    abstract protected function resolve(array $mapped, string $emptyPolicy): array;

    /** @param array<string, mixed> $payload */
    abstract protected function createRow(array $payload): int|false;

    /** @param array<string, mixed> $payload */
    abstract protected function updateRow(int $id, array $payload): bool;

    /** @return array<string, mixed> */
    protected function requireCatalog(string $table, string $raw, bool $hasCodigo, string $label, string $nameCol = 'nome'): array
    {
        $row = (new SstImportLookup())->catalog($table, $raw, $hasCodigo, $nameCol);
        if ($row === null) {
            throw new \RuntimeException($label . ' não encontrado' . ($raw !== '' ? ': ' . $raw : '.'));
        }

        return $row;
    }

    protected function optionalPosition(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $id = (new SstImportLookup())->position($raw);
        if ($id === null) {
            throw new \RuntimeException('Cargo não encontrado: ' . $raw);
        }

        return $id;
    }

    protected function optionalDepartment(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $id = (new SstImportLookup())->department($raw);
        if ($id === null) {
            throw new \RuntimeException('Departamento não encontrado: ' . $raw);
        }

        return $id;
    }

    protected function optionalRiscoId(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $row = (new SstImportLookup())->catalog('adms_sst_riscos', $raw, true);
        if ($row === null) {
            throw new \RuntimeException('Risco não encontrado: ' . $raw);
        }

        return (int) $row['id'];
    }

    protected function requireUser(string $raw, string $label = 'Colaborador'): int
    {
        $id = (new SstImportLookup())->user($raw);
        if ($id === null) {
            throw new \RuntimeException($label . ' não encontrado' . ($raw !== '' ? ': ' . $raw : '.'));
        }

        return $id;
    }

    /**
     * @param array<string, string> $mapped
     * @param array<string, int|string|null> $where
     */
    protected function existingByIdOrLink(string $table, array $mapped, array $where): ?array
    {
        $lookup = new SstImportLookup();
        if (SstImportValues::v($mapped, 'id') !== '') {
            $byId = $lookup->byId($table, (int) SstImportValues::v($mapped, 'id'));
            if ($byId !== null) {
                return $byId;
            }
        }

        return $lookup->findLink($table, $where);
    }
}
