<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationUserProfileService
 * 
 * Esta classe é responsável por validar os dados do formulário de perfil do usuário, aplicando regras de validação
 * para atualização de informações pessoais e senha. Ela utiliza o pacote `Rakit\Validation` para realizar as validações.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationUserProfileService 
{
    /**
     * Validar os dados do formulário de perfil.
     * 
     * Este método valida os dados fornecidos no formulário de perfil do usuário, aplicando regras
     * para nome, email e data de nascimento.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array 
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Instanciar a classe validar formulário
        $validator = new Validator();

        // Definir as regras de validação para perfil
        $rules = [
            'name'              => 'required|min:3',
            'email'             => 'required|email',
            'data_nascimento'   => 'required|date|before:tomorrow',
        ];

        // Definir as mensagens de erro personalizadas
        $messages = [
            'name:required' => 'O nome é obrigatório.',
            'name:min' => 'O nome deve ter pelo menos 3 caracteres.',
            'email:required' => 'O e-mail é obrigatório.',
            'email:email' => 'O e-mail deve ser válido.',
            'data_nascimento:required' => 'A data de nascimento é obrigatória.',
            'data_nascimento:date' => 'A data de nascimento deve ser válida.',
            'data_nascimento:before' => 'A data de nascimento não pode ser futura.',
        ];

        // Fazer a validação
        $validation = $validator->make($data, $rules, $messages);
        $validation->validate();

        // Verificar se há erros de validação
        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();
        }

        return $errors;
    }

    /**
     * Validar os dados do formulário de alteração de senha.
     * 
     * Este método valida os dados fornecidos no formulário de alteração de senha, aplicando regras
     * para senha e confirmação de senha.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validatePassword(array $data): array 
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Instanciar a classe validar formulário
        $validator = new Validator();

        // Definir as regras de validação para senha
        $rules = [
            'password'          => 'required|min:6|regex:/[a-zA-Z]/|regex:/[0-9]/|regex:/[^\w\s]/',
            'confirm_password'  => 'required|same:password',
        ];

        // Definir as mensagens de erro personalizadas
        $messages = [
            'password:required' => 'A senha é obrigatória.',
            'password:min' => 'A senha deve ter pelo menos 6 caracteres.',
            'password:regex' => 'A senha deve conter pelo menos uma letra, um número e um caractere especial.',
            'confirm_password:required' => 'A confirmação de senha é obrigatória.',
            'confirm_password:same' => 'As senhas não coincidem.',
        ];

        // Fazer a validação
        $validation = $validator->make($data, $rules, $messages);
        $validation->validate();

        // Verificar se há erros de validação
        if ($validation->fails()) {
            $errors = $validation->errors()->firstOfAll();
        }

        return $errors;
    }
}
