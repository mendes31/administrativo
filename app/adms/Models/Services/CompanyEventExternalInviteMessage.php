<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\NotificationOpenHelper;

/**
 * Texto do convite externo e link do WhatsApp.
 * A imagem fica na página aberta; o WhatsApp leva o texto e o endereço dessa página.
 */
final class CompanyEventExternalInviteMessage
{
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10 || strlen($digits) === 11) {
            $digits = '55' . $digits;
        }
        if (!str_starts_with($digits, '55') || strlen($digits) < 12 || strlen($digits) > 13) {
            return null;
        }

        return $digits;
    }

    public static function formatPhone(string $digits): string
    {
        $digits = preg_replace('/\D+/', '', $digits) ?? '';
        if (strlen($digits) === 13) {
            return sprintf(
                '+%s (%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 2),
                substr($digits, 4, 5),
                substr($digits, 9, 4)
            );
        }
        if (strlen($digits) === 12) {
            return sprintf(
                '+%s (%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 2),
                substr($digits, 4, 4),
                substr($digits, 8, 4)
            );
        }

        return $digits;
    }

    public static function inviteUrl(string $token): string
    {
        $base = rtrim(NotificationOpenHelper::currentRequestAppBase(), '/');

        return $base . '/convite-evento/' . $token;
    }

    /**
     * @param array<string, mixed> $event
     */
    public static function message(string $guestName, string $reason, array $event, string $inviteUrl): string
    {
        $when = self::whenLabel($event);
        $where = trim((string) ($event['location'] ?? ''));
        $lines = [
            'Olá, ' . trim($guestName) . '!',
            '',
            'Você está convidado(a) para: ' . trim((string) ($event['title'] ?? 'Evento')),
            'Quando: ' . $when,
        ];
        if ($where !== '') {
            $lines[] = 'Onde: ' . $where;
        }
        $reason = trim($reason);
        if ($reason !== '') {
            $lines[] = 'Motivo do convite: ' . $reason;
        }
        $lines[] = '';
        $lines[] = 'Abra o convite para ver a imagem do evento e confirmar ou recusar:';
        $lines[] = $inviteUrl;

        return implode("\n", $lines);
    }

    public static function whatsAppUrl(string $phoneDigits, string $text): ?string
    {
        $digits = self::normalizePhone($phoneDigits);
        if ($digits === null || trim($text) === '') {
            return null;
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($text);
    }

    public static function plainExcerpt(string $html, int $limit = 700): string
    {
        $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        if ($limit > 0 && mb_strlen($text) > $limit) {
            return rtrim(mb_substr($text, 0, $limit - 1)) . '…';
        }

        return $text;
    }

    /**
     * @param array<string, mixed> $event
     */
    public static function eventIsOpen(array $event, ?int $now = null): bool
    {
        $ends = strtotime((string) ($event['ends_at'] ?? ''));
        if ($ends === false) {
            return false;
        }

        return $ends >= ($now ?? time());
    }

    /**
     * @param array<string, mixed> $event
     */
    private static function whenLabel(array $event): string
    {
        $start = self::formatDate($event['starts_at'] ?? null);
        $end = self::formatDate($event['ends_at'] ?? null);
        if ($start === '' && $end === '') {
            return 'a confirmar';
        }
        if ($end === '' || $end === $start) {
            return $start;
        }

        return $start . ' até ' . $end;
    }

    private static function formatDate(mixed $value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }
        $ts = strtotime($raw);

        return $ts === false ? $raw : date('d/m/Y H:i', $ts);
    }
}
