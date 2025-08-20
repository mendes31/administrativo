<?php

namespace App\adms\Controllers\Services;

use App\adms\Models\Repository\AccessLevelsPagesRepository;
use App\adms\Models\Repository\AccessLevelsRepository;
use App\adms\Models\Repository\PagesRepository;

/**
 * Serviço responsável pela sincronização de níveis de acesso com as páginas.
 *
 * Esta classe executa a lógica de sincronização entre os níveis de acesso e suas permissões de página.
 * Ela verifica quais páginas não estão associadas a determinados níveis de acesso e realiza a atualização
 * no banco de dados, garantindo que cada nível de acesso tenha as permissões corretas.
 *
 * @package App\adms\Controllers\Services
 */
class AccessLevelPageSyncService
{
    /**
     * Sincroniza os níveis de acesso com suas respectivas páginas.
     *
     * Este método obtém todas as páginas disponíveis no sistema e compara com as permissões associadas a cada
     * nível de acesso. Se um nível de acesso não possui permissão para uma página, essa permissão é adicionada.
     * A operação final é feita em lote através do repositório `AccessLevelsPagesRepository`.
     *
     * @return bool Retorna `true` se a sincronização foi bem-sucedida, ou `false` em caso de erro.
     */
    public function accessLevelPageSync(): bool
    {
        // Recuperar todas as páginas em um array.
        $pages = new PagesRepository();
        $resultPages = $pages->getPagesArray();

        // Instanciar o Repository para recuperar os níveis de acesso
        $accessLevels = new AccessLevelsRepository();
        $resultAccessLevels = $accessLevels->getAllAccessLevelsSelect();
        
        // Array para armazenar as páginas do nível de acesso
        $accessLevelPages = [];

        // Percorrer os níveis de acesso e recuperar as permissões cadastradas no banco de dados
        foreach ($resultAccessLevels as $accessLevel) {
            extract($accessLevel);

            // Recuperar todas as páginas do nível de acesso em um array.
            $accessLevelsPages = new AccessLevelsPagesRepository();
            $resultAccessLevelsPages = $accessLevelsPages->getPagesAccessLevelsArray($id);

            // Atribuir no array as páginas do nível de acesso
            $accessLevelPages[$id] = $resultAccessLevelsPages ? $resultAccessLevelsPages : [];
        }

        // Percorrer as páginas do nível de acesso e verificar se o nível de acesso tem a permissão cadastrada para a página
        foreach ($accessLevelPages as $accessLevelId => $accessLevelPages) {
            // Comparar as páginas que o nível de acesso não possui permissão e criar o array com essas páginas
            $noAccessLevelPages[$accessLevelId] = array_values(array_diff($resultPages, $accessLevelPages));
        }

        // Chamar o método do repositório cadastrar página para o nível de acesso
        return $accessLevelsPages->createPagesAccessLevel($noAccessLevelPages);
    }
}
