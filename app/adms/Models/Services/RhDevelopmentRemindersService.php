<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\InternalPushNotificationHelper;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Digest de lembretes de desenvolvimento/clima — Fase 6 (sem IA).
 */
class RhDevelopmentRemindersService extends DbConnection
{
    private const NOTIF_TYPE = 'rh_dev_reminders_digest';
    private const CACHE_DIR = 'storage/cache/system';

    /**
     * @return array{
     *   open_pulse_campaigns: int,
     *   overdue_pdi_actions: int,
     *   draft_reviews_open_cycle: int,
     *   recipients: list<array<string, mixed>>
     * }
     */
    public function collectSummary(int $recipientLimit = 100): array
    {
        return [
            'open_pulse_campaigns' => $this->countOpenPulseCampaigns(),
            'overdue_pdi_actions' => $this->countOverduePdiActions(),
            'draft_reviews_open_cycle' => $this->countDraftReviewsInOpenCycles(),
            'recipients' => $this->listRecipientCandidates($recipientLimit),
        ];
    }

    /**
     * @return array{dry_run: bool, disabled: bool, sent: int, skipped: int, failed: int, summary: array<string, mixed>, errors: list<string>}
     */
    public function run(bool $send, int $limit = 100): array
    {
        $summary = $this->collectSummary($limit);
        $result = [
            'dry_run' => !$send,
            'disabled' => false,
            'sent' => 0,
            'skipped' => 0,
            'failed' => 0,
            'summary' => $summary,
            'errors' => [],
        ];

        if (!$send) {
            return $result;
        }

        if (!$this->isAnyChannelEnabled()) {
            $result['disabled'] = true;
            return $result;
        }

        foreach ($summary['recipients'] as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            if ($userId <= 0) {
                $result['skipped']++;
                continue;
            }
            if ($this->wasNotifiedToday($userId)) {
                $result['skipped']++;
                continue;
            }

            $name = (string) ($row['name'] ?? 'Colaborador');
            $email = trim((string) ($row['email'] ?? ''));
            $lines = $this->buildItemLines($row);
            if ($lines === []) {
                $result['skipped']++;
                continue;
            }

            $sentSomething = false;
            if ($email !== '' && NotificationSettingsService::isEnabled('rh_dev_reminders_email')) {
                $ok = SendEmailService::sendEmail(
                    $email,
                    $name,
                    'Lembretes de desenvolvimento — ação necessária',
                    $this->buildEmailHtml($name, $lines),
                    $this->buildEmailText($name, $lines)
                );
                if ($ok) {
                    $sentSomething = true;
                } else {
                    $result['failed']++;
                    $result['errors'][] = "Falha e-mail user_id={$userId}";
                }
            }

            if (NotificationSettingsService::isEnabled('rh_dev_reminders_inapp')) {
                try {
                    InternalPushNotificationHelper::notifyUser([
                        'user_id' => $userId,
                        'type' => self::NOTIF_TYPE,
                        'title' => 'Lembretes de desenvolvimento',
                        'message' => implode("\n", $lines),
                        'link_url' => rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/list-pdi-plans',
                        'entity_type' => 'rh_dev_reminders',
                        'entity_id' => $userId,
                        'force' => true,
                    ]);
                    $sentSomething = true;
                } catch (\Throwable $e) {
                    $result['errors'][] = 'In-app user_id=' . $userId . ': ' . $e->getMessage();
                }
            }

            if ($sentSomething) {
                $this->markNotifiedToday($userId);
                $result['sent']++;
                GenerateLog::generateLog('info', 'Digest desenvolvimento enviado.', ['user_id' => $userId]);
            } else {
                $result['skipped']++;
            }
        }

        return $result;
    }

    private function isAnyChannelEnabled(): bool
    {
        return NotificationSettingsService::isEnabled('rh_dev_reminders_email')
            || NotificationSettingsService::isEnabled('rh_dev_reminders_inapp');
    }

    private function countOpenPulseCampaigns(): int
    {
        if (!$this->tableExists('adms_pulse_campaigns')) {
            return 0;
        }
        $stmt = $this->getConnection()->query(
            "SELECT COUNT(*) FROM adms_pulse_campaigns WHERE status = 'open'"
        );

        return (int) $stmt->fetchColumn();
    }

    private function countOverduePdiActions(): int
    {
        if (!$this->tableExists('adms_pdi_actions') || !$this->tableExists('adms_pdi_plans')) {
            return 0;
        }
        $sql = "SELECT COUNT(*)
                FROM adms_pdi_actions a
                INNER JOIN adms_pdi_plans p ON p.id = a.pdi_plan_id
                WHERE p.status = 'active'
                  AND a.end_date IS NOT NULL
                  AND a.end_date < CURDATE()
                  AND a.status IN ('pending', 'in_progress')";
        $stmt = $this->getConnection()->query($sql);

        return (int) $stmt->fetchColumn();
    }

    private function countDraftReviewsInOpenCycles(): int
    {
        if (!$this->tableExists('adms_performance_reviews') || !$this->tableExists('adms_performance_cycles')) {
            return 0;
        }
        $sql = "SELECT COUNT(*)
                FROM adms_performance_reviews r
                INNER JOIN adms_performance_cycles c ON c.id = r.performance_cycle_id
                WHERE r.status = 'draft' AND c.status = 'open'";
        $stmt = $this->getConnection()->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listRecipientCandidates(int $limit): array
    {
        $limit = max(1, min(500, $limit));
        $map = [];

        if ($this->tableExists('adms_pdi_actions') && $this->tableExists('adms_pdi_plans')) {
            $sql = "SELECT u.id AS user_id, u.name, u.email,
                           COUNT(*) AS overdue_pdi_actions
                    FROM adms_pdi_actions a
                    INNER JOIN adms_pdi_plans p ON p.id = a.pdi_plan_id
                    INNER JOIN adms_users u ON u.id = p.user_id
                    WHERE p.status = 'active'
                      AND a.end_date IS NOT NULL
                      AND a.end_date < CURDATE()
                      AND a.status IN ('pending', 'in_progress')
                      AND u.status = 'Ativo'
                    GROUP BY u.id, u.name, u.email";
            foreach ($this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $id = (int) $row['user_id'];
                $map[$id] = array_merge($map[$id] ?? [
                    'user_id' => $id,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'overdue_pdi_actions' => 0,
                    'draft_reviews' => 0,
                ], [
                    'overdue_pdi_actions' => (int) $row['overdue_pdi_actions'],
                ]);
            }
        }

        if ($this->tableExists('adms_performance_reviews') && $this->tableExists('adms_performance_cycles')) {
            $sql = "SELECT u.id AS user_id, u.name, u.email,
                           COUNT(*) AS draft_reviews
                    FROM adms_performance_reviews r
                    INNER JOIN adms_performance_cycles c ON c.id = r.performance_cycle_id
                    INNER JOIN adms_users u ON u.id = r.employee_id
                    WHERE r.status = 'draft' AND c.status = 'open' AND u.status = 'Ativo'
                    GROUP BY u.id, u.name, u.email";
            foreach ($this->getConnection()->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $id = (int) $row['user_id'];
                $base = $map[$id] ?? [
                    'user_id' => $id,
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'overdue_pdi_actions' => 0,
                    'draft_reviews' => 0,
                ];
                $base['draft_reviews'] = (int) $row['draft_reviews'];
                $base['name'] = $row['name'];
                $base['email'] = $row['email'];
                $map[$id] = $base;
            }
        }

        $list = array_values($map);
        usort($list, static fn (array $a, array $b): int =>
            (($b['overdue_pdi_actions'] ?? 0) + ($b['draft_reviews'] ?? 0))
            <=> (($a['overdue_pdi_actions'] ?? 0) + ($a['draft_reviews'] ?? 0))
        );

        return array_slice($list, 0, $limit);
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function buildItemLines(array $row): array
    {
        $lines = [];
        $pdi = (int) ($row['overdue_pdi_actions'] ?? 0);
        $rev = (int) ($row['draft_reviews'] ?? 0);
        if ($pdi > 0) {
            $lines[] = "{$pdi} ação(ões) de PDI com prazo vencido";
        }
        if ($rev > 0) {
            $lines[] = "{$rev} avaliação(ões) em rascunho no ciclo aberto";
        }

        return $lines;
    }

    /** @param list<string> $lines */
    private function buildEmailHtml(string $name, array $lines): string
    {
        $items = '';
        foreach ($lines as $line) {
            $items .= '<li>' . htmlspecialchars($line) . '</li>';
        }
        $url = htmlspecialchars((string) ($_ENV['URL_ADM'] ?? ''));

        return "<p>Olá " . htmlspecialchars($name) . ",</p>"
            . "<p>Há pendências de desenvolvimento que precisam da sua atenção:</p>"
            . "<ul>{$items}</ul>"
            . "<p><a href=\"{$url}\">Acessar o sistema</a></p>";
    }

    /** @param list<string> $lines */
    private function buildEmailText(string $name, array $lines): string
    {
        return "Olá {$name},\n\nPendências:\n- " . implode("\n- ", $lines) . "\n";
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->getConnection()->prepare(
            'SELECT COUNT(*) FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :t'
        );
        $stmt->bindValue(':t', $table);
        $stmt->execute();

        return (int) $stmt->fetchColumn() > 0;
    }

    private function wasNotifiedToday(int $userId): bool
    {
        $path = $this->throttlePath($userId);
        if (!is_file($path)) {
            return false;
        }
        $raw = (string) file_get_contents($path);
        $data = json_decode($raw, true);
        return is_array($data) && ($data['date'] ?? '') === date('Y-m-d');
    }

    private function markNotifiedToday(int $userId): void
    {
        $dir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::CACHE_DIR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->throttlePath($userId), json_encode(['date' => date('Y-m-d'), 'user_id' => $userId]));
    }

    private function throttlePath(int $userId): string
    {
        $dir = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, self::CACHE_DIR);

        return $dir . DIRECTORY_SEPARATOR . 'rh_dev_reminders_' . $userId . '.json';
    }
}
