<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Formatação de campos do catálogo de treinamentos SST. */
final class SstTreinamentoDisplayHelper
{
    public static function cargaHorariaHoras(?int $minutos): string
    {
        if ($minutos === null || $minutos <= 0) {
            return '-';
        }
        $horas = $minutos / 60;
        if (fmod($horas, 1.0) === 0.0) {
            return (string) (int) $horas . ' h';
        }

        return number_format($horas, 1, ',', '') . ' h';
    }

    /** Valor para input (horas decimais). */
    public static function cargaHorariaInputValue(?int $minutos): string
    {
        if ($minutos === null || $minutos <= 0) {
            return '';
        }
        $horas = $minutos / 60;

        return fmod($horas, 1.0) === 0.0 ? (string) (int) $horas : rtrim(rtrim(number_format($horas, 2, '.', ''), '0'), '.');
    }

    public static function validadeReciclagem(?int $meses): string
    {
        if ($meses === null) {
            return '-';
        }
        if ($meses === 0) {
            return 'Não possui reciclagem';
        }

        return $meses . ' meses';
    }

    public static function prazoPrimeiro(?int $dias): string
    {
        if ($dias === null) {
            return '-';
        }
        if ($dias === 0) {
            return 'Imediato / conforme exposição';
        }
        if ($dias === 1) {
            return '1 dia após admissão/vínculo';
        }

        return $dias . ' dias após admissão/vínculo';
    }
}
