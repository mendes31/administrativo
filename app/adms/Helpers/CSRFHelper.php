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
        // A função random_bytes gera uma sequência de 32 bytes aleatórios.
        // A função bin2hex converte os bytes binários gerados pela random_bytes em uma representação hexadecimal.
        $token = bin2hex(random_bytes(32));

        // Salvar o TOKEN CSRF na sessão
        $_SESSION['csrf_tokens'][$formIdentifier] = $token;

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
        // Verificar se existe o csrf_token e se o valor que vem do formulário é igual ao csrf salvo na sessão.
        if(isset($_SESSION['csrf_tokens'][$formIdentifier]) && hash_equals($_SESSION['csrf_tokens'][$formIdentifier], $token)){

            // Token usado deve se rinvalidado.
            unset($_SESSION['csrf_tokens'][$formIdentifier]);

            return true;
        }
        return false;
     }

}