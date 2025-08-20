<?php

namespace App\adms\Controllers\errors;

/**
 * Classe Error403
 * 
 * Esta classe é responsável por exibir uma página de erro 403, que indica que o acesso à página solicitada 
 * é proibido. É usada para mostrar uma mensagem de erro amigável quando um usuário tenta acessar uma 
 * página para a qual não tem permissão.
 * 
 * @package App\adms\Controllers\errors
 * @author Rafael Mendes
 */
class Error403
{
    /**
     * Exibir a página de erro 403
     * 
     * Este método exibe uma mensagem simples de "Acesso Proibido" (Erro 403), informando ao usuário que ele 
     * não tem permissão para acessar a página solicitada.
     * 
     * @return void
     */
    public function index()
    {
        echo "<h1>403 - Acesso negado</h1>";
        echo "<p>Você não tem permissão para acessar esta pagina.</p>";

    }
      
}