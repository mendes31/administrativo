<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Características de equipamentos de segurança (agente / capacidade).
 *
 * Padrão de mercado: Grupo = Extintor (checklist/prefixo únicos);
 * no equipamento, campos separados para agente (Pó ABC, CO₂…) e capacidade (kg/L).
 */
final class SstEquipamentoCaracteristicasHelper
{
    /**
     * Agentes / classes usuais de extintores (NBR / mercado BR).
     *
     * @return list<string>
     */
    public static function agentesExtintor(): array
    {
        return [
            'Água pressurizada',
            'Espuma (AFFF)',
            'Pó ABC',
            'Pó BC',
            'Pó químico seco',
            'CO₂',
            'Halogenado / limpo',
            'Outro',
        ];
    }

    /**
     * Capacidades nominais comuns (texto livre armazenado).
     *
     * @return list<string>
     */
    public static function capacidadesComuns(): array
    {
        return [
            '1 kg',
            '2 kg',
            '4 kg',
            '6 kg',
            '8 kg',
            '10 kg',
            '12 kg',
            '20 kg',
            '50 kg',
            '2 L',
            '4 L',
            '6 L',
            '10 L',
            '20 L',
            '50 L',
            'Outro',
        ];
    }

    public static function isAgenteConhecido(?string $valor): bool
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return false;
        }

        return in_array($v, self::agentesExtintor(), true);
    }

    public static function isCapacidadeConhecida(?string $valor): bool
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return false;
        }

        return in_array($v, self::capacidadesComuns(), true);
    }
}
