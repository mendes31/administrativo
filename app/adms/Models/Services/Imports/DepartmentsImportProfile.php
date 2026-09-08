<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\DepartmentsRepository;

final class DepartmentsImportProfile implements ImportProfileInterface
{
    public function key(): string
    {
        return 'departments';
    }

    public function label(): string
    {
        return 'Departamentos / setores';
    }

    public function permission(): string
    {
        return 'ImportCenterDepartments';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'name' => 'Nome (chave)',
        ];
    }

    public function keyFields(): array
    {
        return ['name', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'name';
    }

    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array
    {
        $keyField = (string) ($mapped['_key_field'] ?? 'name');
        $name = trim((string) ($mapped['name'] ?? ''));
        $idRaw = trim((string) ($mapped['id'] ?? ''));
        $repo = new DepartmentsRepository();

        $existing = null;
        if ($keyField === 'id') {
            $id = (int) $idRaw;
            if ($id <= 0) {
                return ['action' => 'error', 'message' => 'ID inválido.', 'key' => $idRaw];
            }
            $existing = $repo->getDepartment($id) ?: null;
        } else {
            if ($name === '') {
                return ['action' => 'error', 'message' => 'Nome vazio.', 'key' => ''];
            }
            $name = (string) preg_replace('/\s+/u', ' ', $name);
            $existing = $repo->getByName($name) ?: null;
        }

        if ($existing === null) {
            if ($operation === 'update') {
                return ['action' => 'skipped', 'message' => 'Departamento não encontrado.', 'key' => $name !== '' ? $name : $idRaw];
            }
            if ($name === '') {
                return ['action' => 'error', 'message' => 'Para criar, informe o nome.', 'key' => $idRaw];
            }
            if ($dryRun) {
                return ['action' => 'would_create', 'message' => 'Seria criado.', 'key' => $name];
            }
            $ok = $repo->createDepartment(['name' => $name]);
            if (!$ok) {
                return ['action' => 'error', 'message' => 'Falha ao criar.', 'key' => $name];
            }

            return ['action' => 'created', 'message' => 'Departamento criado.', 'key' => $name];
        }

        if ($operation === 'insert') {
            return ['action' => 'skipped', 'message' => 'Já existe.', 'key' => (string) ($existing['name'] ?? $name)];
        }
        if ($name === '' || trim((string) $existing['name']) === $name) {
            return ['action' => 'skipped', 'message' => 'Nenhuma alteração.', 'key' => (string) $existing['name']];
        }
        if ($dryRun) {
            return ['action' => 'would_update', 'message' => 'Seria atualizado.', 'key' => $name];
        }
        $ok = $repo->updateDepartment(['id' => (int) $existing['id'], 'name' => $name]);
        if (!$ok) {
            return ['action' => 'error', 'message' => 'Falha ao atualizar.', 'key' => $name];
        }

        return ['action' => 'updated', 'message' => 'Nome atualizado.', 'key' => $name];
    }
}
