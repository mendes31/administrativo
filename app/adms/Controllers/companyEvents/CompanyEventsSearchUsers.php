<?php

declare(strict_types=1);

namespace App\adms\Controllers\companyEvents;

use App\adms\Helpers\CompanyEventRsvpAccessHelper;
use App\adms\Models\Repository\ButtonPermissionUserRepository;
use App\adms\Models\Repository\CompanyEventsRepository;
use App\adms\Models\Repository\UsersRepository;

/**
 * GET JSON: autocomplete de colaboradores para confirmação manual de RSVP (criador/gestão).
 */
class CompanyEventsSearchUsers
{
    public function index(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'users' => []]);
            return;
        }
        $eventId = (int)($_GET['event_id'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        if ($eventId <= 0) {
            http_response_code(422);
            echo json_encode(['success' => false, 'users' => []]);
            return;
        }
        $repo = new CompanyEventsRepository();
        $event = $repo->getById($eventId);
        if (!$event || empty($event['ativo'])) {
            http_response_code(404);
            echo json_encode(['success' => false, 'users' => []]);
            return;
        }
        $permRepo = new ButtonPermissionUserRepository();
        $raw = $permRepo->buttonPermission([
            'UpdateCompanyEvent',
            'DeleteCompanyEvent',
            'CreateCompanyEvent',
            'CompanyEventReport',
        ]);
        $btnPerms = is_array($raw) ? $raw : [];
        $uid = (int)$_SESSION['user_id'];
        if (!CompanyEventRsvpAccessHelper::canAdminRsvpForOthers($event, $uid, $btnPerms)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'users' => []]);
            return;
        }
        $usersRepo = new UsersRepository();
        $users = $usersRepo->searchActiveUsersForAutocomplete($q, 15);
        echo json_encode(['success' => true, 'users' => $users], JSON_UNESCAPED_UNICODE);
    }
}
