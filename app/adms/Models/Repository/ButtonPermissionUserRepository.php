<?php

namespace App\adms\Models\Repository;

use App\adms\Helpers\UserAccessHelper;
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
        // Verificar se o array $button está vazio
        if(empty($button)){
            return [];
        }

        // Super Administrador (nível 1) ou Super usuário (flag no cadastro): acesso total aos botões solicitados
        if (UserAccessHelper::hasFullSystemAccess()) {
            return $button;
        }

        // Reutiliza cache de controllers permitidos (mesma fonte do menu)
        $allowedSet = array_flip((new MenuPermissionUserRepository())->getAllowedControllersForUser());
        $out = [];
        foreach ($button as $controller) {
            if (isset($allowedSet[$controller])) {
                $out[] = $controller;
            }
        }

        return $out;
    }
}
