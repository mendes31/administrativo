<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Formata o nome do cargo apenas para exibição (UI, e-mails legíveis, exportações “humanas”).
 * Remove sufixos de senioridade JR / PL / SR usados no cadastro, preservando o texto no banco.
 */
final class PositionDisplayHelper
{
    /**
     * @param string|null $positionName Nome completo do cargo como cadastrado em `adms_positions.name`
     */
    public static function formatForDisplay(?string $positionName): string
    {
        $s = trim((string) ($positionName ?? ''));
        if ($s === '') {
            return '';
        }

        $suffix = '/(?:\s*[-–·]\s*|\s+)(JR|PL|SR)\s*$/iu';
        $prev = null;
        while ($prev !== $s) {
            $prev = $s;
            $s = preg_replace($suffix, '', $s) ?? '';
            $s = trim($s);
        }

        $s = preg_replace('/\s*[-–·]\s*$/u', '', $s) ?? '';

        return trim($s);
    }
}
