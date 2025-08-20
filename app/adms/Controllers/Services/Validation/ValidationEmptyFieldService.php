<?php

namespace App\adms\Controllers\Services\Validation;

/**
 * Classe ValidationEmptyFieldService
 * 
 * Esta classe é responsável por validar se todos os campos de um formulário foram preenchidos, garantindo que nenhum campo esteja vazio.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationEmptyFieldService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método verifica se todos os campos do formulário foram preenchidos. Caso algum campo esteja vazio, uma mensagem de erro é retornada.
     *
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array
    {

        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Retirar espaços em branco
        $data = array_map('trim', $data);

        // Verificar se algum elemento do array está vazio indicando que o campo não possui valor
        if (in_array('', $data)) {
            $errors['msg'] = 'Necessário preencher todos os campos.';
        }

        return $errors;
    }
}
