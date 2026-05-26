<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\SendEmailService;
use App\adms\Helpers\GenerateLog;

/**
 * Envio de e-mails ao cliente SAC (resposta, confirmação de abertura, encerramento).
 */
final class SacEmailService
{
    private const SAC_FROM_EMAIL = 'tiaraju@tiaraju.com.br';
    private const SAC_FROM_NAME = 'Tiaraju — SAC';

    /**
     * Envia e-mail de resposta ao cliente.
     *
     * @param array<int, array{path:string, name:string}> $attachments
     */
    public static function sendReplyToClient(array $ticket, string $replyMessage, string $agentName, array $attachments = []): bool
    {
        $clientEmail = trim((string) ($ticket['client_email'] ?? ''));
        if ($clientEmail === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $clientName = self::clientDisplayName($ticket);
        $code = (string) ($ticket['code'] ?? '');
        $subject = "Atualização do chamado #{$code} — " . self::appName();

        $body = self::buildReplyHtml($ticket, $replyMessage, $agentName);
        $altBody = self::buildReplyText($ticket, $replyMessage, $agentName);

        try {
            return SendEmailService::sendEmail(
                $clientEmail,
                $clientName,
                $subject,
                $body,
                $altBody,
                self::SAC_FROM_EMAIL,
                self::SAC_FROM_NAME,
                self::SAC_FROM_NAME,
                self::SAC_FROM_EMAIL,
                true,
                $attachments,
            );
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'SacEmailService::sendReplyToClient falhou', [
                'ticket_id' => $ticket['id'] ?? 0,
                'client_email' => $clientEmail,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Envia e-mail de confirmação de abertura de chamado ao cliente.
     */
    public static function sendTicketCreatedToClient(array $ticket): bool
    {
        $clientEmail = trim((string) ($ticket['client_email'] ?? ''));
        if ($clientEmail === '' || !filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $clientName = self::clientDisplayName($ticket);
        $code = (string) ($ticket['code'] ?? '');
        $subject = "Chamado #{$code} registrado — " . self::appName();

        $body = self::buildCreatedHtml($ticket);
        $altBody = self::buildCreatedText($ticket);

        try {
            return SendEmailService::sendEmail(
                $clientEmail,
                $clientName,
                $subject,
                $body,
                $altBody,
                self::SAC_FROM_EMAIL,
                self::SAC_FROM_NAME,
                self::SAC_FROM_NAME,
                self::SAC_FROM_EMAIL,
                true,
            );
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'SacEmailService::sendTicketCreatedToClient falhou', [
                'ticket_id' => $ticket['id'] ?? 0,
                'client_email' => $clientEmail,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private static function footerHtml(): string
    {
        return <<<'HTML'
  <tr><td style="padding:20px 30px;background:#2d6a2e;color:#fff;">
    <table width="100%" cellpadding="0" cellspacing="0">
      <tr><td style="padding-bottom:12px;">
        <strong style="font-size:15px;">Tiaraju</strong>
      </td></tr>
      <tr><td style="font-size:12px;line-height:1.8;color:#d4edda;">
        <span style="margin-right:6px;">&#x1F4CD;</span> Av. Sagrada Família, 2924 – Anexo I – Bairro: José Alcebíades Oliveira – Santo Ângelo – RS<br>
        <span style="margin-right:6px;">&#x260E;</span> +55 (55) 3314 7103 &nbsp;|&nbsp; SAC: 0800 644 2924<br>
        <span style="margin-right:6px;">&#x2709;</span> <a href="mailto:tiaraju@tiaraju.com.br" style="color:#d4edda;text-decoration:underline;">tiaraju@tiaraju.com.br</a><br>
        <span style="margin-right:6px;">&#x1F552;</span> Seg a Sex: 7:40–12:00 / 13:00–17:30
      </td></tr>
      <tr><td style="padding-top:10px;font-size:11px;color:#a3d9a5;">
        Relatos de eventos adversos e queixas técnicas: Nutrivigilância
      </td></tr>
    </table>
  </td></tr>
HTML;
    }

    private static function footerText(): string
    {
        return "---\n"
            . "Tiaraju\n"
            . "Av. Sagrada Família, 2924 – Anexo I – Bairro: José Alcebíades Oliveira – Santo Ângelo – RS\n"
            . "Tel: +55 (55) 3314 7103 | SAC: 0800 644 2924\n"
            . "E-mail: tiaraju@tiaraju.com.br\n"
            . "Seg a Sex: 7:40–12:00 / 13:00–17:30\n";
    }

    private static function buildReplyHtml(array $ticket, string $replyMessage, string $agentName): string
    {
        $code = self::esc($ticket['code'] ?? '');
        $ticketSubject = self::esc($ticket['subject'] ?? '');
        $messageHtml = nl2br(self::esc($replyMessage));
        $agentHtml = self::esc($agentName);
        $appName = self::esc(self::appName());
        $footer = self::footerHtml();

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
  <tr><td style="background:#0d6efd;padding:20px 30px;color:#fff;">
    <h2 style="margin:0;font-size:20px;">{$appName} — SAC</h2>
  </td></tr>
  <tr><td style="padding:30px;">
    <p style="margin:0 0 5px;color:#666;font-size:13px;">Chamado <strong>#{$code}</strong></p>
    <h3 style="margin:0 0 20px;color:#333;">{$ticketSubject}</h3>
    <div style="background:#f8f9fa;border-left:4px solid #0d6efd;padding:15px 20px;border-radius:4px;margin-bottom:20px;">
      {$messageHtml}
    </div>
    <p style="color:#666;font-size:13px;margin:0;">Atendente: <strong>{$agentHtml}</strong></p>
  </td></tr>
  <tr><td style="padding:12px 30px;background:#f8f9fa;text-align:center;font-size:12px;color:#999;">
    Este e-mail foi enviado automaticamente. Para responder, entre em contato pelos canais abaixo.
  </td></tr>
{$footer}
</table>
</body>
</html>
HTML;
    }

    private static function buildReplyText(array $ticket, string $replyMessage, string $agentName): string
    {
        $code = $ticket['code'] ?? '';
        $subject = $ticket['subject'] ?? '';
        return "Chamado #{$code} — {$subject}\n\n"
            . "Resposta:\n{$replyMessage}\n\n"
            . "Atendente: {$agentName}\n\n"
            . self::footerText();
    }

    private static function buildCreatedHtml(array $ticket): string
    {
        $code = self::esc($ticket['code'] ?? '');
        $ticketSubject = self::esc($ticket['subject'] ?? '');
        $description = nl2br(self::esc($ticket['description'] ?? ''));
        $priority = self::esc($ticket['priority'] ?? 'Média');
        $appName = self::esc(self::appName());
        $footer = self::footerHtml();

        $priorityColor = match ($ticket['priority'] ?? 'Média') {
            'Urgente' => '#dc3545',
            'Alta' => '#fd7e14',
            'Média' => '#ffc107',
            'Baixa' => '#6c757d',
            default => '#6c757d',
        };

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;margin:20px auto;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);">
  <tr><td style="background:#198754;padding:20px 30px;color:#fff;">
    <h2 style="margin:0;font-size:20px;">{$appName} — SAC</h2>
  </td></tr>
  <tr><td style="padding:30px;">
    <h3 style="margin:0 0 10px;color:#333;">Chamado #{$code} registrado com sucesso</h3>
    <p style="margin:0 0 20px;color:#666;">Seu chamado foi recebido e será analisado pela nossa equipe.</p>
    <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #dee2e6;border-radius:4px;margin-bottom:20px;">
      <tr style="background:#f8f9fa;">
        <td style="font-weight:bold;color:#555;width:120px;">Assunto</td>
        <td>{$ticketSubject}</td>
      </tr>
      <tr>
        <td style="font-weight:bold;color:#555;">Prioridade</td>
        <td><span style="background:{$priorityColor};color:#fff;padding:2px 10px;border-radius:4px;font-size:12px;">{$priority}</span></td>
      </tr>
      <tr style="background:#f8f9fa;">
        <td style="font-weight:bold;color:#555;">Descrição</td>
        <td>{$description}</td>
      </tr>
    </table>
  </td></tr>
  <tr><td style="padding:12px 30px;background:#f8f9fa;text-align:center;font-size:12px;color:#999;">
    Guarde o código <strong>#{$code}</strong> para acompanhamento. Este e-mail foi enviado automaticamente.
  </td></tr>
{$footer}
</table>
</body>
</html>
HTML;
    }

    private static function buildCreatedText(array $ticket): string
    {
        $code = $ticket['code'] ?? '';
        $subject = $ticket['subject'] ?? '';
        $description = $ticket['description'] ?? '';
        $priority = $ticket['priority'] ?? 'Média';
        return "Chamado #{$code} registrado com sucesso\n\n"
            . "Assunto: {$subject}\nPrioridade: {$priority}\nDescrição: {$description}\n\n"
            . "Guarde o código #{$code} para acompanhamento.\n\n"
            . self::footerText();
    }

    private static function clientDisplayName(array $ticket): string
    {
        $fantasy = trim((string) ($ticket['client_nome_fantasia'] ?? ''));
        $razao = trim((string) ($ticket['client_razao_social'] ?? ''));
        return $fantasy !== '' ? $fantasy : ($razao !== '' ? $razao : 'Cliente');
    }

    private static function appName(): string
    {
        return trim((string) ($_ENV['APP_NAME'] ?? 'Portal'));
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}
