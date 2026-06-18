<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Categorias de avaliação ocupacional (tipo do evento ASO — não são exames complementares).
 */
final class SstCategoriaAsoHelper
{
    public const ADMISSIONAL = 'Admissional';
    public const PERIODICO = 'Periódico';
    public const MUDANCA_FUNCAO = 'Mudança de função';
    public const RETORNO_TRABALHO = 'Retorno ao trabalho';
    public const DEMISSIONAL = 'Demissional';

  /** @return list<string> */
    public static function all(): array
    {
        return [
            self::ADMISSIONAL,
            self::PERIODICO,
            self::MUDANCA_FUNCAO,
            self::RETORNO_TRABALHO,
            self::DEMISSIONAL,
        ];
    }

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }

    /** Categorias que costumam ter periodicidade recorrente no PCMSO. */
    public static function hasPeriodicidadeRecorrente(string $categoria): bool
    {
        return $categoria === self::PERIODICO;
    }
}
