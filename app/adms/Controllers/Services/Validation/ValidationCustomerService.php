<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationCustomerService
 * 
 * Esta classe é responsável por validar os dados de um formulário de frequencia, aplicando regras de validação para criação e edição de Cliente.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes 
 */
class ValidationCustomerService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de frequencia, aplicando diferentes regras dependendo se é uma criação ou edição de Centros de Custo.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array $data): array
    {
        // Criar o array para receber as mensagens de erro
        $errors = [];

        // Instanciar a classe Validator para validar o formulário
        $validator = new Validator();

        // Adicionar a regra personalizada de unicidade em múltiplas colunas
        $validator->addValidator('uniqueInColumns', new UniqueInColumnsRule());

        // Definir as regras de validação
        $rules = [];

        // Se o ID estiver ausente, é uma criação (cadastrar)
        if (!isset($data['id'])) {
            $rules['card_code'] = 'required|uniqueInColumns:adms_customer,card_code';
            $rules['card_name'] = 'required|uniqueInColumns:adms_customer,card_name';
        } else {
            // Para edição, adicionar validação de id e ignorar o próprio frequencia
            $rules['id'] = 'required|integer';
            $rules['card_code'] = 'required|uniqueInColumns:adms_customer,card_code,' . $data['id'];
            $rules['card_name'] = 'required|uniqueInColumns:adms_customer,card_name,' . $data['id'];
        }

        // Definir as mensagens de erro personalizadas
        $messages = [
            'id:required' => 'Dados inválidos.',
            'id:integer' => 'Dados inválidos.',
            'card_code:required' => 'Código é obrigatório.',
            'card_code:uniqueInColumns' => 'Código já cadastrado.',
            'card_name:required' => 'O campo nome é obrigatório.',
            'card_name:uniqueInColumns' => 'Já existe um Cliente cadastrado com este nome.',
        ];

        // Criar o validador com os dados e regras fornecidos
        $validation = $validator->make($data, $rules);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages($messages);

        // Validar os dados
        $validation->validate();

        // Retornar erros se houver
        if ($validation->fails()) {
            // Recuperar os erros 
            $arrayErrors = $validation->errors();

            // Percorrer o array de erros e armazenar a primeira mensagem de erro para cada campo validado
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }

        return $errors;
    }
}
