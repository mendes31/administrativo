<?php

declare(strict_types=1);

namespace App\adms\Models\Services\Imports;

use App\adms\Models\Repository\TiRustdeskRepository;
use App\adms\Models\Services\TiRustdeskSecretService;

final class TiRustdeskImportProfile implements ImportProfileInterface
{
    public function key(): string
    {
        return 'ti_rustdesk';
    }

    public function label(): string
    {
        return 'TI — RustDesk';
    }

    public function permission(): string
    {
        return 'ImportCenterTi';
    }

    public function fields(): array
    {
        return [
            'id' => 'ID interno (chave)',
            'rustdesk_id' => 'ID RustDesk (chave)',
            'alias' => 'Apelido',
            'senha' => 'Senha (criptografada ao gravar)',
            'colaborador' => 'Colaborador (login, e-mail, CPF, nome ou ID)',
            'status' => 'Status (ativo/inativo)',
            'observacoes' => 'Observações',
        ];
    }

    public function keyFields(): array
    {
        return ['rustdesk_id', 'id'];
    }

    public function defaultKeyField(): string
    {
        return 'rustdesk_id';
    }

    /**
     * @return list<string>
     */
    public function sampleRow(): array
    {
        return ['', '1554351509', 'usuario@maquina', 'altere-me', 'fulano.silva', 'ativo', ''];
    }

    public function processRow(array $mapped, string $operation, string $emptyPolicy, bool $dryRun): array
    {
        $keyField = (string) ($mapped['_key_field'] ?? $this->defaultKeyField());
        $keyRaw = SstImportValues::v($mapped, $keyField);
        if ($keyRaw === '' && $keyField !== 'rustdesk_id') {
            $keyRaw = SstImportValues::v($mapped, 'rustdesk_id');
        }
        if ($keyRaw === '') {
            return ['action' => 'error', 'message' => 'Chave vazia (' . $keyField . ').', 'key' => ''];
        }

        try {
            $repo = new TiRustdeskRepository();
            $existing = $this->findExisting($repo, $mapped, $keyField);
            $actorId = (int) ($_SESSION['user_id'] ?? 0);

            if ($existing === null) {
                if ($operation === 'update') {
                    return ['action' => 'skipped', 'message' => 'RustDesk não encontrado.', 'key' => $keyRaw];
                }
                [$payload, $warnings] = $this->buildCreatePayload($mapped);
                $msg = $this->withWarnings($dryRun ? 'Seria criado.' : 'RustDesk cadastrado.', $warnings);
                if ($dryRun) {
                    return ['action' => 'would_create', 'message' => $msg, 'key' => $payload['rustdesk_id']];
                }
                $ok = $repo->create($payload, $actorId);
                if (!$ok) {
                    return ['action' => 'error', 'message' => 'Falha ao criar. Verifique se o ID já existe.', 'key' => $keyRaw];
                }

                return ['action' => 'created', 'message' => $msg, 'key' => $payload['rustdesk_id']];
            }

            if ($operation === 'insert') {
                return ['action' => 'skipped', 'message' => 'Já existe.', 'key' => $keyRaw];
            }
            [$payload, $warnings] = $this->buildUpdatePayload($mapped, $existing, $emptyPolicy);
            $msg = $this->withWarnings($dryRun ? 'Seria atualizado.' : 'RustDesk atualizado.', $warnings);
            if ($dryRun) {
                return ['action' => 'would_update', 'message' => $msg, 'key' => $keyRaw];
            }
            $ok = $repo->update((int) $existing['id'], $payload, $actorId);
            if (!$ok) {
                return ['action' => 'error', 'message' => 'Falha ao atualizar.', 'key' => $keyRaw];
            }

            return ['action' => 'updated', 'message' => $msg, 'key' => $keyRaw];
        } catch (\Throwable $e) {
            return ['action' => 'error', 'message' => $e->getMessage(), 'key' => $keyRaw];
        }
    }

    /**
     * @param array<string, string> $mapped
     * @return array<string, mixed>|null
     */
    private function findExisting(TiRustdeskRepository $repo, array $mapped, string $keyField): ?array
    {
        if ($keyField === 'id') {
            $id = (int) preg_replace('/\D+/', '', SstImportValues::v($mapped, 'id')) ;
            return $id > 0 ? $repo->getById($id) : null;
        }

        $rid = SstImportValues::v($mapped, 'rustdesk_id');
        if ($rid === '') {
            $rid = SstImportValues::v($mapped, $keyField);
        }

        return $repo->getByRustdeskId($rid);
    }

    /**
     * @param array<string, string> $mapped
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function buildCreatePayload(array $mapped): array
    {
        $rustdeskId = TiRustdeskRepository::normalizeId(SstImportValues::v($mapped, 'rustdesk_id'));
        $alias = SstImportValues::v($mapped, 'alias');
        if ($rustdeskId === '') {
            throw new \RuntimeException('Para criar, informe o ID RustDesk.');
        }
        if (strlen($rustdeskId) < 6 || strlen($rustdeskId) > 16) {
            throw new \RuntimeException('O ID do RustDesk deve ter entre 6 e 16 dígitos.');
        }
        if ($alias === '') {
            throw new \RuntimeException('Para criar, informe o apelido.');
        }
        $senha = SstImportValues::v($mapped, 'senha');
        if ($senha !== '' && !TiRustdeskSecretService::isConfigured()) {
            throw new \RuntimeException('Não foi possível preparar a criptografia da senha (storage/private/secrets).');
        }

        [$userId, $warnings] = $this->resolveColaborador(SstImportValues::v($mapped, 'colaborador'));

        return [
            [
                'alias' => $alias,
                'rustdesk_id' => $rustdeskId,
                'adms_user_id' => $userId,
                'observacoes' => SstImportValues::v($mapped, 'observacoes') ?: null,
                'status' => $this->normalizeStatus(SstImportValues::v($mapped, 'status')) ?? TiRustdeskRepository::STATUS_ATIVO,
                'senha' => $senha,
                'limpar_senha' => false,
            ],
            $warnings,
        ];
    }

    /**
     * @param array<string, string> $mapped
     * @param array<string, mixed> $existing
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    private function buildUpdatePayload(array $mapped, array $existing, string $emptyPolicy): array
    {
        $rustdeskId = TiRustdeskRepository::normalizeId(SstImportValues::v($mapped, 'rustdesk_id'));
        if ($rustdeskId === '') {
            $rustdeskId = (string) ($existing['rustdesk_id'] ?? '');
        }
        $alias = SstImportValues::v($mapped, 'alias');
        if ($alias === '') {
            $alias = (string) ($existing['alias'] ?? '');
        }

        $senha = SstImportValues::has($mapped, 'senha') ? SstImportValues::v($mapped, 'senha') : '';
        $limpar = false;
        if (SstImportValues::has($mapped, 'senha') && $senha === '' && $emptyPolicy === 'clear') {
            $limpar = true;
        }
        if ($senha !== '' && !TiRustdeskSecretService::isConfigured()) {
            throw new \RuntimeException('Não foi possível preparar a criptografia da senha (storage/private/secrets).');
        }

        $obs = SstImportValues::v($mapped, 'observacoes');
        if ($obs === '' && $emptyPolicy === 'skip') {
            $obs = (string) ($existing['observacoes'] ?? '');
        }

        $status = $this->normalizeStatus(SstImportValues::v($mapped, 'status'));
        if ($status === null) {
            $status = (string) ($existing['status'] ?? TiRustdeskRepository::STATUS_ATIVO);
        }

        $userId = (int) ($existing['adms_user_id'] ?? 0);
        $warnings = [];
        if (SstImportValues::has($mapped, 'colaborador')) {
            $raw = SstImportValues::v($mapped, 'colaborador');
            if ($raw === '') {
                $userId = $emptyPolicy === 'clear' ? 0 : $userId;
            } else {
                [$resolved, $colabWarnings] = $this->resolveColaborador($raw);
                $warnings = $colabWarnings;
                if ($resolved !== null) {
                    $userId = $resolved;
                }
            }
        }

        return [
            [
                'alias' => $alias,
                'rustdesk_id' => $rustdeskId,
                'adms_user_id' => $userId > 0 ? $userId : null,
                'observacoes' => $obs !== '' ? $obs : null,
                'status' => $status,
                'senha' => $senha,
                'limpar_senha' => $limpar,
            ],
            $warnings,
        ];
    }

    /**
     * @return array{0: ?int, 1: list<string>}
     */
    private function resolveColaborador(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [null, []];
        }
        $id = (new SstImportLookup())->user($raw);
        if ($id === null) {
            return [null, ['Colaborador não encontrado: ' . $raw . ' (não vinculado).']];
        }

        return [$id, []];
    }

    /**
     * @param list<string> $warnings
     */
    private function withWarnings(string $message, array $warnings): string
    {
        if ($warnings === []) {
            return $message;
        }

        return $message . ' ' . implode(' ', $warnings);
    }

    private function normalizeStatus(string $raw): ?string
    {
        $v = mb_strtolower(trim($raw), 'UTF-8');
        if ($v === '') {
            return null;
        }
        if (in_array($v, ['ativo', 'a', '1', 'sim', 'yes', 'on'], true)) {
            return TiRustdeskRepository::STATUS_ATIVO;
        }
        if (in_array($v, ['inativo', 'i', '0', 'nao', 'não', 'no', 'off'], true)) {
            return TiRustdeskRepository::STATUS_INATIVO;
        }

        return in_array($raw, [TiRustdeskRepository::STATUS_ATIVO, TiRustdeskRepository::STATUS_INATIVO], true)
            ? $raw
            : TiRustdeskRepository::STATUS_ATIVO;
    }
}
