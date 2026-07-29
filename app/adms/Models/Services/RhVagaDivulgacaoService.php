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
     * Rascunho de título/conteúdo para informativo de seleção interna.
     * Inclui dados da oportunidade (descrição, requisitos, etc.) e um único CTA
     * “Candidatar-se” no portal autenticado — sem URL pública.
     *
     * @param array<string, mixed> $vaga
     * @return array{from_vaga: int, titulo: string, conteudo: string}
     */
    public static function buildInformativoPrefill(array $vaga): array
    {
        $id = (int) ($vaga['id'] ?? 0);
        $tituloVaga = trim((string) ($vaga['titulo'] ?? 'Vaga'));
        $titulo = 'Vaga interna: ' . $tituloVaga;
        $linkInterno = self::baseUrl() . 'vagas-internas/' . $id;

        $parts = [];
        $parts[] = '<p>Está aberta a vaga interna <strong>'
            . htmlspecialchars($tituloVaga, ENT_QUOTES, 'UTF-8')
            . '</strong>.</p>';

        $meta = [];
        $area = trim((string) ($vaga['area_nome'] ?? ''));
        $cargo = trim((string) ($vaga['cargo_nome'] ?? ''));
        $local = trim((string) ($vaga['local_trabalho'] ?? ''));
        $jornada = trim((string) ($vaga['jornada_trabalho'] ?? ''));
        $contrato = trim((string) ($vaga['tipo_contrato'] ?? ''));
        $qtd = (int) ($vaga['quantidade_vagas'] ?? 0);
        if ($area !== '') {
            $meta[] = '<strong>Área:</strong> ' . htmlspecialchars($area, ENT_QUOTES, 'UTF-8');
        }
        if ($cargo !== '') {
            $meta[] = '<strong>Cargo:</strong> ' . htmlspecialchars($cargo, ENT_QUOTES, 'UTF-8');
        }
        if ($local !== '') {
            $meta[] = '<strong>Local:</strong> ' . htmlspecialchars($local, ENT_QUOTES, 'UTF-8');
        }
        if ($jornada !== '') {
            $meta[] = '<strong>Jornada:</strong> ' . htmlspecialchars($jornada, ENT_QUOTES, 'UTF-8');
        }
        if ($contrato !== '') {
            $meta[] = '<strong>Contrato:</strong> ' . htmlspecialchars($contrato, ENT_QUOTES, 'UTF-8');
        }
        if ($qtd > 0) {
            $meta[] = '<strong>Vagas:</strong> ' . $qtd;
        }
        if ($meta !== []) {
            $parts[] = '<p>' . implode('<br>', $meta) . '</p>';
        }

        $descricao = trim((string) ($vaga['descricao'] ?? ''));
        if ($descricao !== '') {
            $parts[] = '<p><strong>Descrição</strong></p>'
                . '<p>' . nl2br(htmlspecialchars($descricao, ENT_QUOTES, 'UTF-8'), false) . '</p>';
        }

        $requisitos = trim((string) ($vaga['requisitos'] ?? ''));
        if ($requisitos !== '') {
            $parts[] = '<p><strong>Requisitos</strong></p>'
                . '<p>' . nl2br(htmlspecialchars($requisitos, ENT_QUOTES, 'UTF-8'), false) . '</p>';
        }

        $beneficios = trim((string) ($vaga['beneficios'] ?? ''));
        if ($beneficios !== '') {
            $parts[] = '<p><strong>Benefícios</strong></p>'
                . '<p>' . nl2br(htmlspecialchars($beneficios, ENT_QUOTES, 'UTF-8'), false) . '</p>';
        }

        if ($descricao === '' && $requisitos === '' && $beneficios === '') {
            $parts[] = '<p><em>Descrição e requisitos ainda não estão preenchidos na ficha da vaga. '
                . 'Complete-os em Vagas (ATS) e gere o informativo de novo, edite este texto, '
                . 'ou anexe um PDF com os detalhes.</em></p>';
        }

        $parts[] = '<p>Para a descrição completa da oportunidade (folder, detalhes adicionais), '
            . 'consulte o <strong>arquivo em anexo</strong> neste comunicado, quando houver.</p>';
        $parts[] = '<p>Se tiver interesse, candidate-se pelo app — seus dados de colaborador já estão disponíveis.</p>';
        $parts[] = '<p><a href="' . htmlspecialchars($linkInterno, ENT_QUOTES, 'UTF-8') . '"><strong>Candidatar-se</strong></a></p>';

        return [
            'from_vaga' => $id,
            'titulo' => $titulo,
            'conteudo' => implode("\n", $parts),
        ];
    }

    /**
     * URL do formulário de novo informativo (só o id da vaga na query — o texto é montado no servidor).
     *
     * @param array<string, mixed> $vaga
     */
    public static function createInformativoUrl(array $vaga): string
    {
        $id = (int) ($vaga['id'] ?? 0);

        return self::baseUrl() . 'create-informativo?' . http_build_query([
            'from_vaga' => $id,
        ]);
    }
}
