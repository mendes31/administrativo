<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\InternalPushNotificationHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Services\NotificationSettingsService;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Alertas por e-mail e notificação in-app para pendências SST (EPI e exames; treinamentos opcionais).
 */
class SstNotificationService extends DbConnection
{
    private const THROTTLE_DAYS = 7;
    private const NOTIF_TYPE = 'sst_alert_digest';

    private SstPendenciasService $pendenciasService;

    public function __construct()
    {
        $this->pendenciasService = new SstPendenciasService();
    }

    /**
     * Envia resumo de pendências SST para cada colaborador com itens pendentes.
     *
     * @return array{sent: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public function sendPendenciasDigest(): array
    {
        $results = [
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'disabled' => false,
        ];

        if (!$this->isAnyChannelEnabled()) {
            $results['disabled'] = true;
            return $results;
        }

        try {
            $grupos = $this->pendenciasService->getPendenciasParaNotificacao();

            foreach ($grupos as $grupo) {
                $userId = (int) ($grupo['user_id'] ?? 0);
                if ($userId <= 0) {
                    continue;
                }

                if ($this->wasRecentlyNotified($userId)) {
                    $results['skipped']++;
                    continue;
                }

                $email = trim((string) ($grupo['user_email'] ?? ''));
                $name = (string) ($grupo['user_name'] ?? 'Colaborador');
                $itens = $grupo['itens'] ?? [];

                if ($itens === []) {
                    $results['skipped']++;
                    continue;
                }

                $emailOk = true;
                $sentSomething = false;

                if ($email !== '' && self::isEmailEnabled()) {
                    $subject = 'Pendências SST — ação necessária';
                    $body = $this->buildEmailBody($name, $itens, $userId);
                    $altBody = $this->buildEmailAltBody($name, $itens);
                    $emailOk = SendEmailService::sendEmail($email, $name, $subject, $body, $altBody);
                    if ($emailOk) {
                        $sentSomething = true;
                    }
                }

                if (self::isInAppEnabled()) {
                    $this->dispatchInAppNotification($userId, $itens);
                    $sentSomething = true;
                }

                if (!$sentSomething) {
                    $results['skipped']++;
                    continue;
                }

                if ($email === '' || $emailOk || !self::isEmailEnabled()) {
                    $results['sent']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Falha ao enviar e-mail SST para {$email}";
                }
            }

            GenerateLog::generateLog('info', 'Notificações SST de pendências processadas', $results);
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Erro ao enviar notificações SST', ['error' => $e->getMessage()]);
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }

    public static function isEmailEnabled(): bool
    {
        return NotificationSettingsService::isEnabled('sst_pendencias_email');
    }

    public static function isInAppEnabled(): bool
    {
        return NotificationSettingsService::isEnabled('sst_pendencias_inapp');
    }

    public static function isAnyChannelEnabled(): bool
    {
        return self::isEmailEnabled() || self::isInAppEnabled();
    }

    /** @deprecated Use isAnyChannelEnabled() */
    public static function isEnabled(): bool
    {
        return self::isAnyChannelEnabled();
    }

    private function wasRecentlyNotified(int $userId): bool
    {
        $sql = 'SELECT 1 FROM adms_notifications
                WHERE user_id = :uid AND type = :type
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                LIMIT 1';
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':type', self::NOTIF_TYPE, PDO::PARAM_STR);
        $stmt->bindValue(':days', self::THROTTLE_DAYS, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @param array<int, array<string, mixed>> $itens
     */
    private function dispatchInAppNotification(int $userId, array $itens): void
    {
        $criticas = count(array_filter(
            $itens,
            static fn(array $i): bool => in_array((string) ($i['situacao'] ?? ''), SstPendenciasService::SITUACOES_CRITICAS, true)
        ));

        $total = count($itens);
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $link = $base . '/sst-employee-profile/' . $userId;

        $title = $criticas > 0
            ? "SST: {$criticas} pendência(s) crítica(s)"
            : "SST: {$total} item(ns) a vencer";

        $lines = [];
        foreach (array_slice($itens, 0, 5) as $item) {
            $label = $item['epi_nome'] ?? $item['exame_nome'] ?? $item['treinamento_nome'] ?? 'Item';
            $lines[] = $label . ' — ' . ($item['situacao_label'] ?? $item['situacao'] ?? '');
        }
        if ($total > 5) {
            $lines[] = '... e mais ' . ($total - 5) . ' item(ns)';
        }

        InternalPushNotificationHelper::notifyUser([
            'user_id' => $userId,
            'type' => self::NOTIF_TYPE,
            'title' => $title,
            'message' => implode("\n", $lines),
            'link_url' => $link,
            'entity_type' => 'sst_pendencias',
            'entity_id' => $userId,
            'priority' => $criticas > 0 ? 40 : 10,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $itens
     */
    private function buildEmailBody(string $name, array $itens, int $userId): string
    {
        $base = rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/');
        $rows = '';
        foreach ($itens as $item) {
            $label = htmlspecialchars((string) ($item['epi_nome'] ?? $item['exame_nome'] ?? $item['treinamento_nome'] ?? 'Item'));
            $sit = htmlspecialchars((string) ($item['situacao_label'] ?? $item['situacao'] ?? ''));
            $rows .= "<tr><td style=\"padding:6px 10px;border:1px solid #ddd;\">{$label}</td>"
                . "<td style=\"padding:6px 10px;border:1px solid #ddd;\">{$sit}</td></tr>";
        }

        return '<div style="font-family:Arial,sans-serif;max-width:600px;">'
            . '<h2 style="color:#c0392b;">Pendências SST</h2>'
            . '<p>Olá, <strong>' . htmlspecialchars($name) . '</strong>,</p>'
            . '<p>Identificamos pendências em Saúde e Segurança do Trabalho vinculadas ao seu cargo. '
            . 'Regularize com o setor de SST o quanto antes.</p>'
            . '<table style="border-collapse:collapse;width:100%;margin:16px 0;">'
            . '<thead><tr><th style="padding:8px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Item</th>'
            . '<th style="padding:8px 10px;border:1px solid #ddd;background:#f5f5f5;text-align:left;">Situação</th></tr></thead>'
            . '<tbody>' . $rows . '</tbody></table>'
            . '<p><a href="' . htmlspecialchars($base . '/sst-employee-profile/' . $userId) . '" '
            . 'style="display:inline-block;padding:10px 18px;background:#0d6efd;color:#fff;text-decoration:none;border-radius:4px;">'
            . 'Ver perfil SST</a></p>'
            . '<p style="color:#666;font-size:12px;">Mensagem automática do sistema administrativo.</p>'
            . '</div>';
    }

    /**
     * @param array<int, array<string, mixed>> $itens
     */
    private function buildEmailAltBody(string $name, array $itens): string
    {
        $lines = ["Olá, {$name},", '', 'Pendências SST:', ''];
        foreach ($itens as $item) {
            $label = $item['epi_nome'] ?? $item['exame_nome'] ?? $item['treinamento_nome'] ?? 'Item';
            $sit = $item['situacao_label'] ?? $item['situacao'] ?? '';
            $lines[] = "- {$label}: {$sit}";
        }
        $lines[] = '';
        $lines[] = 'Acesse o sistema para mais detalhes.';

        return implode("\n", $lines);
    }
}
