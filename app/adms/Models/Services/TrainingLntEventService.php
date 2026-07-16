<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\AppEnvironmentHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\DepartmentsRepository;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\PositionsRepository;
use App\adms\Models\Repository\TrainingLntEventsRepository;
use App\adms\Models\Repository\UsersRepository;

class TrainingLntEventService
{
    private TrainingLntEventsRepository $eventsRepo;

    public function __construct(?TrainingLntEventsRepository $eventsRepo = null)
    {
        $this->eventsRepo = $eventsRepo ?? new TrainingLntEventsRepository();
    }

    public function registerNovoColaborador(int $userId, ?int $actorUserId = null): void
    {
        $snapshot = $this->buildUserSnapshot($userId);
        if ($snapshot === null) {
            return;
        }

        $this->registerAndNotify([
            'event_type' => TrainingLntEventsRepository::TYPE_NOVO_COLABORADOR,
            'action_label' => 'Novo colaborador',
            'user_id' => $userId,
            'position_id' => $snapshot['position_id'],
            'collaborator_name' => $snapshot['name'],
            'collaborator_cpf' => $snapshot['cpf'],
            'department_name' => $snapshot['department_name'],
            'position_name' => $snapshot['position_name'],
            'data_admissao' => $snapshot['data_admissao'],
            'data_desligamento' => null,
            'details_json' => json_encode(['status' => $snapshot['status'] ?? null], JSON_UNESCAPED_UNICODE),
            'actor_user_id' => $actorUserId,
        ]);
    }

    public function registerColaboradorDesligado(int $userId, ?int $actorUserId = null, ?array $before = null): void
    {
        $snapshot = $this->buildUserSnapshot($userId, $before);
        if ($snapshot === null) {
            return;
        }

        $this->registerAndNotify([
            'event_type' => TrainingLntEventsRepository::TYPE_COLABORADOR_DESLIGADO,
            'action_label' => 'Colaborador desligado',
            'user_id' => $userId,
            'position_id' => $snapshot['position_id'],
            'collaborator_name' => $snapshot['name'],
            'collaborator_cpf' => $snapshot['cpf'],
            'department_name' => $snapshot['department_name'],
            'position_name' => $snapshot['position_name'],
            'data_admissao' => $snapshot['data_admissao'],
            'data_desligamento' => $snapshot['data_desligamento'],
            'details_json' => json_encode([
                'motivo_desligamento' => $snapshot['motivo_desligamento'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'actor_user_id' => $actorUserId,
        ]);
    }

    public function registerAlteracaoCargo(int $userId, ?int $cargoAnteriorId, ?int $cargoNovoId, ?int $actorUserId = null): void
    {
        $snapshot = $this->buildUserSnapshot($userId);
        if ($snapshot === null) {
            return;
        }

        $positionsRepo = new PositionsRepository();
        $cargoAnterior = $cargoAnteriorId ? $positionsRepo->getPosition((int)$cargoAnteriorId) : null;
        $cargoNovo = $cargoNovoId ? $positionsRepo->getPosition((int)$cargoNovoId) : null;

        $this->registerAndNotify([
            'event_type' => TrainingLntEventsRepository::TYPE_ALTERACAO_CARGO,
            'action_label' => 'Alteração de cargo',
            'user_id' => $userId,
            'position_id' => $cargoNovoId,
            'collaborator_name' => $snapshot['name'],
            'collaborator_cpf' => $snapshot['cpf'],
            'department_name' => $snapshot['department_name'],
            'position_name' => $snapshot['position_name'],
            'data_admissao' => $snapshot['data_admissao'],
            'data_desligamento' => null,
            'details_json' => json_encode([
                'cargo_anterior' => is_array($cargoAnterior) ? ($cargoAnterior['name'] ?? null) : null,
                'cargo_novo' => is_array($cargoNovo) ? ($cargoNovo['name'] ?? null) : null,
            ], JSON_UNESCAPED_UNICODE),
            'actor_user_id' => $actorUserId,
        ]);
    }

    public function registerNovoCargo(int $positionId, ?int $actorUserId = null): void
    {
        $positionsRepo = new PositionsRepository();
        $position = $positionsRepo->getPosition($positionId);
        if (!$position || !is_array($position)) {
            return;
        }

        $this->registerAndNotify([
            'event_type' => TrainingLntEventsRepository::TYPE_NOVO_CARGO,
            'action_label' => 'Novo cargo',
            'user_id' => null,
            'position_id' => $positionId,
            'collaborator_name' => null,
            'collaborator_cpf' => null,
            'department_name' => null,
            'position_name' => (string)($position['name'] ?? ''),
            'data_admissao' => null,
            'data_desligamento' => null,
            'details_json' => json_encode([
                'cargo_codigo' => $position['codigo'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'actor_user_id' => $actorUserId,
        ]);
    }

    /**
     * Envia relatório diário (dia anterior) para a equipe de treinamentos.
     *
     * @return array{sent:int,events:int,recipients:int,disabled:bool}
     */
    public function sendDailyDigest(?string $referenceDate = null): array
    {
        $result = ['sent' => 0, 'events' => 0, 'recipients' => 0, 'disabled' => false];

        if (!NotificationSettingsService::isEnabled('training_lnt_event_digest_email')) {
            $result['disabled'] = true;
            return $result;
        }

        $refDate = $referenceDate ?: date('Y-m-d', strtotime('-1 day'));
        $events = $this->eventsRepo->getPendingDigestForDate($refDate);
        if ($events === []) {
            return $result;
        }

        $recipients = $this->eventsRepo->getTrainingTeamRecipients();
        if ($recipients === []) {
            return $result;
        }

        $result['events'] = count($events);
        $result['recipients'] = count($recipients);

        // Prefixo [TESTE] e banner: aplicados em SendEmailService só se for base local/teste
        $subject = 'Relatório LNT — eventos de RH de ' . date('d/m/Y', strtotime($refDate));
        $body = $this->buildDigestEmailHtml($events, $refDate);
        $altBody = $this->buildDigestEmailText($events, $refDate);

        $ids = [];
        foreach ($recipients as $recipient) {
            $email = trim((string)($recipient['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $ok = SendEmailService::sendEmail(
                $email,
                (string)($recipient['name'] ?? 'Equipe de Treinamentos'),
                $subject,
                $body,
                $altBody
            );
            if ($ok) {
                $result['sent']++;
            }
        }

        foreach ($events as $event) {
            $ids[] = (int)$event['id'];
        }
        if ($result['sent'] > 0) {
            $this->eventsRepo->markDigestSent($ids);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function registerAndNotify(array $payload): void
    {
        try {
            $eventId = $this->eventsRepo->insert($payload);
            if ($eventId <= 0) {
                return;
            }

            if (NotificationSettingsService::isEnabled('training_lnt_event_inapp')) {
                if ($this->notifyInApp($eventId, $payload)) {
                    $this->eventsRepo->markInappNotified($eventId);
                }
            }
        } catch (\Throwable $e) {
            GenerateLog::generateLog('error', 'Falha ao registrar evento LNT.', [
                'event_type' => $payload['event_type'] ?? '',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function notifyInApp(int $eventId, array $payload): bool
    {
        $actorUserId = isset($payload['actor_user_id']) ? (int)$payload['actor_user_id'] : null;
        $recipients = $this->eventsRepo->getTrainingTeamRecipients(
            $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null
        );
        if ($recipients === []) {
            return false;
        }

        $title = AppEnvironmentHelper::inAppTitlePrefix()
            . (string)($payload['action_label'] ?? 'Evento LNT');
        $name = (string)($payload['collaborator_name'] ?? $payload['position_name'] ?? '');
        $message = $name !== ''
            ? $title . ': ' . $name
            : $title;

        $notificationsRepo = new NotificationsRepository();
        $link = ($_ENV['URL_ADM'] ?? '') . 'list-training-lnt-events';

        foreach ($recipients as $recipient) {
            $notificationsRepo->create([
                'user_id' => (int)$recipient['id'],
                'type' => 'training_lnt_event',
                'title' => $title,
                'message' => $message,
                'link_url' => $link,
                'entity_type' => 'training_lnt_event',
                'entity_id' => $eventId,
                'priority' => NotificationsRepository::PRIORITY_DEFAULT,
            ]);
        }

        return true;
    }

    /**
     * @param array<string, mixed>|null $fallbackUser
     * @return array<string, mixed>|null
     */
    private function buildUserSnapshot(int $userId, ?array $fallbackUser = null): ?array
    {
        $usersRepo = new UsersRepository();
        $user = $usersRepo->getUser($userId);
        if ((!$user || !is_array($user)) && $fallbackUser) {
            $user = $fallbackUser;
        }
        if (!$user || !is_array($user)) {
            return null;
        }

        $departmentName = null;
        $positionName = null;
        if (!empty($user['user_department_id'])) {
            $depRepo = new DepartmentsRepository();
            $dep = $depRepo->getDepartment((int)$user['user_department_id']);
            $departmentName = is_array($dep) ? ($dep['name'] ?? null) : null;
        }
        if (!empty($user['user_position_id'])) {
            $posRepo = new PositionsRepository();
            $pos = $posRepo->getPosition((int)$user['user_position_id']);
            $positionName = is_array($pos) ? ($pos['name'] ?? null) : null;
        }

        return [
            'name' => (string)($user['name'] ?? ''),
            'cpf' => $this->formatCpf((string)($user['cpf'] ?? '')),
            'department_name' => $departmentName,
            'position_name' => $positionName,
            'position_id' => !empty($user['user_position_id']) ? (int)$user['user_position_id'] : null,
            'data_admissao' => !empty($user['data_admissao']) ? (string)$user['data_admissao'] : null,
            'data_desligamento' => !empty($user['data_desligamento']) ? (string)$user['data_desligamento'] : null,
            'motivo_desligamento' => $user['motivo_desligamento'] ?? null,
            'status' => $user['status'] ?? null,
        ];
    }

    private function formatCpf(string $cpf): string
    {
        $digits = preg_replace('/\D/', '', $cpf) ?: '';
        if (strlen($digits) !== 11) {
            return $cpf;
        }

        return substr($digits, 0, 3) . '.'
            . substr($digits, 3, 3) . '.'
            . substr($digits, 6, 3) . '-'
            . substr($digits, 9, 2);
    }

    /**
     * @param array<int, array<string, mixed>> $events
     */
    private function buildDigestEmailHtml(array $events, string $refDate): string
    {
        $rows = '';
        foreach ($events as $event) {
            $rows .= '<tr>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars((string)($event['action_label'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars((string)($event['collaborator_name'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars((string)($event['collaborator_cpf'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars((string)($event['department_name'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . htmlspecialchars((string)($event['position_name'] ?? '-'), ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->formatDateBr($event['data_admissao'] ?? null) . '</td>'
                . '<td style="border:1px solid #ccc;padding:4px;">' . $this->formatDateBr($event['data_desligamento'] ?? null) . '</td>'
                . '</tr>';
        }

        $refBr = date('d/m/Y', strtotime($refDate));

        return '<p>Relatório diário de eventos de RH para apoio ao <strong>LNT</strong> — referência: <strong>' . $refBr . '</strong>.</p>'
            . '<table style="border-collapse:collapse;font-size:12px;" width="100%">'
            . '<thead><tr style="background:#f0f0f0;">'
            . '<th style="border:1px solid #ccc;padding:4px;">Ação</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">Nome</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">CPF</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">Setor</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">Cargo</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">Admissão</th>'
            . '<th style="border:1px solid #ccc;padding:4px;">Desligamento</th>'
            . '</tr></thead><tbody>' . $rows . '</tbody></table>'
            . '<p style="font-size:11px;color:#666;">Acesse o sistema em Treinamentos → Eventos LNT (RH) para detalhes e geração de LNT.</p>';
    }

    /**
     * @param array<int, array<string, mixed>> $events
     */
    private function buildDigestEmailText(array $events, string $refDate): string
    {
        $lines = ['Relatório LNT — eventos de RH de ' . date('d/m/Y', strtotime($refDate)), ''];
        foreach ($events as $event) {
            $lines[] = sprintf(
                '%s | %s | CPF %s | %s | %s | Adm %s | Desl %s',
                $event['action_label'] ?? '',
                $event['collaborator_name'] ?? '-',
                $event['collaborator_cpf'] ?? '-',
                $event['department_name'] ?? '-',
                $event['position_name'] ?? '-',
                $this->formatDateBr($event['data_admissao'] ?? null),
                $this->formatDateBr($event['data_desligamento'] ?? null)
            );
        }

        return implode("\n", $lines);
    }

    private function formatDateBr(?string $date): string
    {
        if ($date === null || $date === '' || $date === '0000-00-00') {
            return '-';
        }

        $ts = strtotime($date);
        return $ts ? date('d/m/Y', $ts) : '-';
    }
}
