<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Helpers\UserFormHelper;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\UsersRepository;

final class UsersImportProfile implements ImportProfileInterface
{
    public function key(): string
    {
        return 'users';
    }

    public function label(): string
    {
        return 'Usuários / colaboradores';
    }

    public function permission(): string
    {
        return 'ImportCenterUsers';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID (chave)',
            'cpf' => 'CPF (chave)',
            'username' => 'Usuário / login (chave)',
            'name' => 'Nome',
            'email' => 'E-mail corporativo',
            'email_pessoal' => 'E-mail pessoal',
            'celular' => 'Celular',
            'department' => 'Departamento (nome ou ID)',
            'position' => 'Cargo (nome ou ID)',
            'status' => 'Status (Ativo/Inativo)',
            'bloqueado' => 'Bloqueado',
            'data_nascimento' => 'Data de nascimento',
            'data_admissao' => 'Data de admissão',
            'data_desligamento' => 'Data de desligamento',
            'motivo_desligamento' => 'Motivo do desligamento',
            'matricula' => 'Matrícula',
        ];
    }

    public function keyFields(): array
    {
        return ['cpf', 'username', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'cpf';
    }

    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array
    {
        $keyField = (string) ($mapped['_key_field'] ?? 'cpf');
        $keyRaw = trim((string) ($mapped[$keyField] ?? ''));
        if ($keyRaw === '') {
            return ['action' => 'error', 'message' => 'Chave vazia (' . $keyField . ').', 'key' => ''];
        }

        $repo = new UsersRepository();
        $existing = $this->findExisting($repo, $keyField, $keyRaw);

        if ($existing === null) {
            if ($operation === 'update') {
                return ['action' => 'skipped', 'message' => 'Usuário não encontrado.', 'key' => $keyRaw];
            }
            $payload = $this->buildCreatePayload($mapped, $emptyPolicy);
            if ($dryRun) {
                return ['action' => 'would_create', 'message' => 'Seria criado.', 'key' => $keyRaw];
            }
            $ok = $repo->createUser($payload);
            if (!$ok) {
                return ['action' => 'error', 'message' => 'Falha ao criar usuário.', 'key' => $keyRaw];
            }

            return ['action' => 'created', 'message' => 'Usuário criado.', 'key' => $keyRaw];
        }

        if ($operation === 'insert') {
            return ['action' => 'skipped', 'message' => 'Já existe; operação é só inserir.', 'key' => $keyRaw];
        }

        $payload = $this->buildUpdatePayload($mapped, $existing, $emptyPolicy);
        $payload['id'] = (int) $existing['id'];
        if ($dryRun) {
            return ['action' => 'would_update', 'message' => 'Seria atualizado.', 'key' => $keyRaw];
        }
        $ok = $repo->updateUser($payload);
        if (!$ok) {
            return ['action' => 'error', 'message' => 'Falha ao atualizar.', 'key' => $keyRaw];
        }

        return ['action' => 'updated', 'message' => 'Campos mapeados gravados.', 'key' => $keyRaw];
    }

    private function findExisting(UsersRepository $repo, string $keyField, string $keyRaw): ?array
    {
        if ($keyField === 'id') {
            $id = (int) preg_replace('/\D/', '', $keyRaw);
            $row = $id > 0 ? $repo->getUser($id) : false;

            return $row ?: null;
        }
        if ($keyField === 'cpf') {
            $cpf11 = preg_replace('/\D/', '', $keyRaw) ?? '';
            $row = $repo->getUserByNormalizedCpf($cpf11);

            return $row ?: null;
        }
        $byUser = $repo->getUserByUsername($keyRaw);
        if (!$byUser) {
            return null;
        }
        $full = $repo->getUser((int) $byUser['id']);

        return $full ?: null;
    }

    /**
     * @param array<string, string> $mapped
     * @return array<string, mixed>
     */
    private function buildCreatePayload(array $mapped, string $emptyPolicy): array
    {
        $name = $this->value($mapped, 'name');
        $email = $this->value($mapped, 'email');
        $username = $this->value($mapped, 'username');
        $dep = $this->resolveDepartment($this->value($mapped, 'department'));
        $pos = $this->resolvePosition($this->value($mapped, 'position'));
        if ($name === '' || $email === '' || $username === '' || $dep === null || $pos === null) {
            throw new \RuntimeException('Para criar: informe name, email, username, department e position.');
        }
        if ($this->value($mapped, 'data_desligamento') !== '') {
            throw new \RuntimeException('Não crie usuário já desligado via importação.');
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'user_department_id' => $dep,
            'user_position_id' => $pos,
            'password' => bin2hex(random_bytes(6)),
            'status' => $this->normalizeStatus($this->value($mapped, 'status')) ?? 'Ativo',
            'bloqueado' => $this->toBoolLabel($this->value($mapped, 'bloqueado')) ?? 'Não',
            'senha_nunca_expira' => 'Não',
            'modificar_senha_proximo_logon' => 'Não',
        ];
        $this->applyOptional($payload, $mapped, $emptyPolicy, true);
        $payload = UserFormHelper::applyEmpresaContratanteToForm($payload, $payload['empresa_contratante'] ?? null);

        return $payload;
    }

    /**
     * @param array<string, string> $mapped
     * @param array<string, mixed> $existing
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $payload = [];
        $this->applyOptional($payload, $mapped, $emptyPolicy, false);
        if ($this->hasMapped($mapped, 'name') && ($emptyPolicy === 'skip' ? $this->value($mapped, 'name') !== '' : true)) {
            $name = $this->value($mapped, 'name');
            if ($name !== '') {
                $payload['name'] = $name;
            }
        }
        if ($this->hasMapped($mapped, 'email') && $this->value($mapped, 'email') !== '') {
            $payload['email'] = $this->value($mapped, 'email');
        }
        if ($this->hasMapped($mapped, 'username') && $this->value($mapped, 'username') !== '') {
            $payload['username'] = $this->value($mapped, 'username');
        }
        if ($this->hasMapped($mapped, 'department')) {
            $raw = $this->value($mapped, 'department');
            if ($raw !== '') {
                $dep = $this->resolveDepartment($raw);
                if ($dep !== null) {
                    $payload['user_department_id'] = $dep;
                }
            }
        }
        if ($this->hasMapped($mapped, 'position')) {
            $raw = $this->value($mapped, 'position');
            if ($raw !== '') {
                $pos = $this->resolvePosition($raw);
                if ($pos !== null) {
                    $payload['user_position_id'] = $pos;
                }
            }
        }
        if ($this->hasMapped($mapped, 'status')) {
            $st = $this->normalizeStatus($this->value($mapped, 'status'));
            if ($st !== null) {
                $payload['status'] = $st;
            }
        }
        if ($this->hasMapped($mapped, 'bloqueado')) {
            $bl = $this->toBoolLabel($this->value($mapped, 'bloqueado'));
            if ($bl !== null) {
                $payload['bloqueado'] = $bl;
            }
        }

        foreach (['name', 'email', 'username', 'status', 'bloqueado', 'senha_nunca_expira', 'modificar_senha_proximo_logon'] as $req) {
            if (!array_key_exists($req, $payload) || $payload[$req] === null || $payload[$req] === '') {
                $payload[$req] = $existing[$req] ?? ($req === 'status' ? 'Ativo' : ($req === 'bloqueado' || str_contains($req, 'senha') ? 'Não' : ''));
            }
        }
        if (empty($payload['user_department_id'])) {
            $payload['user_department_id'] = (int) ($existing['user_department_id'] ?? 0);
        }
        if (empty($payload['user_position_id'])) {
            $payload['user_position_id'] = (int) ($existing['user_position_id'] ?? 0);
        }
        $optional = ['cpf', 'celular', 'data_nascimento', 'matricula', 'email_pessoal', 'data_admissao', 'data_desligamento', 'motivo_desligamento'];
        foreach ($optional as $opt) {
            if (!array_key_exists($opt, $payload) || $payload[$opt] === null || $payload[$opt] === '') {
                if ($emptyPolicy === 'skip' && array_key_exists($opt, $existing) && $existing[$opt] !== null && $existing[$opt] !== '') {
                    $payload[$opt] = $existing[$opt];
                }
            }
        }
        if (!empty($payload['data_desligamento'])) {
            $payload['status'] = 'Inativo';
        }
        unset($payload['password']);

        return UserFormHelper::applyEmpresaContratanteToForm($payload, $payload['empresa_contratante'] ?? null);
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $mapped
     */
    private function applyOptional(array &$payload, array $mapped, string $emptyPolicy, bool $creating): void
    {
        $map = [
            'cpf' => 'cpf',
            'celular' => 'celular',
            'email_pessoal' => 'email_pessoal',
            'matricula' => 'matricula',
            'motivo_desligamento' => 'motivo_desligamento',
        ];
        foreach ($map as $field => $col) {
            if (!$this->hasMapped($mapped, $field)) {
                continue;
            }
            $raw = $this->value($mapped, $field);
            if ($raw === '') {
                if ($emptyPolicy === 'clear' && !$creating) {
                    $payload[$col] = null;
                }
                continue;
            }
            if ($field === 'cpf') {
                $digits = preg_replace('/\D/', '', $raw) ?? '';
                $payload['cpf'] = strlen($digits) === 11
                    ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $digits)
                    : $raw;
            } else {
                $payload[$col] = $raw;
            }
        }
        foreach (['data_nascimento', 'data_admissao', 'data_desligamento'] as $dateField) {
            if (!$this->hasMapped($mapped, $dateField)) {
                continue;
            }
            $raw = $this->value($mapped, $dateField);
            if ($raw === '') {
                if ($emptyPolicy === 'clear' && !$creating) {
                    $payload[$dateField] = null;
                }
                continue;
            }
            $payload[$dateField] = $this->toDate($raw);
        }
    }

    /** @param array<string, string> $mapped */
    private function hasMapped(array $mapped, string $field): bool
    {
        return array_key_exists($field, $mapped);
    }

    /** @param array<string, string> $mapped */
    private function value(array $mapped, string $field): string
    {
        return trim((string) ($mapped[$field] ?? ''));
    }

    private function resolveDepartment(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $repo = new DepartmentsRepository();
        if (ctype_digit($raw)) {
            $row = $repo->getDepartment((int) $raw);
            if (!$row) {
                throw new \RuntimeException('Departamento ID ' . $raw . ' não encontrado.');
            }

            return (int) $row['id'];
        }
        $row = $repo->getByName($raw);
        if (!$row) {
            throw new \RuntimeException('Departamento "' . $raw . '" não encontrado. Importe o catálogo antes.');
        }

        return (int) $row['id'];
    }

    private function resolvePosition(string $raw): ?int
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        $repo = new PositionsRepository();
        if (ctype_digit($raw)) {
            $row = $repo->getPosition((int) $raw);
            if (!$row) {
                throw new \RuntimeException('Cargo ID ' . $raw . ' não encontrado.');
            }

            return (int) $row['id'];
        }
        $row = $repo->getByName($raw);
        if (!$row) {
            throw new \RuntimeException('Cargo "' . $raw . '" não encontrado. Importe o catálogo antes.');
        }

        return (int) $row['id'];
    }

    private function normalizeStatus(string $raw): ?string
    {
        $v = mb_strtolower(trim($raw), 'UTF-8');
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['ativo', '1', 'sim', 'yes', 'a'], true)) {
            return 'Ativo';
        }
        if (in_array($v, ['inativo', '0', 'nao', 'não', 'no', 'i'], true)) {
            return 'Inativo';
        }

        return in_array($raw, ['Ativo', 'Inativo'], true) ? $raw : 'Ativo';
    }

    private function toBoolLabel(string $raw): ?string
    {
        $v = mb_strtolower(trim($raw), 'UTF-8');
        if ($v === '') {
            return null;
        }
        $v = strtr($v, ['á' => 'a', 'ã' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
        if (in_array($v, ['sim', 's', 'yes', 'y', 'true', '1'], true)) {
            return 'Sim';
        }

        return 'Não';
    }

    private function toDate(string $val): ?string
    {
        $v = trim($val);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $v, $m)) {
            return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        $t = strtotime($v);

        return $t ? date('Y-m-d', $t) : null;
    }
}
