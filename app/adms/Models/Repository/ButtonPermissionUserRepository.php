<?php

namespace App\adms\Models\Repository;

use App\adms\Models\Services\DbConnection;
use PDO;

/**
 * Repositório para verificar permissões de botões do usuário.
 *
 * Esta classe interage com as tabelas relacionadas ao usuário, níveis de acesso e páginas, 
 * para verificar se o usuário tem permissão para acessar determinadas páginas com base 
 * nos níveis de acesso que possui.
 * 
 * @package App\adms\Models\Repository
 * @author Rafael Mendes
 */
class ButtonPermissionUserRepository extends DbConnection
{
    /**
     * Verifica se o usuário tem permissão para acessar páginas específicas.
     *
     * Este método recebe um array de nomes de controllers (representando as páginas) e verifica
     * quais dessas páginas o usuário tem permissão para acessar com base nos níveis de acesso 
     * atribuídos a ele.
     *
     * @param array $button Array de nomes de controllers (páginas) a serem verificadas.
     * @return array|bool Retorna um array com os nomes dos controllers que o usuário tem permissão de acessar, ou false se não houver permissão.
     */
    public function buttonPermission(array $button): array|bool
    {
        // var_dump($button);
        // return [];

        // Verificar se o array $button está vazio
            if(empty($button)){
                return [];
            }

        // Criar uma string de placeholders do mesmo tamanho do array de controllers
        $placeholders = implode(', ', array_fill(0, count($button), '?'));

        //QUERY para verificar a permissão do usuário em relação às páginas
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

        // Preparar a QUERY
        $stmt = $this->getConnection()->prepare($sql);

        // Combinar o valor do 'user_id' com o array de botões (controllers)
        $params = array_merge([$_SESSION['user_id']], $button);

        // Executar a QUERY com os parâmetros
        $stmt->execute($params);

        // Ler os registros
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Retornar apenas os valores de 'controller' como array simples
        return $result ? array_column($result, 'controller') : [];
    }
}
