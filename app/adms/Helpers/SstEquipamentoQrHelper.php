<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * URLs e geração de QR Code para equipamentos SST.
 */
final class SstEquipamentoQrHelper
{
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function buildScanUrl(string $qrToken): string
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');

        return $base . '/sst-scan-equipamento/' . rawurlencode($qrToken);
    }

    /**
     * PNG binário do QR ou null se biblioteca indisponível.
     */
    public static function renderPng(string $data, int $size = 280): ?string
    {
        if (!class_exists(\Mpdf\QrCode\QrCode::class)) {
            return null;
        }

        $qrCode = new \Mpdf\QrCode\QrCode($data);
        $output = new \Mpdf\QrCode\Output\Png();
        $output->setSize(max(120, $size));

        return $output->output($qrCode);
    }

    /**
     * Extrai o token a partir do texto lido (URL completa ou token puro).
     */
    public static function extractTokenFromScan(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        if (preg_match('#/sst-scan-equipamento/([A-Za-z0-9]+)#', $raw, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $raw)) {
            return $raw;
        }

        return null;
    }
}
