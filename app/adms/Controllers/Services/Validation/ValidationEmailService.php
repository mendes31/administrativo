<?php


namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Classe ValidationEmailService
 * 
 * Esta classe é responsável por validar email
 * 
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes
 */
class ValidationEmailService
{
    
    public function validate(array $data): array 
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Instanciar a classe validar formulário
        $validator = new Validator();

        // Criar o validador com os dados e regras fornecidas
        $validation = $validator->make($data, [
            'email' => 'required|email',            
        ]);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages([
            'email:required'               => 'O campo email é obrigatório.',
            'email:email'                  => 'O campo email deve ser um email válido.',
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