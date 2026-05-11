<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\UserAccessHelper;
use App\adms\Models\Services\DbConnection;
use PDO;

class PagesRoutesRepository extends DbConnection
{

    public function getPage(string $controller): array|bool
    {
        // QUERY para recuperar o registro do banco de dados sobre a página
        // Busca pelo campo 'controller' (nome da classe) ou pelo 'controller_url' (slug)
        // LEFT JOIN: pacote órfão ou FK inválida não pode esconder a página (produção).
        $sql = 'SELECT ap.id AS id_ap, ap.controller, ap.controller_url, ap.directory, ap.public_page,
                       COALESCE(app.name, \'adms\') AS name_app
                FROM adms_pages AS ap
                LEFT JOIN adms_packages_pages AS app ON app.id = ap.adms_packages_page_id
                WHERE (ap.controller = :controller OR ap.controller_url = :controller_url)
                AND ap.page_status = 1
                LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':controller', $controller, PDO::PARAM_STR);

        $controllerUrl = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $controller));
        $stmt->bindValue(':controller_url', $controllerUrl, PDO::PARAM_STR);

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca página só pelo slug (controller_url), com o mesmo JOIN tolerante a pacote.
     */
    public function getPageByControllerUrl(string $controllerUrl): array|bool
    {
        $sql = 'SELECT ap.id AS id_ap, ap.controller, ap.controller_url, ap.directory, ap.public_page,
                       COALESCE(app.name, \'adms\') AS name_app
                FROM adms_pages AS ap
                LEFT JOIN adms_packages_pages AS app ON app.id = ap.adms_packages_page_id
                WHERE ap.controller_url = :controller_url
                AND ap.page_status = 1
                LIMIT 1';

        $stmt = $this->getConnection()->prepare($sql);
        $stmt->bindValue(':controller_url', $controllerUrl, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkUserPagePermission(int $pageId)
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

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

    /**
     * Permissão se o utilizador tiver pelo menos uma das páginas (controllers) com permission=1.
     * Usado quando a rota é a listagem mas o nível de acesso concede só "visualizar/editar" (ex.: salas de reunião).
     *
     * @param list<string> $controllers Nomes de classe em PascalCase (coluna adms_pages.controller)
     */
    public function checkUserAnyPagePermissionForControllers(array $controllers): bool
    {
        if (UserAccessHelper::hasFullSystemAccess()) {
            return true;
        }

        $controllers = array_values(array_unique(array_filter(array_map('strval', $controllers))));
        if ($controllers === []) {
            return false;
        }

        $placeholders = implode(', ', array_fill(0, count($controllers), '?'));
        $sql = "SELECT 1
                FROM adms_users_access_levels AS aual
                INNER JOIN adms_access_levels_pages AS alp
                    ON alp.adms_access_level_id = aual.adms_access_level_id
                    AND alp.permission = 1
                INNER JOIN adms_pages AS ap ON ap.id = alp.adms_page_id AND ap.page_status = 1
                WHERE aual.adms_user_id = ?
                  AND ap.controller IN ($placeholders)
                LIMIT 1";

        $stmt = $this->getConnection()->prepare($sql);
        $params = array_merge([(int) ($_SESSION['user_id'] ?? 0)], $controllers);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

}