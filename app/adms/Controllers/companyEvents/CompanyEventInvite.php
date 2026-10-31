<?php

declare(strict_types=1);

namespace App\adms\Controllers\companyEvents;

use App\adms\Helpers\CompanyEventUploadHelper;
use App\adms\Helpers\CSRFHelper;
use App\adms\Helpers\NotificationOpenHelper;
use App\adms\Models\Repository\CompanyEventExternalInvitesRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Services\CompanyEventExternalInviteMessage;

/**
 * Página aberta do convite. Quem recebe o link não precisa de login.
 */
final class CompanyEventInvite
{
    public function index(string|null $token = null): void
    {
        $token = strtolower(trim((string) $token));
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            $this->renderMissing();
            return;
        }

        $invites = new CompanyEventExternalInvitesRepository();
        $invite = $invites->findByToken($token);
        if ($invite === null) {
            $this->renderMissing();
            return;
        }

        $events = new CompanyEventsRepository();
        $event = $events->getById((int) ($invite['event_id'] ?? 0));
        if ($event === null) {
            $this->renderMissing();
            return;
        }

        $notice = (string) ($_SESSION['external_invite_notice'] ?? '');
        unset($_SESSION['external_invite_notice']);

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $answer = (string) ($_POST['answer'] ?? '');
            if (!CSRFHelper::validateCSRFToken('form_company_event_external_invite_public', (string) ($_POST['csrf_token'] ?? ''))) {
                $notice = 'Não foi possível registrar a resposta. Atualize a página e tente de novo.';
            } elseif (!CompanyEventExternalInviteMessage::eventIsOpen($event)) {
                $notice = 'O evento já encerrou. A resposta não pode mais ser alterada.';
            } elseif (!in_array($answer, ['confirmed', 'declined'], true)) {
                $notice = 'Escolha confirmar ou recusar.';
            } elseif ($invites->setStatusByToken($token, $answer)) {
                $_SESSION['external_invite_notice'] = $answer === 'confirmed'
                    ? 'Presença confirmada. Obrigado.'
                    : 'Presença recusada. Obrigado por responder.';
                header('Location: ' . CompanyEventExternalInviteMessage::inviteUrl($token));
                exit;
            } else {
                $notice = 'Não foi possível salvar a resposta.';
            }
            $invite = $invites->findByToken($token) ?? $invite;
        }

        $imageUrl = '';
        $images = $events->getImagesForEvent((int) $event['id']);
        $firstPath = (string) ($images[0]['image_path'] ?? '');
        if ($firstPath !== '') {
            $imageUrl = NotificationOpenHelper::urlOnCurrentHost(CompanyEventUploadHelper::serveUrl($firstPath));
        }

        $this->render([
            'invite' => $invite,
            'event' => $event,
            'image_url' => $imageUrl,
            'open' => CompanyEventExternalInviteMessage::eventIsOpen($event),
            'excerpt' => CompanyEventExternalInviteMessage::plainExcerpt((string) ($event['description'] ?? '')),
            'notice' => $notice,
            'csrf' => CSRFHelper::generateCSRFToken('form_company_event_external_invite_public'),
        ]);
    }

    private function renderMissing(): void
    {
        http_response_code(404);
        $this->render([
            'missing' => true,
            'invite' => [],
            'event' => [],
            'image_url' => '',
            'open' => false,
            'excerpt' => '',
            'notice' => '',
            'csrf' => '',
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function render(array $data): void
    {
        extract($data, EXTR_SKIP);
        include dirname(__DIR__, 2) . '/Views/companyEvents/invite_public.php';
    }
}
