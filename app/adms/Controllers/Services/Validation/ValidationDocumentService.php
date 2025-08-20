<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationDocumentService
 * 
 * Esta classe é responsável por validar os dados de um formulário de documento, aplicando regras de validação para criação e edição de documentos.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui regras para verificar os campos obrigatórios e suas respectivas regras.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Cesar <cesar@celke.com.br>
 */
class ValidationDocumentService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de documento, aplicando regras diferentes dependendo se é uma criação ou edição de documento.
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

        // Definir as regras de validação
        $rules = [
            'cod_doc' => 'required',
            'name_doc' => 'required',
            'version' => 'required',
            'active' => 'required|boolean',
        ];

        // Se o ID estiver presente, adicionar a validação para o ID
        if (isset($data['id'])) {
            $rules['id'] = 'required|integer';
        }

        // Definir as mensagens de erro personalizadas
        $messages = [
            'id:required' => 'Dados inválidos.',
            'id:integer' => 'Dados inválidos.',
            'cod_doc:required' => 'O campo código é obrigatório.',
            'name_doc:required' => 'O campo nome é obrigatório.',
            'version:required' => 'O campo versão é obrigatório.',
            'active:required' => 'O campo status é obrigatório.',
            'active:boolean' => 'O campo status deve ser true ou false.',
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
