<?php

namespace App\adms\Controllers\Services\Validation;

use Rakit\Validation\Validator;
use App\adms\Models\Repository\AdmsPasswordPolicyRepository;

/**
 * Validação específica para troca obrigatória de senha
 */
class ValidationUserPasswordForceChangeService
{
    /**
     * Valida os campos de nova senha e confirmação, seguindo a política dinâmica.
     * @param array $data
     * @return array Lista de erros
     */
    public function validate(array $data): array
    {
        $errors = [];
        $validator = new Validator();

        // Buscar política de senha
        $policyRepo = new AdmsPasswordPolicyRepository();
        $policy = $policyRepo->getPolicy();
        $minLength = $policy ? (int)$policy->comprimento_minimo : 6;
        $minUpper = $policy ? (int)$policy->min_maiusculas : 0;
        $minLower = $policy ? (int)$policy->min_minusculas : 0;
        $minDigits = $policy ? (int)$policy->min_digitos : 0;
        $minSpecial = $policy ? (int)$policy->min_nao_alfanumericos : 0;

        // Montar regex dinâmica
        $regex = '/^';
        $mensagensExtras = [];
        if ($minUpper > 0) {
            $regex .= '(?=(?:.*[A-Z]){' . $minUpper . ',})';
            $mensagensExtras[] = "• Pelo menos $minUpper letra(s) maiúscula(s)";
        }
        if ($minLower > 0) {
            $regex .= '(?=(?:.*[a-z]){' . $minLower . ',})';
            $mensagensExtras[] = "• Pelo menos $minLower letra(s) minúscula(s)";
        }
        if ($minDigits > 0) {
            $regex .= '(?=(?:.*\\d){' . $minDigits . ',})';
            $mensagensExtras[] = "• Pelo menos $minDigits número(s)";
        }
        if ($minSpecial > 0) {
            $regex .= '(?=(?:.*[^a-zA-Z0-9]){' . $minSpecial . ',})';
            $mensagensExtras[] = "• Pelo menos $minSpecial caractere(s) especial(is)";
        }
        $regex .= '.{' . $minLength . ',}$';
        $regex .= '/';
        $mensagensExtras[] = "• Mínimo de $minLength caracteres";

        $validation = $validator->make($data, [
            'password' => ['required', 'regex:' . $regex],
            'confirm_password' => ['required', 'same:password'],
        ]);

        $validation->setMessages([
            'password:required' => 'O campo nova senha é obrigatório.',
            'password:regex' => 'A senha não atende à política de segurança:<br>' . implode('<br>', $mensagensExtras),
            'confirm_password:required' => 'O campo confirmar senha é obrigatório.',
            'confirm_password:same' => 'A confirmação da senha deve ser igual à nova senha.',
        ]);

        $validation->validate();

        if ($validation->fails()) {
            $arrayErrors = $validation->errors();
            foreach ($arrayErrors->firstOfAll() as $key => $message) {
                $errors[$key] = $message;
            }
        }
        return $errors;
    }
} 