<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

/**
 * Templates versionados de e-mail de entrevista (catálogo PHP — Expand Fase 2).
 */
final class RhEntrevistaEmailTemplateCatalog
{
    public const KEY_AGENDADA = 'rh.entrevista.agendada';
    public const KEY_REAGENDADA = 'rh.entrevista.reagendada';

    /**
     * @param array{
     *   candidato_nome?: string,
     *   vaga_titulo?: string|null,
     *   data_hora?: string,
     *   data_hora_anterior?: string|null,
     *   local?: string|null,
     *   tipo?: string|null
     * } $vars
     * @return array{key: string, version: int, subject: string, body_html: string, body_text: string}
     */
    public static function render(string $key, array $vars): array
    {
        $candidato = trim((string) ($vars['candidato_nome'] ?? 'Candidato'));
        $vaga = trim((string) ($vars['vaga_titulo'] ?? ''));
        $dataHora = trim((string) ($vars['data_hora'] ?? ''));
        $local = trim((string) ($vars['local'] ?? ''));
        $tipo = trim((string) ($vars['tipo'] ?? ''));
        $dataAnterior = trim((string) ($vars['data_hora_anterior'] ?? ''));

        $vagaLine = $vaga !== '' ? "Vaga: {$vaga}" : 'Vaga: (não informada)';
        $localLine = $local !== '' ? "Local: {$local}" : 'Local: a confirmar';
        $tipoLine = $tipo !== '' ? "Tipo: {$tipo}" : '';

        if ($key === self::KEY_REAGENDADA) {
            $subject = 'Reagendamento de entrevista' . ($vaga !== '' ? " — {$vaga}" : '');
            $bodyText = "Olá, {$candidato}.\n\n"
                . "Sua entrevista foi reagendada.\n"
                . ($dataAnterior !== '' ? "Horário anterior: {$dataAnterior}\n" : '')
                . "Novo horário: {$dataHora}\n"
                . "{$vagaLine}\n{$localLine}\n"
                . ($tipoLine !== '' ? "{$tipoLine}\n" : '')
                . "\nEsta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.";
            $bodyHtml = '<p>Olá, ' . htmlspecialchars($candidato) . '.</p>'
                . '<p>Sua entrevista foi <strong>reagendada</strong>.</p>'
                . ($dataAnterior !== '' ? '<p>Horário anterior: ' . htmlspecialchars($dataAnterior) . '</p>' : '')
                . '<p>Novo horário: <strong>' . htmlspecialchars($dataHora) . '</strong></p>'
                . '<p>' . htmlspecialchars($vagaLine) . '<br>' . htmlspecialchars($localLine)
                . ($tipoLine !== '' ? '<br>' . htmlspecialchars($tipoLine) : '') . '</p>'
                . '<p><small>Esta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.</small></p>';

            return [
                'key' => self::KEY_REAGENDADA,
                'version' => 2,
                'subject' => $subject,
                'body_html' => $bodyHtml,
                'body_text' => $bodyText,
            ];
        }

        $subject = 'Entrevista agendada' . ($vaga !== '' ? " — {$vaga}" : '');
        $bodyText = "Olá, {$candidato}.\n\n"
            . "Sua entrevista foi agendada.\n"
            . "Horário: {$dataHora}\n"
            . "{$vagaLine}\n{$localLine}\n"
            . ($tipoLine !== '' ? "{$tipoLine}\n" : '')
            . "\nEsta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.";
        $bodyHtml = '<p>Olá, ' . htmlspecialchars($candidato) . '.</p>'
            . '<p>Sua entrevista foi <strong>agendada</strong>.</p>'
            . '<p>Horário: <strong>' . htmlspecialchars($dataHora) . '</strong></p>'
            . '<p>' . htmlspecialchars($vagaLine) . '<br>' . htmlspecialchars($localLine)
            . ($tipoLine !== '' ? '<br>' . htmlspecialchars($tipoLine) : '') . '</p>'
            . '<p><small>Esta é uma mensagem automática. Em caso de dúvida, entre em contato com o RH.</small></p>';

        return [
            'key' => self::KEY_AGENDADA,
            'version' => 2,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }
}
