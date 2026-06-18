<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use App\adms\Models\Repository\SstRiscosRepository;

/** Normalização e validação do cadastro de riscos ocupacionais. */
final class SstRiscoCatalogHelper
{
    /**
     * @return array<string, mixed>
     */
    public static function parseFormData(array $post): array
    {
        $codigo = trim((string) ($post['codigo'] ?? ''));
        $nome = trim((string) ($post['nome'] ?? ''));
        $descricao = trim((string) ($post['descricao'] ?? ''));
        $grupo = trim((string) ($post['grupo_risco'] ?? ''));
        $status = (string) ($post['status'] ?? 'Ativo');

        return [
            'codigo' => $codigo !== '' ? strtoupper($codigo) : null,
            'nome' => $nome !== '' ? $nome : null,
            'descricao' => $descricao !== '' ? $descricao : null,
            'grupo_risco' => $grupo !== '' ? $grupo : null,
            'tipo' => $grupo !== '' ? $grupo : null,
            'necessita_monitoramento_medico' => !empty($post['necessita_monitoramento_medico']),
            'necessita_epi' => !empty($post['necessita_epi']),
            'status' => in_array($status, ['Ativo', 'Inativo'], true) ? $status : 'Ativo',
        ];
    }

    public static function validate(array $data, SstRiscosRepository $repo, ?int $excludeId = null): ?string
    {
        if (empty($data['nome'])) {
            return 'Informe o nome do risco.';
        }

        if (empty($data['grupo_risco']) || !SstRiscoGrupoHelper::isValid((string) $data['grupo_risco'])) {
            return 'Selecione o grupo de risco.';
        }

        if (!empty($data['codigo']) && $repo->existsCodigo((string) $data['codigo'], $excludeId)) {
            return 'Já existe um risco com este código interno.';
        }

        return null;
    }
}
