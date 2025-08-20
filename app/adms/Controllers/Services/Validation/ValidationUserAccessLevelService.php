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
class ValidationUserAccessLevelService
{
    
    public function validate(array $data): array 
    {
        // Criar o array que deve receber as mensagens de erro 
        $errors = [];

        // Instanciar a classe validar formulário
        $validator = new Validator();

        // Criar o validador com os dados e regras fornecidas
        $validation = $validator->make($data, [
            'adms_user_id' => 'required|integer',            
        ]);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages([
            'adms_user_id:required'               => 'Dados inválidos.',
            'adms_user_id:integer'                => 'Dados inválidos.',
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