<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Models\Repository\RhEntrevistaComunicacoesRepository;

/**
 * Preflight de intenções de comunicação de entrevista (sem SMTP).
 * Avalia recorded → ready|blocked; apply só com flag explícita de ambiente.
 */
final class RhEntrevistaComunicacaoPreflightService
{
    /**
     * @return array{
     *   dry_run: bool,
     *   limit: int,
     *   totals: array{scanned: int, ready: int, blocked: int, applied: int, skipped: int},
     *   items: list<array{id: int, entrevista_id: int, decision: string, reasons: list<string>}>
     * }
     */
    public function run(int $limit = 20, bool $apply = false): array
    {
        $repo = new RhEntrevistaComunicacoesRepository();
        $rows = $repo->listRecordedForPreflight($limit);

        $totals = [
            'scanned' => 0,
            'ready' => 0,
            'blocked' => 0,
            'applied' => 0,
            'skipped' => 0,
        ];
        $items = [];

        foreach ($rows as $row) {
            $totals['scanned']++;
            $eval = $this->evaluate($row);
            $decision = $eval['decision'];
            $reasons = $eval['reasons'];

            if ($decision === RhEntrevistaComunicacoesRepository::STATUS_READY) {
                $totals['ready']++;
            } else {
                $totals['blocked']++;
            }

            $applied = false;
            if ($apply) {
                $error = $decision === RhEntrevistaComunicacoesRepository::STATUS_BLOCKED
                    ? implode('; ', $reasons)
                    : null;
                $applied = $repo->updateStatus((int) $row['id'], $decision, $error);
                if ($applied) {
                    $totals['applied']++;
                } else {
                    $totals['skipped']++;
                }
            }

            $items[] = [
                'id' => (int) $row['id'],
                'entrevista_id' => (int) $row['rh_entrevista_id'],
                'decision' => $decision,
                'reasons' => $reasons,
                'applied' => $applied,
            ];
        }

        return [
            'dry_run' => !$apply,
            'limit' => $limit,
            'totals' => $totals,
            'items' => $items,
        ];
    }

    public static function isApplyEnabled(): bool
    {
        $raw = strtolower(trim((string) ($_ENV['RH_ENTREVISTA_PREFLIGHT_APPLY'] ?? getenv('RH_ENTREVISTA_PREFLIGHT_APPLY') ?: '')));

        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $row
     * @return array{decision: string, reasons: list<string>}
     */
    public function evaluate(array $row): array
    {
        $reasons = [];

        $subject = trim((string) ($row['subject_snapshot'] ?? ''));
        if ($subject === '') {
            $reasons[] = 'subject_snapshot vazio';
        }

        if (empty($row['has_body_html'])) {
            $reasons[] = 'body_html_snapshot vazio';
        }

        $email = trim((string) ($row['recipient_address'] ?? ''));
        if ($email === '') {
            $reasons[] = 'recipient_address ausente';
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $reasons[] = 'recipient_address inválido';
        }

        $outboxId = (int) ($row['outbox_event_id'] ?? 0);
        if ($outboxId <= 0) {
            $reasons[] = 'outbox_event_id ausente';
        } elseif (empty($row['event_name'])) {
            $reasons[] = 'evento outbox não encontrado';
        } elseif (($row['outbox_status'] ?? '') !== 'pending') {
            $reasons[] = 'outbox_status diferente de pending';
        }

        $purpose = (string) ($row['purpose'] ?? '');
        if (!in_array($purpose, [
            RhEntrevistaComunicacoesRepository::PURPOSE_AGENDAMENTO,
            RhEntrevistaComunicacoesRepository::PURPOSE_REAGENDAMENTO,
        ], true)) {
            $reasons[] = 'purpose inválido';
        }

        if ($reasons === []) {
            return [
                'decision' => RhEntrevistaComunicacoesRepository::STATUS_READY,
                'reasons' => ['ok para fila de envio futura'],
            ];
        }

        return [
            'decision' => RhEntrevistaComunicacoesRepository::STATUS_BLOCKED,
            'reasons' => $reasons,
        ];
    }
}
