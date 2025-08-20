<?php


namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationUserRakitService
 * 
 * Esta classe é responsável por validar os dados de um formulário de usuário, aplicando regras de validação para criação e edição de usuários.
 * Ela utiliza o pacote `Rakit\Validation` para realizar as validações e inclui uma regra personalizada de unicidade em múltiplas colunas.
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationUserRakitService 
{
    /**
     * Validar os dados do formulário.
     * 
     * Este método valida os dados fornecidos no formulário de usuário, aplicando diferentes regras dependendo se é uma criação ou edição de usuário.
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

        $validator->addValidator('uniqueInColumns', new UniqueInColumnsRule());

        // Definir as regras de validação
        $rules = [
            'name'              => 'required',
            'email'             => 'required|email',
            // 'username'          => 'required|uniqueInColumns:adms_users,username',
            // 'password'          => 'required|min:6|regex:/[a-zA-Z]/|regex:/[0-9]/|regex:/[^\w\s]/',
            'data_nascimento'   => 'required|date|before:tomorrow',
            
        ];

        // Se estiver ausente o ID, então é uma criação (cadastrar)
        if(!isset($data['id'])){
            $rules['email'] = 'required|email|uniqueInColumns:adms_users,email;username';
            $rules['username'] = 'required|min:6|regex:/^\S*$/|uniqueInColumns:adms_users,email;username';
            // $rules['password'] = 'required|regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_]).{6,}$/';
            $rules['password'] = 'required|min:6|regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[\W_])/';
            $rules['confirm_password'] = 'required|same:password';
            // Validação de imagem apenas se enviada
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $rules['image'] = 'uploaded_file:0,2048K,png,jpg,jpeg,gif';
            }
        } else {
            // Para edição, adicionar validação de ID e ignorar o próprio usuário na verificação de email e username
            $rules['id'] = 'required|integer';
            $rules['username'] = 'required|min:6|regex:/^\S*$/|uniqueInColumns:adms_users,email;username,' . $data['id'];
            $rules['email'] = 'required|email|uniqueInColumns:adms_users,email;username,' . $data['id'];
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $rules['image'] = 'uploaded_file:0,2048K,png,jpg,jpeg,gif';
            }
        }
        
         // Definir mensagens personalizadas
         $messages = [
            'id:required'                   => 'Dados inválidos.',
            'id:integer'                    => 'Dados inválidos.',
            
            'name:required'                 => 'O campo nome é obrigatório.',
            
            'email:required'                => 'O campo email é obrigatório.',
            'email:email'                   => 'O campo email deve ser um email válido.',
            'email:uniqueInColumns'         => 'O email já está cadastrado.',
            
            'username:required'             => 'O campo usuário é obrigatório.',
            'username:min'                  => 'O usuário deve conter no minimo 6 caracteres.',
            'username:regex'                => 'O usuário não pode conter espaços em branco.',
            'username:uniqueInColumns'      => 'O usuário já existe.',
            
            'password:required'             => 'O campo senha é obrigatório.',
            'password:min'                  => 'A senha deve ter no mínimo 6 caracteres.',
            'password:regex'                => 'A senha deve conter letra(s), numero(s) e caractere(s) especial.',
            'confirm_password:required'     => 'O campo confirmar senha é obrigatório.',
            'confirm_password:same'         => 'A confirmação da senha deve ser igual à senha.',
            'data_nascimento:required' => 'O campo data de nascimento é obrigatório.',
            'data_nascimento:date' => 'A data de nascimento deve ser uma data válida.',
            'data_nascimento:before_or_equal' => 'A data de nascimento não pode ser futura.',
            'image:uploaded_file' => 'A imagem deve ser JPG, PNG ou GIF e ter no máximo 2MB.',
        ];

        // Criar o validador com os dados e regras fornecidas
        $validation = $validator->make($data, $rules);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages($messages);


        // Validar os dados
        $validation->validate();

        // Retornar erros se houver
        if($validation->fails()){

            // Recuperar os erros
            $arrayErrors = $validation->errors();

            // Percorrer o arraqy de erros
            // firstOfAll - obter a primeira mensagem de erro para cada campo invalido.
            foreach($arrayErrors->firstOfAll() as $key => $message){
                $errors[$key] =  $message;
            }
        }

        return $errors;
    }
}