<?php

namespace App\adms\Controllers\accessLevels;

use App\adms\Controllers\Services\AccessLevelPageSyncService;

/**
 * Controller responsável por iniciar a sincronização de níveis de acesso com as páginas.
 *
 * Esta classe utiliza o serviço `AccessLevelPageSyncService` para executar a lógica de sincronização entre
 * níveis de acesso e suas respectivas permissões de página. Após a sincronização, o resultado é armazenado em 
 * uma variável de sessão, e o usuário é redirecionado para a página de listagem de níveis de acesso.
 *
 * @package App\adms\Controllers\accessLevels
 */
class AccessLevelPageSync
{
    /**
     * Executa a sincronização de níveis de acesso com as páginas.
     *
     * Este método instância o serviço `AccessLevelPageSyncService`, que realiza a sincronização entre níveis de
     * acesso e páginas. Dependendo do resultado, uma mensagem de sucesso ou erro é armazenada na sessão, e o 
     * usuário é redirecionado para a página de listagem de níveis de acesso.
     *
     * @return void
     */
    public function index(): void
    {
        // Instanciar o serviço sincronizar nível de acesso com página
        $accessLevelPage = new AccessLevelPageSyncService();
        $resultAccessLevelPage = $accessLevelPage->accessLevelPageSync();

        // Se a sincronização entre nível de acesso e página for bem-sucedida
        if ($resultAccessLevelPage) {
            // Mensagem de sucesso
            $_SESSION['success'] = "Sincronização entre nível de acesso e página realizada com sucesso!";
        } else {
            // Mensagem de erro
            $_SESSION['error'] = "Sincronização entre nível de acesso e página não realizada com sucesso!";
        }

        // Redirecionar para a página listar nível de acesso
        header("Location: {$_ENV['URL_ADM']}list-access-levels");
        return;
    }
}
