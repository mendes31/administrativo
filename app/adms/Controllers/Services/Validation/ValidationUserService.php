<?php

namespace App\adms\Controllers\Services\Validation;

/**
 * Classe ValidationUserService
 * 
 * Esta classe é responsável por validar os dados de um formulário de usuário, garantindo que os campos nome, e-mail e senha atendam aos critérios especificados.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationUserService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método verifica se os campos nome, e-mail e senha foram preenchidos corretamente. Ele garante que o nome não esteja vazio, que o e-mail seja válido, e que a senha atenda a critérios de segurança.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Verificar se o campo nome está vazio
        if (empty($data['name'])) {
            $errors['name'] = ' O campo nome é obrigatório.';
        }

        // Verificar se o campo email está vazio e se o valor é do tipo email
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = ' O campo email é obrigatório e deve ser um email válido.';
        }

        // Verificar se o campo username está vazio
        if (empty($data['username'])) {
            $errors['username'] = ' O campo usuário é obrigatório.';
        }

        // Verificar se o campo password está vazio, se a senha possui mínimo 6 caracteres e ao menos um caracteres especial
        if (empty($data['password']) || strlen($data['password']) < 6 || !preg_match('/[a-zA-Z]/', $data['password']) || !preg_match('/[0-9]/', $data['password']) || !preg_match('/[^\w\s]/', $data['password'])) {
            $errors['password'] = ' O campo senha é obrigatório, deve conter no mínimo 6 caracteres, ao menos 1 leta, ao menos 1 número e ao menos 1 caractere especial.';
        }

        return $errors;
    }
}
