<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/** Categorias de proteção do EPI no catálogo SST. */
final class SstEpiCategoriaHelper
{
    public const CABECA = 'Proteção de Cabeça';
    public const AUDITIVA = 'Proteção Auditiva';
    public const VISUAL_FACIAL = 'Proteção Visual / Facial';
    public const RESPIRATORIA = 'Proteção Respiratória';
    public const MAOS_BRACOS = 'Proteção de Mãos e Braços';
    public const PES_PERNAS = 'Proteção de Pés e Pernas';
    public const TRONCO = 'Proteção do Tronco';
    public const QUEDAS = 'Proteção contra Quedas';
    public const OUTROS = 'Outros';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::CABECA,
            self::AUDITIVA,
            self::VISUAL_FACIAL,
            self::RESPIRATORIA,
            self::MAOS_BRACOS,
            self::PES_PERNAS,
            self::TRONCO,
            self::QUEDAS,
            self::OUTROS,
        ];
    }

    /**
     * Valor canónico da lista, ou null se vazio/desconhecido.
     * Aceita variações (ex.: "Proteção de Tronco", acentos trocados).
     */
    public static function canonicalize(?string $value): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
        if ($value === '') {
            return null;
        }
        if (in_array($value, self::all(), true)) {
            return $value;
        }

        $fold = self::fold($value);
        foreach (self::all() as $cat) {
            if (self::fold($cat) === $fold) {
                return $cat;
            }
        }

        $aliases = [
            'protecao de tronco' => self::TRONCO,
            'tronco' => self::TRONCO,
            'cabeca' => self::CABECA,
            'protecao cabeca' => self::CABECA,
            'auditiva' => self::AUDITIVA,
            'respiratoria' => self::RESPIRATORIA,
            'visual facial' => self::VISUAL_FACIAL,
            'visual / facial' => self::VISUAL_FACIAL,
            'maos e bracos' => self::MAOS_BRACOS,
            'pes e pernas' => self::PES_PERNAS,
            'quedas' => self::QUEDAS,
        ];

        return $aliases[$fold] ?? null;
    }

    public static function isValid(?string $value): bool
    {
        return self::canonicalize($value) !== null;
    }

    private static function fold(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = strtr($s, [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e',
            'í' => 'i', 'ì' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        $s = str_replace(['\\', '-'], ['/', ' '], $s);
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

        return trim($s);
    }
}
