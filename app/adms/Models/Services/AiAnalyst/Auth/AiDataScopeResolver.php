<?php

declare(strict_types=1);

namespace App\adms\Models\Services\AiAnalyst\Auth;

/**
 * Resolve somente escopos que o servidor consegue comprovar nesta fase.
 * O mapeamento entre filial do portal e BPLId SAP ainda nao esta homologado.
 */
final class AiDataScopeResolver
{
    /** @return array{scope_mode: 'all'|'none', branch_ids: list<int>} */
    public function resolve(bool $fullSystemAccess): array
    {
        if ($fullSystemAccess) {
            return [
                'scope_mode' => 'all',
                'branch_ids' => [],
            ];
        }

        return [
            'scope_mode' => 'none',
            'branch_ids' => [],
        ];
    }
}
