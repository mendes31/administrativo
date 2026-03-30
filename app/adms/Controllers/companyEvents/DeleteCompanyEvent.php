<?php

namespace App\adms\Controllers\companyEvents;

use App\adms\Models\Repository\CompanyEventsRepository;

class DeleteCompanyEvent
{
    public function index(string|null $id = null): void
    {
        $eventId = (int)($id ?? 0);
        if ($eventId <= 0) {
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $repo = new CompanyEventsRepository();
        $ev = $repo->getById($eventId);
        if (!$ev) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Evento não encontrado.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        if ((int)($ev['created_by'] ?? 0) !== (int)($_SESSION['user_id'] ?? 0)
            && !\App\adms\Helpers\UserAccessHelper::hasFullSystemAccess()) {
            $_SESSION['msg'] = '<div class="alert alert-danger">Sem permissão para excluir.</div>';
            header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
            exit;
        }
        $_SESSION['msg'] = $repo->deleteEvent($eventId)
            ? '<div class="alert alert-success">Evento excluído.</div>'
            : '<div class="alert alert-danger">Erro ao excluir.</div>';
        header('Location: ' . $_ENV['URL_ADM'] . 'list-company-events');
        exit;
    }
}
