<?php

declare(strict_types=1);

namespace App\adms\Controllers\sac;

use App\adms\Helpers\CSRFHelper;
use App\adms\Models\Repository\SacTicketsRepository;

class SacRateTicket
{
    public function index(string|int|null $id = null): void
    {
        if (!$id) {
            $_SESSION['msg'] = "ID do chamado não informado.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-list-tickets");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
            exit;
        }

        if (!CSRFHelper::validateCSRFToken('sac_rating_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['msg'] = "Token CSRF inválido. Atualize a página e tente novamente.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $id);
            exit;
        }

        $ticketId = (int)$id;
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            $_SESSION['msg'] = "Avaliação inválida. Selecione de 1 a 5 estrelas.";
            $_SESSION['msg_type'] = "danger";
            header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
            exit;
        }

        $ticketsRepo = new SacTicketsRepository();
        $result = $ticketsRepo->updateTicket($ticketId, [
            'satisfaction_rating' => $rating,
            'satisfaction_comment' => $comment ?: null,
        ]);

        if ($result) {
            $_SESSION['msg'] = "Avaliação registrada com sucesso!";
            $_SESSION['msg_type'] = "success";
        } else {
            $_SESSION['msg'] = "Erro ao registrar avaliação.";
            $_SESSION['msg_type'] = "danger";
        }

        header("Location: " . $_ENV['URL_ADM'] . "sac-view-ticket/" . $ticketId);
        exit;
    }
}
