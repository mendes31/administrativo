<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Links de divulgação de vaga (portal público + atalho para informativo interno).
 */
final class RhVagaDivulgacaoService
{
    public const VIS_EXTERNA = 'externa';
    public const VIS_INTERNA = 'interna';
    public const VIS_AMBAS = 'ambas';

    /**
     * @return list<string>
     */
    public static function visibilidadesValidas(): array
    {
        return [self::VIS_EXTERNA, self::VIS_INTERNA, self::VIS_AMBAS];
    }

    public static function normalizeVisibilidade(mixed $raw): string
    {
        $v = strtolower(trim((string) $raw));
        return in_array($v, self::visibilidadesValidas(), true) ? $v : self::VIS_EXTERNA;
    }

    public static function permitePortalPublico(string $visibilidade): bool
    {
        $v = self::normalizeVisibilidade($visibilidade);

        return $v === self::VIS_EXTERNA || $v === self::VIS_AMBAS;
    }

    public static function permiteDivulgacaoInterna(string $visibilidade): bool
    {
        $v = self::normalizeVisibilidade($visibilidade);

        return $v === self::VIS_INTERNA || $v === self::VIS_AMBAS;
    }

    public static function labelVisibilidade(string $visibilidade): string
    {
        return match (self::normalizeVisibilidade($visibilidade)) {
            self::VIS_INTERNA => 'Interna (app / informativos)',
            self::VIS_AMBAS => 'Externa e interna',
            default => 'Externa (portal público)',
        };
    }

    public static function baseUrl(): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/';
    }

    public static function portalListUrl(): string
    {
        return self::baseUrl() . 'vagas-abertas';
    }

    public static function portalVagaUrl(int $vagaId, string $utmSource = ''): string
    {
        $url = self::baseUrl() . 'vagas-abertas/' . max(0, $vagaId);
        if ($utmSource === '') {
            return $url;
        }

        return $url . '?' . http_build_query([
            'utm_source' => $utmSource,
            'utm_medium' => 'social',
            'utm_campaign' => 'vaga_' . $vagaId,
        ]);
    }

    /**
     * @return list<array{canal: string, label: string, url: string}>
     */
    public static function linksExternos(int $vagaId): array
    {
        if ($vagaId <= 0) {
            return [];
        }

        return [
            ['canal' => 'portal', 'label' => 'Portal (limpo)', 'url' => self::portalVagaUrl($vagaId)],
            ['canal' => 'site', 'label' => 'Site organizacional', 'url' => self::portalVagaUrl($vagaId, 'site')],
            ['canal' => 'linkedin', 'label' => 'LinkedIn', 'url' => self::portalVagaUrl($vagaId, 'linkedin')],
            ['canal' => 'redes', 'label' => 'Redes sociais', 'url' => self::portalVagaUrl($vagaId, 'redes')],
            ['canal' => 'lista', 'label' => 'Lista de vagas abertas', 'url' => self::portalListUrl()],
        ];
    }

    /**
     * URL do formulário de novo informativo já pré-preenchido.
     *
     * @param array<string, mixed> $vaga
     */
    public static function createInformativoUrl(array $vaga): string
    {
        $id = (int) ($vaga['id'] ?? 0);
        $tituloVaga = trim((string) ($vaga['titulo'] ?? 'Vaga'));
        $titulo = 'Vaga interna: ' . $tituloVaga;

        $vis = self::normalizeVisibilidade($vaga['visibilidade'] ?? self::VIS_EXTERNA);
        $publicada = !empty($vaga['publicada']) && self::permitePortalPublico($vis);
        $interna = self::permiteDivulgacaoInterna($vis);

        $body = '<p>Abrimos a vaga <strong>' . htmlspecialchars($tituloVaga, ENT_QUOTES, 'UTF-8') . '</strong> para candidaturas internas.</p>';
        if ($interna) {
            $linkInterno = self::baseUrl() . 'vagas-internas/' . $id;
            $body .= '<p>Colaboradores: candidatem-se no app em '
                . '<a href="' . htmlspecialchars($linkInterno, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($linkInterno, ENT_QUOTES, 'UTF-8') . '</a>.</p>';
        }
        if ($publicada) {
            $link = self::portalVagaUrl($id, 'informativo');
            $body .= '<p>Também há formulário público: <a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</a></p>';
        } elseif (!$interna) {
            $body .= '<p>Entre em contato com o RH para se candidatar.</p>';
        }

        return self::baseUrl() . 'create-informativo?' . http_build_query([
            'from_vaga' => $id,
            'titulo' => $titulo,
            'conteudo' => $body,
        ]);
    }
}
