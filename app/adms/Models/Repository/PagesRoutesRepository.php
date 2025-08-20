<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

class PagesRoutesRepository extends DbConnection
{

    public function getPage(string $controller): array|bool
    {
        
        // QUERY para recuperar o registro do banco de dados sobre a página
        $sql = 'SELECT ap.id AS id_ap, ap.directory, ap.public_page, app.name AS name_app
                FROM adms_pages AS ap
                INNER JOIN adms_packages_pages AS app ON app.id=ap.adms_packages_page_id
                WHERE ap.controller = :controller
                AND ap.page_status = 1
                LIMIT 1';

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':controller', $controller, PDO::PARAM_STR);

        // Executar a QUERY
        $stmt->execute();

        // Ler o registro e retornar
        return $stmt->fetch(PDO::FETCH_ASSOC);

    }

    public function checkUserPagePermission(int $pageId)
    {
        // QUERY para verificar a permissão do usuário em relação à página
        $sql = 'SELECT 
                    CASE
                        WHEN aulp.adms_access_level_id = 1 THEN 1
                        ELSE alp.permission
                    END AS permission          
                FROM 
                    adms_users_access_levels AS aulp
                LEFT JOIN 
                    adms_access_levels_pages As alp ON alp.adms_access_level_id = aulp.adms_access_level_id 
                    AND alp.adms_page_id = :adms_page_id
                WHERE 
                    aulp.adms_user_id = :adms_user_id
                    AND (aulp.adms_access_level_id = 1 OR alp.permission = 1)
                LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':adms_user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':adms_page_id', $pageId, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result && isset($result['permission']) && $result['permission'] == 1) ? true : false;
    }

}