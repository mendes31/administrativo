<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationClassificacaoDadosService
 * 
 * Esta classe é responsável por validar os dados de um formulário de classificação de dados LGPD, aplicando regras de validação para criação e edição de classificações.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes 
 */
class ValidationClassificacaoDadosService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de classificação de dados, aplicando diferentes regras dependendo se é uma criação ou edição de classificações.
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
            $rules['classificacao'] = 'required|uniqueInColumns:lgpd_classificacoes_dados,classificacao';
            $rules['base_legal_id'] = 'required|integer';
        } else {
            // Para edição, adicionar validação de id e ignorar a própria classificação
            $rules['id'] = 'required|integer';
            $rules['classificacao'] = 'required|uniqueInColumns:lgpd_classificacoes_dados,classificacao,' . $data['id'];
            $rules['base_legal_id'] = 'required|integer';
        }

        // Definir as mensagens de erro personalizadas
        $messages = [
            'id:required' => 'Dados inválidos.',
            'id:integer' => 'Dados inválidos.',
            'classificacao:required' => 'O campo classificação é obrigatório.',
            'classificacao:uniqueInColumns' => 'Já existe uma classificação cadastrada com este nome.',
            'base_legal_id:required' => 'O campo base legal é obrigatório.',
            'base_legal_id:integer' => 'Base legal inválida.',
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