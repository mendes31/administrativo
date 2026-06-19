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

    /**
     * Normaliza checkboxes do formulário: vazio ou todas marcadas = uma linha sem categoria (vale para todos).
     *
     * @param list<string> $posted
     * @return list<string|null>
     */
    public static function normalizeSelection(array $posted): array
    {
        $valid = array_values(array_filter($posted, static fn (mixed $c): bool => is_string($c) && self::isValid($c)));
        if ($valid === [] || count($valid) === count(self::all())) {
            return [null];
        }

        return $valid;
    }
}
