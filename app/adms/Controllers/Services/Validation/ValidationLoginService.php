<?php


namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

class ValidationLoginService
{
   
    public function validate(array $data): array 
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Instanciar a classe validar formulário
        $validator = new Validator();

        // Criar o validador com os dados e regras fornecidas
        $validation = $validator->make($data, [
            'username' => 'required',
            'password' => 'required',
        ]);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages([
            'username:required'             => 'O campo usuário é obrigatório.',
            'password:required'             => 'O campo senha é obrigatório.',            
        ]);


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