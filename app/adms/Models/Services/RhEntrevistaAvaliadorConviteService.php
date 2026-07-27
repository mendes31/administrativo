<?php

declare(strict_types=1);

namespace App\adms\Models\Services;

use App\adms\Helpers\AppEnvironmentHelper;
use App\adms\Helpers\GenerateLog;
use App\adms\Helpers\SendEmailService;
use App\adms\Models\Repository\NotificationsRepository;
use App\adms\Models\Repository\RhEntrevistasRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * Convite in-app + e-mail para avaliadores adicionais da entrevista.
 */
final class RhEntrevistaAvaliadorConviteService
{
    public const ENTITY_TYPE = 'rh_entrevista';

    public const TYPE_INVITE = 'rh_entrevista_avaliador_convite';

    /**
     * @param list<int|string> $avaliadorIds
     */
    public function enviarConvites(int $entrevistaId, array $avaliadorIds, ?int $excludeUserId = null): void
    {
        $ids = [];
        foreach ($avaliadorIds as $raw) {
            $id = (int) $raw;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $ids = array_values($ids);
        if ($entrevistaId <= 0 || $ids === []) {
            return;
        }

        $entrevista = (new RhEntrevistasRepository())->getById($entrevistaId);
        if ($entrevista === null) {
            return;
        }

        $viewLink = $this->buildViewLink($entrevistaId);
        $candidato = trim((string) ($entrevista['candidato_nome'] ?? ''));
        $vaga = trim((string) ($entrevista['vaga_titulo'] ?? ''));
        $dataHora = trim((string) ($entrevista['data_hora'] ?? ''));

        $summaryParts = [];
        if ($candidato !== '') {
            $summaryParts[] = 'Candidato: ' . $candidato;
        }
        if ($vaga !== '') {
            $summaryParts[] = 'Vaga: ' . $vaga;
        }
        if ($dataHora !== '') {
            $summaryParts[] = 'Quando: ' . $dataHora;
        }
        $summary = $summaryParts !== []
            ? implode(' — ', $summaryParts)
            : 'Você foi convidado(a) a avaliar uma entrevista.';

        $title = 'Convite para avaliar entrevista';
        $message = $summary . ' Abra a entrevista para aceitar ou recusar.';

        $usersRepo = new UsersRepository();
        $notifRepo = new NotificationsRepository();

        foreach ($ids as $userId) {
            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            try {
                $notifRepo->create([
                    'user_id' => $userId,
                    'type' => self::TYPE_INVITE,
                    'title' => AppEnvironmentHelper::formatInAppTitle($title),
                    'message' => AppEnvironmentHelper::formatInAppMessage($message),
                    'link_url' => $viewLink,
                    'entity_type' => self::ENTITY_TYPE,
                    'entity_id' => $entrevistaId,
                ]);
            } catch (\Throwable $e) {
                GenerateLog::generateLog('warning', 'Falha ao criar notificação de convite de avaliador.', [
                    'entrevista_id' => $entrevistaId,
                    'avaliador_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->sendEmailIfPossible($usersRepo, $userId, $title, $message, $viewLink);
        }
    }

    public function reenviar(int $entrevistaId, int $avaliadorId): void
    {
        $this->enviarConvites($entrevistaId, [$avaliadorId]);
    }

    private function sendEmailIfPossible(
        UsersRepository $usersRepo,
        int $userId,
        string $subject,
        string $message,
        string $link
    ): void {
        $user = $usersRepo->getUser($userId);
        if (!is_array($user)) {
            return;
        }

        $email = trim((string) ($user['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $name = (string) ($user['name'] ?? $email);
        $prefix = AppEnvironmentHelper::emailSubjectPrefix();
        $bannerHtml = AppEnvironmentHelper::emailHtmlBanner();
        $bannerText = AppEnvironmentHelper::emailTextBanner();

        $bodyHtml = ($bannerHtml ?? '')
            . '<p>Olá, ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '">Abrir entrevista</a></p>';

        $altBody = ($bannerText !== null ? $bannerText . "\n\n" : '')
            . $message . "\n\n" . $link;

        try {
            SendEmailService::sendEmail(
                $email,
                $name,
                $prefix . $subject,
                $bodyHtml,
                $altBody
            );
        } catch (\Throwable $e) {
            GenerateLog::generateLog('warning', 'Falha ao enviar e-mail de convite de avaliador.', [
                'avaliador_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildViewLink(int $entrevistaId): string
    {
        return rtrim((string) ($_ENV['URL_ADM'] ?? ''), '/') . '/rh-entrevistas-view/' . $entrevistaId;
    }
}
