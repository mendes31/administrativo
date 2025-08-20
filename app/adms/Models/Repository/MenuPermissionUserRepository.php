<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class MenuPermissionUserRepository extends DbConnection
{

    public function menuPermission(array $menu): array|bool
    {
        if(empty($menu)){
            return [];
        }

        // Se for super admin (nível 1)
        if (isset($_SESSION['user_access_level_id']) && $_SESSION['user_access_level_id'] == 1) {
            $placeholders = implode(', ', array_fill(0, count($menu), '?'));
            $sql = "SELECT controller FROM adms_pages WHERE controller IN ($placeholders) AND page_status = 1";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($menu);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $result ? array_column($result, 'controller') : [];
        }

        // Regra normal para outros usuários
        $placeholders = implode(', ', array_fill(0, count($menu), '?'));
        $sql = "SELECT
                    ap.controller
                FROM 
                    adms_users_access_levels AS aual
                LEFT JOIN
                    adms_access_levels_pages AS alp ON alp.adms_access_level_id = aual.adms_access_level_id
                LEFT JOIN 
                    adms_pages AS ap ON ap.id = alp.adms_page_id
                WHERE 
                    aual.adms_user_id = ?
                    AND ap.controller IN ($placeholders)
                    AND alp.permission = 1";
        $stmt = $this->getConnection()->prepare($sql);
        $params = array_merge([$_SESSION['user_id']], $menu);
        $stmt->execute($params);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result ? array_column($result, 'controller') : [];
    }
}
