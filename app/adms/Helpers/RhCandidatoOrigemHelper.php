<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Origem / canal de candidatura (cadastro da pessoa + UTM do portal público).
 */
final class RhCandidatoOrigemHelper
{
    public const FORM_TRABALHE_CONOSCO = 'form_trabalhe_conosco';
    public const PORTAL_INTERNO = 'portal_interno';
    public const PORTAL_SITE = 'portal_site';
    public const PORTAL_LINKEDIN = 'portal_linkedin';
    public const PORTAL_REDES = 'portal_redes';
    public const PORTAL_INFORMATIVO = 'portal_informativo';
    public const EMAIL = 'email';
    public const WHATSAPP = 'whatsapp';
    public const MANUAL = 'manual';
    public const OUTRO = 'outro';

    /**
     * @return array<string, string> valor => rótulo
     */
    public static function opcoesSelect(): array
    {
        return [
            self::EMAIL => 'E-mail',
            self::WHATSAPP => 'WhatsApp',
            self::FORM_TRABALHE_CONOSCO => 'Portal público (geral)',
            self::PORTAL_SITE => 'Portal — Site organizacional',
            self::PORTAL_LINKEDIN => 'Portal — LinkedIn',
            self::PORTAL_REDES => 'Portal — Redes sociais',
            self::PORTAL_INFORMATIVO => 'Portal — Informativo interno',
            self::PORTAL_INTERNO => 'Portal interno (colaborador)',
            self::MANUAL => 'Manual',
            self::OUTRO => 'Outro',
        ];
    }

    public static function label(string $origem): string
    {
        $map = self::opcoesSelect();
        return $map[$origem] ?? ($origem !== '' ? $origem : '—');
    }

    /**
     * Converte utm_source do link de divulgação no código persistido.
     */
    public static function fromUtmSource(?string $utmSource): string
    {
        $s = strtolower(trim((string) $utmSource));
        return match ($s) {
            'site' => self::PORTAL_SITE,
            'linkedin' => self::PORTAL_LINKEDIN,
            'redes' => self::PORTAL_REDES,
            'informativo' => self::PORTAL_INFORMATIVO,
            default => self::FORM_TRABALHE_CONOSCO,
        };
    }

    /**
     * Aceita código já normalizado ou utm cru vindo do form.
     */
    public static function normalizeFromInput(mixed $raw): string
    {
        $s = strtolower(trim((string) $raw));
        if ($s === '') {
            return self::FORM_TRABALHE_CONOSCO;
        }
        if (isset(self::opcoesSelect()[$s])) {
            return $s;
        }

        return self::fromUtmSource($s);
    }
}
