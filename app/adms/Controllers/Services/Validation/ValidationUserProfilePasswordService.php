<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;

/**
 * Serviço de validação para alteração de senha do perfil do usuário logado
 *
 * Esta classe valida apenas os campos necessários para alteração de senha:
 * - password: nova senha com requisitos de segurança
 * - confirm_password: confirmação da nova senha
 *
 * @package App\adms\Controllers\Services\Validation
 * @author Rafael Mendes <raffaell_mendez@hotmail.com>
 */
class ValidationUserProfilePasswordService
{
    /**
     * Validar dados para alteração de senha do perfil
     *
     * @param array $data Dados do formulário
     * @return array Array com erros de validação (vazio se não houver erros)
     */
    public function validate(array $data): array
    {
        $validator = new Validator;

        $validation = $validator->make($data, [
            'password' => 'required|min:6|regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]/',
            'confirm_password' => 'required|same:password'
        ], [
            'required' => 'O campo :attribute é obrigatório',
            'min' => 'O campo :attribute deve ter pelo menos :min caracteres',
            'regex' => 'O campo :attribute deve conter pelo menos uma letra, um número e um caractere especial',
            'same' => 'O campo :attribute deve ser igual ao campo :same',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter pelo menos 6 caracteres',
            'password.regex' => 'A senha deve conter pelo menos uma letra, um número e um caractere especial',
            'confirm_password.required' => 'A confirmação da senha é obrigatória',
            'confirm_password.same' => 'As senhas não coincidem'
        ]);

        $validation->validate();

        if ($validation->fails()) {
            // Retornar erros no formato esperado pelo sistema (igual ao ValidationUserProfileService)
            return $validation->errors()->firstOfAll();
        }

        return [];
    }
}
