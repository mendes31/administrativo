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
