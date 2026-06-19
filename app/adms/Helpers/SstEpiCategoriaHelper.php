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

    public static function isValid(?string $value): bool
    {
        return $value !== null && $value !== '' && in_array($value, self::all(), true);
    }
}
