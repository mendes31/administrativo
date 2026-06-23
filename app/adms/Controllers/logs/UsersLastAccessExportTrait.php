<?php

declare(strict_types=1);

namespace App\adms\Controllers\logs;

trait UsersLastAccessExportTrait
{
    /**
     * @return array{usuario_nome: string, status: string, apenas_nunca: string, sort: string}
     */
    protected function resolveUsersLastAccessFilters(): array
    {
        return [
            'usuario_nome' => trim((string) ($_GET['usuario_nome'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'apenas_nunca' => ($_GET['apenas_nunca'] ?? '') === '1' ? '1' : '',
            'sort' => ($_GET['sort'] ?? '') === 'ultimo_login' ? 'ultimo_login' : 'nome',
        ];
    }

    /**
     * @param array{usuario_nome: string, status: string, apenas_nunca: string, sort: string} $filtros
     */
    protected function buildUsersLastAccessFilterSummary(array $filtros): string
    {
        $parts = [];

        if ($filtros['usuario_nome'] !== '') {
            $parts[] = 'busca: ' . $filtros['usuario_nome'];
        }
        if ($filtros['status'] !== '') {
            $parts[] = 'status: ' . $filtros['status'];
        }
        if ($filtros['apenas_nunca'] === '1') {
            $parts[] = 'somente quem nunca acessou';
        }
        $parts[] = $filtros['sort'] === 'ultimo_login'
            ? 'ordem: último login (recente)'
            : 'ordem: nome (A–Z)';

        return implode(' | ', $parts);
    }

    protected function formatUltimoLogin(?string $value): string
    {
        if ($value === null || $value === '') {
            return 'Nunca acessou';
        }

        $ts = strtotime($value);

        return $ts !== false ? date('d/m/Y H:i:s', $ts) : $value;
    }
}
