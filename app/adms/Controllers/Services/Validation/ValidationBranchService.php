<?php

namespace App\adms\Controllers\Services\Validation;

/**
 * Service de validação para Filiais
 *
 * Responsável por validar os dados do formulário de cadastro/edição de filial.
 */
class ValidationBranchService
{
    /**
     * Valida os dados do formulário de filial.
     *
     * @param array|null $data Dados do formulário
     * @return array Lista de erros encontrados
     */
    public function validate(?array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'O nome da filial é obrigatório.';
        }
        if (empty($data['code'])) {
            $errors[] = 'O código da filial é obrigatório.';
        }
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail da filial inválido.';
        }
        if (!empty($data['phone']) && !preg_match('/^[0-9\-\(\)\s]+$/', $data['phone'])) {
            $errors[] = 'Telefone da filial inválido.';
        }
        // Outros campos podem ser validados conforme necessidade

        return $errors;
    }
} 