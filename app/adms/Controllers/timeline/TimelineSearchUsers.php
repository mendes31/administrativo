<?php

declare(strict_types=1);

namespace App\adms\Controllers\timeline;

use App\adms\Models\Repository\UsersRepository;

/**
 * Autocomplete para menções (@) na timeline e nos comentários.
 */
class TimelineSearchUsers
{
    public function index(string|int|null $routeParam = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'users' => []]);
            return;
        }

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new UsersRepository();
        $users = $repo->searchUsersForTimeline($q, 12);

        echo json_encode(['success' => true, 'users' => $users]);
    }
}
