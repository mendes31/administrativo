<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Status e cálculo de validade de recarga de equipamentos SST.
 */
final class SstEquipamentoRecargaHelper
{
    public const ALERTA_DIAS = 30;

    /**
     * @return 'ok'|'a_vencer'|'vencido'|'sem_data'|null null = tipo não controla recarga
     */
    public static function status(?string $dataProxima, bool $controlaRecarga): ?string
    {
        if (!$controlaRecarga) {
            return null;
        }
        if ($dataProxima === null || trim($dataProxima) === '') {
            return 'sem_data';
        }
        $ts = strtotime($dataProxima . ' 23:59:59');
        if ($ts === false) {
            return 'sem_data';
        }
        $hoje = strtotime(date('Y-m-d') . ' 00:00:00');
        if ($ts < $hoje) {
            return 'vencido';
        }
        $limite = strtotime('+' . self::ALERTA_DIAS . ' days', $hoje);
        if ($ts <= $limite) {
            return 'a_vencer';
        }

        return 'ok';
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'vencido' => 'Recarga vencida',
            'a_vencer' => 'Recarga a vencer',
            'ok' => 'Recarga em dia',
            'sem_data' => 'Sem data de recarga',
            default => '—',
        };
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            'vencido' => 'bg-danger',
            'a_vencer' => 'bg-warning text-dark',
            'ok' => 'bg-success',
            'sem_data' => 'bg-secondary',
            default => 'bg-light text-muted',
        };
    }

    public static function calcularProxima(string $dataRecarga, int $meses): string
    {
        $meses = max(1, $meses);
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dataRecarga)
            ?: new \DateTimeImmutable($dataRecarga);

        return $dt->modify('+' . $meses . ' months')->format('Y-m-d');
    }

    /** @return list<string> */
    public static function tiposEvento(): array
    {
        return ['Recarga', 'Teste hidrostático', 'Manutenção', 'Outro'];
    }
}
