<?php

declare(strict_types=1);

namespace App\adms\Helpers;

use Mpdf\Mpdf;

/**
 * Cabeçalho institucional para PDFs (mesmo padrão visual do LNT).
 */
final class PdfInstitutionalHeaderHelper
{
    public static function resolveLogoAbsolutePath(): ?string
    {
        $root = defined('APP_ROOT')
            ? APP_ROOT
            : dirname(__DIR__, 3);
        $candidates = [
            $root . '/public/adms/image/logo/Logo-Tiaraju.png',
            $root . '/public/adms/image/logo/logo.png',
            $root . '/app/public/adms/image/logo/Logo-Tiaraju.png',
            $root . '/app/public/adms/image/logo/logo.png',
        ];
        foreach ($candidates as $p) {
            if (is_readable($p)) {
                return $p;
            }
        }

        return null;
    }

    public static function logoImgTagForMpdf(?string $path = null, int $maxW = 72): string
    {
        $path ??= self::resolveLogoAbsolutePath();
        if ($path === null) {
            return '<span style="font-size:8pt;color:#2d5f2e;font-weight:bold;">TIARAJU</span>';
        }
        $data = @file_get_contents($path);
        if ($data === false) {
            return '<span style="font-size:8pt;color:#2d5f2e;font-weight:bold;">TIARAJU</span>';
        }
        $mime = 'image/png';
        if (stripos($path, '.jpg') !== false || stripos($path, '.jpeg') !== false) {
            $mime = 'image/jpeg';
        }
        $src = 'data:' . $mime . ';base64,' . base64_encode($data);

        return '<img src="' . $src . '" style="max-width:' . $maxW . 'px;max-height:42px;" alt="Logo" />';
    }

    /**
     * Tabela 3 colunas: logo | título | logo (padrão LNT).
     */
    public static function buildHeaderTable(string $title, string $subtitle = ''): string
    {
        return self::buildHeaderTableSized($title, $subtitle, 72, '10pt', '8pt');
    }

    /**
     * Cabeçalho compacto para repetir em todas as páginas (mPDF SetHTMLHeader).
     */
    public static function buildRepeatingHeaderHtml(string $title, string $subtitle = ''): string
    {
        return self::buildHeaderTableSized($title, $subtitle, 58, '9pt', '7pt');
    }

    /**
     * Aplica cabeçalho institucional em todas as páginas do PDF.
     */
    public static function applyRepeatingHeader(Mpdf $mpdf, string $title, string $subtitle = ''): void
    {
        $html = self::buildRepeatingHeaderHtml($title, $subtitle);
        $mpdf->SetHTMLHeader($html, 'O');
        $mpdf->SetHTMLHeader($html, 'E');
    }

    /**
     * Margens recomendadas quando o cabeçalho se repete em todas as páginas.
     *
     * @return array<string, int|float|string>
     */
    public static function mpdfMarginConfig(): array
    {
        return [
            'margin_top' => 46,
            'margin_header' => 4,
            'setAutoTopMargin' => 'stretch',
        ];
    }

    private static function buildHeaderTableSized(
        string $title,
        string $subtitle,
        int $logoMaxW,
        string $titleSize,
        string $subtitleSize,
    ): string {
        $logoHtml = self::logoImgTagForMpdf(null, $logoMaxW);
        $titleEsc = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $subEsc = htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8');
        $subLine = $subtitle !== ''
            ? '<br/><span style="font-size:' . $subtitleSize . ';">' . $subEsc . '</span>'
            : '';

        return '<table width="100%" style="border-collapse:collapse;margin-bottom:4px;">
<tr>
<td style="width:22%;vertical-align:middle;text-align:center;border:1px solid #000;padding:4px;">' . $logoHtml . '</td>
<td style="width:56%;vertical-align:middle;text-align:center;border:1px solid #000;padding:6px;">
<strong style="font-size:' . $titleSize . ';">' . $titleEsc . '</strong>' . $subLine . '
</td>
<td style="width:22%;vertical-align:middle;text-align:center;border:1px solid #000;padding:4px;">' . $logoHtml . '</td>
</tr>
</table>';
    }

    /**
     * Bloco de identificação da empresa (razão social da filial do equipamento, quando houver).
     */
    public static function buildEmpresaInfoTable(?string $empresaContratanteSlug, string $documentoLabel = 'Documento'): string
    {
        $slug = UserFormHelper::resolveEmpresaContratanteSlug($empresaContratanteSlug) ?? '';
        $razao = $slug !== '' ? UserFormHelper::empresaContratantePdfLabel($slug) : '';
        $filial = UserFormHelper::empresaContratanteLabel($empresaContratanteSlug);
        $emissao = date('d/m/Y H:i');

        $rows = '';
        $rows .= self::infoRow('Empresa / grupo', 'Laboratório Tiaraju');
        if ($razao !== '') {
            $rows .= self::infoRow('Razão social (unidade)', $razao);
        }
        $rows .= self::infoRow('Unidade / filial', $filial);
        $rows .= self::infoRow('Data do ' . $documentoLabel, $emissao);

        return '<table width="100%" style="border-collapse:collapse;margin-bottom:10px;font-size:9pt;">'
            . $rows
            . '</table>';
    }

    private static function infoRow(string $label, string $value): string
    {
        $l = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        $v = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');

        return '<tr>'
            . '<td style="border:1px solid #000;padding:5px;width:28%;"><strong>' . $l . '</strong></td>'
            . '<td style="border:1px solid #000;padding:5px;">' . $v . '</td>'
            . '</tr>';
    }
}
