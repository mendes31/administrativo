<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationPageService
 * 
 * Esta classe é responsável por validar os dados de um formulário de página, aplicando regras de validação para criação e edição de páginas.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui regras para verificar os campos obrigatórios e suas respectivas regras.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Cesar <cesar@celke.com.br>
 */
class ValidationPageService
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de página, aplicando regras diferentes dependendo se é uma criação ou edição de página.
     * 
     * @param array $data Dados do formulário.
     * @return array Lista de erros. Se não houver erros, o array será vazio.
     */
    public function validate(array &$data): array
    {
        // Criar o array para receber as mensagens de erro
        $errors = [];

        foreach (['name', 'controller', 'controller_url', 'directory', 'obs'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        // Instanciar a classe Validator para validar o formulário
        $validator = new Validator();

        // Controller = nome da classe PHP (PascalCase), igual aos arquivos em app/adms/Controllers/{directory}/.
        // controller_url = slug na URL (kebab-case), gerado pelo SlugController a partir do primeiro segmento.
        $rules = [
            'name' => 'required',
            'controller' => 'required|regex:/^[A-Z][a-zA-Z0-9]*$/',
            'controller_url' => 'required|regex:/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/',
            'directory' => 'required',
            'page_status' => 'required|boolean',
            'public_page' => 'required|boolean',
            'default_page' => 'required|boolean',
            'adms_packages_page_id' => 'required|integer',
            'adms_groups_page_id' => 'required|integer',
        ];

        // Se o ID estiver presente, adicionar a validação para o ID
        if (isset($data['id'])) {
            $rules['id'] = 'required|integer';
        }

        // Definir as mensagens de erro personalizadas
        $messages = [
            'id:required' => 'Dados inválidos.',
            'id:integer' => 'Dados inválidos.',
            'name:required' => 'O campo nome é obrigatório.',
            'controller:required' => 'O campo controller é obrigatório.',
            'controller:regex' => 'Use o nome da classe PHP em PascalCase (ex.: ListUsers, CreateAccessLevel), igual ao arquivo em Controllers/. Sem hífens.',
            'controller_url:required' => 'O campo URL é obrigatório.',
            'controller_url:regex' => 'Use o slug da URL em kebab-case minúsculo (ex.: list-users, create-access-level).',
            'directory:required' => 'O campo diretório é obrigatório.',
            'page_status:required' => 'O campo status é obrigatório.',
            'page_status:boolean' => 'O campo status deve ser true ou false.',
            'public_page:required' => 'O campo público é obrigatório.',
            'public_page:boolean' => 'O campo público deve ser true ou false.',
            'default_page:required' => 'O campo página padrão é obrigatório.',
            'default_page:boolean' => 'O campo página padrão deve ser true ou false.',
            'adms_packages_page_id:required' => 'O campo pacote é obrigatório.',
            'adms_packages_page_id:integer' => 'O campo pacote deve ser um número inteiro.',
            'adms_groups_page_id:required' => 'O campo grupo é obrigatório.',
            'adms_groups_page_id:integer' => 'O campo grupo deve ser um número inteiro.',
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
