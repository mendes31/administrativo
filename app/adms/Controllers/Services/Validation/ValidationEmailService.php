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

        // Regra básica: campo obrigatório
        // A regra de formato (e-mail ou CPF) será validada manualmente abaixo
        $validation = $validator->make($data, [
            'email' => 'required',
        ]);

        // Definir as mensagens de erro personalizadas
        $validation->setMessages([
            'email:required' => 'O campo e-mail ou CPF é obrigatório.',
        ]);


        // Validar os dados
        $validation->validate();

        // Retornar erros se houver (obrigatoriedade)
        if ($validation->fails()) {
            $arrayErrors = $validation->errors();
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        } else {
            // Validação adicional: aceitar E-MAIL ou CPF
            $valor = trim($data['email'] ?? '');

            $isEmailValido = filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;

            // CPF no formato 000.000.000-00 ou somente dígitos (11)
            $soDigitos = preg_replace('/\D/', '', $valor);
            $isCpfFormatoValido = (bool) preg_match('/^\d{11}$/', $soDigitos);

            if (!$isEmailValido && !$isCpfFormatoValido) {
                $errors['email'] = 'Informe um e-mail válido ou CPF no formato 000.000.000-00.';
            }
        }

        return $errors;
    }
}