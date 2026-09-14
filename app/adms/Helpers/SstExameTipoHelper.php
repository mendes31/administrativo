<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Classificação do exame complementar no catálogo SST. */
final class SstExameTipoHelper
{
    public const CLINICO = 'Clínico';
    public const LABORATORIAL = 'Laboratorial';
    public const IMAGEM = 'Imagem';
    public const FUNCIONAL = 'Funcional';
    public const AVALIACAO_MEDICA = 'Avaliação Médica';
    public const OUTROS = 'Outros';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CLINICO,
            self::LABORATORIAL,
            self::IMAGEM,
            self::FUNCIONAL,
            self::AVALIACAO_MEDICA,
            self::OUTROS,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }

    /**
     * Consulta clínica / avaliação médica do evento ASO: a aptidão fica no Resultado ASO,
     * não como achado Normal/Alterado de complementar.
     */
    public static function isEventoClinicoAso(?string $tipo, ?string $nome = null): bool
    {
        $tipo = trim((string) $tipo);
        if (in_array($tipo, [self::CLINICO, self::AVALIACAO_MEDICA], true)) {
            return true;
        }
        $n = mb_strtolower(trim((string) $nome), 'UTF-8');
        $n = strtr($n, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a',
            'é' => 'e', 'ê' => 'e',
            'í' => 'i',
            'ó' => 'o', 'ô' => 'o',
            'ú' => 'u',
            'ç' => 'c',
        ]);
        $n = preg_replace('/[^a-z0-9]+/', '', $n) ?? '';

        return $n === 'consultaclinica' || str_contains($n, 'consultaclinica') || $n === 'aso';
    }

    /**
     * Opções de resultado no lançamento do exame complementar (ASO), por tipo.
     * Hoje todos os tipos usam Normal / Alterado; extensível no futuro.
     *
     * @return list<string>
     */
    public static function defaultResultadoOptions(?string $tipo = null): array
    {
        return SstExameResultadoHelper::catalogOptions();
    }
}
