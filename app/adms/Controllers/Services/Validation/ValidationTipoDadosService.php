<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationTipoDadosService
 * 
 * Esta classe é responsável por validar os dados de um formulário de tipo de dados LGPD, aplicando regras de validação para criação e edição de tipos de dados.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes 
 */
class ValidationTipoDadosService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de tipo de dados, aplicando diferentes regras dependendo se é uma criação ou edição de tipos de dados.
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
            $rules['tipo_dado'] = 'required|uniqueInColumns:lgpd_tipos_dados,tipo_dado';
        } else {
            // Para edição, adicionar validação de id e ignorar o próprio tipo de dados
            $rules['id'] = 'required|integer';
            $rules['tipo_dado'] = 'required|uniqueInColumns:lgpd_tipos_dados,tipo_dado,' . $data['id'];
        }

        // Definir as mensagens de erro personalizadas
        $messages = [
            'id:required' => 'Dados inválidos.',
            'id:integer' => 'Dados inválidos.',
            'tipo_dado:required' => 'O campo tipo de dado é obrigatório.',
            'tipo_dado:uniqueInColumns' => 'Já existe um tipo de dado cadastrado com este nome.',
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