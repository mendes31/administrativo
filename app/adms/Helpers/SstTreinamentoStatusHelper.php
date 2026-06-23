<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Rótulos e badges de status dos vínculos de treinamento SST. */
final class SstTreinamentoStatusHelper
{
    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            'pendente' => 'Pendente',
            'agendado' => 'Agendado',
            'concluido' => 'Concluído',
            'vencido' => 'Vencido',
            'proximo_vencimento' => 'Próximo do vencimento',
            'dentro_do_prazo' => 'Dentro do prazo',
        ];
    }

    public static function label(?string $status): string
    {
        if ($status === null || $status === '') {
            return '-';
        }

        return self::labels()[$status] ?? $status;
    }

    public static function badgeClass(?string $status): string
    {
        return match ($status) {
            'pendente' => 'bg-warning text-dark',
            'agendado' => 'bg-info text-dark',
            'concluido' => 'bg-success',
            'vencido' => 'bg-danger',
            'proximo_vencimento' => 'bg-warning text-dark',
            'dentro_do_prazo' => 'bg-success',
            default => 'bg-secondary',
        };
    }

    /** @return list<string> */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
