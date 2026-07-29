<?php

declare(strict_types=1);

namespace App\adms\Helpers;

/**
 * Monta data/hora a partir de campos separados do formulário (date + time),
 * evitando o datetime-local do Chrome que esconde/corta a hora.
 */
final class AdmsDateTimeFormHelper
{
    /**
     * @param string $prefix Ex.: publish_at → publish_at_date / publish_at_time (ou publish_at legado)
     * @param string $defaultTime Se a data veio sem hora (00:00 publicação, 23:59 expiração)
     */
    public static function fromPost(string $prefix, string $defaultTime = '00:00'): string
    {
        $combined = trim((string) ($_POST[$prefix] ?? ''));
        if ($combined !== '') {
            return str_replace(' ', 'T', $combined);
        }

        $date = trim((string) ($_POST[$prefix . '_date'] ?? ''));
        if ($date === '') {
            return '';
        }

        $time = trim((string) ($_POST[$prefix . '_time'] ?? ''));
        if ($time === '') {
            $time = $defaultTime;
        }
        // Aceita HH:MM ou HH:MM:SS
        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            $time .= ':00';
        }

        return $date . 'T' . $time;
    }
}
