<?php

namespace App\adms\Helpers;

/**
 * Classe para gerar e validar tokens CSRF.
 *
 * Esta classe fornece métodos estáticos para gerar e validar tokens CSRF (Cross-Site Request Forgery).
 * Os tokens CSRF são usados para proteger contra ataques de falsificação de solicitação entre sites, garantindo
 * que as solicitações sejam feitas apenas pelo usuário autenticado e autorizado.
 *
 * @package App\adms\Helpers
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class CSRFHelper
{
    /**
     * Gerar um token CSRF único.
     *
     * Este método gera um token CSRF único para um formulário específico. O token é salvo na sessão e retornado
     * para ser incluído no formulário como um campo oculto.
     *
     * @param string $formIdentifier Identificador do formulário. Usado para distinguir tokens de diferentes formulários.
     * @return string Token CSRF gerado. Um valor hexadecimal único.
     */
    public static function generateCSRFToken(string $formIdentifier): string
    {
        // Garantir que a sessão tenha o array de tokens CSRF
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        // Reutilizar token ainda válido (não consumido) para o mesmo formulário.
        // Evita invalidar o envio quando o utilizador tem várias abas abertas ou recarrega a página.
        if (!empty($_SESSION['csrf_tokens'][$formIdentifier])) {
            return $_SESSION['csrf_tokens'][$formIdentifier];
        }

        // A função random_bytes gera uma sequência de 32 bytes aleatórios.
        // A função bin2hex converte os bytes binários gerados pela random_bytes em uma representação hexadecimal.
        $token = bin2hex(random_bytes(32));

        // Salvar o TOKEN CSRF na sessão
        $_SESSION['csrf_tokens'][$formIdentifier] = $token;
        
        // Log para debug
        file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
            date('Y-m-d H:i:s') . " - [CSRF] TOKEN GERADO - form: {$formIdentifier}, token: {$token}\n", 
            FILE_APPEND
        );

        // Retornar o token gerado
        return $token;
    }

    /**
     * Validar um token CSRF.
     *
     * Este método valida um token CSRF recebido em uma solicitação comparando-o com o token armazenado na sessão.
     * Após a validação, o token é invalidado para evitar reutilização.
     *
     * @param string $formIdentifier Identificador do formulário. Usado para localizar o token CSRF na sessão.
     * @param string $token Token CSRF para validar. O token recebido do formulário.
     * @return bool Retorna true se o token for válido e coincidir com o token armazenado na sessão; false caso contrário.
     */
     public static function validateCSRFToken(string $formIdentifier, string $token)
     {
        // Log detalhado para debug
        file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
            date('Y-m-d H:i:s') . " - [CSRF] VALIDANDO - form: {$formIdentifier}, token_recebido: {$token}\n", 
            FILE_APPEND
        );
        
        // Verificar se a sessão tem tokens CSRF
        if (!isset($_SESSION['csrf_tokens']) || !is_array($_SESSION['csrf_tokens'])) {
            file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
                date('Y-m-d H:i:s') . " - [CSRF] ERRO: Sessão não tem tokens CSRF\n", 
                FILE_APPEND
            );
            return false;
        }
        
        // Verificar se existe o token específico para este formulário
        if (!isset($_SESSION['csrf_tokens'][$formIdentifier])) {
            file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
                date('Y-m-d H:i:s') . " - [CSRF] ERRO: Token não encontrado para form: {$formIdentifier}\n", 
                FILE_APPEND
            );
            return false;
        }
        
        // Verificar se o token recebido é igual ao token salvo na sessão
        if (hash_equals($_SESSION['csrf_tokens'][$formIdentifier], $token)) {
            // Log para debug
            file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
                date('Y-m-d H:i:s') . " - [CSRF] TOKEN VALIDO - form: {$formIdentifier}, token: {$token}\n", 
                FILE_APPEND
            );
            
            // Token usado deve ser invalidado.
            unset($_SESSION['csrf_tokens'][$formIdentifier]);
            
            return true;
        }
        
        // Log para debug
        file_put_contents(__DIR__ . '/../../../logs/csrf_debug.log', 
            date('Y-m-d H:i:s') . " - [CSRF] TOKEN INVALIDO - form: {$formIdentifier}, token_recebido: {$token}, token_sessao: " . ($_SESSION['csrf_tokens'][$formIdentifier] ?? 'NULL') . "\n", 
            FILE_APPEND
        );
        
        return false;
     }

}